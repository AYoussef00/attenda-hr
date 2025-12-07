<?php

use App\Http\Controllers\Company\Auth\LoginController;
use App\Http\Controllers\Company\Auth\RegisterController;
use App\Http\Controllers\Company\DashboardController;
use App\Http\Controllers\Company\EmployeeController;
use App\Http\Controllers\Company\DepartmentController;
use App\Http\Controllers\Company\ShiftController;
use App\Http\Controllers\Company\AttendanceController;
use App\Http\Controllers\Company\LeaveController;
use App\Http\Controllers\Company\ReportController;
use App\Http\Controllers\Company\PerformanceController;
use App\Http\Controllers\Company\SettingsController;
use App\Http\Controllers\Company\SubscriptionController as CompanySubscriptionController;
use App\Http\Controllers\Company\LeaveTypeController;
use App\Http\Controllers\Company\PayrollController;
use App\Http\Controllers\Company\JobController;
use App\Http\Controllers\Company\AssetController;
use App\Http\Controllers\Company\AssetAssignmentController;
use App\Http\Controllers\Company\AssetMaintenanceController;
use App\Http\Controllers\Company\AssetReportController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\AttendanceController as EmployeeAttendanceController;
use App\Http\Controllers\Employee\LeaveController as EmployeeLeaveController;
use App\Http\Controllers\Employee\ProfileController as EmployeeProfileController;
use App\Http\Controllers\Employee\PerformanceController as EmployeePerformanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Company Routes
|--------------------------------------------------------------------------
|
| Routes for the company panel dashboard
|
*/

