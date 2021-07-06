<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Helpers\ResponseHelper;
use App\Models\PotentialMatch;
use App\Models\RunnerStats;
use App\Models\RunningLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Validator;

class MatcherController extends Controller
{
    public static $available_fields = [
        'avg_total_distance_per_week' => [0, 100], // between 0 and 100 km
        'avg_moving_time_per_week' => [0, 36000], // between 0 and 10 hours
        'avg_longest_distance_per_week' => [0, 42], // between 0 and 42 km
        'avg_pace_per_week' => [2.5, 10], // between 2.5 (2:30) min / km and 10 min / km
        'avg_start_time_per_week' => [0, 86400], // between 0 and 86400 sec
        'avg_total_elevation_per_week' => [0, 10000] // between 0 and 10000 m
    ];

    private static $radiusOfSearch;
    private static $numberOfRunnersInRadius;
    private static $numberOfTopRunnersForMatching;
    private static $scalingFactorForPriorityField;

    public function __construct()
    {
        self::$radiusOfSearch = CommonHelper::get_param('matchingengine.RadiusOfSearch');
        self::$numberOfRunnersInRadius = CommonHelper::get_param('matchingengine.NumberOfRunnersInRadius');
        self::$numberOfTopRunnersForMatching = CommonHelper::get_param('matchingengine.NumberOfTopRunnersForMatching');
        self::$scalingFactorForPriorityField = CommonHelper::get_param('matchingengine.ScalingFactorForPriorityField');
    }

    public function find_partner(Request $request, $runner_id)
    {
        $authenticated_user = $request->header('X-User');

        if($authenticated_user == null or $authenticated_user != $runner_id)
            return ResponseHelper::GenerateSimpleTextResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);

        // data validation
        $params = $request->only(['runner_id', 'priority_field']);

        $validator = Validator::make($params, [
            'priority_field' => 'sometimes|string|in:' . implode(',', array_keys(self::$available_fields))
        ]);

        if($validator->fails())
            return ResponseHelper::GenerateValidatorErrorMessage($validator->errors());

        $priority_field = $params['priority_field'] ?? null;

        $thisRunnerLocation = RunningLocation::get_running_location_by_runner_id($runner_id);
        $thisRunnerStats = RunnerStats::get_runner_stats_by_runner_id($runner_id);

        if($thisRunnerStats === null or $thisRunnerLocation === null)
            return ResponseHelper::GenerateSimpleTextResponse('This runner has no required data to make this match.', Response::HTTP_BAD_REQUEST);

        // first check if there is an already suggested runner
        $potentialMatch = PotentialMatch::get_next_potential_match($runner_id);
        if($potentialMatch !== null)
        {
            $potentialMatchLocation = RunningLocation::get_running_location_by_runner_id($potentialMatch->suggested_runner);
            $potentialMatchStats = RunnerStats::get_runner_stats_by_runner_id($potentialMatch->suggested_runner);

            if($potentialMatchLocation === null or $potentialMatchStats === null)
                return ResponseHelper::GenerateSimpleTextResponse('Unexpected error.', Response::HTTP_INTERNAL_SERVER_ERROR);

            return response()->json(self::generate_response_data(
                $thisRunnerLocation,
                $thisRunnerStats,
                $potentialMatchLocation,
                $potentialMatchStats,
                $potentialMatch->score
            ), Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
        }

        // there is no already suggested runners; let's find several potential candidates, and return the first one
        $potentialMatchesLocations = RunningLocation::find_nearest_runners(
            $runner_id,
            $thisRunnerLocation->lat,
            $thisRunnerLocation->lng,
            self::$radiusOfSearch,
            self::$numberOfRunnersInRadius
        );

        if(count($potentialMatchesLocations) === 0)
            return ResponseHelper::GenerateSimpleTextResponse("There are no matches for this runner. Try again later.", Response::HTTP_NOT_FOUND);

        // calculate the score for the runners
        $scoredMatches = [];
        foreach($potentialMatchesLocations as $potentialMatchLocation)
        {
            $potentialMatchStats = RunnerStats::get_runner_stats_by_runner_id($potentialMatchLocation->runner_id);

            if($potentialMatchStats === null)
                continue;

            array_push($scoredMatches,
                self::generate_response_data(
                    $thisRunnerLocation,
                    $thisRunnerStats,
                    $potentialMatchLocation,
                    $potentialMatchStats,
                    self::calculate_score($thisRunnerStats, $potentialMatchStats, $priority_field)
                )
            );
        }

        usort($scoredMatches, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        // save top x matches based on score and return the first one
        $numOfScoredMatches = count($scoredMatches);
        for($i = 0; $i < $numOfScoredMatches; $i++)
        {
            if($i >= self::$numberOfTopRunnersForMatching)
                break;

            PotentialMatch::add_potential_match($runner_id, $scoredMatches[$i]['suggested_runner']['runner_id'], $scoredMatches[$i]['score']);
        }

        return response()->json($scoredMatches[0], Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
    }

    private static function generate_response_data($thisRunnerLocation, $thisRunnerStats, $potentialMatchLocation, $potentialMatchStats, $score)
    {
        return [
            'me' => [
                'runner_id' => $thisRunnerLocation->runner_id,
                'stats' => $thisRunnerStats,
                'location' => [$thisRunnerLocation->lat, $thisRunnerLocation->lng]
            ],
            'suggested_runner' => [
                'runner_id' => $potentialMatchLocation->runner_id,
                'stats' => $potentialMatchStats,
                'location' => [$potentialMatchLocation->lat, $potentialMatchLocation->lng]
            ],
            'score' => $score
        ];
    }

    private static function calculate_score($thisRunnerStats, $potentialMatchStats, $priorityField)
    {
        $totalScore = 0;

        foreach(self::$available_fields as $field => $range)
        {
            $totalScore += self::scoring_function(
                self::normalize_field_value($thisRunnerStats->{$field}, $range[0], $range[1]),
                self::normalize_field_value($potentialMatchStats->{$field}, $range[0], $range[1])
            ) * (($field === $priorityField) ? self::$scalingFactorForPriorityField : 1);
        }

        return $totalScore;
    }

    private static function normalize_field_value($value, $expectedMinVal, $expectedMaxVal)
    {
        // if value is not available, use avg
        if($value === null)
            return 0.5;

        return ($value - $expectedMinVal) / ($expectedMaxVal - $expectedMinVal);
    }

    private static function scoring_function($normalized_value1, $normalized_value2)
    {
        return 1 - abs($normalized_value1 - $normalized_value2);
    }
}
