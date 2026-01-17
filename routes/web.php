<?php

use App\Http\Controllers\Landing\HomeController;
use App\Http\Controllers\Landing\ChatController;
use App\Http\Controllers\Landing\SitemapController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

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

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/company.php';
