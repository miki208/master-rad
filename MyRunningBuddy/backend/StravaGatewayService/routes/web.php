<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

// health check for StravaGatewayService
$router->get('/status', function() {
    return response()->json(['status' => 'ok'], \Illuminate\Http\Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
});

// get params needed to initiate authorization with Strava external service
$router->get('/authorization_params', ['uses' => 'StravaController@get_authorization_params']);

// callback for accepting authorization grant from external services
$router->post('/external_service', ['uses' => 'StravaController@authorization_grant_received']);
