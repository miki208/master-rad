<?php

namespace App\Console;

use App\Helpers\HttpHelper;
use App\Models\ExternalAccount;
use App\Helpers\ServiceSpecificHelper;
use Illuminate\Http\Response;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

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
            $syncingOperationStartedAt = time(); // we want to limit how much time this syncing job takes (we have to avoid two parallel jobs)

            // get authorized external accounts that are not synced more than 24 hours
            $externalAccounts = ExternalAccount::where('confirmation_id', ExternalAccount::CONFIRMATION_ID_AUTHORIZED)
                ->where(function ($query)
                {
                    $query->where('last_sync', '<=', time() - 24 * 60 * 60)
                        ->orWhere('last_sync', null);
                })->get();

            foreach($externalAccounts as $externalAccount)
            {
                if(time() - $syncingOperationStartedAt > 13 * 60) // we'll set syncing threshold to 13 minutes (another job will kick in 2 minutes)
                    return;

                // first check if the access token should be refreshed
                if(ServiceSpecificHelper::should_refresh_access_token($externalAccount->expires_at))
                {
                    // ok, refresh the access token
                    $new_tokens = ServiceSpecificHelper::refresh_access_token($externalAccount->refresh_token, $externalAccount->service_name);

                    if($new_tokens != null)
                    {
                        $externalAccount->access_token = $new_tokens['access_token'];
                        $externalAccount->refresh_token = $new_tokens['refresh_token'];
                        $externalAccount->expires_at = $new_tokens['expires_at'];

                        $externalAccount->save();
                    }
                    else
                    {
                        continue; // there is no reason to continue with syncing operation for this user
                    }
                }

                $firstSync = false;
                if($externalAccount->last_sync === null)
                    $firstSync = true;

                $response = HttpHelper::request('get', $externalAccount->service_name, '/activities', [], [
                    'access_token' => $externalAccount->access_token,
                    'first_sync' => $firstSync
                ], true);

                // service is unavailable (or it was unavailable recently), let's try again a little bit later
                if($response === null)
                    return;

                // we should stop syncing and wait some time
                if($response->getStatusCode() === Response::HTTP_TOO_MANY_REQUESTS)
                    return;

                // ok, we can proceed and forward activities to the matching engine
                $matchingEngineResponse = HttpHelper::request('post', 'MatchingEngineService', '/activities', [], [
                    'runner_id' => $externalAccount->runner_id,
                    'activities' => $response->json()
                ], true);

                if($matchingEngineResponse === null) // matching engine is not available right now, stop all syncing operations
                    return;

                // mark this operation as a successful sync
                if($matchingEngineResponse->getStatusCode() === Response::HTTP_OK)
                {
                    $externalAccount->last_sync = time();

                    $externalAccount->save();
                }
            }
        })->everyFifteenMinutes();
    }
}
