<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-config', function () {
    return [
        'session_driver' => config('session.driver'),
        'same_site' => config('session.same_site'),
        'secure_cookie' => config('session.secure'),
        'is_https_detected' => request()->secure(),
        'server_time' => now()->toDateTimeString(),
        'timezone' => config('app.timezone'),
        'sanctum_stateful_domains' => config('sanctum.stateful'),
        'request_host' => request()->getHost(),
        'request_origin' => request()->headers->get('origin'),
    ];
});

Route::get('/debug-csrf', function () {
    return [
        'config_cookie_name' => config('session.cookie'),
        'session_id' => session()->getId(),
        'csrf_token_server' => csrf_token(),
        'xsrf_cookie_recieved' => $_COOKIE['XSRF-TOKEN'] ?? null,
        'xsrf_header_recieved' => request()->header('X-XSRF-TOKEN'),
        'csrf_header_recieved' => request()->header('X-CSRF-TOKEN'), // NEW: Plain Text Header
        'session_cookie_recieved' => request()->cookie(config('session.cookie')),
        'all_cookies_raw' => array_keys($_COOKIE), // Debug: See all cookies received
    ];
});

Route::get('/remote-clear', function () {
    Artisan::call('optimize:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    return "Remote Cache Cleared! Time: " . now();
});

// Debug: Check if Authorization header is being received
Route::get('/debug-auth', function () {
    $authHeader = request()->header('Authorization');
    $bearerToken = null;
    $tokenFromDb = null;
    $user = null;
    
    if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
        $bearerToken = substr($authHeader, 7);
        
        // Try to find the token in the database
        $tokenId = explode('|', $bearerToken)[0] ?? null;
        if ($tokenId && is_numeric($tokenId)) {
            $tokenFromDb = \Laravel\Sanctum\PersonalAccessToken::find($tokenId);
            if ($tokenFromDb) {
                $user = $tokenFromDb->tokenable;
            }
        }
    }
    
    return [
        'authorization_header_received' => $authHeader ? 'YES' : 'NO',
        'authorization_header_value' => $authHeader ? substr($authHeader, 0, 30) . '...' : null,
        'bearer_token_extracted' => $bearerToken ? substr($bearerToken, 0, 20) . '...' : null,
        'token_id_parsed' => explode('|', $bearerToken ?? '')[0] ?? null,
        'token_found_in_db' => $tokenFromDb ? 'YES' : 'NO',
        'token_user_id' => $user?->id,
        'token_user_name' => $user?->username,
        'all_headers' => array_keys(request()->headers->all()),
    ];
});
