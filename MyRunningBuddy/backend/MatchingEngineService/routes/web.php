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

// health check for MatchingEngineService
$router->get('/status', function() {
    return response()->json(['status' => 'ok'], \Illuminate\Http\Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
});

$router->post('/activities', ['uses' => 'ActivityImporterController@import_activities']);
$router->get('/matcher/stats/{runner_id}', ['uses' => 'StatsController@get_stats']);
$router->post('/matcher/stats/{runner_id}', ['uses' => 'StatsController@set_stats']);
