<?php

namespace App\Services;

use App\Models\Company\Employee;
use App\Models\Company\PayrollCycle;
use App\Models\Company\PayrollEntry;
use App\Models\Company\PayrollSetting;
use App\Models\Company\AttendanceRecord;
use App\Models\Company\LeaveRequest;
use App\Models\Company\PayrollManualDeduction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollService
{
    /**
     * Generate payroll for a company for a specific month
     */
    public function generatePayroll(int $companyId, string $month): array
    {
        // Validate month format (YYYY-MM)
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new \InvalidArgumentException('Invalid month format. Expected YYYY-MM');
        }

        // Check if cycle already exists
        $existingCycle = PayrollCycle::where('company_id', $companyId)
            ->where('month', $month)
            ->first();

        if ($existingCycle && $existingCycle->status !== 'draft') {
            throw new \Exception("Payroll cycle for {$month} already exists and is not in draft status.");
        }

        DB::beginTransaction();
        try {
            // Create or get payroll cycle
            $cycle = $existingCycle ?? PayrollCycle::create([
                'company_id' => $companyId,
                'month' => $month,
                'status' => 'draft',
            ]);

            // Get month start and end dates
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

            // Get all active employees
            $employees = Employee::where('company_id', $companyId)
                ->where('status', 'active')
                ->with(['shift', 'user'])
                ->get();

            $summary = [
                'total_employees' => $employees->count(),
                'processed_employees' => 0,
                'total_net_salary' => 0,
                'entries' => [],
            ];

            // Process each employee
            foreach ($employees as $employee) {
                $entry = $this->calculateEmployeePayroll($employee, $cycle->id, $startDate, $endDate);
                
                if ($entry) {
                    $summary['processed_employees']++;
                    $summary['total_net_salary'] += $entry['net_salary'];
                    $summary['entries'][] = $entry;
                }
            }

            // Update cycle status
            $cycle->update([
                'status' => 'generated',
                'generated_at' => now(),
            ]);

            DB::commit();

            Log::info("Payroll generated for company {$companyId}, month {$month}", $summary);

            return [
                'cycle' => $cycle,
                'summary' => $summary,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Payroll generation failed for company {$companyId}, month {$month}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate payroll for a single employee
     */
    protected function calculateEmployeePayroll(Employee $employee, int $cycleId, Carbon $startDate, Carbon $endDate): ?array
    {
        // Skip if employee has no basic salary
        if (!$employee->basic_salary) {
            return null;
        }

        // Get employee's working hours per day (default 8)
        $workingHoursPerDay = $employee->working_hours_per_day ?? 8;
        $workingDaysPerMonth = $employee->working_days_per_month ?? 26;
        $hourlyRate = $employee->hourly_rate ?? ($employee->basic_salary / ($workingDaysPerMonth * $workingHoursPerDay));
        $overtimeRate = $employee->overtime_rate;

        // 1. Basic Salary
        $basicSalary = $employee->basic_salary ?? 0;

        // 2. Fixed Allowances
        $totalAllowances = 0;
        if ($employee->allowances_fixed && is_array($employee->allowances_fixed)) {
            $totalAllowances = array_sum(array_column($employee->allowances_fixed, 'amount'));
        }

        // Get payroll settings
        $settings = PayrollSetting::where('company_id', $employee->company_id)->first();

        // 3. Calculate Overtime (check settings and employee rate)
        $totalOvertimeAmount = 0;
        if ($settings && $settings->overtime_enabled) {
            if ($overtimeRate !== null || $settings->overtime_normal_rate) {
                $effectiveOvertimeRate = $overtimeRate ?? $settings->overtime_normal_rate;
                $totalOvertimeAmount = $this->calculateOvertime($employee, $startDate, $endDate, $hourlyRate, $effectiveOvertimeRate, $workingHoursPerDay);
            }
        } elseif ($overtimeRate !== null) {
            // If no settings but employee has overtime rate, use it
            $totalOvertimeAmount = $this->calculateOvertime($employee, $startDate, $endDate, $hourlyRate, $overtimeRate, $workingHoursPerDay);
        }

        // 4. Calculate Attendance Deductions (can be negative if bonus)
        $attendanceDeductions = $this->calculateAttendanceDeductions($employee, $startDate, $endDate, $hourlyRate, $workingHoursPerDay);
        
        // 5. Calculate Attendance Bonus and merge with deductions
        $attendanceBonus = $this->calculateAttendanceBonus($employee, $startDate, $endDate, $basicSalary, $settings);
        
        // Merge bonus with deductions (bonus reduces deductions, so subtract it)
        // attendance_deductions can be negative if bonus > deductions
        $attendanceDeductions = $attendanceDeductions - $attendanceBonus;
        
        // 6. Calculate Leave Deductions
        $leaveDeductions = $this->calculateLeaveDeductions($employee, $startDate, $endDate, $hourlyRate, $workingHoursPerDay);
        
        // 7. Manual Deductions
        $manualDeductions = $this->getManualDeductions($employee, $startDate, $endDate);

        // 8. Fixed Deductions
        $fixedDeductions = 0;
        if ($employee->deductions_fixed && is_array($employee->deductions_fixed)) {
            $fixedDeductions = array_sum(array_column($employee->deductions_fixed, 'amount'));
        }

        // 9. Total Deductions (attendance deductions can be negative if bonus)
        // Use max(0, attendanceDeductions) to ensure we don't add negative to total
        $totalDeductions = max(0, $attendanceDeductions) + $leaveDeductions + $manualDeductions + $fixedDeductions;

        // 9. Net Salary
        $netSalary = $basicSalary + $totalAllowances + $totalOvertimeAmount - $totalDeductions;

        // Ensure net salary is not negative
        $netSalary = max(0, $netSalary);

        // Create or update payroll entry
        $entry = PayrollEntry::updateOrCreate(
            [
                'cycle_id' => $cycleId,
                'employee_id' => $employee->id,
            ],
            [
                'basic_salary' => $basicSalary,
                'total_allowances' => $totalAllowances,
                'total_overtime_amount' => $totalOvertimeAmount,
                'total_deductions' => $totalDeductions,
                'attendance_deductions' => $attendanceDeductions,
                'leave_deductions' => $leaveDeductions,
                'manual_deductions' => $manualDeductions,
                'fixed_deductions' => $fixedDeductions,
                'net_salary' => $netSalary,
                'status' => 'pending',
                'notes' => $this->generateNotes($attendanceDeductions, $leaveDeductions, $manualDeductions, $totalOvertimeAmount, $attendanceBonus),
            ]
        );

        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->user->name ?? 'N/A',
            'basic_salary' => $basicSalary,
            'total_allowances' => $totalAllowances,
            'total_overtime_amount' => $totalOvertimeAmount,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'entry_id' => $entry->id,
        ];
    }

    /**
     * Recalculate a specific payroll entry
     */
    public function recalculatePayrollEntry(int $entryId): array
    {
        $entry = PayrollEntry::with(['employee', 'cycle'])->findOrFail($entryId);
        $employee = $entry->employee;
        $cycle = $entry->cycle;

        // Get month start and end dates
        $startDate = Carbon::createFromFormat('Y-m', $cycle->month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $cycle->month)->endOfMonth();

        // Recalculate using the existing method
        $result = $this->calculateEmployeePayroll($employee, $cycle->id, $startDate, $endDate);

        if (!$result) {
            throw new \Exception('Failed to recalculate payroll entry.');
        }

        return $result;
    }

    /**
     * Calculate overtime hours and amount
     */
    protected function calculateOvertime(Employee $employee, Carbon $startDate, Carbon $endDate, float $hourlyRate, float $overtimeRate, int $workingHoursPerDay): float
    {
        // Get payroll settings for the company
        $settings = PayrollSetting::where('company_id', $employee->company_id)->first();
        
        // If overtime is disabled in settings, return 0
        if ($settings && !$settings->overtime_enabled) {
            return 0;
        }

        // Get attendance records for the month
        $attendanceRecords = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('datetime', [$startDate, $endDate])
            ->orderBy('datetime')
            ->get();

        $totalOvertimeHours = 0;

        // Group by date
        $groupedByDate = $attendanceRecords->groupBy(function ($record) {
            return $record->datetime->format('Y-m-d');
        });

        foreach ($groupedByDate as $date => $records) {
            $checkIn = $records->where('type', 'in')->first();
            $checkOut = $records->where('type', 'out')->last();

            if ($checkIn && $checkOut) {
                $checkInTime = Carbon::parse($checkIn->datetime);
                $checkOutTime = Carbon::parse($checkOut->datetime);
                $workedHours = $checkInTime->diffInHours($checkOutTime);

                // Calculate overtime (hours worked beyond regular hours)
                if ($workedHours > $workingHoursPerDay) {
                    $overtimeHours = $workedHours - $workingHoursPerDay;
                    
                    // Apply max overtime per day limit if set
                    if ($settings && $settings->overtime_max_per_day) {
                        $overtimeHours = min($overtimeHours, $settings->overtime_max_per_day);
                    }
                    
                    $totalOvertimeHours += $overtimeHours;
                }
            }
        }

        // Apply max overtime per month limit if set
        if ($settings && $settings->overtime_max_per_month) {
            $totalOvertimeHours = min($totalOvertimeHours, $settings->overtime_max_per_month);
        }

        // Use overtime rate from settings if available, otherwise use employee's rate
        $effectiveOvertimeRate = $settings ? $settings->overtime_normal_rate : $overtimeRate;

        // Calculate overtime amount
        $overtimeAmount = $totalOvertimeHours * $hourlyRate * $effectiveOvertimeRate;

        return round($overtimeAmount, 2);
    }

    /**
     * Calculate deductions from attendance (late arrivals, early leaves)
     */
    protected function calculateAttendanceDeductions(Employee $employee, Carbon $startDate, Carbon $endDate, float $hourlyRate, int $workingHoursPerDay): float
    {
        $deductions = 0;

        // Get payroll settings for the company
        $settings = PayrollSetting::where('company_id', $employee->company_id)->first();
        
        // If no settings, return 0 (no deductions)
        if (!$settings) {
            return 0;
        }

        // Get shift for the employee
        $shift = $employee->shift;
        if (!$shift) {
            return 0;
        }

        $shiftStartTime = Carbon::parse($shift->start_time);
        $shiftEndTime = Carbon::parse($shift->end_time);
        
        // Use grace minutes from payroll settings or shift (settings take priority)
        $lateGraceMinutes = $settings->late_grace_minutes ?? ($shift->late_grace_minutes ?? 0);

        // Get attendance records
        $attendanceRecords = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('datetime', [$startDate, $endDate])
            ->orderBy('datetime')
            ->get();

        $groupedByDate = $attendanceRecords->groupBy(function ($record) {
            return $record->datetime->format('Y-m-d');
        });

        foreach ($groupedByDate as $date => $records) {
            $checkIn = $records->where('type', 'in')->first();
            $checkOut = $records->where('type', 'out')->last();

            // Late arrival deduction (only if enabled in settings)
            if ($checkIn && $settings->late_deduction_enabled) {
                $checkInTime = Carbon::parse($checkIn->datetime);
                $expectedStart = Carbon::parse($date . ' ' . $shiftStartTime->format('H:i:s'));
                $graceEndTime = $expectedStart->copy()->addMinutes($lateGraceMinutes);

                // Check if late (after grace period)
                if ($checkInTime->gt($graceEndTime)) {
                    // Calculate late minutes correctly (checkInTime - expectedStart)
                    $lateMinutes = $expectedStart->diffInMinutes($checkInTime, false);
                    $deductibleMinutes = $lateMinutes - $lateGraceMinutes;
                    
                    // Only deduct if there are deductible minutes
                    if ($deductibleMinutes > 0) {
                        // Calculate deduction based on unit (hour or minute)
                        if ($settings->late_calculation_unit === 'hour') {
                            // Round up to nearest hour
                            $deductibleHours = ceil($deductibleMinutes / 60);
                            $deductions += $deductibleHours * $hourlyRate;
                        } else {
                            // Calculate by minute
                            $deductibleHours = $deductibleMinutes / 60;
                            $deductions += $deductibleHours * $hourlyRate;
                        }
                    }
                }
            }

            // Early leave deduction (only if enabled in settings)
            if ($checkOut && $settings->early_leave_deduction_enabled) {
                $checkOutTime = Carbon::parse($checkOut->datetime);
                $expectedEnd = Carbon::parse($date . ' ' . $shiftEndTime->format('H:i:s'));

                // Check if early leave (more than 15 minutes early)
                if ($checkOutTime->lt($expectedEnd)) {
                    $earlyMinutes = $expectedEnd->diffInMinutes($checkOutTime);
                    if ($earlyMinutes > 15) {
                        $earlyHours = $earlyMinutes / 60;
                        $deductions += $earlyHours * $hourlyRate;
                    }
                }
            }
        }

        return round($deductions, 2);
    }

    /**
     * Calculate deductions from unpaid leaves (absence days)
     */
    protected function calculateLeaveDeductions(Employee $employee, Carbon $startDate, Carbon $endDate, float $hourlyRate, int $workingHoursPerDay): float
    {
        // Get payroll settings for the company
        $settings = PayrollSetting::where('company_id', $employee->company_id)->first();
        
        if (!$settings) {
            return 0; // No settings, no deductions
        }

        // Get basic salary and working days per month
        $basicSalary = $employee->basic_salary ?? 0;
        $workingDaysPerMonth = $employee->working_days_per_month ?? 26;
        
        if ($basicSalary <= 0 || $workingDaysPerMonth <= 0) {
            return 0;
        }

        // Calculate daily rate
        $dailyRate = $basicSalary / $workingDaysPerMonth;

        // Get all attendance records for the period
        $attendanceRecords = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('datetime', [$startDate, $endDate])
            ->get();

        // Group by date to find days with attendance
        $daysWithAttendance = $attendanceRecords
            ->groupBy(function ($record) {
                return $record->datetime->format('Y-m-d');
            })
            ->keys()
            ->toArray();

        // Calculate total working days in the period (excluding weekends)
        $totalWorkingDays = 0;
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($currentDate->dayOfWeek !== 0 && $currentDate->dayOfWeek !== 6) {
                $totalWorkingDays++;
            }
            $currentDate->addDay();
        }

        // Calculate absence days (working days without attendance)
        $absenceDays = $totalWorkingDays - count($daysWithAttendance);

        if ($absenceDays <= 0) {
            return 0; // No absence days
        }

        // Calculate deductions based on settings
        $deductions = 0;

        if ($settings->absence_deduction_type === 'full_day') {
            // Deduct full day for each absence day
            $deductions = $absenceDays * $dailyRate;
        } elseif ($settings->absence_deduction_type === 'percentage') {
            // Deduct percentage of daily rate for each absence day
            $percentage = $settings->absence_deduction_percentage ?? 0;
            if ($percentage > 0) {
                $deductionPerDay = ($dailyRate * $percentage) / 100;
                $deductions = $absenceDays * $deductionPerDay;
            }
        }

        return round($deductions, 2);
    }

    /**
     * Get manual deductions for the period
     */
    protected function getManualDeductions(Employee $employee, Carbon $startDate, Carbon $endDate): float
    {
        $deductions = PayrollManualDeduction::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        return round($deductions, 2);
    }

    /**
     * Calculate attendance bonus based on settings
     */
    protected function calculateAttendanceBonus(Employee $employee, Carbon $startDate, Carbon $endDate, float $basicSalary, ?PayrollSetting $settings): float
    {
        if (!$settings || !$settings->attendance_bonus_enabled) {
            return 0;
        }

        // Get attendance records for the period
        $attendanceRecords = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('datetime', [$startDate, $endDate])
            ->orderBy('datetime')
            ->get();

        // Get shift for the employee
        $shift = $employee->shift;
        if (!$shift) {
            return 0;
        }

        $shiftStartTime = Carbon::parse($shift->start_time);
        $shiftEndTime = Carbon::parse($shift->end_time);
        $lateGraceMinutes = $settings->late_grace_minutes ?? ($shift->late_grace_minutes ?? 0);

        // Group by date
        $groupedByDate = $attendanceRecords->groupBy(function ($record) {
            return $record->datetime->format('Y-m-d');
        });

        // Calculate working days (excluding weekends)
        $totalWorkingDays = 0;
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            if ($currentDate->dayOfWeek !== 0 && $currentDate->dayOfWeek !== 6) {
                $totalWorkingDays++;
            }
            $currentDate->addDay();
        }

        // Analyze attendance
        $perfectDays = 0;
        $daysWithNoLate = 0;
        $daysWithAttendance = 0;
        $lateCount = 0;
        $absenceDays = 0;

        foreach ($groupedByDate as $date => $records) {
            $checkIn = $records->where('type', 'in')->first();
            $checkOut = $records->where('type', 'out')->last();

            if ($checkIn && $checkOut) {
                $daysWithAttendance++;
                
                $checkInTime = Carbon::parse($checkIn->datetime);
                $expectedStart = Carbon::parse($date . ' ' . $shiftStartTime->format('H:i:s'));
                $checkOutTime = Carbon::parse($checkOut->datetime);
                $expectedEnd = Carbon::parse($date . ' ' . $shiftEndTime->format('H:i:s'));

                // Check if late
                $isLate = $checkInTime->gt($expectedStart->copy()->addMinutes($lateGraceMinutes));
                if (!$isLate) {
                    $daysWithNoLate++;
                } else {
                    $lateCount++;
                }

                // Check if perfect day (on time and full day)
                if (!$isLate && $checkOutTime->gte($expectedEnd)) {
                    $perfectDays++;
                }
            }
        }

        $absenceDays = $totalWorkingDays - $daysWithAttendance;

        // Check bonus condition
        $bonusEligible = false;

        switch ($settings->attendance_bonus_condition) {
            case 'perfect_attendance':
                $bonusEligible = ($perfectDays === $totalWorkingDays && $totalWorkingDays > 0);
                break;
            case 'no_late':
                $bonusEligible = ($lateCount === 0 && $daysWithAttendance === $totalWorkingDays);
                break;
            case 'no_absence':
                $bonusEligible = ($absenceDays === 0);
                break;
            case 'custom_days':
                $minDays = $settings->attendance_bonus_min_days ?? 0;
                $bonusEligible = ($daysWithAttendance >= $minDays);
                break;
        }

        if (!$bonusEligible) {
            return 0;
        }

        // Calculate bonus amount
        $bonusAmount = 0;

        switch ($settings->attendance_bonus_type) {
            case 'fixed_amount':
                $bonusAmount = $settings->attendance_bonus_amount ?? 0;
                break;
            case 'percentage':
                $percentage = $settings->attendance_bonus_amount ?? 0;
                $bonusAmount = ($basicSalary * $percentage) / 100;
                break;
            case 'per_day':
                $perDayAmount = $settings->attendance_bonus_amount ?? 0;
                $bonusAmount = $daysWithAttendance * $perDayAmount;
                break;
        }

        return round($bonusAmount, 2);
    }

    /**
     * Generate notes for payroll entry
     */
    protected function generateNotes(float $attendanceDeductions, float $leaveDeductions, float $manualDeductions, float $overtimeAmount, float $attendanceBonus = 0): string
    {
        $notes = [];
        
        if ($overtimeAmount > 0) {
            $notes[] = "Overtime: " . number_format($overtimeAmount, 2);
        }
        
        if ($attendanceBonus > 0) {
            $notes[] = "Attendance bonus: " . number_format($attendanceBonus, 2);
        }
        
        if ($attendanceDeductions > 0) {
            $notes[] = "Attendance deductions: " . number_format($attendanceDeductions, 2);
        } elseif ($attendanceDeductions < 0) {
            $notes[] = "Attendance bonus: " . number_format(abs($attendanceDeductions), 2);
        }
        
        if ($leaveDeductions > 0) {
            $notes[] = "Leave deductions: " . number_format($leaveDeductions, 2);
        }
        
        if ($manualDeductions > 0) {
            $notes[] = "Manual deductions: " . number_format($manualDeductions, 2);
        }

        return implode(' | ', $notes);
    }
}

