<?php

namespace App\Helpers;

use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Log;
use Carbon\Carbon;
use App\Helpers\CommonHelper;

class HttpHelper
{
    private const SERVICE_WAS_DOWN = 1;

    public static function request($method, $service_name, $route, $headers, $params, $fail_if_service_was_down = false)
    {
        $maxNumberOfRetries = CommonHelper::get_param('httpclient.retries');
        $timeoutInSeconds = CommonHelper::get_param('httpclient.timeout');

        $service_url = self::resolve_service($service_name, $fail_if_service_was_down);

        if($service_url == null)
        {
            Log::error("Service resolving failed: non-existent service $service_name");

            return null;
        }
        else if($fail_if_service_was_down and $service_url === self::SERVICE_WAS_DOWN)
        {
            return null;
        }

        for($numberOfRetries = 0; $numberOfRetries < $maxNumberOfRetries; $numberOfRetries++)
        {
            try
            {
                return Http::withHeaders($headers)
                    ->timeout($timeoutInSeconds)
                    ->$method('http://' . $service_url . $route, $params);
            } catch (HttpClientException $e)
            {
                Log::warning("Problem while trying to contact the $service_name service. Retrying...");
            }
        }

        // the service is probably unavailable, we'll stop retrying
        if($numberOfRetries == $maxNumberOfRetries)
            return null;
    }

    private static function resolve_service($service_name, $resolve_to_null_if_was_unavailable = false)
    {
        if($service_name == 'ServiceRegistry')
            return CommonHelper::get_param('serviceregistry.ServiceRegistryUrl');

        // check if we have cached this service location
        $cacheEntry = Cache::get("service.$service_name");
        if($cacheEntry == null)
        {
            // ask service registry if there isn't any cached entry
            $servicesResponse = self::request('get', 'ServiceRegistry', "/service/$service_name", [], []);
            if($servicesResponse == null or $servicesResponse->status() != Response::HTTP_OK)
                return null;

            $services = json_decode((string) $servicesResponse->getBody(), true);
            if(count($services) == 0)
                return null;

            Cache::put("service.$service_name", $services, Carbon::now()->addSeconds(config('serviceregistry.ServiceLocationCachingInSeconds')));

            $cacheEntry = $services;
        }

        if(count($cacheEntry) == 1)
        {
            $service = $cacheEntry[0];
        }
        else
        {
            // try to use a random service, and if its last status is 'down', use the first one which has status 'up'
            $service = $cacheEntry[rand(0, count($cacheEntry) - 1)];

            if($service['last_status'] == 'down')
            {
                foreach($cacheEntry as $cachedService)
                {
                    if($cachedService['last_status'] == 'up')
                    {
                        $service = $cachedService;

                        break;
                    }
                }
            }
        }

        if($resolve_to_null_if_was_unavailable and $service['last_status'] == 'down')
            return self::SERVICE_WAS_DOWN;

        return $service['location'] . ':' . $service['port'];
    }
}
