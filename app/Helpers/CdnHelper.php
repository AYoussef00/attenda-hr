<?php

if (!function_exists('cdn_asset')) {
    /**
     * Generate an asset URL using CDN if configured, otherwise fallback to asset()
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function cdn_asset(string $path, ?bool $secure = null): string
    {
        $cdnUrl = config('app.cdn_url');
        
        if (!$cdnUrl) {
            return asset($path, $secure);
        }

        // Remove leading slash if present
        $path = ltrim($path, '/');

        // Remove trailing slash from CDN URL
        $cdnUrl = rtrim($cdnUrl, '/');

        return $cdnUrl . '/' . $path;
    }
}

if (!function_exists('cdn_storage')) {
    /**
     * Generate a storage URL using CDN if configured
     *
     * @param string $path
     * @return string
     */
    function cdn_storage(string $path): string
    {
        $cdnUrl = config('app.cdn_url');
        
        if (!$cdnUrl) {
            return asset('storage/' . $path);
        }

        // Remove leading slash if present
        $path = ltrim($path, '/');
        
        // Remove leading 'storage/' if present (since CDN will serve from storage directory)
        $path = preg_replace('#^storage/#', '', $path);

        // Remove trailing slash from CDN URL
        $cdnUrl = rtrim($cdnUrl, '/');

        return $cdnUrl . '/storage/' . $path;
    }
}
