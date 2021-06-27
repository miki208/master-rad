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

$router->get('/status', function() {
    return response()->json(['status' => 'ok'], \Illuminate\Http\Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
});

$router->post("/runner", ['uses' => 'RunnerController@register']);
$router->get("/runner/{id}", ['uses' => 'RunnerController@get_runner']);
$router->patch('/runner/{id}', ['uses' => 'RunnerController@update_runner']);
$router->get('/runner/{id}/linked_services', ['uses' => 'RunnerController@get_linked_services']);
$router->get('/runner/{id}/authorization_params', ['uses' => 'RunnerController@get_authorization_params']);
