<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\RunnerStats;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StatsController extends Controller
{
    public function get_stats(Request $request, $runner_id)
    {
        $runnerStats = RunnerStats::where('runner_id', $runner_id)->first();

        if($runnerStats === null)
            return ResponseHelper::GenerateSimpleTextResponse('This user does not have stats.', Response::HTTP_NOT_FOUND);

        return response()->json($runnerStats, Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    public function set_stats(Request $request, $runner_id)
    {
        $authenticated_user = $request->header('X-User');

        if($authenticated_user == null or $authenticated_user != $runner_id)
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        $input = $request->only([
            'avg_total_distance_per_week',
            'avg_moving_time_per_week',
            'avg_longest_distance_per_week',
            'avg_pace_per_week',
            'avg_start_time_per_week',
            'avg_total_elevation_per_week'
        ]);

        RunnerStats::updateOrCreate([
            'runner_id' => $runner_id
        ], $input);

        return ResponseHelper::GenerateSimpleTextResponse('Stats for the user are successfully updated.', Response::HTTP_OK);
    }
}
