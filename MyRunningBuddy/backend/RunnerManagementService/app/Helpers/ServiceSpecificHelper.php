<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\HttpHelper;

class ServiceSpecificHelper
{
    public static function check_if_authorized(Request $request, $id)
    {
        $authenticated_user = $request->header('X-User');

        if($authenticated_user == null or $authenticated_user != $id)
            return false;

        return true;
    }

    public static function should_refresh_access_token($expires_at)
    {
        if($expires_at - time() < 30 * 60)
            return true;

        return false;
    }

    public static function refresh_access_token($refresh_token, $service_name)
    {
        $response = HttpHelper::request('patch', $service_name, '/access_token', [], ['refresh_token' => $refresh_token]);

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
