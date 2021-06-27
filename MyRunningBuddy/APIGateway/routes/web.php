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

// unprotected routes
$router->post('/user', ['uses' => 'UserController@register']);

$router->get('/status', function() {
    return response()->json(['status' => 'ok'], \Illuminate\Http\Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
});

$router->get("/system_status", function() {
    $servicesResponse = \App\Helpers\HttpHelper::request('get', 'ServiceRegistry', '/service', [], []);

    if($servicesResponse == null)
        return \App\Helpers\ResponseHelper::GenerateInternalServiceUnavailableErrorResponse();

    $services = json_decode((string) $servicesResponse->getBody(), true);

    $response = [];
    foreach($services as $service)
    {
        array_push($response, [
            'id' => $service['id'],
            'service_name' => $service['service_name'],
            'last_status' => $service['last_status'],
            'updated_at' => $service['updated_at']
        ]);
    }

    return response()->json($response, \Illuminate\Http\Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
});

// protected routes
$router->group(['middleware' => 'auth'], function () use ($router) {
    $router->get('/user/{id}', ['uses' => 'UserController@get_user']);
    $router->patch('/user/{id}', ['uses' => 'UserController@update_user']);
    $router->get('/user/{id}/linked_services', ['uses' => 'UserController@get_linked_services']);
    $router->get('/user/{id}/authorization_params', ['uses' => 'UserController@get_authorization_params']);
});
