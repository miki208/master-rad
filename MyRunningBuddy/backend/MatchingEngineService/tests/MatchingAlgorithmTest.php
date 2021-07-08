<?php

use App\Models\PotentialMatch;
use App\Models\RunnerStats;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Laravel\Lumen\Testing\DatabaseMigrations;

class MatchingAlgorithmTest extends TestCase
{
    use DatabaseMigrations;

    public function testScoringFunction()
    {
        $method = self::getPrivateMethod('App\Http\Controllers\MatcherController', 'scoring_function');

        // same normalized values should result in maximized score
        $this->assertEquals(1, $method->invoke(null, 1, 1));
        $this->assertEquals(1, $method->invoke(null, 0.3, 0.3));
        $this->assertEquals(1, $method->invoke(null, 1.1, 1.1));

        // very different normalized values should result in very low score
        $this->assertEquals(0.2, $method->invoke(null, 0.9, 0.1));

        // score function should be commutative
        $this->assertTrue($method->invoke(null, 0.6, 0.4) === $method->invoke(null, 0.4, 0.6));

        // it also should work for values out of range [0, 1]
        $this->assertEquals(-1.9, $method->invoke(null, 1.5, -1.4));
    }

    public function testNormalizationFunction()
    {
        $method = self::getPrivateMethod('App\Http\Controllers\MatcherController', 'normalize_field_value');

        // null value should result in average normalized value (0.5)
        $this->assertEquals(0.5, $method->invoke(null, null, 1, 10));

        // value in the middle of range should also give result of 0.5
        $this->assertEquals(0.5, $method->invoke(null, 5, 0, 10));

        // values at the limits of the range should give 0 and 1 respectively
        $this->assertEquals(0, $method->invoke(null, 6, 6, 12));
        $this->assertEquals(1, $method->invoke(null, 12, 6, 12));

        // it should also be possible to get scores lower than 0 and bigger than 1 if the value is out of the defined range
        $this->assertTrue($method->invoke(null, 5, 6, 12) < 0);
        $this->assertTrue($method->invoke(null, 13, 6, 12) > 1);
    }

    public function testScoreCalculation()
    {
        // test setup
        $thisRunner = RunnerStats::factory()->create([
            'runner_id' => 1,
            'avg_total_distance_per_week' => 50,
            'avg_moving_time_per_week' => 16200,
            'avg_longest_distance_per_week' => 16,
            'avg_pace_per_week' => 5.2
        ]);

        $suggestedRunner = RunnerStats::factory()->create([
            'runner_id' => 2,
            'avg_total_distance_per_week' => 25,
            'avg_moving_time_per_week' => 7500,
            'avg_longest_distance_per_week' => 10,
            'avg_pace_per_week' => 6,
            'avg_total_elevation_per_week' => 50
        ]);

        $method = self::getPrivateMethod('App\Http\Controllers\MatcherController', 'calculate_score');

        $result = $method->invoke(null, $thisRunner, $suggestedRunner, null);

        $this->assertEqualsWithDelta(4.76, $result, 0.01);
    }

    public function testGetAllMatches()
    {
        self::createFirstNRunnerStats(10);

        PotentialMatch::factory()->create(['runner_id' => 0, 'suggested_runner' => 3, 'score' => 4, 'accepted' => true]);
        PotentialMatch::factory()->create(['runner_id' => 0, 'suggested_runner' => 8, 'score' => 5]);
        PotentialMatch::factory()->create(['runner_id' => 0, 'suggested_runner' => 9, 'score' => 4, 'accepted' => true]);
        PotentialMatch::factory()->create(['runner_id' => 0, 'suggested_runner' => 4, 'score' => 3, 'accepted' => true]);
        PotentialMatch::factory()->create(['runner_id' => 0, 'suggested_runner' => 2, 'score' => 3, 'accepted' => true]);

        PotentialMatch::factory()->create(['runner_id' => 3, 'suggested_runner' => 0, 'score' => 4, 'accepted' => true]);
        PotentialMatch::factory()->create(['runner_id' => 4, 'suggested_runner' => 0, 'score' => 5, 'accepted' => true]);
        PotentialMatch::factory()->create(['runner_id' => 2, 'suggested_runner' => 0, 'score' => 4, 'accepted' => true]);
        PotentialMatch::factory()->create(['runner_id' => 2, 'suggested_runner' => 8, 'score' => 5, 'accepted' => true]);

        $this->get("/matcher/0/matches", ['X-User' => 0]);
        $responseJson = $this->response->json();

        $this->assertEquals(3, count($responseJson));
        $this->assertNotFalse(array_search(['suggested_runner' => 3, 'score' => 4], $responseJson));
        $this->assertNotFalse(array_search(['suggested_runner' => 4, 'score' => 3], $responseJson));
        $this->assertNotFalse(array_search(['suggested_runner' => 2, 'score' => 3], $responseJson));
    }

