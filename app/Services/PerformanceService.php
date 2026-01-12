<?php

namespace App\Services;

use App\Models\Company\Employee;
use App\Models\Company\AttendanceRecord;
use App\Models\Company\PerformanceAttendanceScore;
use Carbon\Carbon;

class PerformanceService
{
    /**
     * Calculate and update performance for an employee for a specific month
     */
    public function calculateEmployeePerformance(Employee $employee, string $month): PerformanceAttendanceScore
    {
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        // Get all attendance records for the month
        $attendanceRecords = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('datetime', [$startDate, $endDate])
            ->orderBy('datetime')
            ->get();

        // Get employee's shift
        $shift = $employee->shift;
        if (!$shift) {
            // If no shift, create a default calculation
            return $this->createDefaultScore($employee, $month);
        }

        $shiftStart = Carbon::parse($shift->start_time);
        $shiftEnd = Carbon::parse($shift->end_time);
        $lateGraceMinutes = $shift->late_grace_minutes ?? 15;

        // Group records by date
        $groupedByDate = $attendanceRecords->groupBy(function ($record) {
            return $record->datetime->format('Y-m-d');
        });

        $workingDays = 0;
        $lateCount = 0;
        $earlyLeaveCount = 0;
        $absenceDays = 0;
        $perfectDays = 0;

        // Get all working days in the month (excluding weekends)
        $currentDate = $startDate->copy();
        $totalWorkingDaysInMonth = 0;

        while ($currentDate <= $endDate) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($currentDate->dayOfWeek !== 0 && $currentDate->dayOfWeek !== 6) {
                $totalWorkingDaysInMonth++;
                
                $dateKey = $currentDate->format('Y-m-d');
                $dayRecords = $groupedByDate->get($dateKey, collect());

                $checkIn = $dayRecords->where('type', 'in')->first();
                $checkOut = $dayRecords->where('type', 'out')->last();

                if ($checkIn && $checkOut) {
                    // Employee attended
                    $workingDays++;
                    
                    $checkInTime = Carbon::parse($checkIn->datetime);
                    $checkOutTime = Carbon::parse($checkOut->datetime);
                    
                    $expectedStart = $currentDate->copy()->setTime(
                        $shiftStart->hour,
                        $shiftStart->minute,
                        $shiftStart->second
                    );
                    
                    $expectedEnd = $currentDate->copy()->setTime(
                        $shiftEnd->hour,
                        $shiftEnd->minute,
                        $shiftEnd->second
                    );

                    // Check for late arrival
                    $isLate = false;
                    if ($checkInTime->gt($expectedStart)) {
                        $lateMinutes = $checkInTime->diffInMinutes($expectedStart);
                        if ($lateMinutes > $lateGraceMinutes) {
                            $lateCount++;
                            $isLate = true;
                        }
                    }

                    // Check for early leave
                    $isEarlyLeave = false;
                    if ($checkOutTime->lt($expectedEnd)) {
                        $earlyMinutes = $expectedEnd->diffInMinutes($checkOutTime);
                        if ($earlyMinutes > 15) { // More than 15 minutes early
                            $earlyLeaveCount++;
                            $isEarlyLeave = true;
                        }
                    }

                    // Perfect day: on time, no early leave
                    if (!$isLate && !$isEarlyLeave) {
                        $perfectDays++;
                    }
                } else {
                    // Absence (no check-in or no check-out)
                    $absenceDays++;
                }
            }
            
            $currentDate->addDay();
        }

        // Calculate score
        // Base score: 100 points
        // Deduct 5 points per late arrival
        // Deduct 5 points per early leave
        // Deduct 10 points per absence day
        // Bonus: +2 points per perfect day (max 100)

        $baseScore = 100;
        $latePenalty = $lateCount * 5;
        $earlyLeavePenalty = $earlyLeaveCount * 5;
        $absencePenalty = $absenceDays * 10;
        
        $score = $baseScore - $latePenalty - $earlyLeavePenalty - $absencePenalty;
        
        // Add bonus for perfect days (but don't exceed 100)
        $perfectBonus = min($perfectDays * 2, 20); // Max 20 bonus points
        $score = min($score + $perfectBonus, 100);
        
        // Ensure score is not negative
        $score = max(0, $score);

        // Determine status
        $status = $this->statusFromScore($score);

        // Update or create performance score
        return PerformanceAttendanceScore::updateOrCreate(
            [
                'company_id' => $employee->company_id,
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
    }

    /**
     * Calculate performance for all employees in a company for a specific month
     */
    public function calculateCompanyPerformance(int $companyId, string $month): void
    {
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->get();

        foreach ($employees as $employee) {
            $this->calculateEmployeePerformance($employee, $month);
        }
    }

    /**
     * Calculate performance for current month when attendance record is created/updated
     */
    public function updateCurrentMonthPerformance(AttendanceRecord $record): void
    {
        $month = $record->datetime->format('Y-m');
        $employee = $record->employee;
        
        if ($employee) {
            $this->calculateEmployeePerformance($employee, $month);
        }
    }

    /**
     * Map score to status
     */
    protected function statusFromScore(float $score): ?string
    {
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

    /**
     * Create default score when employee has no shift
     */
    protected function createDefaultScore(Employee $employee, string $month): PerformanceAttendanceScore
    {
        return PerformanceAttendanceScore::updateOrCreate(
            [
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'month' => $month,
            ],
            [
                'working_days' => 0,
                'late_count' => 0,
                'early_leave_count' => 0,
                'absence_days' => 0,
                'perfect_days' => 0,
                'score' => 0,
                'status' => 'poor',
            ]
        );
    }
}

