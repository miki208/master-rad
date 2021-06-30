<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use App\Models\Runner;
use App\Models\ExternalService;
use App\Models\ExternalAccount;
use \Illuminate\Support\Facades\Http;
use \Illuminate\Http\Response;

class AuthorizationGrantTest extends TestCase
{
    use DatabaseMigrations;

    public function testAuthorizationGrantOk()
    {
        // test setup
        $runner = Runner::factory()->create();
        ExternalService::factory()->create();
        ExternalAccount::factory()->create([
            'confirmation_id' => 12345,
            'runner_id' => $runner->id
        ]);

        Http::fake([
            'http://' . config('serviceregistry.ServiceRegistryUrl') . '/service/StravaGatewayService' => Http::response(
                [
                    [
                        'location' => 'strava-gateway-service',
                        'port' => 8000
                    ]
                ], Response::HTTP_OK),
            'http://strava-gateway-service:8000/authorization_grant_callback' => Http::response([
                'access_token' => 'accesstoken',
                'refresh_token' => 'refreshtoken',
                'expires_at' => date('Y-m-d H:i:s', time() + 6 * 60 * 60),
                'athlete' => [
                    'lastname' => 'LastName',
                    'city' => 'CityExample',
                    'country' => 'CountryExample'
                ]
            ], Response::HTTP_OK)
        ]);

        // test case run
        $this->post('/authorization_grant_callback', [
            'service_name' => 'StravaGatewayService',
            'confirmation_id' => 12345,
            'scope' => 'read,profile:read_all,activity:read_all'
        ]);

        // asserts
        $this->assertResponseOk();

        $this->seeInDatabase('ExternalAccounts', [
            'service_name' => 'StravaGatewayService',
            'runner_id' => $runner->id,
            'confirmation_id' => ExternalAccount::CONFIRMATION_ID_AUTHORIZED,
            'access_token' => 'accesstoken',
            'refresh_token' => 'refreshtoken'
        ]);

        $this->seeInDatabase('runners', [
            'id' => $runner->id,
            'location' => 'CityExample, CountryExample',
            'surname' => 'LastName'
        ]);
    }
}
