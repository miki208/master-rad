<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PotentialMatch extends Model
{
    use HasFactory;

    protected $table = 'PotentialMatches';

    protected $fillable = [
        'runner_id', 'suggested_runner'
    ];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public static function get_next_potential_match($runner_id)
    {
        return PotentialMatch::where('runner_id', $runner_id)->where('accepted', null)->orderBy('id', 'asc')->first();
    }

    public static function add_potential_match($runner_id, $suggested_runner_id, $score)
    {
        $potentialMatchFirstSide = new PotentialMatch();
        $potentialMatchFirstSide->runner_id = $runner_id;
        $potentialMatchFirstSide->suggested_runner = $suggested_runner_id;
        $potentialMatchFirstSide->score = $score;
        $potentialMatchFirstSide->save();

        $potentialMatchSecondSide = new PotentialMatch();
        $potentialMatchSecondSide->runner_id = $suggested_runner_id;
        $potentialMatchSecondSide->suggested_runner = $runner_id;
        $potentialMatchSecondSide->score = $score;
        $potentialMatchSecondSide->save();
    }
}
