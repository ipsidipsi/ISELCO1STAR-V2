<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class FixXsrfCookieDomain
{
    /**
     * Handle an incoming request.
     * 
     * This middleware rewrites the XSRF-TOKEN cookie to include the subdomain-shared domain
     * so browsers accessing from star.iselcouno.com can store cookies set by apistar.iselcouno.com.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Get all cookies from the response
        if (method_exists($response, 'headers')) {
            $cookies = $response->headers->getCookies();

            foreach ($cookies as $cookie) {
                if ($cookie->getName() === 'XSRF-TOKEN') {
                    // Rewrite the cookie with subdomain-shared domain
                    $newCookie = new Cookie(
                        $cookie->getName(),
                        $cookie->getValue(),
                        $cookie->getExpiresTime(),
                        '/', // Path
                        '.iselcouno.com', // FORCE subdomain-shared domain
                        true, // Secure
                        false, // HttpOnly (MUST be false for XSRF-TOKEN to be readable by JS)
                        $cookie->isRaw(),
                        'none' // SameSite - Changed to 'none' for Mobile/APK compatibility
                    );
                    
                    // Remove old cookie, set new one
                    $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
                    $response->headers->setCookie($newCookie);
                }
            }
        }

        return $response;
    }
}
