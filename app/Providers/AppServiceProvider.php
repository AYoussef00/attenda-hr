<?php

namespace App\Providers;

use App\Models\Company\AttendanceRecord;
use App\Observers\AttendanceRecordObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Load helper functions
        require_once app_path('Helpers/CdnHelper.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Observer for automatic performance calculation
        AttendanceRecord::observe(AttendanceRecordObserver::class);
    }
}
