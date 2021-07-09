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

// health check for MessagingService
$router->get('/status', function() {
    return response()->json(['status' => 'ok'], \Illuminate\Http\Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
});

$router->post('/messages', ['uses' => 'MessagingController@create_conversation']);
$router->post('/messages/{runner_id1}/{runner_id2}', ['uses' => 'MessagingController@add_message']);
$router->get('/messages/{runner_id}', ['uses' => 'MessagingController@get_conversations']);
$router->get('/messages/{runner_id1}/{runner_id2}', ['uses' => 'MessagingController@get_conversation']);
