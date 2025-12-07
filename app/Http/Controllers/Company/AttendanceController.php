<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get filter parameters
        $date = $request->get('date');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $employeeId = $request->get('employee_id');
        $type = $request->get('type'); // 'in' or 'out'

        $query = AttendanceRecord::where('company_id', $company->id)
            ->with(['employee.user']);

        // Filter by date range
        if ($date) {
            // Single date filter
            $query->whereDate('datetime', $date);
        } elseif ($startDate && $endDate) {
            // Date range filter
            $query->whereBetween('datetime', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        } else {
            // Default: Show all records from the oldest record date
            // This ensures all historical data is shown regardless of when it was created
            $oldestRecord = AttendanceRecord::where('company_id', $company->id)
                ->orderBy('datetime', 'asc')
                ->first();
            
            if ($oldestRecord) {
                // Use the oldest record date to show all historical data
                $query->where('datetime', '>=', Carbon::parse($oldestRecord->datetime)->startOfDay());
            } else {
                // Fallback to last 24 months if no records exist
                $query->where('datetime', '>=', now()->subMonths(24)->startOfDay());
            }
        }

        // Filter by employee
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        // Determine date range for absence calculation
        $dateRangeStart = null;
        $dateRangeEnd = null;

        if ($date) {
            $dateRangeStart = Carbon::parse($date)->startOfDay();
            $dateRangeEnd = Carbon::parse($date)->endOfDay();
        } elseif ($startDate && $endDate) {
            $dateRangeStart = Carbon::parse($startDate)->startOfDay();
            $dateRangeEnd = Carbon::parse($endDate)->endOfDay();
        } else {
            // Use oldest and newest record dates
            $oldestRecord = AttendanceRecord::where('company_id', $company->id)
                ->orderBy('datetime', 'asc')
                ->first();
            $newestRecord = AttendanceRecord::where('company_id', $company->id)
                ->orderBy('datetime', 'desc')
                ->first();
            
            if ($oldestRecord && $newestRecord) {
                $dateRangeStart = Carbon::parse($oldestRecord->datetime)->startOfDay();
                $dateRangeEnd = Carbon::parse($newestRecord->datetime)->endOfDay();
            } else {
                $dateRangeStart = now()->subMonths(24)->startOfDay();
                $dateRangeEnd = now()->endOfDay();
            }
        }

        // We want a per-employee daily summary similar to:
        // Name | Check-in time | Duration | Check-out time | Position
        $records = $query
            ->orderBy('datetime')
            ->get();

        // Group records by employee_id and date
        $grouped = $records->groupBy(function ($record) {
            return $record->employee_id . '-' . Carbon::parse($record->datetime)->format('Y-m-d');
        });

        // Get all employees in the date range
        $employeesQuery = \App\Models\Company\Employee::where('company_id', $company->id)
            ->with('user');
        
        if ($employeeId) {
            $employeesQuery->where('id', $employeeId);
        }
        
        $allEmployees = $employeesQuery->get();

        // Build attendance records with absences
        $attendanceRecords = collect();

        foreach ($allEmployees as $employee) {
            $user = $employee->user;
            $employeeDatesWithRecords = $grouped->keys()
                ->filter(function ($key) use ($employee) {
                    return str_starts_with($key, $employee->id . '-');
                })
                ->map(function ($key) {
                    return explode('-', $key, 2)[1]; // Extract date part
                })
                ->toArray();

            // Process days with records
            foreach ($grouped as $key => $items) {
                if (!str_starts_with($key, $employee->id . '-')) {
                    continue;
                }

                $first = $items->first();
                $recordDate = Carbon::parse($first->datetime)->format('Y-m-d');

                // Determine check-in and check-out for this day
                $checkInRecord = $items->where('type', 'in')->sortBy('datetime')->first();
                $checkOutRecord = $items->where('type', 'out')->sortByDesc('datetime')->first();

                $checkIn = $checkInRecord ? Carbon::parse($checkInRecord->datetime) : null;
                $checkOut = $checkOutRecord ? Carbon::parse($checkOutRecord->datetime) : null;

                $duration = null;
                if ($checkIn && $checkOut && $checkOut->greaterThan($checkIn)) {
                    $minutes = $checkIn->diffInMinutes($checkOut);
                    $hours = intdiv($minutes, 60);
                    $mins = $minutes % 60;
                    $duration = sprintf('%dh %02dm', $hours, $mins);
                }

                // Optional filter by type (if user chooses "Check In" or "Check Out")
                if ($type === 'in' && !$checkInRecord) {
                    continue;
                }
                if ($type === 'out' && !$checkOutRecord) {
                    continue;
                }

                $attendanceRecords->push([
                    'employee_id' => $employee->id,
                    'employee_name' => $user->name ?? 'N/A',
                    'employee_code' => $employee->employee_code ?? 'N/A',
                    'position' => $employee->position ?? null,
                    'date' => $recordDate,
                    'check_in_time' => $checkIn ? $checkIn->format('h:i A') : null,
                    'check_out_time' => $checkOut ? $checkOut->format('h:i A') : null,
                    'duration' => $duration,
                    'status' => ($checkIn && $checkOut) ? 'present' : ($checkIn ? 'partial' : 'absent'),
                ]);
            }

            // Add absence days (weekdays without any records)
            if ($dateRangeStart && $dateRangeEnd) {
                $currentDate = $dateRangeStart->copy();
                while ($currentDate->lte($dateRangeEnd)) {
                    // Skip weekends
                    if ($currentDate->dayOfWeek !== 0 && $currentDate->dayOfWeek !== 6) {
                        $dateString = $currentDate->format('Y-m-d');
                        
                        // If no record exists for this date, add as absence
                        if (!in_array($dateString, $employeeDatesWithRecords)) {
                            // Skip if type filter is set to 'in' or 'out' (only show actual records)
                            if (!$type || $type === '') {
                                $attendanceRecords->push([
                                    'employee_id' => $employee->id,
                                    'employee_name' => $user->name ?? 'N/A',
                                    'employee_code' => $employee->employee_code ?? 'N/A',
                                    'position' => $employee->position ?? null,
                                    'date' => $dateString,
                                    'check_in_time' => null,
                                    'check_out_time' => null,
                                    'duration' => null,
                                    'status' => 'absent',
                                ]);
                            }
                        }
                    }
                    $currentDate->addDay();
                }
            }
        }

        $attendanceRecords = $attendanceRecords
            ->sortByDesc('date')
            ->values()
            ->toArray();

        // Get employees for filter dropdown
        $employees = \App\Models\Company\Employee::where('company_id', $company->id)
            ->with('user')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->user->name ?? 'N/A',
                    'employee_code' => $employee->employee_code,
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Company/Attendance/Index', [
            'attendanceRecords' => $attendanceRecords,
            'employees' => $employees,
            'filters' => [
                'date' => $date,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'employee_id' => $employeeId,
                'type' => $type,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Delete all attendance records for the company
     */
    public function deleteAll(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $deletedCount = AttendanceRecord::where('company_id', $company->id)->delete();

        return back()->with('success', "All attendance records ({$deletedCount} records) have been deleted successfully.");
    }
}
