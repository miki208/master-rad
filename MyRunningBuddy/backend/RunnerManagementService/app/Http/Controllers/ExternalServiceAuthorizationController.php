<?php

namespace App\Http\Controllers;

use App\Helpers\HttpHelper;
use App\Helpers\ResponseHelper;
use App\Models\ExternalAccount;
use App\Models\ExternalService;
use App\Models\Runner;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExternalServiceAuthorizationController extends Controller
{
    public function authorization_grant_callback(Request $request)
    {
        $input = $request->all();

        if(!isset($input['service_name'])
            or !isset($input['confirmation_id'])
            or !isset($input['scope'])
            or ExternalService::where('service_name', $input['service_name'])->count() != 1
            or $input['confirmation_id'] == ExternalAccount::CONFIRMATION_ID_AUTHORIZED // reject special confirmation ids
            or $input['confirmation_id'] == ExternalAccount::CONFIRMATION_ID_REVOKED) // reject special confirmation ids
            return ResponseHelper::GenerateSimpleTextResponse('Invalid authorization callback request.', Response::HTTP_BAD_REQUEST);

        // check if the user provided necessary scope
        $scope = explode(',', $input['scope']);
        if(!in_array('activity:read_all', $scope))
            return ResponseHelper::GenerateSimpleTextResponse('Application need to view data about your private activities.', Response::HTTP_BAD_REQUEST);

        // find the corresponding authorization request
        $externalAccount = ExternalAccount::where('service_name', $input['service_name'])->where('confirmation_id', $input['confirmation_id'])->first();
        if($externalAccount == null)
            return ResponseHelper::GenerateSimpleTextResponse('Invalid authorization callback request (confirmation id is invalid).', Response::HTTP_BAD_REQUEST);

        // retrieve access_token and refresh_token in exchange for authorization grant
        $response = HttpHelper::request('post', $input['service_name'], '/authorization_grant_callback', [], $input);

        // handle errors if there are any and return a meaningful error if possible
        if ($response == null)
            return ResponseHelper::GenerateInternalServiceUnavailableErrorResponse();

        if ($response->status() != Response::HTTP_OK)
            return ResponseHelper::GenerateErrorResponseFromAnotherResponse($response);

        // revoke all other ongoing authorization requests and authorized accounts for the runner
        $externalAccounts = ExternalAccount::where('runner_id', $externalAccount->runner_id)
            ->where('confirmation_id', '!=', ExternalAccount::CONFIRMATION_ID_REVOKED)
            ->where('confirmation_id', '!=', $externalAccount->confirmation_id)
            ->get();

        foreach ($externalAccounts as $externalAcc)
        {
            $externalAcc->confirmation_id = ExternalAccount::CONFIRMATION_ID_REVOKED;
            $externalAcc->save();
        }

        // populate all the information needed for the external account
        $responseJson = $response->json();
        $externalAccount->access_token = $responseJson['access_token'];
        $externalAccount->refresh_token = $responseJson['refresh_token'];
        $externalAccount->expires_at = $responseJson['expires_at'];
        $externalAccount->confirmation_id = ExternalAccount::CONFIRMATION_ID_AUTHORIZED;
        $externalAccount->scope = $input['scope'];
        $externalAccount->save();

        // try to populate missing athlete info
        $athlete = isset($response['athlete']) ? $responseJson['athlete'] : null;

        if($athlete != null)
        {
            $runner = Runner::where('id', $externalAccount->runner_id)->first();

            assert($runner != null);

            if($runner->surname == '' and isset($athlete['lastname']))
                $runner->surname = $athlete['lastname'];

            if($runner->location == '' and isset($athlete['city']) and $athlete['city'] != '')
            {
                $runner->location = $athlete['city'];

                if(isset($athlete['country']))
                    $runner->location = $runner->location . ', ' . $athlete['country'];
            }

            if($runner->location == '' and isset($athlete['country']))
                $runner->location = $athlete['country'];

            $runner->save();
        }

        // everything is ok
        return ResponseHelper::GenerateSimpleTextResponse('Authorization successful.', Response::HTTP_OK);
    }
}
