<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectXsrfHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Logic: If Header is Missing, but Cookie Exists -> Inject it
        if (!$request->header('X-XSRF-TOKEN') && !$request->header('X-CSRF-TOKEN') && isset($_COOKIE['XSRF-TOKEN'])) {
            // DIRECT MAPPING: COOKIE (Plain) -> HEADER (Plain)
            // Debug confirms $_COOKIE['XSRF-TOKEN'] matches csrf_token(), so it is NOT encrypted.
            // verifying X-CSRF-TOKEN allows Laravel to skip the 'Encrypter' step and check directly as verify-token.
            
            $request->headers->set('X-CSRF-TOKEN', $_COOKIE['XSRF-TOKEN']);
        }

        return $next($request);
    }
}
