<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\VisitorLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalysisController extends Controller
{
    public function index(Request $request)
    {
        $totalVisits = VisitorLog::count();
        $uniqueIps = VisitorLog::distinct('ip_address')->count('ip_address');

        $topPages = VisitorLog::selectRaw('path, COUNT(*) as visits')
            ->groupBy('path')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        $topCountries = VisitorLog::selectRaw('COALESCE(country_name, "Unknown") as country_name, COALESCE(country_code, "XX") as country_code, COUNT(*) as visits')
            ->groupBy('country_name', 'country_code')
            ->orderByDesc('visits')
            ->limit(20)
            ->get();

        $recentVisits = VisitorLog::latest('created_at')
            ->limit(50)
            ->get();

        return Inertia::render('Admin/Analysis/Index', [
            'stats' => [
                'total_visits' => $totalVisits,
                'unique_ips' => $uniqueIps,
            ],
            'topPages' => $topPages,
            'topCountries' => $topCountries,
            'recentVisits' => $recentVisits,
        ]);
    }
}


