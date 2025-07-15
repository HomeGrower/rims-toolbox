<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;

class CDNHelper
{
    /**
     * Get CDN URL for an asset
     */
    public static function asset($path)
    {
        // In local environment, use local assets
        if (App::environment('local')) {
            return asset($path);
        }

        // Remove leading slash if present
        $path = ltrim($path, '/');

        // Get CDN URL from config
        $cdnUrl = config('app.cdn_url', config('app.url'));

        // Add version hash for cache busting
        $version = config('app.asset_version', '1.0.0');
        $separator = parse_url($path, PHP_URL_QUERY) ? '&' : '?';
        
        return $cdnUrl . '/' . $path . $separator . 'v=' . $version;
    }

    /**
     * Get CDN URL for images
     */
    public static function image($path, $options = [])
    {
        // If using image optimization service
        if (config('app.image_cdn_url')) {
            return self::optimizedImage($path, $options);
        }

        return self::asset($path);
    }

    /**
     * Get optimized image URL (for services like Cloudinary, Imgix)
     */
    private static function optimizedImage($path, $options = [])
    {
        $cdnUrl = config('app.image_cdn_url');
        $path = ltrim($path, '/');

        // Build transformation parameters
        $transforms = [];
        
        if (isset($options['width'])) {
            $transforms[] = 'w_' . $options['width'];
        }
        
        if (isset($options['height'])) {
            $transforms[] = 'h_' . $options['height'];
        }
        
        if (isset($options['quality'])) {
            $transforms[] = 'q_' . $options['quality'];
        } else {
            $transforms[] = 'q_auto';
        }
        
        if (isset($options['format'])) {
            $transforms[] = 'f_' . $options['format'];
        } else {
            $transforms[] = 'f_auto';
        }

        $transformation = implode(',', $transforms);

        // Example for Cloudinary-style URL
        return $cdnUrl . '/' . $transformation . '/' . $path;
    }

    /**
     * Preload critical assets
     */
    public static function preloadTags()
    {
        $tags = [];

        // Preload critical CSS
        $criticalCss = [
            'css/app.css',
        ];

        foreach ($criticalCss as $css) {
            $tags[] = sprintf(
                '<link rel="preload" href="%s" as="style">',
                self::asset($css)
            );
        }

        // Preload critical fonts
        $fonts = [
            'fonts/inter-var.woff2',
        ];

        foreach ($fonts as $font) {
            $tags[] = sprintf(
                '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>',
                self::asset($font)
            );
        }

        // Preload critical JavaScript
        $criticalJs = [
            'js/app.js',
        ];

        foreach ($criticalJs as $js) {
            $tags[] = sprintf(
                '<link rel="preload" href="%s" as="script">',
                self::asset($js)
            );
        }

        return implode("\n", $tags);
    }

    /**
     * Generate resource hints
     */
    public static function resourceHints()
    {
        $hints = [];

        // DNS prefetch for external resources
        $domains = [
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
        ];

        foreach ($domains as $domain) {
            $hints[] = sprintf('<link rel="dns-prefetch" href="%s">', $domain);
        }

        // Preconnect to CDN
        if (config('app.cdn_url') !== config('app.url')) {
            $cdnUrl = parse_url(config('app.cdn_url'), PHP_URL_SCHEME) . '://' . 
                      parse_url(config('app.cdn_url'), PHP_URL_HOST);
            $hints[] = sprintf('<link rel="preconnect" href="%s">', $cdnUrl);
            $hints[] = sprintf('<link rel="preconnect" href="%s" crossorigin>', $cdnUrl);
        }

        return implode("\n", $hints);
    }
}