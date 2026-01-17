<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Employee;
use App\Models\Company\AttendanceRecord;
use App\Models\Company\LeaveRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the company dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get company statistics
        $stats = [
            'total_employees' => Employee::where('company_id', $company->id)->count(),
            'total_attendance' => AttendanceRecord::where('company_id', $company->id)->count(),
            'total_leaves' => LeaveRequest::whereHas('employee', function ($q) use ($company) {
                    $q->where('company_id', $company->id);
                })->count(),
        ];

        // Get recent employees
        $recent_employees = Employee::where('company_id', $company->id)
            ->with(['user'])
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->user->name ?? 'N/A',
                    'employee_code' => $employee->employee_code,
                    'position' => $employee->position,
                    'status' => $employee->status,
                    'hire_date' => $employee->hire_date?->format('Y-m-d'),
                ];
            });

        // Attendance Overview - Today
        $today = Carbon::today();
        $attendanceToday = [
            'check_ins' => AttendanceRecord::where('company_id', $company->id)
                ->where('type', 'in')
                ->whereDate('datetime', $today)
                ->count(),
            'check_outs' => AttendanceRecord::where('company_id', $company->id)
                ->where('type', 'out')
                ->whereDate('datetime', $today)
                ->count(),
            'total_records' => AttendanceRecord::where('company_id', $company->id)
                ->whereDate('datetime', $today)
                ->count(),
        ];

        // Recent Attendance Records (Today)
        $recent_attendance = AttendanceRecord::where('company_id', $company->id)
            ->whereDate('datetime', $today)
            ->with(['employee.user'])
            ->latest('datetime')
            ->limit(10)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'employee_name' => $record->employee->user->name ?? 'N/A',
                    'employee_code' => $record->employee->employee_code ?? 'N/A',
                    'type' => $record->type,
                    'datetime' => $record->datetime->format('H:i:s'),
                    'method' => $record->method,
                ];
            });

        return Inertia::render('Company/Dashboard', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'logo' => $company->logo ? cdn_storage($company->logo) : null,
            ],
            'stats' => $stats,
            'recent_employees' => $recent_employees,
            'attendance_overview' => [
                'today' => $attendanceToday,
            ],
            'recent_attendance' => $recent_attendance,
        ]);
    }
}
