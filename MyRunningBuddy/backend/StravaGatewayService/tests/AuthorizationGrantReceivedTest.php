<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;

class AuthorizationGrantReceivedTest extends TestCase
{
    public function testAuthorizationGrantOk()
    {
        Http::fake([
            'https://www.strava.com/api/v3/oauth/token' => Http::response([
                'refresh_token' => 'refreshtoken',
                'access_token' => 'accesstoken',
                'expires_at' => 'expires_at',
                'athlete' => []
            ], 200, [])
        ]);

        $this->json('POST', '/authorization_grant_callback', [
            'code' => 'grantcode'
        ])->seeJson([
            'refresh_token' => 'refreshtoken',
            'access_token' => 'accesstoken',
            'expires_at' => 'expires_at'
        ]);

        $this->assertResponseOk();
    }
}