    public function testMatchingActionWrongAction()
    {
        $this->post('/matcher/match/0/1', ['action' => 'wrong_action'], ['X-User' => 0]);
        $this->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testMatchingActionUnauthorized()
    {
        $this->post('/matcher/match/0/1', ['action' => 'wrong_action'], ['X-User' => 1]);
        $this->assertResponseStatus(Response::HTTP_UNAUTHORIZED);

        $this->post('/matcher/match/0/1', ['action' => 'wrong_action'], []);
        $this->assertResponseStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testMatchingActionNonExistingMatch()
    {
        $this->post('/matcher/match/0/1', ['action' => 'accept'], ['X-User' => 0]);
        $this->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        $this->post('/matcher/match/0/1', ['action' => 'reject'], ['X-User' => 0]);
        $this->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testMatchingActionExistingButNotFirst()
    {
        self::createFirstNRunnerStats(10);

        PotentialMatch::factory()->create(['runner_id' => 0, 'suggested_runner' => 3, 'score' => 4, 'accepted' => null]);
        PotentialMatch::factory()->create(['runner_id' => 0, 'suggested_runner' => 4, 'score' => 4, 'accepted' => null]);
        PotentialMatch::factory()->create(['runner_id' => 3, 'suggested_runner' => 0, 'score' => 4, 'accepted' => null]);
        PotentialMatch::factory()->create(['runner_id' => 4, 'suggested_runner' => 0, 'score' => 4, 'accepted' => null]);

        $this->post('/matcher/match/0/4', ['action' => 'reject'], ['X-User' => 0]);
        $this->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testMatchingActionAlreadySentAction()
    {
        self::createFirstNRunnerStats(10);

        PotentialMatch::factory()->create(['runner_id' => 0, 'suggested_runner' => 3, 'score' => 4, 'accepted' => true]);
        PotentialMatch::factory()->create(['runner_id' => 3, 'suggested_runner' => 0, 'score' => 4, 'accepted' => null]);

        $this->post('/matcher/match/0/3', ['action' => 'accept'], ['X-User' => 0]);
        $this->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        $this->post('/matcher/match/0/3', ['action' => 'reject'], ['X-User' => 0]);
        $this->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    public function testMatchingActionInvalidState()
    {
        self::createFirstNRunnerStats(10);

        // there is no other side of the match
        PotentialMatch::factory()->create(['runner_id' => 0, 'suggested_runner' => 3, 'score' => 4, 'accepted' => null]);

        $this->post('/matcher/match/0/3', ['action' => 'accept'], ['X-User' => 0]);
        $this->assertResponseStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function testMatchingActionFirstAccept()
    {
        self::createFirstNRunnerStats(10);

        PotentialMatch::factory()->create(['runner_id' => 0, 'suggested_runner' => 3, 'score' => 4, 'accepted' => null]);
        PotentialMatch::factory()->create(['runner_id' => 3, 'suggested_runner' => 0, 'score' => 4, 'accepted' => null]);

        $this->post('/matcher/match/0/3', ['action' => 'accept'], ['X-User' => 0]);
        $this->assertResponseStatus(Response::HTTP_OK);
        $this->assertEquals('accepted', $this->response->json('status'));
    }

    public function testMatchingActionSecondAccept()
    {
        self::createFirstNRunnerStats(10);

        PotentialMatch::factory()->create(['runner_id' => 0, 'suggested_runner' => 3, 'score' => 4, 'accepted' => null]);
        PotentialMatch::factory()->create(['runner_id' => 3, 'suggested_runner' => 0, 'score' => 4, 'accepted' => null]);

        Http::fake([
            'http://' . config('serviceregistry.ServiceRegistryUrl') . '/service/MessagingService' => Http::response([
                [
                    'location' => 'messaging-service',
                    'port' => 8000
                ]
            ], Response::HTTP_OK),
            '*' => Http::response([], Response::HTTP_CREATED, [])
        ]);

        $this->post('/matcher/match/3/0', ['action' => 'accept'], ['X-User' => 3]);
        $this->post('/matcher/match/0/3', ['action' => 'accept'], ['X-User' => 0]);

        $this->assertResponseStatus(Response::HTTP_OK);
        $this->assertEquals('matched', $this->response->json('status'));
    }

    public function testMatchingActionAcceptAfterReject()
    {
        self::createFirstNRunnerStats(10);

        PotentialMatch::factory()->create(['runner_id' => 0, 'suggested_runner' => 3, 'score' => 4, 'accepted' => null]);
        PotentialMatch::factory()->create(['runner_id' => 3, 'suggested_runner' => 0, 'score' => 4, 'accepted' => null]);

        $this->post('/matcher/match/3/0', ['action' => 'reject'], ['X-User' => 3]);
        $this->post('/matcher/match/0/3', ['action' => 'accept'], ['X-User' => 0]);

        $this->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    private static function createFirstNRunnerStats($n)
    {
        $runnersStats = [];
        for($i = 0; $i < 10; $i++)
            array_push($runnersStats, RunnerStats::factory()->create(['runner_id' => $i]));

        return $runnersStats;
    }

    private static function getPrivateMethod($class_name, $name) {
        $class = new ReflectionClass($class_name);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
}
