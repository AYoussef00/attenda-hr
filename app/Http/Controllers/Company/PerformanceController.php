<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\AttendanceRecord;
use App\Models\Company\Employee;
use App\Models\Company\PerformanceAttendanceScore;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PerformanceController extends Controller
{
    /**
     * Map a numeric score (0–100) to a qualitative status.
     */
    protected function statusFromScore(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        if ($score >= 90) {
            return 'excellent';
        }

        if ($score >= 75) {
            return 'good';
        }

        if ($score >= 60) {
            return 'fair';
        }

        return 'poor';
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get all months that have attendance records (automatically detected)
        $monthsWithAttendance = AttendanceRecord::where('company_id', $company->id)
            ->selectRaw('DATE_FORMAT(datetime, "%Y-%m") as month')
            ->distinct()
            ->orderBy('month', 'desc')
            ->pluck('month')
            ->toArray();

        // Get current month to exclude it from results (only show completed months)
        $currentMonth = now()->format('Y-m');
        
        // Get default month (last completed month with actual data, or last month)
        $defaultMonth = $request->get('month');
        if (!$defaultMonth) {
            // Get last completed month with actual working days (not empty data, and before current month)
            $lastMonthWithData = PerformanceAttendanceScore::where('company_id', $company->id)
                ->where('working_days', '>', 0)
                ->where('month', '<', $currentMonth) // Only completed months
                ->orderBy('month', 'desc')
                ->value('month');
            
            // If no month with data, get any last completed month
            if (!$lastMonthWithData) {
                $lastMonthWithData = PerformanceAttendanceScore::where('company_id', $company->id)
                    ->where('month', '<', $currentMonth) // Only completed months
                    ->orderBy('month', 'desc')
                    ->value('month');
            }
            
            // If still no data, use last month from attendance records or previous month from current
            if (!$lastMonthWithData && !empty($monthsWithAttendance)) {
                // Get the most recent month from attendance that is before current month
                $lastMonthWithData = collect($monthsWithAttendance)
                    ->filter(function ($month) use ($currentMonth) {
                        return $month < $currentMonth;
                    })
                    ->first();
            }
            
            $defaultMonth = $lastMonthWithData ?? (now()->subMonth()->format('Y-m'));
        }
        
        $month = $defaultMonth;
        
        // Load all performance scores for completed months only (exclude current month)
        $allScores = PerformanceAttendanceScore::where('company_id', $company->id)
            ->where('working_days', '>', 0) // Only months with actual attendance
            ->where('month', '<', $currentMonth) // Only completed months (before current month)
            ->with('employee.user')
            ->orderBy('month', 'desc')
            ->orderBy('employee_id')
            ->get();

        // Build items array with all completed months for all employees
        $items = $allScores->map(function ($score) {
            $employee = $score->employee;
            $user = $employee->user;

            $workingDays = $score->working_days ?? 0;
            $finalScore = $score->score;
            $dailyScore = $workingDays > 0 && $finalScore !== null
                ? round($finalScore / $workingDays, 2)
                : null;

            return [
                'employee_id' => $employee->id,
                'name' => $user?->name ?? 'N/A',
                'employee_code' => $employee->employee_code ?? null,
                'position' => $employee->position ?? null,
                'month' => $score->month,
                'working_days' => $workingDays,
                'late_count' => $score->late_count ?? 0,
                'early_leave_count' => $score->early_leave_count ?? 0,
                'absence_days' => $score->absence_days ?? 0,
                'perfect_days' => $score->perfect_days ?? 0,
                'score' => $finalScore,
                'daily_score' => $dailyScore,
                'status' => $score->status ?? $this->statusFromScore($finalScore),
            ];
        })->values();

        // Get employee filter from request
        $employeeId = $request->get('employee_id');
        
        // Filter items by employee if selected
        $filteredItems = $items;
        if ($employeeId) {
            $filteredItems = $items->filter(function ($item) use ($employeeId) {
                return $item['employee_id'] == $employeeId;
            })->values();
        }

        // Get employees with score >= 70% (top performers) from ALL months
        // This shows employees with good performance regardless of which month
        $bestEmployees = $items
            ->filter(function ($item) {
                // Only include those with score >= 70
                return $item['score'] !== null && $item['score'] >= 70;
            })
            ->sortByDesc('score') // Sort by score descending (highest first)
            ->take(10) // Take top 10 performers
            ->values();
        
        // Get employees with score less than 70% (needs attention) from ALL months
        // This shows employees who need attention regardless of which month
        $worstEmployees = $items
            ->filter(function ($item) {
                // Only include those with score < 70
                return $item['score'] !== null && $item['score'] < 70;
            })
            ->sortBy('score') // Sort by score ascending (lowest first)
            ->take(3) // Take top 3 worst
            ->values();

        // Calculate daily performance for the selected month
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        
        $dailyPerformance = [];
        
        // Get all attendance records for the month
        $attendanceRecords = AttendanceRecord::where('company_id', $company->id)
            ->whereBetween('datetime', [$monthStart, $monthEnd])
            ->with(['employee.shift', 'employee.user'])
            ->get()
            ->groupBy(function ($record) {
                return $record->employee_id . '-' . Carbon::parse($record->datetime)->format('Y-m-d');
            });

        // Get all employees
        $allEmployees = Employee::where('company_id', $company->id)
            ->with(['shift', 'user'])
            ->get();

        // Process each day in the month
        $currentDate = $monthStart->copy();
        while ($currentDate->lte($monthEnd)) {
            // Skip weekends
            if ($currentDate->dayOfWeek !== 0 && $currentDate->dayOfWeek !== 6) {
                $dateString = $currentDate->format('Y-m-d');
                
                foreach ($allEmployees as $employee) {
                    $key = $employee->id . '-' . $dateString;
                    $dayRecords = $attendanceRecords->get($key);
                    
                    $checkIn = null;
                    $checkOut = null;
                    $isLate = false;
                    $isEarlyLeave = false;
                    $dailyScore = null;
                    $status = 'absent';
                    
                    if ($dayRecords) {
                        $checkInRecord = $dayRecords->where('type', 'in')->sortBy('datetime')->first();
                        $checkOutRecord = $dayRecords->where('type', 'out')->sortByDesc('datetime')->first();
                        
                        if ($checkInRecord) {
                            $checkIn = Carbon::parse($checkInRecord->datetime);
                            $status = 'partial';
                            
                            // Check if late
                            if ($employee->shift) {
                                $shiftStart = Carbon::parse($checkIn->format('Y-m-d') . ' ' . $employee->shift->start_time);
                                $graceMinutes = $employee->shift->late_grace_minutes ?? 15;
                                
                                // Check if check-in is after shift start + grace period
                                if ($checkIn->gt($shiftStart->copy()->addMinutes($graceMinutes))) {
                                    $isLate = true;
                                }
                            }
                        }
                        
                        if ($checkOutRecord) {
                            $checkOut = Carbon::parse($checkOutRecord->datetime);
                            $status = 'present';
                            
                            // Check if early leave
                            if ($employee->shift && $checkIn) {
                                $shiftEnd = Carbon::parse($checkOut->format('Y-m-d') . ' ' . $employee->shift->end_time);
                                
                                if ($checkOut->lt($shiftEnd)) {
                                    $earlyMinutes = $shiftEnd->diffInMinutes($checkOut);
                                    if ($earlyMinutes > 15) {
                                        $isEarlyLeave = true;
                                    }
                                }
                            }
                        }
                        
                        // Calculate daily score
                        if ($checkIn && $checkOut) {
                            $baseScore = 100;
                            if ($isLate) {
                                $baseScore -= 5;
                            }
                            if ($isEarlyLeave) {
                                $baseScore -= 5;
                            }
                            $dailyScore = max(0, min(100, $baseScore));
                        } elseif ($checkIn) {
                            $dailyScore = 50; // Partial attendance
                        } else {
                            $dailyScore = 0; // Absent
                        }
                    } else {
                        $dailyScore = 0; // Absent
                    }
                    
                    $dailyPerformance[] = [
                        'date' => $dateString,
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->user->name ?? 'N/A',
                        'employee_code' => $employee->employee_code ?? 'N/A',
                        'check_in' => $checkIn ? $checkIn->format('H:i') : null,
                        'check_out' => $checkOut ? $checkOut->format('H:i') : null,
                        'is_late' => $isLate,
                        'is_early_leave' => $isEarlyLeave,
                        'score' => $dailyScore,
                        'status' => $status,
                    ];
                }
            }
            
            $currentDate->addDay();
        }

        // Get employees list for dropdown
        $employees = Employee::where('company_id', $company->id)
            ->with('user')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->user->name ?? 'N/A',
                    'employee_code' => $employee->employee_code ?? null,
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Company/Performance/Index', [
            'month' => $month,
            'monthsWithAttendance' => $monthsWithAttendance,
            'items' => $filteredItems,
            'bestEmployees' => $bestEmployees,
            'worstEmployees' => $worstEmployees,
            'dailyPerformance' => $dailyPerformance,
            'employees' => $employees,
            'filters' => [
                'employee_id' => $employeeId,
            ],
        ]);
    }

    /**
     * Show detailed performance records for a specific month.
     */
    public function showMonth(Request $request, string $month)
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            abort(403, 'User does not belong to any company.');
        }

        // Validate month format (Y-m)
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            abort(404, 'Invalid month format.');
        }

        // Get month start and end dates
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        // Get all performance scores for this month
        $scores = PerformanceAttendanceScore::where('company_id', $company->id)
            ->where('month', $month)
            ->with('employee.user')
            ->orderBy('employee_id')
            ->get();

        // Get all attendance records for the month
        $attendanceRecords = AttendanceRecord::where('company_id', $company->id)
            ->whereBetween('datetime', [$monthStart, $monthEnd])
            ->with(['employee.shift', 'employee.user'])
            ->get()
            ->groupBy(function ($record) {
                return $record->employee_id . '-' . Carbon::parse($record->datetime)->format('Y-m-d');
            });

        // Get all employees
        $allEmployees = Employee::where('company_id', $company->id)
            ->with(['shift', 'user'])
            ->get();

        // Build detailed daily performance records
        $dailyPerformance = [];
        $currentDate = $monthStart->copy();
        
        while ($currentDate->lte($monthEnd)) {
            // Skip weekends
            if ($currentDate->dayOfWeek !== 0 && $currentDate->dayOfWeek !== 6) {
                $dateString = $currentDate->format('Y-m-d');
                
                foreach ($allEmployees as $employee) {
                    $key = $employee->id . '-' . $dateString;
                    $dayRecords = $attendanceRecords->get($key);
                    
                    $checkIn = null;
                    $checkOut = null;
                    $isLate = false;
                    $isEarlyLeave = false;
                    $dailyScore = null;
                    $status = 'absent';
                    
                    if ($dayRecords) {
                        $checkInRecord = $dayRecords->where('type', 'in')->sortBy('datetime')->first();
                        $checkOutRecord = $dayRecords->where('type', 'out')->sortByDesc('datetime')->first();
                        
                        if ($checkInRecord) {
                            $checkIn = Carbon::parse($checkInRecord->datetime);
                            $status = 'partial';
                            
                            // Check if late
                            if ($employee->shift) {
                                $shiftStart = Carbon::parse($checkIn->format('Y-m-d') . ' ' . $employee->shift->start_time);
                                $graceMinutes = $employee->shift->late_grace_minutes ?? 15;
                                
                                // Check if check-in is after shift start + grace period
                                if ($checkIn->gt($shiftStart->copy()->addMinutes($graceMinutes))) {
                                    $isLate = true;
                                }
                            }
                        }
                        
                        if ($checkOutRecord) {
                            $checkOut = Carbon::parse($checkOutRecord->datetime);
                            $status = 'present';
                            
                            // Check if early leave
                            if ($employee->shift && $checkIn) {
                                $shiftEnd = Carbon::parse($checkOut->format('Y-m-d') . ' ' . $employee->shift->end_time);
                                
                                if ($checkOut->lt($shiftEnd)) {
                                    $earlyMinutes = $shiftEnd->diffInMinutes($checkOut);
                                    if ($earlyMinutes > 15) {
                                        $isEarlyLeave = true;
                                    }
                                }
                            }
                        }
                        
                        // Calculate daily score
                        if ($checkIn && $checkOut) {
                            $baseScore = 100;
                            if ($isLate) {
                                $baseScore -= 5;
                            }
                            if ($isEarlyLeave) {
                                $baseScore -= 5;
                            }
                            $dailyScore = max(0, min(100, $baseScore));
                        } elseif ($checkIn) {
                            $dailyScore = 50; // Partial attendance
                        } else {
                            $dailyScore = 0; // Absent
                        }
                    } else {
                        $dailyScore = 0; // Absent
                    }
                    
                    $dailyPerformance[] = [
                        'date' => $dateString,
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->user->name ?? 'N/A',
                        'employee_code' => $employee->employee_code ?? 'N/A',
                        'position' => $employee->position ?? null,
                        'check_in' => $checkIn ? $checkIn->format('H:i') : null,
                        'check_out' => $checkOut ? $checkOut->format('H:i') : null,
                        'is_late' => $isLate,
                        'is_early_leave' => $isEarlyLeave,
                        'score' => $dailyScore,
                        'status' => $status,
                    ];
                }
            }
            
            $currentDate->addDay();
        }

        // Group by employee for summary
        $employeeSummaries = $scores->map(function ($score) {
            $employee = $score->employee;
            $user = $employee->user;

            $workingDays = $score->working_days ?? 0;
            $finalScore = $score->score;
            $dailyScore = $workingDays > 0 && $finalScore !== null
                ? round($finalScore / $workingDays, 2)
                : null;

            return [
                'employee_id' => $employee->id,
                'name' => $user?->name ?? 'N/A',
                'employee_code' => $employee->employee_code ?? null,
                'position' => $employee->position ?? null,
                'month' => $score->month,
                'working_days' => $workingDays,
                'late_count' => $score->late_count ?? 0,
                'early_leave_count' => $score->early_leave_count ?? 0,
                'absence_days' => $score->absence_days ?? 0,
                'perfect_days' => $score->perfect_days ?? 0,
                'score' => $finalScore,
                'daily_score' => $dailyScore,
                'status' => $score->status ?? $this->statusFromScore($finalScore),
            ];
        })->values();

        return Inertia::render('Company/Performance/Show', [
            'month' => $month,
            'employeeSummaries' => $employeeSummaries,
            'dailyPerformance' => $dailyPerformance,
            'company' => [
                'id' => $company->id,
                'name' => $company->name ?? 'Company',
            ],
        ]);
    }
}


