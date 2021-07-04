<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RunningLocation extends Model
{
    use HasFactory;

    protected $table = 'RunningLocations';

    protected $fillable = [
        'runner_id', 'lat', 'lng'
    ];

    protected $hidden = [

    ];

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
