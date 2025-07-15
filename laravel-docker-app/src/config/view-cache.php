<?php

return [
    /*
    |--------------------------------------------------------------------------
    | View Cache Configuration
    |--------------------------------------------------------------------------
    |
    | This file defines which views should be cached and for how long.
    | View caching can significantly improve performance for rarely changing views.
    |
    */

    'enabled' => env('VIEW_CACHE_ENABLED', true),

    'default_ttl' => env('VIEW_CACHE_TTL', 3600), // 1 hour

    'views' => [
        // Static views that rarely change
        'layouts.app' => 86400, // 24 hours
        'layouts.guest' => 86400,
        'layouts.admin' => 86400,
        
        // Partials
        'partials.header' => 3600,
        'partials.footer' => 3600,
        'partials.navigation' => 1800, // 30 minutes
        
        // Components
        'components.alert' => 86400,
        'components.modal' => 86400,
        'components.form-field' => 86400,
        'components.loading' => 86400,
        
        // Error pages
        'errors.404' => 86400,
        'errors.500' => 86400,
        'errors.503' => 86400,
    ],

    // Views that should never be cached
    'exclude' => [
        'auth.*',
        'admin.*',
        'setup.*',
        'dashboard',
    ],

    // Cache tags for easy invalidation
    'tags' => [
        'views',
        'partials',
        'components',
    ],
];