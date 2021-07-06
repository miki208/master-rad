<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class RunningLocation extends Model
{
    use HasFactory;

    protected $table = 'RunningLocations';

    protected $fillable = [
        'runner_id', 'lat', 'lng'
    ];

    protected $hidden = [

    ];

    public static function get_running_location_by_runner_id($runner_id)
    {
        return RunningLocation::where('runner_id', $runner_id)->first();
    }

    public static function find_nearest_runners($runner_id, $lat, $lng, $radius, $number_of_runners)
    {
        // find potential partners in the given radius
        // equirectangular approx - https://jonisalonen.com/2014/computing-distance-between-coordinates-can-be-simple-and-fast/
        $query = 'SELECT runner_id, lat, lng
                    FROM RunningLocations
                    WHERE runner_id != :runner_id1
                        AND POW(lat - :lat1, 2) + POW((lng - :lng1) * COS(RADIANS(:lat2)), 2) < POW(:radius1 / 110.25, 2)
                        AND runner_id NOT IN (
                            SELECT suggested_runner
                            FROM PotentialMatches
                            WHERE runner_id = :runner_id2
                        )
                    LIMIT :number_of_matches1';

        return DB::select($query, [
            'runner_id1' => $runner_id,
            'runner_id2' => $runner_id,
            'lat1' => $lat,
            'lat2' => $lat,
            'lng1' => $lng,
            'radius1' => $radius,
            'number_of_matches1' => $number_of_runners
        ]);
    }

    // https://www.mathworks.com/matlabcentral/answers/229312-how-to-calculate-the-middle-point-between-two-points-on-the-earth-in-matlab
    public static function get_middle_coord($lat1, $lng1, $lat2, $lng2)
    {
        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);

        $Bx = cos($lat2) * cos($lng2 - $lng1);
        $By = cos($lat2) * sin($lng2 - $lng1);

        return [
            rad2deg(atan2(sin($lat1) + sin($lat2), sqrt(pow(cos($lat1) + $Bx, 2) + pow($By, 2)))),
            rad2deg($lng1 + atan2($By, cos($lat1) + $Bx))
        ];
    }
}
