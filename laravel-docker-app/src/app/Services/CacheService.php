<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache TTL configurations
     */
    const TTL_SHORT = 300;        // 5 minutes
    const TTL_MEDIUM = 1800;      // 30 minutes
    const TTL_LONG = 3600;        // 1 hour
    const TTL_DAY = 86400;        // 24 hours
    const TTL_WEEK = 604800;      // 7 days
    
    /**
     * Cache tags
     */
    const TAG_PROJECTS = 'projects';
    const TAG_USERS = 'users';
    const TAG_MODULES = 'modules';
    const TAG_CONFIG = 'config';
    const TAG_API = 'api';
    
    /**
     * Remember data with cache
     */
    public static function remember(string $key, $ttl, callable $callback, array $tags = [])
    {
        try {
            if (config('cache.default') === 'redis' && !empty($tags)) {
                return Cache::tags($tags)->remember($key, $ttl, $callback);
            }
            
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache remember failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to direct execution
            return $callback();
        }
    }
    
    /**
     * Store data in cache
     */
    public static function put(string $key, $value, $ttl = null, array $tags = [])
    {
        try {
            $ttl = $ttl ?? self::TTL_MEDIUM;
            
            if (config('cache.default') === 'redis' && !empty($tags)) {
                return Cache::tags($tags)->put($key, $value, $ttl);
            }
            
            return Cache::put($key, $value, $ttl);
        } catch (\Exception $e) {
            Log::warning('Cache put failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Get data from cache
     */
    public static function get(string $key, $default = null, array $tags = [])
    {
        try {
            if (config('cache.default') === 'redis' && !empty($tags)) {
                return Cache::tags($tags)->get($key, $default);
            }
            
            return Cache::get($key, $default);
        } catch (\Exception $e) {
            Log::warning('Cache get failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return $default;
        }
    }
    
    /**
     * Flush cache by tags
     */
    public static function flushTags(array $tags)
    {
        try {
            if (config('cache.default') === 'redis') {
                return Cache::tags($tags)->flush();
            }
            
            // For non-taggable cache stores, we need to track keys manually
            return true;
        } catch (\Exception $e) {
            Log::warning('Cache flush tags failed', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Generate cache key
     */
    public static function key(string $prefix, ...$parts): string
    {
        $parts = array_map(function ($part) {
            return is_array($part) ? md5(json_encode($part)) : $part;
        }, $parts);
        
        return $prefix . ':' . implode(':', $parts);
    }
    
    /**
     * Cache project data
     */
    public static function rememberProject($projectId, callable $callback, $ttl = null)
    {
        $key = self::key('project', $projectId);
        $ttl = $ttl ?? self::TTL_MEDIUM;
        
        return self::remember($key, $ttl, $callback, [self::TAG_PROJECTS]);
    }
    
    /**
     * Cache user data
     */
    public static function rememberUser($userId, callable $callback, $ttl = null)
    {
        $key = self::key('user', $userId);
        $ttl = $ttl ?? self::TTL_MEDIUM;
        
        return self::remember($key, $ttl, $callback, [self::TAG_USERS]);
    }
    
    /**
     * Cache configuration data
     */
    public static function rememberConfig(string $configKey, callable $callback, $ttl = null)
    {
        $key = self::key('config', $configKey);
        $ttl = $ttl ?? self::TTL_DAY;
        
        return self::remember($key, $ttl, $callback, [self::TAG_CONFIG]);
    }
    
    /**
     * Cache API response
     */
    public static function rememberApiResponse(string $endpoint, array $params, callable $callback, $ttl = null)
    {
        $key = self::key('api', $endpoint, $params);
        $ttl = $ttl ?? self::TTL_SHORT;
        
        return self::remember($key, $ttl, $callback, [self::TAG_API]);
    }
    
    /**
     * Warm up cache
     */
    public static function warmUp()
    {
        try {
            // Warm up frequently accessed data
            Log::info('Cache warm-up started');
            
            // Cache all active modules
            $modules = \App\Models\Module::where('is_active', true)->get();
            self::put('modules:active', $modules, self::TTL_DAY, [self::TAG_MODULES]);
            
            // Cache PMS types
            $pmsTypes = \App\Models\PmsType::where('is_active', true)->get();
            self::put('pms_types:active', $pmsTypes, self::TTL_DAY, [self::TAG_CONFIG]);
            
            Log::info('Cache warm-up completed');
        } catch (\Exception $e) {
            Log::error('Cache warm-up failed', ['error' => $e->getMessage()]);
        }
    }
}