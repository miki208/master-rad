<?php

namespace App\Http\Controllers;

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

        $authorizationUrl = $authorizationUrl . "&client_id={$clientId}&scope={$authorizationScope}&redirect_uri=http://location_url/authorization_grant_callback/StravaGatewayService";

        return response()->json(['authorization_url' => $authorizationUrl], Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function authorization_grant_callback(Request $request)
    {
        $input = $request->all();

        if(!isset($input['code']))
            return ResponseHelper::GenerateSimpleTextResponse('Invalid authorization callback request (authorization grant missing).', Response::HTTP_BAD_REQUEST);

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
                return ResponseHelper::GenerateSimpleTextResponse('Authorization failed.', Response::HTTP_BAD_REQUEST);

            $responseJson = $response->json();

            if(!isset($responseJson['expires_at']) or !isset($responseJson['refresh_token']) or !isset($responseJson['access_token']))
                return ResponseHelper::GenerateSimpleTextResponse('Unexpected response from external service.', Response::HTTP_BAD_REQUEST);

            return response()->json([
                'access_token' => $responseJson['access_token'],
                'refresh_token' => $responseJson['refresh_token'],
                'expires_at' => $responseJson['expires_at'],
                'athlete' => $responseJson['athlete']
            ], Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
        } catch (HttpClientException $e)
        {
            Log::warning("Problem while trying to contact the Strava external service.");

            return ResponseHelper::GenerateSimpleTextResponse('Unable to contact external service.', Response::HTTP_BAD_GATEWAY);
        }
    }

    public function refresh_access_token(Request $request)
    {
        $refreshToken = $request->get('refresh_token');

        if($refreshToken == null)
            return ResponseHelper::GenerateSimpleTextResponse('Refresh token is missing.', Response::HTTP_BAD_REQUEST);

        $clientId = $this->get_api_param('ClientId');
        $clientSecret = $this->get_api_param('ClientSecret');

        if($clientId == null or $clientSecret == null)
        {
            Log::error("Strava gateway isn't configured properly.");

            return ResponseHelper::GenerateSimpleTextResponse("Strava gateway isn't configured properly.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try
        {
            $response = Http::post('https://www.strava.com/api/v3/oauth/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken
            ]);

            if($response->getStatusCode() != Response::HTTP_OK)
                return ResponseHelper::GenerateSimpleTextResponse('Refreshing access token failed.', Response::HTTP_BAD_REQUEST);

            $responseJson = $response->json();
            if(!isset($responseJson['access_token']) or !isset($responseJson['expires_at']) or !isset($responseJson['refresh_token']))
                return ResponseHelper::GenerateSimpleTextResponse('Unexpected response from external service.', Response::HTTP_BAD_REQUEST);

            return response()->json([
                'access_token' => $responseJson['access_token'],
                'refresh_token' => $responseJson['refresh_token'],
                'expires_at' => $responseJson['expires_at']
            ], Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
        }
        catch(HttpClientException $e)
        {
            Log::warning("Problem while trying to contact the Strava external service.");

            return ResponseHelper::GenerateSimpleTextResponse('Unable to contact external service.', Response::HTTP_BAD_GATEWAY);
        }
    }

    public function revoke_access_token(Request $request)
    {
        $access_token = $request->get('access_token');

        if($access_token == null)
            return ResponseHelper::GenerateSimpleTextResponse('Access token is missing.', Response::HTTP_BAD_REQUEST);

        try
        {
            $response = Http::post('https://www.strava.com/oauth/deauthorize', [
                'access_token' => $access_token
            ]);

            if($response->getStatusCode() != Response::HTTP_OK)
                return ResponseHelper::GenerateSimpleTextResponse('Revoking access token failed.', Response::HTTP_BAD_REQUEST);

            return ResponseHelper::GenerateSimpleTextResponse('Access token successfully revoked.', Response::HTTP_OK);
        }
        catch(HttpClientException $e)
        {
            Log::warning("Problem while trying to contact the Strava external service.");

            return ResponseHelper::GenerateSimpleTextResponse('Unable to contact external service.', Response::HTTP_BAD_GATEWAY);
        }
    }

    public function get_activities(Request $request)
    {
        $access_token = $request->get('access_token');
        $activities_after = $request->get('activities_after');

        if($access_token == null)
            return ResponseHelper::GenerateSimpleTextResponse('Access token is missing.', Response::HTTP_BAD_REQUEST);

        $four_weeks_ago = time() - 4 * 7 * 24 * 60 * 60;
        if($activities_after === null or $activities_after < $four_weeks_ago)
            $activities_after = $four_weeks_ago;

        try
        {
            $response = Http::withHeaders([
                'Authorization' => "Bearer $access_token"
            ])->get('https://www.strava.com/api/v3/athlete/activities', [
                'after' => $activities_after,
                'page' => 1,
                'per_page' => 100
            ]);

            if($response->getStatusCode() != Response::HTTP_OK)
            {
                if($response->getStatusCode() == Response::HTTP_TOO_MANY_REQUESTS) // we have to signalize the sender to stop sending new requests
                    return response()->json("Too Many Requests; rate limits exceeded.", Response::HTTP_TOO_MANY_REQUESTS, [], JSON_UNESCAPED_SLASHES);
                else
                    return ResponseHelper::GenerateSimpleTextResponse('Getting new activities failed.', Response::HTTP_BAD_REQUEST);
            }

            $activitiesResponse = [];
            $responseJson = $response->json();
            foreach($responseJson as $activity)
            {
                if($activity['type'] == 'Run' and $activity['trainer'] == false and $activity['manual'] == false)
                {
                    array_push($activitiesResponse, [
                        'activity_id' => 'strava_' . $activity['id'],
                        'distance_km' => $activity['distance'] * 1.0 / 1000,
                        'moving_time_sec' => $activity['moving_time'],
                        'total_elevation_gain_m' => $activity['total_elevation_gain'],
                        'start_date' => strtotime($activity['start_date']),
                        'start_latlng' => $activity['start_latlng'],
                        'end_latlng' => $activity['end_latlng'],
                        'pace' => 1000.0 / (60 * $activity['average_speed'])
                    ]);
                    /*
                     * interesting fields:
                     * distance (in meters)
                     * moving_time (in seconds)
                     * total_elevation_gain (in meters)
                     * start_date (gmt)
                     * start_latlng
                     * end_latlng
                     * average_speed (meters per seconds)
                     *
                     * search ideas:
                     * total distance per week
                     * average moving time
                     * longest distance during the week
                     * location (based on start and end)
                     * average pace
                     * median of start times
                     * total elevation gain per week
                     */
                }
            }

            return response()->json($activitiesResponse, Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
        }
        catch(HttpClientException $e)
        {
            Log::warning("Problem while trying to contact the Strava external service.");

            return ResponseHelper::GenerateSimpleTextResponse('Unable to contact external service.', Response::HTTP_BAD_GATEWAY);
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
