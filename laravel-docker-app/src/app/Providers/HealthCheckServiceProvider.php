<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class HealthCheckServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Route::get('/health', function () {
            $checks = [
                'application' => true,
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'queue' => $this->checkQueue(),
                'storage' => $this->checkStorage(),
            ];

            $healthy = !in_array(false, $checks, true);
            $status = $healthy ? 200 : 503;

            return response()->json([
                'status' => $healthy ? 'healthy' : 'unhealthy',
                'timestamp' => now()->toIso8601String(),
                'checks' => $checks,
                'version' => config('app.version', '1.0.0'),
                'environment' => app()->environment(),
            ], $status);
        });

        Route::get('/api/health', function () {
            return response()->json([
                'status' => 'ok',
                'timestamp' => now()->toIso8601String(),
            ]);
        });
    }

    /**
     * Check database connection
     */
    private function checkDatabase(): bool
    {
        try {
            \DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check cache connection
     */
    private function checkCache(): bool
    {
        try {
            cache()->put('health-check', true, 10);
            $result = cache()->get('health-check');
            cache()->forget('health-check');
            return $result === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check queue connection
     */
    private function checkQueue(): bool
    {
        try {
            if (config('queue.default') === 'sync') {
                return true;
            }
            
            // Check Redis connection for queue
            if (config('queue.default') === 'redis') {
                $redis = app('redis')->connection();
                return $redis->ping();
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check storage accessibility
     */
    private function checkStorage(): bool
    {
        try {
            $testFile = storage_path('app/.health-check');
            file_put_contents($testFile, 'test');
            $result = file_exists($testFile);
            unlink($testFile);
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }
}