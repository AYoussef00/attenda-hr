<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Company\Employee;
use App\Models\Company\AttendanceRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display the employee attendance records.
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

        // Get filter parameters
        $date = $request->get('date');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $type = $request->get('type'); // 'in' or 'out'

        $query = AttendanceRecord::where('employee_id', $employee->id);

        // Filter by date
        if ($date) {
            $query->whereDate('datetime', $date);
        } elseif ($startDate && $endDate) {
            $query->whereBetween('datetime', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        } else {
            // Default: last 30 days
            $query->where('datetime', '>=', now()->subDays(30)->startOfDay());
        }

        // Filter by type
        if ($type) {
            $query->where('type', $type);
        }

        $attendanceRecords = $query
            ->latest('datetime')
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'type' => $record->type,
                    'datetime' => $record->datetime->format('Y-m-d H:i:s'),
                    'date' => $record->datetime->format('Y-m-d'),
                    'time' => $record->datetime->format('H:i:s'),
                    'method' => $record->method,
                    'ip_address' => $record->ip_address,
                ];
            })
            ->values()
            ->toArray();

        // Group by date for better display
        $groupedRecords = [];
        foreach ($attendanceRecords as $record) {
            $dateKey = $record['date'];
            if (!isset($groupedRecords[$dateKey])) {
                $groupedRecords[$dateKey] = [
                    'date' => $dateKey,
                    'check_in' => null,
                    'check_out' => null,
                    'duration' => null, // Duration in minutes/hours
                    'duration_formatted' => null, // Formatted duration (e.g., "8h 30m")
                    'records' => [],
                ];
            }
            
            $groupedRecords[$dateKey]['records'][] = $record;
            
            if ($record['type'] === 'in' && !$groupedRecords[$dateKey]['check_in']) {
                $groupedRecords[$dateKey]['check_in'] = $record;
            } elseif ($record['type'] === 'out' && !$groupedRecords[$dateKey]['check_out']) {
                $groupedRecords[$dateKey]['check_out'] = $record;
            }
        }

        // Calculate duration for each date
        foreach ($groupedRecords as $dateKey => &$record) {
            if ($record['check_in'] && $record['check_out']) {
                $checkInTime = Carbon::parse($record['check_in']['datetime']);
                $checkOutTime = Carbon::parse($record['check_out']['datetime']);
                $duration = $checkInTime->diffInMinutes($checkOutTime);
                $record['duration'] = $duration;
                
                // Format duration (e.g., "8h 30m" or "30m")
                $hours = floor($duration / 60);
                $minutes = $duration % 60;
                
                if ($hours > 0) {
                    $record['duration_formatted'] = $hours . 'h ' . $minutes . 'm';
                } else {
                    $record['duration_formatted'] = $minutes . 'm';
                }
            }
        }

        // Sort by date descending
        krsort($groupedRecords);

        // Get today's attendance status
        $today = Carbon::today();
        $todayCheckIn = AttendanceRecord::where('employee_id', $employee->id)
            ->where('type', 'in')
            ->whereDate('datetime', $today)
            ->latest('datetime')
            ->first();

        $todayCheckOut = AttendanceRecord::where('employee_id', $employee->id)
            ->where('type', 'out')
            ->whereDate('datetime', $today)
            ->latest('datetime')
            ->first();

        $canCheckIn = !$todayCheckIn || $todayCheckOut; // Can check in if no check in today or already checked out
        $canCheckOut = $todayCheckIn && !$todayCheckOut; // Can check out if checked in but not checked out

        return Inertia::render('Employee/Attendance/Index', [
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'name' => $employee->user->name ?? 'N/A',
                'position' => $employee->position,
                'department' => $employee->department->name ?? 'N/A',
                'shift' => $employee->shift->name ?? 'N/A',
            ],
            'attendance_records' => array_values($groupedRecords),
            'today_status' => [
                'check_in' => $todayCheckIn ? [
                    'id' => $todayCheckIn->id,
                    'time' => $todayCheckIn->datetime->format('H:i:s'),
                    'datetime' => $todayCheckIn->datetime->format('Y-m-d H:i:s'),
                ] : null,
                'check_out' => $todayCheckOut ? [
                    'id' => $todayCheckOut->id,
                    'time' => $todayCheckOut->datetime->format('H:i:s'),
                    'datetime' => $todayCheckOut->datetime->format('Y-m-d H:i:s'),
                ] : null,
                'can_check_in' => $canCheckIn,
                'can_check_out' => $canCheckOut,
            ],
            'filters' => [
                'date' => $date,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => $type,
            ],
        ]);
    }

    /**
     * Store a new attendance record (Check In or Check Out).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get employee record
        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->first();

        if (!$employee) {
            abort(403, 'Employee record not found.');
        }

        $validated = $request->validate([
            'type' => ['required', 'in:in,out'],
            'datetime' => ['nullable', 'string'], // Client-side datetime in local timezone (YYYY-MM-DD HH:mm:ss)
        ]);

        // Use client-side datetime if provided, otherwise use server time
        $clientDateTime = $validated['datetime'] ?? null;
        if ($clientDateTime) {
            // Parse the local datetime string (format: YYYY-MM-DD HH:mm:ss)
            // This is the local time from the client device
            // We'll parse it and use it as-is without timezone conversion
            $now = Carbon::createFromFormat('Y-m-d H:i:s', $clientDateTime, config('app.timezone'));
        } else {
            $now = Carbon::now();
        }

        $today = $now->copy()->startOfDay();

        // Check if already checked in/out today (based on client date)
        $clientDate = $now->toDateString();
        if ($validated['type'] === 'in') {
            $todayCheckIn = AttendanceRecord::where('employee_id', $employee->id)
                ->where('type', 'in')
                ->whereDate('datetime', $clientDate)
                ->first();

            if ($todayCheckIn) {
                // Check if already checked out
                $todayCheckOut = AttendanceRecord::where('employee_id', $employee->id)
                    ->where('type', 'out')
                    ->whereDate('datetime', $clientDate)
                    ->latest('datetime')
                    ->first();

                if (!$todayCheckOut) {
                    return back()->withErrors([
                        'attendance' => 'You have already checked in today. Please check out first.',
                    ]);
                }
            }
        } else {
            // Check Out
            $todayCheckIn = AttendanceRecord::where('employee_id', $employee->id)
                ->where('type', 'in')
                ->whereDate('datetime', $clientDate)
                ->latest('datetime')
                ->first();

            if (!$todayCheckIn) {
                return back()->withErrors([
                    'attendance' => 'You must check in first before checking out.',
                ]);
            }

            $todayCheckOut = AttendanceRecord::where('employee_id', $employee->id)
                ->where('type', 'out')
                ->whereDate('datetime', $clientDate)
                ->first();

            if ($todayCheckOut) {
                return back()->withErrors([
                    'attendance' => 'You have already checked out today.',
                ]);
            }
        }

        // Create attendance record
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'company_id' => $company->id,
            'type' => $validated['type'],
            'datetime' => $now,
            'method' => 'manual',
            'ip_address' => $request->ip(),
        ]);

        $action = $validated['type'] === 'in' ? 'checked in' : 'checked out';

        return redirect()->route('employee.attendance.index')
            ->with('success', "Successfully {$action} at " . $now->format('H:i:s'));
    }
}
