<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Http\Middleware\Authorization;
use Ramsey\Uuid\Uuid;

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

$router->group(['prefix' => 'user'], function() use ($router) {

    $router->post("login", ['uses' => 'UserController@login']);
    $router->post("register", ['uses' => 'UserController@register']);
});

$router->group(['middleware' => Authorization::class], function() use ($router) {

    $router->group(['prefix' => 'user'], function() use ($router) {

        $router->get('get_initial_data', 'UserController@getInitialData');
        $router->get('get_orders', 'UserController@getOrders');
    });

    $router->group(['prefix' => 'order'], function() use ($router) {

        $router->post('make_order', 'OrderController@makeOrder');
        $router->post('pay_order', 'OrderController@payOrder');
    });
});