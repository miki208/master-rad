<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RunnerStats extends Model
{
    use HasFactory;

    protected $table = 'RunnerStats';

    protected $fillable = [
        'runner_id',
        'avg_total_distance_per_week',
        'avg_moving_time_per_week',
        'avg_longest_distance_per_week',
        'avg_pace_per_week',
        'avg_start_time_per_week',
        'avg_total_elevation_per_week'
    ];

    protected $hidden = [
        'id', 'runner_id', 'created_at', 'updated_at'
    ];

    public static function calculate_avg_per_week($activities, $field_name)
    {
        $newestActivity = $activities->first();

        $per_week = [0, 0, 0, 0];
        $one_week = 7 * 24 * 60 * 60;
        $four_weeks_ago = $newestActivity->start_date - 4 * $one_week;

        foreach ($activities as $activity)
            if($activity->{$field_name} !== null)
                $per_week[intdiv($activity->start_date - $four_weeks_ago - 1, $one_week)] += $activity->{$field_name};

        $num_of_weeks = 0;
        $total = 0;
        for($i = 0; $i < 4; $i++)
        {
            if($per_week[$i] != 0)
            {
                $total += $per_week[$i];
                $num_of_weeks += 1;
            }
        }

        if($num_of_weeks === 0)
            return null;

        return 1.0 * $total / $num_of_weeks;
    }

    public static function calculate_avg_max_per_week($activities, $field_name)
    {
        $newestActivity = $activities->first();

        $per_week = [];
        $first_second_in_next_week = self::get_first_second_in_next_week($newestActivity->start_date);
        $one_week = 7 * 24 * 60 * 60;

        foreach ($activities as $activity) {
            if ($activity->{$field_name} !== null) {
                $index = intdiv($first_second_in_next_week - $activity->start_date - 1, $one_week);
                $per_week[$index] = max($activity->{$field_name}, ($per_week[$index] ?? 0));
            }
        }

        $total = 0;
        $num_of_weeks = 0;
        foreach($per_week as $index => $value)
        {
            if($value !== 0)
            {
                $num_of_weeks++;
                $total += $value;
            }
        }

        if($num_of_weeks === 0)
            return null;

        return 1.0 * $total / $num_of_weeks;
    }

    public static function calculate_avg($activities, $field_name)
    {
        $total = 0;
        $len = 0;

        foreach ($activities as $activity)
            if($activity->{$field_name} !== null)
            {
                $total += $activity->{$field_name};
                $len++;
            }

        if($len === 0)
            return null;

        return 1.0 * $total / $len;
    }

    // returns number of seconds since the midnight
    public static function calculate_median_time($activities, $field_name)
    {
        $times = [];

        foreach ($activities as $activity)
            if($activity->{$field_name} !== null)
                array_push($times, date('s', $activity->{$field_name}) + 60 * idate('i', $activity->{$field_name}) + 60 * 60 * idate('H', $activity->{$field_name}));

        if(count($times) === 0)
            return null;

        sort($times);

        $len = count($times);

        if($len & 1)
            return $times[floor($len / 2)];
        else
            return ($times[floor($len / 2) - 1] + $times[floor($len / 2)]) / 2;
    }

    private static function get_first_second_in_next_week($ts)
    {
        $dayOfCurrentWeek = idate('w', $ts);

        if($dayOfCurrentWeek == 0)
            $dayOfCurrentWeek = 7;

        $nextMondayDate = explode('-', date('Y-m-d', $ts + (7 - $dayOfCurrentWeek + 1) * 24 * 60 * 60));

        return mktime(0, 0, 0, intval($nextMondayDate[1]), intval($nextMondayDate[2]), intval($nextMondayDate[0]));
    }
}
