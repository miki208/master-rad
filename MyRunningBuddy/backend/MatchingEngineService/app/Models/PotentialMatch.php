<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

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

    public static function get_matches($runner_id, $page, $results_per_page)
    {
        $query = '
                SELECT PM1.suggested_runner, PM1.score FROM
	                (
		                SELECT * FROM PotentialMatches
			                WHERE runner_id = :runner_id1 AND accepted = true
	                ) AS PM1
                    JOIN
	                (
		                SELECT * FROM PotentialMatches
			                WHERE suggested_runner = :runner_id2 AND accepted = true
	                ) AS PM2
                ON PM1.suggested_runner = PM2.runner_id
                LIMIT :row_offset,:num_of_rows';

        $matches = DB::select($query, [
            'runner_id1' => $runner_id,
            'runner_id2' => $runner_id,
            'row_offset' => ($page - 1) * $results_per_page,
            'num_of_rows' => $results_per_page
        ]);

        return $matches;
    }

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
