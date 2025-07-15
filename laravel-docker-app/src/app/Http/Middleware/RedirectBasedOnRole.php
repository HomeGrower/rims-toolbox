<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectBasedOnRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // If admin is trying to access regular dashboard, redirect to admin
            if ($user->isAdmin() && $request->routeIs('dashboard')) {
                return redirect('/admin');
            }
            
            // If regular user is trying to access admin, redirect to dashboard
            if (!$user->isAdmin() && $request->is('admin/*')) {
                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}