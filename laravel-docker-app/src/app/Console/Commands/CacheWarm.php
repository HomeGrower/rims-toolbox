<?php

namespace App\Console\Commands;

use App\Models\HotelChain;
use App\Models\HotelBrand;
use App\Models\Module;
use App\Models\PmsType;
use App\Models\Project;
use App\Services\CacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheWarm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm {--force : Force refresh all caches}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up application caches for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cache warming process...');
        
        $force = $this->option('force');
        
        // Clear all caches if force flag is set
        if ($force) {
            $this->info('Force flag detected - clearing all caches first...');
            Cache::flush();
        }

        // Warm up configuration caches
        $this->warmConfigurationCaches();

        // Warm up frequently accessed data
        $this->warmFrequentlyAccessedData();

        // Warm up relationship caches
        $this->warmRelationshipCaches();

        // Warm up view caches
        $this->warmViewCaches();

        $this->info('Cache warming completed successfully!');
    }

    /**
     * Warm up configuration caches
     */
    private function warmConfigurationCaches()
    {
        $this->info('Warming configuration caches...');
        
        // Cache all active hotel chains
        CacheService::remember('hotel_chains:active', 3600, function () {
            return HotelChain::where('is_active', true)
                ->orderBy('name')
                ->get();
        }, ['hotel_chains']);

        // Cache all active PMS types
        CacheService::remember('pms_types:active', 3600, function () {
            return PmsType::where('is_active', true)
                ->orderBy('name')
                ->get();
        }, ['pms_types']);

        // Cache all active modules
        CacheService::remember('modules:active', 3600, function () {
            return Module::where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get();
        }, ['modules']);

        // Cache module categories
        CacheService::remember('modules:categories', 3600, function () {
            return Module::where('is_active', true)
                ->distinct()
                ->pluck('category')
                ->sort()
                ->values();
        }, ['modules']);

        $this->line('✓ Configuration caches warmed');
    }

    /**
     * Warm up frequently accessed data
     */
    private function warmFrequentlyAccessedData()
    {
        $this->info('Warming frequently accessed data...');

        // Cache recent projects (for dashboard)
        CacheService::remember('projects:recent', 600, function () {
            return Project::with(['hotelChain', 'hotelBrand', 'pmsType'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }, ['projects']);

        // Cache project statistics
        CacheService::remember('projects:stats', 600, function () {
            return [
                'total' => Project::count(),
                'active' => Project::where('status', 'active')->count(),
                'completed' => Project::where('status', 'completed')->count(),
                'in_progress' => Project::where('status', 'in_progress')->count(),
            ];
        }, ['projects']);

        // Cache hotel brands grouped by chain
        $chains = HotelChain::where('is_active', true)->get();
        foreach ($chains as $chain) {
            CacheService::remember("hotel_brands:chain:{$chain->id}", 3600, function () use ($chain) {
                return HotelBrand::where('hotel_chain_id', $chain->id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();
            }, ['hotel_brands']);
        }

        $this->line('✓ Frequently accessed data cached');
    }

    /**
     * Warm up relationship caches
     */
    private function warmRelationshipCaches()
    {
        $this->info('Warming relationship caches...');

        // Cache PMS type configurations
        $pmsTypes = PmsType::where('is_active', true)->get();
        foreach ($pmsTypes as $pmsType) {
            CacheService::remember("pms_type:{$pmsType->id}:config", 3600, function () use ($pmsType) {
                return [
                    'reservation_settings_config' => $pmsType->reservation_settings_config,
                    'policy_example_images' => $pmsType->policy_example_images,
                    'required_fields' => $pmsType->required_fields ?? [],
                ];
            }, ['pms_types']);
        }

        // Cache module relationships
        $modules = Module::where('is_active', true)->get();
        foreach ($modules as $module) {
            CacheService::remember("module:{$module->id}:fields", 3600, function () use ($module) {
                return [
                    'required_fields' => $module->required_fields ?? [],
                    'optional_fields' => $module->optional_fields ?? [],
                    'dependencies' => $module->dependencies ?? [],
                ];
            }, ['modules']);
        }

        $this->line('✓ Relationship caches warmed');
    }

    /**
     * Warm up view caches
     */
    private function warmViewCaches()
    {
        $this->info('Warming view caches...');

        // Cache rendered navigation
        CacheService::remember('view:navigation:admin', 3600, function () {
            return view('partials.admin-navigation')->render();
        }, ['views']);

        // Cache rendered footer
        CacheService::remember('view:footer', 3600, function () {
            return view('partials.footer')->render();
        }, ['views']);

        // Cache frequently used component views
        $components = ['alert', 'modal', 'form-field', 'loading'];
        foreach ($components as $component) {
            CacheService::remember("view:component:{$component}", 3600, function () use ($component) {
                return view("components.{$component}")->render();
            }, ['views']);
        }

        $this->line('✓ View caches warmed');
    }
}