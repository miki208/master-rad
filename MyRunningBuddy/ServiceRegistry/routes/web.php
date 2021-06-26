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

$router->post('/service', ['uses' => 'ServiceController@AddService']);
$router->get('/service/{service_name}', ['uses' => 'ServiceController@GetService']);
$router->get('/service', ['uses' => 'ServiceController@GetAllServices']);
$router->delete('/service/{service_id}', ['uses' => 'ServiceController@DeleteService']);
$router->patch('/service/{service_id}', ['uses' => 'ServiceController@UpdateService']);

//TODO: add routes for health check
