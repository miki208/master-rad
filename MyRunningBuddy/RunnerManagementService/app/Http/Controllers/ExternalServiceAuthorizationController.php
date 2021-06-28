<?php

namespace App\Http\Controllers;

use App\Helpers\HttpHelper;
use App\Helpers\ResponseHelper;
use App\Models\ExternalAccount;
use App\Models\ExternService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExternalServiceAuthorizationController extends Controller
{
    public function authorization_grant_received(Request $request)
    {
        $input = $request->all();

        if(!isset($input['service_name'])
            or !isset($input['confirmation_id'])
            or !isset($input['scope'])
            or ExternService::where('service_name', $input['service_name'])->count() != 1
            or $input['confirmation_id'] == 0)
            return ResponseHelper::GenerateSimpleTextResponse('Invalid authorization callback request', Response::HTTP_BAD_REQUEST);

        $externalAccount = ExternalAccount::where('service_name', $input['service_name'])->where('confirmation_id', $input['confirmation_id'])->first();
        if($externalAccount == null)
            return ResponseHelper::GenerateSimpleTextResponse('Invalid authorization callback request', Response::HTTP_BAD_REQUEST);

        $response = HttpHelper::request('post', $input['service_name'], '/external_service', [], $input);

        // handle errors if there are any and return a meaningful error if possible
        if ($response == null)
            return ResponseHelper::GenerateInternalServiceUnavailableErrorResponse();

        if ($response->status() != Response::HTTP_OK)
            return ResponseHelper::GenerateErrorResponseFromAnotherResponse($response);

        $responseJson = $response->json();
        $externalAccount->access_token = $responseJson['access_token'];
        $externalAccount->refresh_token = $responseJson['refresh_token'];
        $externalAccount->expiration_date = date('Y-m-d H:i:s', $responseJson['expiration_datetime']);
        $externalAccount->confirmation_id = 0;
        $externalAccount->scope = $input['scope'];
        $externalAccount->save();

        // everything is ok
        return ResponseHelper::GenerateSimpleTextResponse('Authorization successful', Response::HTTP_OK);
    }
}
