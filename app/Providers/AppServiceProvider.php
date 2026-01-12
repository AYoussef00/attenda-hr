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
        //
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
