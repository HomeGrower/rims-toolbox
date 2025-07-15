<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CodeAuthController extends Controller
{
    public function showCodeForm(): Response
    {
        return Inertia::render('Auth/CodeLogin');
    }

    public function loginWithCode(Request $request): RedirectResponse
    {
        $request->validate([
            'access_code' => 'required|string',
        ]);

        $project = Project::where('access_code', strtoupper($request->access_code))
            ->where('status', '!=', 'completed')
            ->first();

        if (!$project) {
            return back()->withErrors([
                'access_code' => 'Invalid access code.',
            ]);
        }

        // Store project in session
        session(['current_project_id' => $project->id]);

        // Activate project if it's the first login
        if ($project->status === 'setup') {
            $project->activate();
        }

        return redirect()->route('client.dashboard');
    }

    public function logout(): RedirectResponse
    {
        session()->forget('current_project_id');
        
        if (Auth::check()) {
            Auth::logout();
        }

        return redirect()->route('code.login');
    }
}