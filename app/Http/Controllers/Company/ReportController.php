<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Employee;
use App\Models\Company\AttendanceRecord;
use App\Models\Company\LeaveRequest;
use App\Models\Company\Department;
use App\Models\Company\Shift;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display the reports page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get filter parameters
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

        // Attendance Statistics
        $attendanceStats = [
            'total_records' => AttendanceRecord::where('company_id', $company->id)
                ->whereBetween('datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->count(),
            'check_ins' => AttendanceRecord::where('company_id', $company->id)
                ->where('type', 'in')
                ->whereBetween('datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->count(),
            'check_outs' => AttendanceRecord::where('company_id', $company->id)
                ->where('type', 'out')
                ->whereBetween('datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->count(),
        ];

        // Leave Statistics
        $leaveStats = [
            'total_requests' => LeaveRequest::whereHas('employee', function ($q) use ($company) {
                    $q->where('company_id', $company->id);
                })
                ->whereBetween('start_date', [$startDate, $endDate])
                ->count(),
            'pending' => LeaveRequest::whereHas('employee', function ($q) use ($company) {
                    $q->where('company_id', $company->id);
                })
                ->where('status', 'pending')
                ->whereBetween('start_date', [$startDate, $endDate])
                ->count(),
            'approved' => LeaveRequest::whereHas('employee', function ($q) use ($company) {
                    $q->where('company_id', $company->id);
                })
                ->where('status', 'approved')
                ->whereBetween('start_date', [$startDate, $endDate])
                ->count(),
            'rejected' => LeaveRequest::whereHas('employee', function ($q) use ($company) {
                    $q->where('company_id', $company->id);
                })
                ->where('status', 'rejected')
                ->whereBetween('start_date', [$startDate, $endDate])
                ->count(),
        ];

        // Employee Statistics
        $employeeStats = [
            'total' => Employee::where('company_id', $company->id)->count(),
            'active' => Employee::where('company_id', $company->id)
                ->where('status', 'active')
                ->count(),
            'inactive' => Employee::where('company_id', $company->id)
                ->where('status', 'inactive')
                ->count(),
            'terminated' => Employee::where('company_id', $company->id)
                ->where('status', 'terminated')
                ->count(),
        ];

        // Department Statistics
        $departmentStats = Department::where('company_id', $company->id)
            ->withCount('employees')
            ->get()
            ->map(function ($dept) {
                return [
                    'name' => $dept->name,
                    'employees_count' => $dept->employees_count,
                ];
            })
            ->toArray();

        // Shift Statistics
        $shiftStats = Shift::where('company_id', $company->id)
            ->withCount('employees')
            ->get()
            ->map(function ($shift) {
                $startTime = Carbon::parse($shift->start_time);
                $endTime = Carbon::parse($shift->end_time);
                $totalMinutes = $startTime->diffInMinutes($endTime);
                $totalHours = ($totalMinutes - $shift->break_minutes) / 60;

                return [
                    'name' => $shift->name,
                    'employees_count' => $shift->employees_count,
                    'total_hours' => round($totalHours, 2),
                ];
            })
            ->toArray();

        // Attendance by Date (Last 30 days)
        $attendanceByDate = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $checkIns = AttendanceRecord::where('company_id', $company->id)
                ->where('type', 'in')
                ->whereDate('datetime', $date)
                ->count();
            $checkOuts = AttendanceRecord::where('company_id', $company->id)
                ->where('type', 'out')
                ->whereDate('datetime', $date)
                ->count();

            $attendanceByDate[] = [
                'date' => $date,
                'formatted_date' => Carbon::parse($date)->format('M d'),
                'check_ins' => $checkIns,
                'check_outs' => $checkOuts,
            ];
        }

        // Leave Requests by Status
        $leaveByStatus = [
            'pending' => LeaveRequest::whereHas('employee', function ($q) use ($company) {
                    $q->where('company_id', $company->id);
                })
                ->where('status', 'pending')
                ->count(),
            'approved' => LeaveRequest::whereHas('employee', function ($q) use ($company) {
                    $q->where('company_id', $company->id);
                })
                ->where('status', 'approved')
                ->count(),
            'rejected' => LeaveRequest::whereHas('employee', function ($q) use ($company) {
                    $q->where('company_id', $company->id);
                })
                ->where('status', 'rejected')
                ->count(),
        ];

        return Inertia::render('Company/Reports/Index', [
            'attendanceStats' => $attendanceStats,
            'leaveStats' => $leaveStats,
            'employeeStats' => $employeeStats,
            'departmentStats' => $departmentStats,
            'shiftStats' => $shiftStats,
            'attendanceByDate' => $attendanceByDate,
            'leaveByStatus' => $leaveByStatus,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
