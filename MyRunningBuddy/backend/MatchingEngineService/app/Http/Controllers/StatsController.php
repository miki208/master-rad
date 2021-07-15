<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\RunnerStats;
use App\Models\RunningLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StatsController extends Controller
{
    public function get_stats(Request $request, $runner_id)
    {
        // get stats
        $runnerStats = RunnerStats::get_runner_stats_by_runner_id($runner_id);

        if($runnerStats === null)
            return ResponseHelper::GenerateSimpleTextResponse("You don't have running stats. Connect with some of the external services to continue", Response::HTTP_NOT_FOUND);

        $response = ['stats' => $runnerStats];

        // get location if available
        $runnerLocation = RunningLocation::get_running_location_by_runner_id($runner_id);

        if($runnerLocation !== null)
            $response['location'] = [$runnerLocation->lat, $runnerLocation->lng];

        return response()->json($response, Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }
}
