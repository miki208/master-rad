<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Activity;
use App\Models\RunnerStats;
use App\Models\RunningLocation;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class ActivityImporterController extends Controller
{
    public function import_activities(Request $request)
    {
        $input = json_decode($request->getContent(), true);

        if(!isset($input['runner_id']))
            return ResponseHelper::GenerateSimpleTextResponse('runner_id is required.', Response::HTTP_BAD_REQUEST);

        $runner_id = $input['runner_id'];

        if(!isset($input['activities']))
            return ResponseHelper::GenerateSimpleTextResponse('Activities are required.', Response::HTTP_BAD_REQUEST);

        if(count($input['activities']) > 0)
        {
            Activity::AddActivities($input['activities'], $runner_id);

            // delete old activities
            Activity::DeleteOldActivities($runner_id);

            // calculate stats
            $this->calculate_stats($runner_id);
        }

        return ResponseHelper::GenerateSimpleTextResponse('Activities are imported successfully.', Response::HTTP_OK);
    }

    private function calculate_stats($runner_id)
    {
        $activities = Activity::GetAllActivitiesForRunner($runner_id);

        if($activities->isEmpty())
            return;

        // location
        $newestActivity = $activities->first();
        $middleCoord = RunningLocation::get_middle_coord($newestActivity->start_lat, $newestActivity->start_lng, $newestActivity->end_lat, $newestActivity->end_lng);
        RunningLocation::updateOrCreate([
            'runner_id' => $runner_id
        ], [
            'lat' => $middleCoord[0],
            'lng' => $middleCoord[1]
        ]);

        // stats
        RunnerStats::updateOrCreate([
            'runner_id' => $runner_id
        ], [
            'avg_total_distance_per_week' => RunnerStats::calculate_avg_per_week($activities, 'distance_km'),
            'avg_moving_time_per_week' => RunnerStats::calculate_avg_per_week($activities, 'moving_time_sec'),
            'avg_longest_distance_per_week' => RunnerStats::calculate_avg_max_per_week($activities, 'distance_km'),
            'avg_pace_per_week' => RunnerStats::calculate_avg($activities, 'pace'),
            'avg_total_elevation_per_week' => RunnerStats::calculate_avg_per_week($activities, 'total_elevation_gain_m'),
            'avg_start_time_per_week' => RunnerStats::calculate_median_time($activities, 'start_date')
        ]);
    }
}
