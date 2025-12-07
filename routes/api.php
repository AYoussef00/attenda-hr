<?php

use App\Http\Controllers\Api\Auth\EmployeeAuthController;
use App\Http\Controllers\Api\Employee\AttendanceController;
use App\Http\Controllers\Api\Employee\DocumentController;
use App\Http\Controllers\Api\Employee\PayslipController;
use App\Http\Controllers\Api\Employee\PerformanceController as EmployeePerformanceController;
use App\Http\Controllers\Api\Employee\SettingsController as EmployeeSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/auth/login', [EmployeeAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [EmployeeAuthController::class, 'logout']);
        Route::get('/auth/me', [EmployeeAuthController::class, 'me']);

        // Employee attendance endpoints
        Route::post('/employee/attendance/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/employee/attendance/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/employee/attendance/today', [AttendanceController::class, 'today']);
        Route::get('/employee/attendance/recent', [AttendanceController::class, 'recent']);

        // Employee documents
        Route::get('/employee/documents', [DocumentController::class, 'uploaded']);
        Route::get('/employee/documents/required', [DocumentController::class, 'required']);
        Route::post('/employee/documents', [DocumentController::class, 'upload']);

        // Employee payslips
        Route::get('/employee/payslips', [PayslipController::class, 'index']);
        Route::get('/employee/payslips/{id}', [PayslipController::class, 'show']);

        // Employee performance history
        Route::get('/employee/performance/history', [EmployeePerformanceController::class, 'history']);

        // Employee / company settings
        Route::get('/employee/settings/allowed-ips', [EmployeeSettingsController::class, 'allowedIps']);
    });
});


