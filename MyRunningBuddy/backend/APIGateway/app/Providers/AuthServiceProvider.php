<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app->router->group(['prefix' => 'oauth', 'namespace' => '\Laravel\Passport\Http\Controllers'], function() {
            $routeRegistrar = new \Dusterio\LumenPassport\RouteRegistrar($this->app->router);

            $routeRegistrar->forAccessTokens();
            $routeRegistrar->forTransientTokens();

            $this->app->router->delete('/token', ['middleware' => 'auth', function(Request $request) {
                $request->user()->token()->revoke();
            }]);
        });
    }
}
