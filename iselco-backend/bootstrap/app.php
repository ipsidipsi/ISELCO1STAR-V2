<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Enable Sanctum's stateful middleware (CRITICAL for Cookie Auth)
        $middleware->statefulApi();

        // Enable CORS for API and Broadcasting routes
		$middleware->trustProxies(at: '*');
        
        // [FIX] Rewrite XSRF-TOKEN cookie to subdomain-shared domain
        $middleware->append(\App\Http\Middleware\FixXsrfCookieDomain::class);
        
        // [FIX] Inject XSRF-TOKEN from cookie to header as fallback
        $middleware->append(\App\Http\Middleware\InjectXsrfHeader::class);

        // Enable CORS for API and Broadcasting routes
        $middleware->api(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        // Add CORS to web middleware for broadcasting auth
        $middleware->web(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        // [FIX] Exclude login and forgot-password from CSRF for Mobile/APK compatibility
        // These routes use Sanctum token auth, not session-based, so CSRF not needed
        $middleware->validateCsrfTokens(except: [
            'api/login',
            'api/forgot-password',
            'sanctum/csrf-cookie',
            'broadcasting/auth', // Echo/Reverb authorization
        ]);
    })->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $exception, Request $request) {
            // Return JSON response for API routes when authentication fails
            if ($request->is('api/*') && $exception instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            return $response;
        });
    })->create();
