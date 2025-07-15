<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $shared = [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
        
        // Always add app logos
        $project = $request->attributes->get('project');
        $brandLogo = null;
        $rimsLogo = null;
        
        try {
            $rimsLogo = Setting::get('rims_logo');
        } catch (\Exception $e) {
            // Settings table might not exist yet
        }
        
        // Get brand logo if project exists
        if ($project) {
            // Force load the relationship if not loaded
            if (!$project->relationLoaded('hotelBrand')) {
                $project->load('hotelBrand');
            }
            
            if ($project->hotelBrand && $project->hotelBrand->logo) {
                $brandLogo = \Storage::url($project->hotelBrand->logo);
                \Log::info('Brand logo set: ' . $brandLogo);
            } else {
                \Log::info('Brand logo not set - Brand: ' . ($project->hotelBrand ? 'exists' : 'null') . ', Logo: ' . ($project->hotelBrand?->logo ?? 'null'));
            }
        } else {
            \Log::info('No project found in request');
        }
        
        $shared['app'] = [
            'rims_logo' => $rimsLogo,
            'brand_logo' => $brandLogo,
        ];
        
        return $shared;
    }
}
