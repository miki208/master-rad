<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Activity extends Model
{
    use HasFactory;

    protected $table = 'activities';

    protected $fillable = [
        'runner_id', 'activity_id', 'distance_km', 'moving_time_sec', 'total_elevation_gain_m', 'start_date',
        'start_lat', 'start_lng', 'end_lat', 'end_lng', 'pace'
    ];

    protected $hidden = [

    ];

    public static function AddActivities($activities, $runner_id)
    {
        foreach($activities as $activity)
        {
            if(!isset($activity['activity_id']) or !isset($activity['start_date']) or !isset($activity['start_latlng']) or !isset($activity['end_latlng']))
                continue;

            if(Activity::where('activity_id', $activity['activity_id'])->count() > 0)
                continue;

            $activityModel = new Activity();

            $activityModel->runner_id = $runner_id;
            $activityModel->activity_id = $activity['activity_id'];
            $activityModel->start_lat = $activity['start_latlng'][0];
            $activityModel->start_lng = $activity['start_latlng'][1];
            $activityModel->end_lat = $activity['end_latlng'][0];
            $activityModel->end_lng = $activity['end_latlng'][1];

            $activityModel->distance_km = $activity['distance_km'] ?? null;
            $activityModel->moving_time_sec = $activity['moving_time_sec'] ?? null;
            $activityModel->total_elevation_gain_m = $activity['total_elevation_gain_m'] ?? null;
            $activityModel->start_date = $activity['start_date'] ?? null;
            $activityModel->pace = $activity['pace'] ?? null;

            $activityModel->save();
        }
    }

    public static function DeleteOldActivities($runner_id)
    {
        $newestActivity = Activity::where('runner_id', $runner_id)->orderBy('start_date', 'desc')->first();

        if($newestActivity == null)
            return;

        $four_weeks = 4 * 7 * 24 * 60 * 60;
        $thresholdDate = $newestActivity->start_date - $four_weeks;

        Activity::where('start_date', '<=', $thresholdDate)->delete();
    }

    public static function GetAllActivitiesForRunner($runner_id)
    {
        return Activity::where('runner_id', $runner_id)->orderBy('start_date', 'desc')->get();
    }
}
