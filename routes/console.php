<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule monthly performance calculation to run on the 1st day of each month at 1:00 AM
Schedule::command('performance:calculate-monthly')
    ->monthlyOn(1, '01:00')
    ->description('Calculate monthly performance scores for all employees');

// Schedule automatic payroll generation to run on the 1st day of each month at 2:00 AM
// This will generate payroll for the previous month (the month that just ended)
Schedule::command('payroll:auto-generate-monthly')
    ->monthlyOn(1, '02:00')
    ->description('Automatically generate payroll for the previous month for all companies');
