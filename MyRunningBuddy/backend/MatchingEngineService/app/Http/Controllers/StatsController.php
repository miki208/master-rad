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
            return ResponseHelper::GenerateSimpleTextResponse('This user does not have stats.', Response::HTTP_NOT_FOUND);

        $response = ['stats' => $runnerStats];

        // get location if available
        $runnerLocation = RunningLocation::get_running_location_by_runner_id($runner_id);

        if($runnerLocation !== null)
            $response['location'] = [$runnerLocation->lat, $runnerLocation->lng];

        return response()->json($response, Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function set_stats(Request $request, $runner_id)
    {
        $authenticated_user = $request->header('X-User');

        if($authenticated_user == null or $authenticated_user != $runner_id)
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        // update stats
        $input_stats = $request->only([
            'avg_total_distance_per_week',
            'avg_moving_time_per_week',
            'avg_longest_distance_per_week',
            'avg_pace_per_week',
            'avg_start_time_per_week',
            'avg_total_elevation_per_week'
        ]);

        RunnerStats::updateOrCreate([
            'runner_id' => $runner_id
        ], $input_stats);

        // update location
        $input_location = $request->only([
            'lat', 'lng'
        ]);

        if(count($input_location) === 2)
        {
            RunningLocation::updateOrCreate([
                'runner_id' => $runner_id
            ], $input_location);
        }

        return ResponseHelper::GenerateSimpleTextResponse('Stats for the user are successfully updated.', Response::HTTP_OK);
    }
}
