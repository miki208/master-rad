<?php

namespace App\Http\Controllers;

use App\Helpers\HttpHelper;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Log;

class StravaController extends Controller
{
    public function get_authorization_params(Request $request)
    {
        $cachingTimeInHours = 1;

        $clientId = Cache::get('ClientId');
        if($clientId == null)
        {
            $clientId = config('externalservice.ClientId');

            if($clientId != null)
                Cache::put('ClientId', $clientId, Carbon::now()->addHours($cachingTimeInHours));
        }

        $authorizationUrl = Cache::get('AuthorizationUrl');
        if($authorizationUrl == null)
        {
            $authorizationUrl = config('externalservice.AuthorizationUrl');

            if($authorizationUrl != null)
                Cache::put('ClientId', $authorizationUrl, Carbon::now()->addHours($cachingTimeInHours));
        }

        $authorizationScope = Cache::get('AuthorizationScope');
        if($authorizationScope == null)
        {
            $authorizationScope = config('externalservice.AuthorizationScope');

            if($authorizationScope != null)
                Cache::put('ClientId', $authorizationScope, Carbon::now()->addHours($cachingTimeInHours));
        }

        if($clientId == null or $authorizationUrl == null or $authorizationScope == null)
            return ResponseHelper::GenerateSimpleTextResponse("Strava gateway isn't configured properly.", Response::HTTP_INTERNAL_SERVER_ERROR);

        $authorizationUrl = $authorizationUrl . "&client_id=$clientId&scope=$authorizationScope&redirect_uri=http://location_url/external_service/strava";

        return response()->json(['authorization_url' => $authorizationUrl], Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }
}
