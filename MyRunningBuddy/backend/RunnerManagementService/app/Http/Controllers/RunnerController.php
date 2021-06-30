<?php

namespace App\Http\Controllers;

use App\Helpers\HttpHelper;
use App\Helpers\ResponseHelper;
use App\Models\ExternalAccount;
use App\Models\ExternalService;
use App\Models\Runner;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Validator;

class RunnerController extends Controller
{
    public function register(Request $request)
    {
        $input = $request->all();

        // data validation
        $validator = Validator::make($input, [
            'name' => 'required|string|max:30',
            'surname' => 'sometimes|string|max:30',
            'aboutme' => 'sometimes|string|max:256',
            'preferences' => 'sometimes|string|max:256',
            'location' => 'sometimes|string|max:64'
        ]);

        if($validator->fails())
            return ResponseHelper::GenerateValidatorErrorMessage($validator->errors());

        // create a runner
        $runner = Runner::create($input);

        return response()->json([
            'runner' => $runner,
            'message' => 'Runner created successfully.',
            'actions' => [
                'get' => "/runner/$runner->id",
                'patch' => "/runner/$runner->id"
            ]
        ], Response::HTTP_CREATED, [], JSON_UNESCAPED_SLASHES);
    }

    public function get_runner(Request $request, $id)
    {
        $runner = Runner::where('id', $id)->first();

        if($runner == null)
            return ResponseHelper::GenerateSimpleTextResponse("Runner doesn't exist.", Response::HTTP_NOT_FOUND);

        return response()->json($runner, Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function update_runner(Request $request, $id)
    {
        if(!$this->check_if_authorized($request, $id))
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        $runner = Runner::where('id', $id)->first();

        if($runner == null)
            return ResponseHelper::GenerateSimpleTextResponse("Runner doesn't exist.", Response::HTTP_NOT_FOUND);

        // data validation
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'sometimes|string|max:256',
            'surname' => 'sometimes|string|max:256',
            'aboutme' => 'sometimes|string|max:256',
            'preferences' => 'sometimes|string|max:256',
            'location' => 'sometimes|string|max:64'
        ]);

        if($validator->fails())
            return ResponseHelper::GenerateValidatorErrorMessage($validator->errors());

        // update a runner
        $runner->fill($input);
        $runner->save();

        return response()->json($runner, Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function get_linked_services(Request $request, $id)
    {
        if(!$this->check_if_authorized($request, $id))
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        if(Runner::where('id', $id)->count() != 1)
            return ResponseHelper::GenerateSimpleTextResponse("Runner doesn't exist.", Response::HTTP_NOT_FOUND);

        // check for every external service if the user has linked account
        $result = [];
        $externalServices = ExternalService::all();
        foreach($externalServices as $service)
        {
            // account is linked if access_token is populated
            $linked = ExternalAccount::where('runner_id', $id)
                    ->where('service_name', $service->service_name)
                    ->where('confirmation_id', ExternalAccount::CONFIRMATION_ID_AUTHORIZED)->count() == 1;

            array_push($result, ['service' => $service, 'linked' => $linked]);
        }

        return response()->json($result, Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function get_external_service_authorization_params(Request $request, $id)
    {
        if (!$this->check_if_authorized($request, $id))
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        if (Runner::where('id', $id)->count() != 1)
            return ResponseHelper::GenerateSimpleTextResponse("Runner doesn't exist.", Response::HTTP_NOT_FOUND);

        // data validation
        $input = $request->all();

        $validator = Validator::make($input, [
            'service_name' => 'required|string|max:64',
        ]);

        if ($validator->fails())
            return ResponseHelper::GenerateValidatorErrorMessage($validator->errors());

        // check if the service exists
        if (ExternalService::where('service_name', $input['service_name'])->count() != 1)
            return ResponseHelper::GenerateSimpleTextResponse("External service doesn't exist.", Response::HTTP_NOT_FOUND);

        // contact external service gateway for authorization params
        $response = HttpHelper::request('get', $input['service_name'], "/authorization_params", [], []);
        if($response == null)
            return ResponseHelper::GenerateInternalServiceUnavailableErrorResponse();

        // check if this operation was successful, and if it isn't try to return a meaningful error
        if($response->status() != Response::HTTP_OK)
            return ResponseHelper::GenerateErrorResponseFromAnotherResponse($response);

        $responseJson = $response->json();
        if(!isset($responseJson['authorization_url']))
            return ResponseHelper::GenerateSimpleTextResponse('Authorization URL is missing.', Response::HTTP_INTERNAL_SERVER_ERROR);

        // revoke all previously authorized external accounts for the runner (and the ones in the process of authorization)
        $externalAccounts = ExternalAccount::where('runner_id', $id)->where('confirmation_id', '!=', ExternalAccount::CONFIRMATION_ID_REVOKED)->get();
        foreach ($externalAccounts as $externalAccount)
        {
            $externalAccount->confirmation_id = ExternalAccount::CONFIRMATION_ID_REVOKED;
            $externalAccount->save();
        }

        // populate a request for authorization of an external account
        $authorization_url = $responseJson['authorization_url'];

        $externalAccount = ExternalAccount::where('service_name', $input['service_name'])->where('runner_id', $id)->first();

        if($externalAccount == null)
        {
            $externalAccount = new ExternalAccount();

            $externalAccount->service_name = $input['service_name'];
            $externalAccount->runner_id = $id;
        }

        // making sure there is no possibility that two users have the same confirmation id
        $externalAccount->confirmation_id = $externalAccount->runner_id * 10000000000 + rand(1000000000, 9999999999);

        $externalAccount->save();

        $authorization_url = $authorization_url . "/" . $externalAccount->confirmation_id;

        return response()->json(['authorization_url' => $authorization_url], Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function revoke_authorization_to_external_service(Request $request, $id, $service_name)
    {
        if (!$this->check_if_authorized($request, $id))
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        if (Runner::where('id', $id)->count() != 1)
            return ResponseHelper::GenerateSimpleTextResponse("Runner doesn't exist.", Response::HTTP_NOT_FOUND);

        // check if the service exists
        if (ExternalService::where('service_name', $service_name)->count() != 1)
            return ResponseHelper::GenerateSimpleTextResponse("External service doesn't exist.", Response::HTTP_NOT_FOUND);

        $externalAccount = ExternalAccount::where('runner_id', $id)
            ->where('service_name', $service_name)
            ->where('confirmation_id', ExternalAccount::CONFIRMATION_ID_AUTHORIZED)->first();

        if($externalAccount == null)
            return ResponseHelper::GenerateSimpleTextResponse("Runner doesn't have a linked account for this external service.", Response::HTTP_BAD_REQUEST);

        if($this->should_refresh_access_token($externalAccount->expires_at))
        {
            $new_tokens = $this->refresh_access_token($externalAccount->refresh_token, $service_name);

            if($new_tokens != null)
            {
                $externalAccount->access_token = $new_tokens['access_token'];
                $externalAccount->refresh_token = $new_tokens['refresh_token'];
                $externalAccount->expires_at = $new_tokens['expires_at'];
            }
        }

        $externalAccount->confirmation_id = ExternalAccount::CONFIRMATION_ID_REVOKED;
        $externalAccount->save();

        $access_token = $externalAccount->access_token;

        // this request may be successful or not, we're not going to retry if it fails, we marked authorization params as revoked in our db
        HttpHelper::request('delete', $service_name, '/access_token', [], ['access_token' => $access_token]);

        return ResponseHelper::GenerateSimpleTextResponse('Authorization for external service successfully revoked.', Response::HTTP_OK);
    }

    private function check_if_authorized(Request $request, $id)
    {
        $authenticated_user = $request->header('X-User');

        if($authenticated_user == null or $authenticated_user != $id)
            return false;

        return true;
    }

    private function should_refresh_access_token($expires_at)
    {
        $now_dt = new \DateTime();
        $expires_at_dt = new \DateTime($expires_at);

        if($expires_at_dt->getTimestamp() - $now_dt->getTimestamp() < 30 * 60)
            return true;

        return false;
    }

    private function refresh_access_token($refresh_token, $service_name)
    {
        $response = HttpHelper::request('patch', $service_name, '/refresh_access_token', [], ['refresh_token' => $refresh_token]);

        if($response == null or $response->status() != Response::HTTP_OK)
            return null;

        $responseJson = $response->json();

        return [
            'access_token' => $responseJson['access_token'],
            'refresh_token' => $responseJson['refresh_token'],
            'expires_at' => $responseJson['expires_at']
        ];
    }
}
