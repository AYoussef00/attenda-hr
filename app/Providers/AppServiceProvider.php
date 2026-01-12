<?php

namespace App\Providers;

use App\Models\Company\AttendanceRecord;
use App\Observers\AttendanceRecordObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Observer for automatic performance calculation
        AttendanceRecord::observe(AttendanceRecordObserver::class);

        // Force HTTPS in production environment
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
