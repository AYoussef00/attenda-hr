<?php

namespace App\Console\Commands;

use App\Models\Admin\Company;
use App\Models\Company\AttendanceRecord;
use App\Models\Company\Employee;
use App\Models\Company\PerformanceAttendanceScore;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateMonthlyPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:calculate-monthly {month?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate monthly performance scores for all employees. If month is not provided, calculates for the previous month.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = $this->argument('month');
        
        // If no month provided, calculate for previous month
        if (!$month) {
            $month = Carbon::now()->subMonth()->format('Y-m');
        }

        $this->info("Calculating monthly performance for: {$month}");

        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        // Get all companies
        $companies = Company::all();

        $totalProcessed = 0;
        $totalScores = 0;

        foreach ($companies as $company) {
            $this->info("Processing company: {$company->name} (ID: {$company->id})");

            // Get all employees for this company
            $employees = Employee::where('company_id', $company->id)
                ->with(['shift', 'user'])
                ->get();

            // Get all attendance records for the month
            $attendanceRecords = AttendanceRecord::where('company_id', $company->id)
                ->whereBetween('datetime', [$monthStart, $monthEnd])
                ->get()
                ->groupBy(function ($record) {
                    return $record->employee_id . '-' . Carbon::parse($record->datetime)->format('Y-m-d');
                });

            foreach ($employees as $employee) {
                $workingDays = 0;
                $lateCount = 0;
                $earlyLeaveCount = 0;
                $absenceDays = 0;
                $perfectDays = 0;

                // Count weekdays in the month
                $currentDate = $monthStart->copy();
                $weekdaysInMonth = 0;
                while ($currentDate->lte($monthEnd)) {
                    if ($currentDate->dayOfWeek !== 0 && $currentDate->dayOfWeek !== 6) {
                        $weekdaysInMonth++;
                    }
                    $currentDate->addDay();
                }

                // Process each weekday in the month
                $currentDate = $monthStart->copy();
                while ($currentDate->lte($monthEnd)) {
                    // Skip weekends
                    if ($currentDate->dayOfWeek === 0 || $currentDate->dayOfWeek === 6) {
                        $currentDate->addDay();
                        continue;
                    }

                    $dateString = $currentDate->format('Y-m-d');
                    $key = $employee->id . '-' . $dateString;
                    $dayRecords = $attendanceRecords->get($key);

                    if ($dayRecords) {
                        $workingDays++;

                        $checkInRecord = $dayRecords->where('type', 'in')->sortBy('datetime')->first();
                        $checkOutRecord = $dayRecords->where('type', 'out')->sortByDesc('datetime')->first();

                        $isLate = false;
                        $isEarlyLeave = false;
                        $isPerfect = true;

                        if ($checkInRecord) {
                            $checkIn = Carbon::parse($checkInRecord->datetime);

                            // Check if late
                            if ($employee->shift) {
                                $shiftStart = Carbon::parse($checkIn->format('Y-m-d') . ' ' . $employee->shift->start_time);
                                $graceMinutes = $employee->shift->late_grace_minutes ?? 15;

                                if ($checkIn->gt($shiftStart->copy()->addMinutes($graceMinutes))) {
                                    $isLate = true;
                                    $lateCount++;
                                    $isPerfect = false;
                                }
                            }
                        } else {
                            $isPerfect = false;
                        }

                        if ($checkOutRecord) {
                            $checkOut = Carbon::parse($checkOutRecord->datetime);

                            // Check if early leave
                            if ($employee->shift && $checkInRecord) {
                                $shiftEnd = Carbon::parse($checkOut->format('Y-m-d') . ' ' . $employee->shift->end_time);

                                if ($checkOut->lt($shiftEnd)) {
                                    $earlyMinutes = $shiftEnd->diffInMinutes($checkOut);
                                    if ($earlyMinutes > 15) {
                                        $isEarlyLeave = true;
                                        $earlyLeaveCount++;
                                        $isPerfect = false;
                                    }
                                }
                            }
                        } else {
                            $isPerfect = false;
                        }

                        if ($isPerfect && $checkInRecord && $checkOutRecord) {
                            $perfectDays++;
                        }
                    } else {
                        // No records for this day = absence
                        $absenceDays++;
                    }

                    $currentDate->addDay();
                }

                // Calculate score
                if ($workingDays > 0) {
                    $baseScore = ($perfectDays * 100) + 
                                (($workingDays - $perfectDays - $lateCount - $earlyLeaveCount) * 80);
                    
                    $penalty = ($lateCount * 5) + ($earlyLeaveCount * 5) + ($absenceDays * 20);
                    
                    $score = max(0, min(100, ($baseScore - $penalty) / $workingDays));
                } else {
                    $score = 0;
                }

                // Determine status
                $status = null;
                if ($score >= 90) {
                    $status = 'excellent';
                } elseif ($score >= 75) {
                    $status = 'good';
                } elseif ($score >= 60) {
                    $status = 'fair';
                } else {
                    $status = 'poor';
                }

                // Create or update performance score
                PerformanceAttendanceScore::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'employee_id' => $employee->id,
                        'month' => $month,
                    ],
                    [
                        'working_days' => $workingDays,
                        'late_count' => $lateCount,
                        'early_leave_count' => $earlyLeaveCount,
                        'absence_days' => $absenceDays,
                        'perfect_days' => $perfectDays,
                        'score' => round($score, 2),
                        'status' => $status,
                    ]
                );

                $totalProcessed++;
                $totalScores++;
                
                $employeeName = $employee->user->name ?? 'N/A';
                $this->line("  ✓ {$employeeName}: Score {$score} ({$status})");
            }
        }

        $this->info("\n✅ Successfully calculated performance scores!");
        $this->info("Total employees processed: {$totalProcessed}");
        $this->info("Total scores created/updated: {$totalScores}");

        return 0;
    }
}
