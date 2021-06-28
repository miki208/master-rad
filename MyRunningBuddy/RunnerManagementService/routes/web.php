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

// health check for RunnerManagementService
$router->get('/status', function() {
    return response()->json(['status' => 'ok'], \Illuminate\Http\Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
});

// register a new runner
$router->post("/runner", ['uses' => 'RunnerController@register']);

// get a runner profile info
$router->get("/runner/{id}", ['uses' => 'RunnerController@get_runner']);

// update a runner profile info
$router->patch('/runner/{id}', ['uses' => 'RunnerController@update_runner']);

// get all available external services along with the info whether the user has linked them to their account
$router->get('/runner/{id}/linked_services', ['uses' => 'RunnerController@get_linked_services']);

// get params needed to initiate authorization with an external service
$router->get('/runner/{id}/authorization_params', ['uses' => 'RunnerController@get_authorization_params']);
