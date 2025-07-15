<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $projectId = session('current_project_id');

        if (!$projectId) {
            return redirect()->route('code.login');
        }

        $project = Project::with(['hotelBrand', 'hotelChain'])->find($projectId);

        if (!$project || $project->status === 'completed') {
            session()->forget('current_project_id');
            return redirect()->route('code.login');
        }

        // Share project with all views
        View::share('currentProject', $project);
        
        // Add project to request for easy access
        $request->attributes->set('project', $project);

        return $next($request);
    }
}