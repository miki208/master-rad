<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use App\Models\Runner;
use App\Models\ExternalService;
use App\Models\ExternalAccount;

class RevokeAccessTokenTest extends TestCase
{
    use DatabaseMigrations;

    public function testRevokeAccessTokenWithoutRefreshingOk()
    {
        // test setup
        $runner = Runner::factory()->create();
        ExternalService::factory()->create();
        ExternalAccount::factory()->create([
            'confirmation_id' => \App\Models\ExternalAccount::CONFIRMATION_ID_AUTHORIZED,
            'runner_id' => $runner->id,
            'access_token' => 'accesstoken',
            'refresh_token' => 'refreshtoken',
            'scope' => 'somescope',
            'expires_at' => date('Y-m-d H:i:s', time() + 6 * 60 * 60)
        ]);

        // run test case
        $this->delete("/runner/$runner->id/external_service/StravaGatewayService", [], ['X-User' => $runner->id]);

        // asserts
        $this->assertResponseOk();

        $this->seeInDatabase('ExternalAccounts', [
            'runner_id' => $runner->id,
            'confirmation_id' => ExternalAccount::CONFIRMATION_ID_REVOKED,
            'service_name' => 'StravaGatewayService'
        ]);
    }
}
