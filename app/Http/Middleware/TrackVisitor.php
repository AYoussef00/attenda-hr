<?php

namespace App\Http\Middleware;

use App\Models\Admin\VisitorLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track GET HTML page requests (avoid assets / API)
        if ($request->method() !== 'GET') {
            return $response;
        }

        // Skip JSON/API requests
        if ($request->expectsJson()) {
            return $response;
        }

        // Only track the public landing page (route name 'home')
        $route = $request->route();
        if (!$route || $route->getName() !== 'home') {
            return $response;
        }

        try {
            $ip = $request->ip() ?? '0.0.0.0';
            $path = '/' . ltrim($request->path(), '/');
            $referrer = $request->headers->get('referer');
            $userAgent = $request->userAgent();

            // Skip local addresses to avoid polluting analytics
            if (in_array($ip, ['127.0.0.1', '::1'])) {
                $countryCode = null;
                $countryName = null;
                $city = null;
            } else {
                $geo = $this->getGeoForIp($ip);
                $countryCode = $geo['countryCode'] ?? null;
                $countryName = $geo['country'] ?? null;
                $city = $geo['city'] ?? null;
            }

            VisitorLog::create([
                'ip_address' => $ip,
                'country_code' => $countryCode,
                'country_name' => $countryName,
                'city' => $city,
                'path' => $path,
                'referrer' => $referrer,
                'user_agent' => $userAgent,
            ]);
        } catch (\Throwable $e) {
            // Don't break the app if analytics fails
        }

        return $response;
    }

    /**
     * Simple GeoIP lookup using ip-api.com with caching.
     */
    protected function getGeoForIp(string $ip): array
    {
        $cacheKey = 'geoip_' . $ip;

        return Cache::remember($cacheKey, now()->addDay(), function () use ($ip) {
            try {
                $res = Http::timeout(3)->get('http://ip-api.com/json/' . $ip, [
                    'fields' => 'status,country,countryCode,city',
                ]);

                if ($res->successful()) {
                    $data = $res->json();
                    if (($data['status'] ?? '') === 'success') {
                        return $data;
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }

            return [];
        });
    }
}


