<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register broadcasting auth route with manual Sanctum authentication
        Route::post('api/broadcasting/auth', function (Request $request) {
            // Manually authenticate using Sanctum token
            $token = $request->bearerToken();
            
            if ($token) {
                $personalAccessToken = PersonalAccessToken::findToken($token);
                if ($personalAccessToken) {
                    $user = $personalAccessToken->tokenable;
                    // Set the authenticated user for this request
                    $request->setUserResolver(function () use ($user) {
                        return $user;
                    });
                }
            }
            
            // Now call Broadcast::auth() with the authenticated user
            return Broadcast::auth($request);
        });

        require base_path('routes/channels.php');
    }
}
