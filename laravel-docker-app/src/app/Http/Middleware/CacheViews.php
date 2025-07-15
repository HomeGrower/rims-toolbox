<?php

namespace App\Http\Middleware;

use App\Services\CacheService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CacheViews
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Skip if caching is disabled
        if (!config('view-cache.enabled', true)) {
            return $next($request);
        }

        // Skip for authenticated users (they get personalized content)
        if ($request->user()) {
            return $next($request);
        }

        // Skip for excluded routes
        $excludedPatterns = config('view-cache.exclude', []);
        foreach ($excludedPatterns as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // Generate cache key based on URL and query parameters
        $cacheKey = 'page:' . md5($request->fullUrl());
        $ttl = config('view-cache.default_ttl', 3600);

        // Try to get cached response
        $cachedResponse = CacheService::remember($cacheKey, $ttl, function () use ($request, $next) {
            $response = $next($request);
            
            // Only cache successful HTML responses
            if ($response->getStatusCode() === 200 && 
                str_contains($response->headers->get('Content-Type'), 'text/html')) {
                return [
                    'content' => $response->getContent(),
                    'headers' => $response->headers->all(),
                ];
            }
            
            return null;
        }, ['pages']);

        // If we have a cached response, return it
        if ($cachedResponse && is_array($cachedResponse)) {
            $response = response($cachedResponse['content']);
            
            // Restore headers
            foreach ($cachedResponse['headers'] as $key => $values) {
                foreach ($values as $value) {
                    $response->headers->set($key, $value);
                }
            }
            
            // Add cache hit header
            $response->headers->set('X-Cache', 'HIT');
            
            return $response;
        }

        // Process normally and add cache miss header
        $response = $next($request);
        $response->headers->set('X-Cache', 'MISS');
        
        return $response;
    }
}