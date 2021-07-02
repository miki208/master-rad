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

/* Unprotected routes */

// register a new user
$router->post('/users', ['uses' => 'UserController@register']);

// health check for APIGateway
$router->get('/status', function() {
    return response()->json(['status' => 'ok'], \Illuminate\Http\Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES);
});

// get the status of the system as whole
$router->get("/system_status", function() {
    $servicesResponse = \App\Helpers\HttpHelper::request('get', 'ServiceRegistry', '/service', [], []);

    if($servicesResponse == null)
        return \App\Helpers\ResponseHelper::GenerateInternalServiceUnavailableErrorResponse();

    $services = $servicesResponse->json();

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

// callback for accepting authorization grant from external services
$router->get('/authorization_grant_callback/{service_name}/{confirmation_id}', ['uses' => 'ExternalServiceAuthorizationController@authorization_grant_callback']);

/* Protected routes */

$router->group(['middleware' => 'auth'], function () use ($router) {
    // get a user profile info
    $router->get('/user/{id}', ['uses' => 'UserController@get_user']);

    // update a user profile info
    $router->patch('/user/{id}', ['uses' => 'UserController@update_user']);

    // get all available external services along with the info whether the user has linked them to their account
    $router->get('/user/{id}/linked_services', ['uses' => 'UserController@get_linked_services']);

    // get params needed to initiate authorization with an external service
    $router->get('/user/{id}/external_service_authorization_params', ['uses' => 'UserController@get_external_service_authorization_params']);

    $router->delete('/user/{id}/external_service/{service_name}', ['uses' => 'UserController@revoke_authorization_to_external_service']);
});
