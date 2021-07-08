<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CommonHelper
{
    // reads data from the cache if available, and if it isn't, reads data from the config
    public static function get_param($paramName)
    {
        $paramValue = Cache::get($paramName);

        if($paramValue == null)
        {
            $paramValue = config($paramName);

            Cache::put($paramName, $paramValue, Carbon::now()->addHours(1));
        }

        return $paramValue;
    }
}