Route::prefix('company')->group(function () {
    // Login routes (outside auth middleware)
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('company.login');
    Route::post('/login', [LoginController::class, 'login'])->name('company.login.store');
    // Registration routes (outside auth middleware)
    // Keep the GET route for the legacy registration page (if needed),
    // but POST is now handled by the JSON API route in routes/web.php.
    Route::get('/register', [RegisterController::class, 'create'])->name('company.register');
    
    // Protected routes
    Route::middleware(['auth'])->group(function () {
        // Company Dashboard (for admin, hr, manager)
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('company.dashboard');
        
        // Employee Dashboard
        Route::get('/employee/dashboard', [EmployeeDashboardController::class, 'index'])->name('employee.dashboard');
        
        // Employee Attendance
        Route::get('/employee/attendance', [EmployeeAttendanceController::class, 'index'])->name('employee.attendance.index');
        Route::post('/employee/attendance', [EmployeeAttendanceController::class, 'store'])->name('employee.attendance.store');
        
        // Employee Leaves
        Route::get('/employee/leaves', [EmployeeLeaveController::class, 'index'])->name('employee.leaves.index');
        Route::get('/employee/leaves/create', [EmployeeLeaveController::class, 'create'])->name('employee.leaves.create');
        Route::post('/employee/leaves', [EmployeeLeaveController::class, 'store'])->name('employee.leaves.store');
        
        // Employee Performance
        Route::get('/employee/performance', [EmployeePerformanceController::class, 'index'])->name('employee.performance.index');
        
        // Employee Profile
        Route::get('/employee/profile', [EmployeeProfileController::class, 'index'])->name('employee.profile.index');
        Route::put('/employee/profile', [EmployeeProfileController::class, 'update'])->name('employee.profile.update');
        
        // Employee Payslips
        Route::get('/employee/payslips', [\App\Http\Controllers\Employee\PayslipController::class, 'index'])->name('employee.payslips.index');
        Route::get('/employee/payslips/{id}', [\App\Http\Controllers\Employee\PayslipController::class, 'show'])->name('employee.payslips.show');
        
        // Employee Documents
        Route::get('/employee/documents', [\App\Http\Controllers\Employee\DocumentController::class, 'index'])->name('employee.documents.index');
        Route::post('/employee/documents', [\App\Http\Controllers\Employee\DocumentController::class, 'store'])->name('employee.documents.store');
        
        // Employees
        Route::resource('employees', EmployeeController::class)->names([
            'index' => 'company.employees.index',
            'create' => 'company.employees.create',
            'store' => 'company.employees.store',
            'show' => 'company.employees.show',
            'edit' => 'company.employees.edit',
            'update' => 'company.employees.update',
            'destroy' => 'company.employees.destroy',
        ]);
        Route::post('/employees/{employee}/remind-document', [EmployeeController::class, 'remindDocument'])->name('company.employees.remind-document');
        Route::put('/employees/{employee}/salary-settings', [EmployeeController::class, 'updateSalarySettings'])->name('company.employees.update-salary-settings');
        
        // Departments
        Route::resource('departments', DepartmentController::class)->names([
            'index' => 'company.departments.index',
            'create' => 'company.departments.create',
            'store' => 'company.departments.store',
            'show' => 'company.departments.show',
            'edit' => 'company.departments.edit',
            'update' => 'company.departments.update',
            'destroy' => 'company.departments.destroy',
        ]);
        
        // Shifts
        Route::resource('shifts', ShiftController::class)->names([
            'index' => 'company.shifts.index',
            'create' => 'company.shifts.create',
            'store' => 'company.shifts.store',
            'show' => 'company.shifts.show',
            'edit' => 'company.shifts.edit',
            'update' => 'company.shifts.update',
            'destroy' => 'company.shifts.destroy',
        ]);
        
        // Attendance
        Route::resource('attendance', AttendanceController::class)->names([
            'index' => 'company.attendance.index',
            'create' => 'company.attendance.create',
            'store' => 'company.attendance.store',
            'show' => 'company.attendance.show',
            'edit' => 'company.attendance.edit',
            'update' => 'company.attendance.update',
            'destroy' => 'company.attendance.destroy',
        ]);
        Route::delete('/attendance/delete-all', [AttendanceController::class, 'deleteAll'])->name('company.attendance.delete-all');
        
        // Leaves
        Route::resource('leaves', LeaveController::class)->names([
            'index' => 'company.leaves.index',
            'create' => 'company.leaves.create',
            'store' => 'company.leaves.store',
            'show' => 'company.leaves.show',
            'edit' => 'company.leaves.edit',
            'update' => 'company.leaves.update',
            'destroy' => 'company.leaves.destroy',
        ]);
        
        // Leave actions
        Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('company.leaves.approve');
        Route::post('/leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('company.leaves.reject');
        
        // Reports (currently disabled page)
        Route::get('/reports', [ReportController::class, 'index'])->name('company.reports.index');

        // Performance analytics
        Route::get('/performance', [PerformanceController::class, 'index'])->name('company.performance.index');
        Route::get('/performance/{month}', [PerformanceController::class, 'showMonth'])->name('company.performance.show');
        
        // Payroll
        Route::get('/payroll', [PayrollController::class, 'index'])->name('company.payroll.index');
        Route::get('/payroll/settings', [PayrollController::class, 'settings'])->name('company.payroll.settings');
        Route::post('/payroll/settings', [PayrollController::class, 'updateSettings'])->name('company.payroll.settings.update');
        Route::post('/payroll/generate/{month}', [PayrollController::class, 'generateCycle'])->name('company.payroll.generate');
        Route::post('/payroll/cycle/{id}/regenerate', [PayrollController::class, 'regenerateCycle'])->name('company.payroll.cycle.regenerate');
        Route::get('/payroll/cycle/{id}', [PayrollController::class, 'viewCycle'])->name('company.payroll.cycle');
        Route::get('/payroll/cycle/{id}/deductions', [PayrollController::class, 'viewDeductions'])->name('company.payroll.cycle.deductions');
        Route::post('/payroll/entry/approve/{id}', [PayrollController::class, 'approveEntry'])->name('company.payroll.entry.approve');
        Route::post('/payroll/entry/pay/{id}', [PayrollController::class, 'markPaid'])->name('company.payroll.entry.pay');
        Route::post('/payroll/cycle/{id}/pay-all', [PayrollController::class, 'markCyclePaid'])->name('company.payroll.cycle.pay-all');
        Route::get('/payroll/entry/payslip/{id}', [PayrollController::class, 'exportPayslip'])->name('company.payroll.entry.payslip');
        
        // Subscription
        Route::get('/subscription', [CompanySubscriptionController::class, 'index'])->name('company.subscription.index');
        
        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('company.settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('company.settings.update');
        
        // Leave Types
        Route::post('/leave-types', [LeaveTypeController::class, 'store'])->name('company.leave-types.store');
        Route::put('/leave-types/{id}', [LeaveTypeController::class, 'update'])->name('company.leave-types.update');
        Route::delete('/leave-types/{id}', [LeaveTypeController::class, 'destroy'])->name('company.leave-types.destroy');
        
        // Jobs
        Route::resource('jobs', JobController::class)->names([
            'index' => 'company.jobs.index',
            'create' => 'company.jobs.create',
            'store' => 'company.jobs.store',
            'show' => 'company.jobs.show',
            'edit' => 'company.jobs.edit',
            'update' => 'company.jobs.update',
            'destroy' => 'company.jobs.destroy',
        ]);
        Route::post('/jobs/{job}/upload-candidates', [JobController::class, 'uploadCandidates'])->name('company.jobs.upload-candidates');
        Route::delete('/jobs/{job}/candidates/{candidate}', [JobController::class, 'destroyCandidate'])->name('company.jobs.candidates.destroy');
        Route::put('/jobs/{job}/candidates/{candidate}/status', [JobController::class, 'updateCandidateStatus'])->name('company.jobs.candidates.update-status');
        Route::put('/jobs/{job}/settings', [JobController::class, 'updateSettings'])->name('company.jobs.update-settings');
        
        // Assets
        Route::resource('assets', AssetController::class)->names([
            'index' => 'company.assets.index',
            'create' => 'company.assets.create',
            'store' => 'company.assets.store',
            'show' => 'company.assets.show',
            'edit' => 'company.assets.edit',
            'update' => 'company.assets.update',
            'destroy' => 'company.assets.destroy',
        ]);
        Route::put('/assets/{id}/status', [AssetController::class, 'updateStatus'])->name('company.assets.update-status');
        Route::get('/assets/{id}/history', [AssetController::class, 'history'])->name('company.assets.history');
        
        // Asset Assignments
        Route::get('/assets/assignments', [AssetAssignmentController::class, 'index'])->name('company.assets.assignments.index');
        Route::post('/assets/assignments/assign', [AssetAssignmentController::class, 'assign'])->name('company.assets.assignments.assign');
        Route::post('/assets/assignments/{id}/return', [AssetAssignmentController::class, 'return'])->name('company.assets.assignments.return');
        Route::get('/assets/assignments/current', [AssetAssignmentController::class, 'currentAssignments'])->name('company.assets.assignments.current');
        
        // Asset Maintenance
        Route::resource('assets/maintenance', AssetMaintenanceController::class)->names([
            'index' => 'company.assets.maintenance.index',
            'create' => 'company.assets.maintenance.create',
            'store' => 'company.assets.maintenance.store',
            'show' => 'company.assets.maintenance.show',
            'edit' => 'company.assets.maintenance.edit',
            'update' => 'company.assets.maintenance.update',
            'destroy' => 'company.assets.maintenance.destroy',
        ]);
        Route::post('/assets/maintenance/{id}/complete', [AssetMaintenanceController::class, 'complete'])->name('company.assets.maintenance.complete');
        
        // Asset Reports
        Route::get('/assets/reports', [AssetReportController::class, 'index'])->name('company.assets.reports.index');
        Route::get('/assets/reports/export/{type}', [AssetReportController::class, 'exportPdf'])->name('company.assets.reports.export');
    });
});

