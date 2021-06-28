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

// register a new service
$router->post('/service', ['uses' => 'ServiceController@AddService']);

// get specific service(s) by name
$router->get('/service/{service_name}', ['uses' => 'ServiceController@GetService']);

// get all registered services
$router->get('/service', ['uses' => 'ServiceController@GetAllServices']);

// delete specific service by service id
$router->delete('/service/{service_id}', ['uses' => 'ServiceController@DeleteService']);

// update specific service by service id
$router->patch('/service/{service_id}', ['uses' => 'ServiceController@UpdateService']);
