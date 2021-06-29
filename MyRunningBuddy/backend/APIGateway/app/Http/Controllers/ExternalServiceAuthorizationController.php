<?php

namespace App\Http\Controllers;

use App\Helpers\HttpHelper;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExternalServiceAuthorizationController extends Controller
{
    public function authorization_grant_received(Request $request, $service_name, $confirmation_id)
    {
        $input = $request->all();

        // check if authorization is denied
        if(isset($input['error']))
            return ResponseHelper::GenerateSimpleTextResponse($input['error'], Response::HTTP_BAD_REQUEST);

        $input['service_name'] = $service_name;
        $input['confirmation_id'] = $confirmation_id;

        $response = HttpHelper::request('post', 'RunnerManagementService', '/external_service', [], $input);

        // handle errors if there are any and return a meaningful error if possible
        if($response == null)
            return ResponseHelper::GenerateInternalServiceUnavailableErrorResponse();

        if($response->status() != Response::HTTP_OK)
            return ResponseHelper::GenerateErrorResponseFromAnotherResponse($response, 'Internal service error');

        // everything is ok
        return response()->json($response->json(), $response->getStatusCode(), [], JSON_UNESCAPED_SLASHES);
    }
}
