<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use App\Models\Runner;
use App\Models\ExternalService;
use App\Models\ExternalAccount;
use \Illuminate\Support\Facades\Http;
use \Illuminate\Http\Response;

class RevokeAccessTokenTest extends TestCase
{
    use DatabaseMigrations;

    public function testRevokeAccessTokenWithoutRefreshingOk()
    {
        // test setup
        $expires_at = time() + 5 * 60 * 60;

        $runner = Runner::factory()->create();
        ExternalService::factory()->create();
        ExternalAccount::factory()->create([
            'confirmation_id' => ExternalAccount::CONFIRMATION_ID_AUTHORIZED,
            'runner_id' => $runner->id,
            'access_token' => 'accesstoken',
            'refresh_token' => 'refreshtoken',
            'scope' => 'somescope',
            'expires_at' => $expires_at
        ]);

        $new_expires_at = time() + 5 * 60 * 60;
        $this->setFakeResponses($new_expires_at);

        // test case run
        $this->delete("/runner/$runner->id/external_service/StravaGatewayService", [], ['X-User' => $runner->id]);

        // asserts
        $this->assertResponseOk();

        $this->seeInDatabase('ExternalAccounts', [
            'runner_id' => $runner->id,
            'confirmation_id' => ExternalAccount::CONFIRMATION_ID_REVOKED,
            'service_name' => 'StravaGatewayService',
            'expires_at' => $expires_at,
            'access_token' => 'accesstoken',
            'refresh_token' => 'refreshtoken'
        ]);
    }

    public function testRevokeAccessTokenWithRefreshingOk()
    {
        // test setup
        $runner = Runner::factory()->create();
        ExternalService::factory()->create();
        ExternalAccount::factory()->create([
            'confirmation_id' => ExternalAccount::CONFIRMATION_ID_AUTHORIZED,
            'runner_id' => $runner->id,
            'access_token' => 'accesstoken',
            'refresh_token' => 'refreshtoken',
            'scope' => 'somescope',
            'expires_at' => time() + 10 * 60 // token expires in 10 minutes, enough to trigger refreshing mechanism
        ]);

        $new_expires_at = time() + 6 * 60 * 60;
        $this->setFakeResponses($new_expires_at);

        // test case run
        $this->delete("/runner/$runner->id/external_service/StravaGatewayService", [], ['X-User' => $runner->id]);

        // asserts
        $this->assertResponseOk();

        $this->seeInDatabase('ExternalAccounts', [
            'runner_id' => $runner->id,
            'confirmation_id' => ExternalAccount::CONFIRMATION_ID_REVOKED,
            'service_name' => 'StravaGatewayService',
            'expires_at' => $new_expires_at,
            'access_token' => 'accesstoken1',
            'refresh_token' => 'refreshtoken1'
        ]);
    }

    private function setFakeResponses($new_expires_at)
    {
        Http::fake(function ($request) use ($new_expires_at) {
            if($request->url() == 'http://strava-gateway-service:8000/access_token' and $request->method() == 'PATCH')
            {
                return Http::response([
                    'access_token' => 'accesstoken1',
                    'refresh_token' => 'refreshtoken1',
                    'expires_at' => $new_expires_at
                ], Response::HTTP_OK);
            }
            else if($request->url() == 'http://strava-gateway-service:8000/access_token' and $request->method() == 'DELETE')
            {
                return Http::response([], Response::HTTP_OK);
            }
            else if($request->url() == 'http://' . config('serviceregistry.ServiceRegistryUrl') . '/service/StravaGatewayService' and $request->method() == 'GET')
            {
                return Http::response(
                    [
                        [
                            'location' => 'strava-gateway-service',
                            'port' => 8000
                        ]
                    ], Response::HTTP_OK);
            }
        });
    }
}
