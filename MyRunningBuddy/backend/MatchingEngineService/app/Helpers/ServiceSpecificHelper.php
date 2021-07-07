<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class ServiceSpecificHelper
{
    public static function check_if_authorized(Request $request, $id)
    {
        $authenticated_user = $request->header('X-User');

        if($authenticated_user == null or $authenticated_user != $id)
            return false;

        return true;
    }
}
