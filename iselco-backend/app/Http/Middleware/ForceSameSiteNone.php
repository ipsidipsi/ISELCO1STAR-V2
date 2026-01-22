<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class ForceSameSiteNone
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // DEBUG: Uncomment to verify middleware is running
        // die("Middleware is RUNNING!");
        
        $response = $next($request);

        if (method_exists($response, 'headers')) {
            $cookies = $response->headers->getCookies();
            $debugCookies = [];

            foreach ($cookies as $cookie) {
                $debugCookies[] = $cookie->getName() . '(' . $cookie->getSameSite() . ')';
                
                // Target the session cookie and XSRF-TOKEN
                if ($cookie->getName() === config('session.cookie') || $cookie->getName() === 'XSRF-TOKEN' || $cookie->getName() === 'iselco_native_session') {
                    $newCookie = new Cookie(
                        $cookie->getName(),
                        $cookie->getValue(),
                        $cookie->getExpiresTime(),
                        $cookie->getPath(),
                        $cookie->getDomain(),
                        true, // Secure
                        $cookie->isHttpOnly(),
                        $cookie->isRaw(),
                        'None' // SameSite=None
                    );
                    
                    $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
                    $response->headers->setCookie($newCookie);
                }
            }
            
            // Append debug info to content if it's a view/text response
            if (method_exists($response, 'getContent') && method_exists($response, 'setContent')) {
                 $content = $response->getContent();
                 if (is_string($content) && str_contains($response->headers->get('Content-Type') ?? '', 'text/html')) {
                     $response->setContent($content . "<!-- DEBUG MIDDLEWARE SAW: " . implode(', ', $debugCookies) . " -->");
                 }
            }
        }

        return $response;
    }
}
