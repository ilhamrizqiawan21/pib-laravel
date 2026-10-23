<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and attach security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $isProduction = app()->isProduction();

        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'".($isProduction ? '' : ' http://localhost:* ws://localhost:* http://127.0.0.1:* ws://127.0.0.1:*'),
            "style-src 'self' 'unsafe-inline'".($isProduction ? '' : ' http://localhost:* http://127.0.0.1:*'),
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'".($isProduction ? '' : ' http://localhost:* ws://localhost:* http://127.0.0.1:* ws://127.0.0.1:*'),
            "frame-ancestors 'self'",
            "form-action 'self'",
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        if ($isProduction) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
