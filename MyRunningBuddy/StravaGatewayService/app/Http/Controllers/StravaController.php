<?php

namespace App\Http\Controllers;

use App\Helpers\HttpHelper;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Log;

class StravaController extends Controller
{
    public function get_authorization_params(Request $request)
    {
        $clientId = $this->get_api_param('ClientId');
        $authorizationUrl = $this->get_api_param('AuthorizationUrl');
        $authorizationScope = $this->get_api_param('AuthorizationScope');

        if($clientId == null or $authorizationUrl == null or $authorizationScope == null)
        {
            Log::error("Strava gateway isn't configured properly.");

            return ResponseHelper::GenerateSimpleTextResponse("Strava gateway isn't configured properly.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $authorizationUrl = $authorizationUrl . "&client_id={$clientId}&scope={$authorizationScope}&redirect_uri=http://location_url/external_service/StravaGatewayService";

        return response()->json(['authorization_url' => $authorizationUrl], Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function authorization_grant_received(Request $request)
    {
        $input = $request->all();

        if(!isset($input['code']))
            return ResponseHelper::GenerateSimpleTextResponse('Invalid authorization callback request', Response::HTTP_BAD_REQUEST);

        $clientId = $this->get_api_param('ClientId');
        $clientSecret = $this->get_api_param('ClientSecret');

        if($clientId == null or $clientSecret == null)
        {
            Log::error("Strava gateway isn't configured properly.");

            return ResponseHelper::GenerateSimpleTextResponse("Strava gateway isn't configured properly.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try
        {
            $response =  Http::post('https://www.strava.com/api/v3/oauth/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $input['code'],
                'grant_type' => 'authorization_code'
            ]);

            if($response->getStatusCode() != Response::HTTP_OK)
                return ResponseHelper::GenerateSimpleTextResponse('Authorization failed', Response::HTTP_BAD_REQUEST);

            $responseJson = $response->json();

            if(!isset($responseJson['expires_at']) or !isset($responseJson['refresh_token']) or !isset($responseJson['access_token']))
                return ResponseHelper::GenerateSimpleTextResponse('Unexpected response from external service', Response::HTTP_BAD_REQUEST);

            return response()->json([
                'access_token' => $responseJson['access_token'],
                'refresh_token' => $responseJson['refresh_token'],
                'expiration_datetime' => $responseJson['expires_at']
            ], Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
        } catch (HttpClientException $e)
        {
            Log::warning("Problem while trying to contact the Strava external service. Retrying...");

            return ResponseHelper::GenerateSimpleTextResponse('Unable to contact external service', Response::HTTP_BAD_GATEWAY);
        }
    }

    private function get_api_param($paramName, $cachingTimeInHours = 1)
    {
        $paramValue = Cache::get($paramName);

        if($paramValue == null)
        {
            $paramValue = config("externalservice.$paramName");

            if($paramValue != null)
                Cache::put($paramName, $paramValue, Carbon::now()->addHours($cachingTimeInHours));
        }

        return $paramValue;
    }
}
