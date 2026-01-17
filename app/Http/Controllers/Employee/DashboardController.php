<?php

namespace App\Http\Controllers\Employee;

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
     * Display the employee dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get employee record
        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->with(['department', 'shift', 'user'])
            ->first();

        if (!$employee) {
            abort(403, 'Employee record not found.');
        }

        // Get employee statistics
        $stats = [
            'total_attendance' => AttendanceRecord::where('employee_id', $employee->id)->count(),
            'total_leaves' => LeaveRequest::where('employee_id', $employee->id)->count(),
            'pending_leaves' => LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->count(),
            'approved_leaves' => LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->count(),
        ];

        // Attendance Overview - Today
        $today = Carbon::today();
        $attendanceToday = [
            'check_ins' => AttendanceRecord::where('employee_id', $employee->id)
                ->where('type', 'in')
                ->whereDate('datetime', $today)
                ->count(),
            'check_outs' => AttendanceRecord::where('employee_id', $employee->id)
                ->where('type', 'out')
                ->whereDate('datetime', $today)
                ->count(),
            'total_records' => AttendanceRecord::where('employee_id', $employee->id)
                ->whereDate('datetime', $today)
                ->count(),
            'last_check_in' => AttendanceRecord::where('employee_id', $employee->id)
                ->where('type', 'in')
                ->whereDate('datetime', $today)
                ->latest('datetime')
                ->first(),
            'last_check_out' => AttendanceRecord::where('employee_id', $employee->id)
                ->where('type', 'out')
                ->whereDate('datetime', $today)
                ->latest('datetime')
                ->first(),
        ];

        // Recent Attendance Records (Last 10)
        $recent_attendance = AttendanceRecord::where('employee_id', $employee->id)
            ->with(['employee.user'])
            ->latest('datetime')
            ->limit(10)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'type' => $record->type,
                    'datetime' => $record->datetime->format('Y-m-d H:i:s'),
                    'date' => $record->datetime->format('Y-m-d'),
                    'time' => $record->datetime->format('H:i:s'),
                    'method' => $record->method,
                ];
            });

        // Recent Leave Requests (Last 5)
        $recent_leaves = LeaveRequest::where('employee_id', $employee->id)
            ->with(['leaveType'])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'leave_type' => $leave->leaveType->name ?? 'N/A',
                    'start_date' => $leave->start_date->format('Y-m-d'),
                    'end_date' => $leave->end_date->format('Y-m-d'),
                    'days' => $leave->days,
                    'status' => $leave->status,
                    'note' => $leave->note,
                ];
            });

        // Get notifications for employee
        $notifications = \App\Models\Company\Notification::where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'read' => $notification->read,
                    'created_at' => $notification->created_at->toDateTimeString(),
                ];
            })
            ->toArray();

        return Inertia::render('Employee/Dashboard', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'logo' => $company->logo ? cdn_storage($company->logo) : null,
            ],
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'position' => $employee->position,
                'department' => $employee->department->name ?? 'N/A',
                'shift' => $employee->shift->name ?? 'N/A',
                'hire_date' => $employee->hire_date?->format('Y-m-d'),
                'status' => $employee->status,
            ],
            'stats' => $stats,
            'attendance_overview' => [
                'today' => [
                    'check_ins' => $attendanceToday['check_ins'],
                    'check_outs' => $attendanceToday['check_outs'],
                    'total_records' => $attendanceToday['total_records'],
                    'last_check_in' => $attendanceToday['last_check_in']?->datetime->format('H:i:s'),
                    'last_check_out' => $attendanceToday['last_check_out']?->datetime->format('H:i:s'),
                ],
            ],
            'recent_attendance' => $recent_attendance,
            'recent_leaves' => $recent_leaves,
            'notifications' => $notifications,
        ]);
    }
}
