<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class AuthorizationParamsTest extends TestCase
{
    public function testGetAuthorizationParams()
    {
        $this->json('GET', '/authorization_params', [])
             ->seeJson([
                'authorization_url' => 'https://www.strava.com/oauth/authorize?&response_type=code&approval_prompt=force&client_id=208&scope=profile:read_all,activity:read_all&redirect_uri=http://location_url/authorization_grant_callback/StravaGatewayService'
             ]);

        $this->assertResponseOk();
    }
}
