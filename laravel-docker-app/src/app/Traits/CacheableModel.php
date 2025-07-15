<?php

namespace App\Traits;

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

trait CacheableModel
{
    /**
     * Boot the cacheable trait
     */
    public static function bootCacheableModel()
    {
        // Clear cache on model events
        static::saved(function ($model) {
            $model->clearModelCache();
        });

        static::deleted(function ($model) {
            $model->clearModelCache();
        });
    }

    /**
     * Find a model by ID with caching
     */
    public static function findCached($id, $ttl = 3600)
    {
        $modelName = strtolower(class_basename(static::class));
        
        return CacheService::remember(
            "{$modelName}:{$id}",
            $ttl,
            function () use ($id) {
                return static::find($id);
            },
            [$modelName]
        );
    }

    /**
     * Find a model with relationships cached
     */
    public static function findWithCached($id, array $relations, $ttl = 3600)
    {
        $modelName = strtolower(class_basename(static::class));
        $relationKey = implode(':', $relations);
        
        return CacheService::remember(
            "{$modelName}:{$id}:with:{$relationKey}",
            $ttl,
            function () use ($id, $relations) {
                return static::with($relations)->find($id);
            },
            [$modelName]
        );
    }

    /**
     * Cache a query result
     */
    public function scopeCached($query, $key, $ttl = 3600)
    {
        $modelName = strtolower(class_basename(static::class));
        
        return CacheService::remember(
            "{$modelName}:query:{$key}",
            $ttl,
            function () use ($query) {
                return $query->get();
            },
            [$modelName]
        );
    }

    /**
     * Cache a paginated query result
     */
    public function scopeCachedPaginate($query, $perPage = 15, $ttl = 600)
    {
        $modelName = strtolower(class_basename(static::class));
        $page = request()->get('page', 1);
        $key = md5($query->toSql() . serialize($query->getBindings()));
        
        return CacheService::remember(
            "{$modelName}:paginate:{$key}:page:{$page}:perpage:{$perPage}",
            $ttl,
            function () use ($query, $perPage) {
                return $query->paginate($perPage);
            },
            [$modelName, 'pagination']
        );
    }

    /**
     * Get cached count
     */
    public static function cachedCount($ttl = 3600)
    {
        $modelName = strtolower(class_basename(static::class));
        
        return CacheService::remember(
            "{$modelName}:count",
            $ttl,
            function () {
                return static::count();
            },
            [$modelName]
        );
    }

    /**
     * Clear all caches for this model
     */
    public function clearModelCache()
    {
        $modelName = strtolower(class_basename(static::class));
        
        // Clear by tag
        Cache::tags([$modelName])->flush();
        
        // Also clear specific instance cache
        Cache::forget("{$modelName}:{$this->id}");
        
        // Clear any relationship caches
        $this->clearRelationshipCache();
    }

    /**
     * Clear relationship caches
     */
    protected function clearRelationshipCache()
    {
        // Override in model to clear specific relationship caches
        // Example:
        // Cache::forget("user:{$this->id}:projects");
    }

    /**
     * Remember a custom query forever with tags
     */
    public static function rememberForever($key, callable $callback)
    {
        $modelName = strtolower(class_basename(static::class));
        
        return CacheService::rememberForever(
            $key,
            $callback,
            [$modelName]
        );
    }

    /**
     * Cache model attributes
     */
    public function cacheAttributes(array $attributes, $ttl = 3600)
    {
        $modelName = strtolower(class_basename(static::class));
        
        foreach ($attributes as $attribute) {
            CacheService::remember(
                "{$modelName}:{$this->id}:attribute:{$attribute}",
                $ttl,
                function () use ($attribute) {
                    return $this->getAttribute($attribute);
                },
                [$modelName, 'attributes']
            );
        }
    }

    /**
     * Get cached attribute
     */
    public function getCachedAttribute($attribute, $ttl = 3600)
    {
        $modelName = strtolower(class_basename(static::class));
        
        return CacheService::remember(
            "{$modelName}:{$this->id}:attribute:{$attribute}",
            $ttl,
            function () use ($attribute) {
                return $this->getAttribute($attribute);
            },
            [$modelName, 'attributes']
        );
    }
}