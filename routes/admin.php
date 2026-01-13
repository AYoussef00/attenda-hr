<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\ChatFeedbackController;
use App\Http\Controllers\Admin\ChatAnalysisController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\AnalysisController;
use App\Http\Controllers\Admin\RequestController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Routes for the admin panel dashboard
|
*/

Route::prefix('system')->group(function () {
    // Login routes (outside auth middleware)
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [LoginController::class, 'login'])->name('admin.login.store');
    
    // Protected routes
    Route::middleware(['auth'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        
        // Companies
        Route::resource('companies', \App\Http\Controllers\Admin\CompanyController::class)->names([
            'index' => 'admin.companies.index',
            'create' => 'admin.companies.create',
            'store' => 'admin.companies.store',
            'show' => 'admin.companies.show',
            'edit' => 'admin.companies.edit',
            'update' => 'admin.companies.update',
            'destroy' => 'admin.companies.destroy',
        ]);
        
        // Plans
        Route::resource('plans', PlanController::class)->names([
            'index' => 'admin.plans.index',
            'create' => 'admin.plans.create',
            'store' => 'admin.plans.store',
            'show' => 'admin.plans.show',
            'edit' => 'admin.plans.edit',
            'update' => 'admin.plans.update',
            'destroy' => 'admin.plans.destroy',
        ]);
        
        // Subscriptions
        Route::resource('subscriptions', SubscriptionController::class)->names([
            'index' => 'admin.subscriptions.index',
            'create' => 'admin.subscriptions.create',
            'store' => 'admin.subscriptions.store',
            'show' => 'admin.subscriptions.show',
            'edit' => 'admin.subscriptions.edit',
            'update' => 'admin.subscriptions.update',
            'destroy' => 'admin.subscriptions.destroy',
        ]);
        // Approve / Reject pending subscriptions
        Route::post('/subscriptions/{subscription}/approve', [SubscriptionController::class, 'approve'])
            ->name('admin.subscriptions.approve');
        Route::post('/subscriptions/{subscription}/reject', [SubscriptionController::class, 'reject'])
            ->name('admin.subscriptions.reject');
        Route::post('/subscriptions/{subscription}/toggle-bypass', [SubscriptionController::class, 'toggleBypass'])
            ->name('admin.subscriptions.toggle-bypass');

        // Analysis
        Route::get('/analysis', [AnalysisController::class, 'index'])->name('admin.analysis.index');
        
        // Chat (visitor conversations)
        Route::get('/chat', [AdminChatController::class, 'index'])->name('admin.chat.index');
        Route::get('/chat/threads', [AdminChatController::class, 'threads'])->name('admin.chat.threads.index');
        Route::get('/chat/{thread}/messages', [AdminChatController::class, 'messages'])->name('admin.chat.messages.index');
        Route::post('/chat/{thread}/messages', [AdminChatController::class, 'store'])->name('admin.chat.messages.store');
        Route::post('/chat/{thread}/accept', [AdminChatController::class, 'accept'])->name('admin.chat.accept');
        Route::post('/chat/{thread}/end', [AdminChatController::class, 'end'])->name('admin.chat.end');
        Route::get('/chat/{thread}/summarize', [AdminChatController::class, 'summarize'])->name('admin.chat.summarize');
        Route::get('/chat-unread-count', [AdminChatController::class, 'unreadCount'])->name('admin.chat.unread-count');

        // Chat feedback listing
        Route::get('/chat-feedback', [ChatFeedbackController::class, 'index'])->name('admin.chat.feedback.index');
        // Chat analysis dashboard
        Route::get('/chat-analysis', [ChatAnalysisController::class, 'index'])->name('admin.chat.analysis.index');
        
        // Admin users (super admins / system users)
        Route::get('/admin-users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::post('/admin-users', [AdminUserController::class, 'store'])->name('admin.users.store');
        
        // Requests
        Route::get('/requests', [RequestController::class, 'index'])->name('admin.requests.index');
        Route::put('/requests/{id}', [RequestController::class, 'update'])->name('admin.requests.update');
        Route::delete('/requests/{id}', [RequestController::class, 'destroy'])->name('admin.requests.destroy');
        
        // Settings
        Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('admin.settings.index');
        Route::post('/settings/update-texts', [\App\Http\Controllers\Admin\SettingsController::class, 'updateTexts'])->name('admin.settings.update-texts');
    });
});

