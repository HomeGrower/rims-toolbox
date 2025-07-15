<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only add headers to HTML responses
        if ($response->headers->get('Content-Type') && str_contains($response->headers->get('Content-Type'), 'text/html')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
            
            // Content Security Policy
            $csp = "default-src 'self'; ";
            $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com; ";
            $csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; ";
            $csp .= "font-src 'self' https://fonts.gstatic.com; ";
            $csp .= "img-src 'self' data: https: blob:; ";
            $csp .= "connect-src 'self' wss://localhost:* ws://localhost:*; ";
            $csp .= "media-src 'self'; ";
            $csp .= "object-src 'none'; ";
            $csp .= "base-uri 'self'; ";
            $csp .= "form-action 'self'; ";
            $csp .= "frame-ancestors 'self'";
            
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}