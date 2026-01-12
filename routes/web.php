<?php

use App\Http\Controllers\Landing\HomeController;
use App\Http\Controllers\Landing\ChatController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', [HomeController::class, 'index'])->name('home');

// System status page
Route::get('/status', [StatusController::class, 'index'])->name('status');

// Public route for demo requests
Route::post('/demo-request', [RequestController::class, 'store'])->name('demo.request.store');

// Public chat widget endpoints
Route::get('/chat/messages', [ChatController::class, 'index'])->name('chat.messages.index');
Route::post('/chat/messages', [ChatController::class, 'store'])->name('chat.messages.store');

// Company Registration (Public) - JSON API endpoint
// CSRF is disabled for this JSON endpoint because it is called via fetch/AJAX from the same origin.
Route::post('/company/register', [\App\Http\Controllers\Company\CompanyRegisterController::class, 'store'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->name('company.register.store');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Sitemap routes
Route::get('/sitemap.xml', function () {
    $baseUrl = url('/');
    
    $pages = [
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
    
    return response()->view('sitemap', ['pages' => $pages])
        ->header('Content-Type', 'text/xml');
})->name('sitemap');

Route::get('/robots.txt', function () {
    $baseUrl = url('/');
    return response("User-agent: *\nAllow: /\n\nSitemap: {$baseUrl}/sitemap.xml")
        ->header('Content-Type', 'text/plain');
})->name('robots');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/company.php';
