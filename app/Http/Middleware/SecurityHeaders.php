<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Always revalidate HTML so browser-cached pages can never go stale
        // (stale cached markup was serving pre-fix mojibake + old CSS links).
        $contentType = $response->headers->get('Content-Type', '');

        if (str_contains($contentType, 'text/html')) {
            $response->headers->set('Cache-Control', 'no-cache, private');
        }

        return $response;
    }
}
