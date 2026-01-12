<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class SitemapController extends Controller
{
    /**
     * Generate and return sitemap.xml
     */
    public function index()
    {
        $baseUrl = config('app.url');
        
        // Ensure base URL uses HTTPS
        if (str_starts_with($baseUrl, 'http://')) {
            $baseUrl = str_replace('http://', 'https://', $baseUrl);
        }
        
        // Remove trailing slash
        $baseUrl = rtrim($baseUrl, '/');
        
        // Public pages that should be indexed
        $urls = [
            [
                'loc' => $baseUrl,
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => $baseUrl . '/company/login',
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'loc' => $baseUrl . '/company/register',
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.9',
            ],
        ];
        
        return response()
            ->view('sitemap', ['urls' => $urls], 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Generate and return robots.txt
     */
    public function robots()
    {
        $baseUrl = config('app.url');
        
        // Ensure base URL uses HTTPS
        if (str_starts_with($baseUrl, 'http://')) {
            $baseUrl = str_replace('http://', 'https://', $baseUrl);
        }
        
        // Remove trailing slash
        $baseUrl = rtrim($baseUrl, '/');
        
        $content = "User-agent: *\n";
        $content .= "Disallow:\n\n";
        $content .= "Sitemap: {$baseUrl}/sitemap.xml\n";
        
        return response($content, 200)
            ->header('Content-Type', 'text/plain');
    }
}
