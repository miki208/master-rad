<?php

namespace App\Console;

use App\Models\Service;

use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Http;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Response;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function() {
            foreach (Service::all() as $service) {
                $available = true;

                try{
                    $response = Http::get("http://$service->location:$service->port/status");

                    if($response->status() != Response::HTTP_OK || !isset($response['status']) || $response['status'] != 'ok')
                        $available = false;
                }
                catch(HttpClientException $e)
                {
                    $available = false;
                }

                if(!$available)
                {
                    if($service->last_status != 'down')
                    {
                        $service->last_status = 'down';
                        $service->save();

                        Log::warning("Service $service->service_name is down.");
                    }
                }
                else if($service->last_status != 'up')
                {
                    $service->last_status = 'up';
                    $service->save();

                    Log::info("Service $service->service_name is up again.");
                }
            }
        })->everyMinute();
    }
}
