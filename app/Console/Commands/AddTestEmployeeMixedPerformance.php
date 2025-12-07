<?php

namespace App\Console\Commands;

use App\Models\Company\AttendanceRecord;
use App\Models\Company\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AddTestEmployeeMixedPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:add-mixed-performance-employee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a test employee with mixed performance: perfect attendance in October 2025, poor attendance in September 2025';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Adding test employee with mixed performance...');

        // Find Microsoft company
        $company = \App\Models\Admin\Company::where('name', 'LIKE', '%microsoft%')
            ->orWhere('name', 'LIKE', '%Microsoft%')
            ->first();

        if (!$company) {
            $this->error('Microsoft company not found!');
            return 1;
        }

        // Get or create department
        $department = \App\Models\Company\Department::where('company_id', $company->id)->first();
        if (!$department) {
            $department = \App\Models\Company\Department::create([
                'company_id' => $company->id,
                'name' => 'Engineering',
                'description' => 'Engineering Department',
            ]);
        }

        // Get or create shift
        $shift = \App\Models\Company\Shift::where('company_id', $company->id)->first();
        if (!$shift) {
            $shift = \App\Models\Company\Shift::create([
                'company_id' => $company->id,
                'name' => 'Morning Shift',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'break_minutes' => 60,
                'late_grace_minutes' => 15,
                'overtime_after' => 480,
            ]);
        }

        // Check if employee already exists
        $existingEmployee = Employee::where('company_id', $company->id)
            ->whereHas('user', function ($query) {
                $query->where('email', 'ali.test@microsoft.com');
            })
            ->first();

        if ($existingEmployee) {
            $this->warn('Employee already exists. Deleting old records...');
            AttendanceRecord::where('employee_id', $existingEmployee->id)->delete();
            \App\Models\Company\PerformanceAttendanceScore::where('employee_id', $existingEmployee->id)->delete();
            $employee = $existingEmployee;
            $user = $employee->user;
        } else {
            // Create user
            $user = \App\Models\Admin\User::create([
                'company_id' => $company->id,
                'name' => 'Ali Test Employee',
                'email' => 'ali.test@microsoft.com',
                'password' => Hash::make('password123'),
                'phone' => '+201234567892',
                'role' => 'employee',
                'status' => 'active',
            ]);

            // Create employee
            $employee = Employee::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'employee_code' => 'MSFT-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'position' => 'Junior Developer',
                'department_id' => $department->id,
                'shift_id' => $shift->id,
                'hire_date' => Carbon::now()->subMonths(4),
                'contract_type' => 'full-time',
                'status' => 'active',
            ]);

            $this->info("Created employee: {$user->name} (Code: {$employee->employee_code})");
        }

        $shiftStart = Carbon::parse($shift->start_time);
        $shiftEnd = Carbon::parse($shift->end_time);

        $attendanceRecords = [];

        // September 2025: Poor attendance with absences and no commitment
        $this->info("\nGenerating poor attendance for September 2025...");
        $septStart = Carbon::create(2025, 9, 1)->startOfMonth();
        $septEnd = $septStart->copy()->endOfMonth();
        $currentDate = $septStart->copy();

        $septWorkingDays = 0;
        $septAbsenceDays = 0;

        while ($currentDate->lte($septEnd)) {
            // Skip weekends
            if ($currentDate->dayOfWeek === 0 || $currentDate->dayOfWeek === 6) {
                $currentDate->addDay();
                continue;
            }

            // 30% chance of absence
            if (rand(1, 100) <= 30) {
                $septAbsenceDays++;
                $currentDate->addDay();
                continue;
            }

            $septWorkingDays++;

            // Late arrival (60% chance)
            $isLate = rand(1, 100) <= 60;
            // Early leave (50% chance)
            $isEarlyLeave = rand(1, 100) <= 50;

            if ($isLate) {
                // Late by 20-90 minutes
                $lateMinutes = rand(20, 90);
                $checkInTime = $currentDate->copy()->setTime($shiftStart->hour, $shiftStart->minute)->addMinutes($lateMinutes);
            } else {
                // On time or slightly late
                $checkInTime = $currentDate->copy()->setTime($shiftStart->hour, $shiftStart->minute)->addMinutes(rand(0, 10));
            }

            if ($isEarlyLeave) {
                // Leave early by 30-120 minutes
                $earlyMinutes = rand(30, 120);
                $checkOutTime = $currentDate->copy()->setTime($shiftEnd->hour, $shiftEnd->minute)->subMinutes($earlyMinutes);
            } else {
                // On time or slightly late
                $checkOutTime = $currentDate->copy()->setTime($shiftEnd->hour, $shiftEnd->minute)->addMinutes(rand(0, 15));
            }

            $attendanceRecords[] = [
                'employee_id' => $employee->id,
                'company_id' => $company->id,
                'type' => 'in',
                'datetime' => $checkInTime,
                'method' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $attendanceRecords[] = [
                'employee_id' => $employee->id,
                'company_id' => $company->id,
                'type' => 'out',
                'datetime' => $checkOutTime,
                'method' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $currentDate->addDay();
        }

        // October 2025: Perfect attendance with full commitment
        $this->info("Generating perfect attendance for October 2025...");
        $octStart = Carbon::create(2025, 10, 1)->startOfMonth();
        $octEnd = $octStart->copy()->endOfMonth();
        $currentDate = $octStart->copy();

        $octWorkingDays = 0;
        $octPerfectDays = 0;

        while ($currentDate->lte($octEnd)) {
            // Skip weekends
            if ($currentDate->dayOfWeek === 0 || $currentDate->dayOfWeek === 6) {
                $currentDate->addDay();
                continue;
            }

            $octWorkingDays++;
            $octPerfectDays++;

            // Perfect check-in: exactly on time (or 1-2 minutes early)
            $checkInTime = $currentDate->copy()
                ->setTime($shiftStart->hour, $shiftStart->minute)
                ->subMinutes(rand(0, 2));

            // Perfect check-out: exactly on time (or 1-5 minutes late)
            $checkOutTime = $currentDate->copy()
                ->setTime($shiftEnd->hour, $shiftEnd->minute)
                ->addMinutes(rand(0, 5));

            $attendanceRecords[] = [
                'employee_id' => $employee->id,
                'company_id' => $company->id,
                'type' => 'in',
                'datetime' => $checkInTime,
                'method' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $attendanceRecords[] = [
                'employee_id' => $employee->id,
                'company_id' => $company->id,
                'type' => 'out',
                'datetime' => $checkOutTime,
                'method' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $currentDate->addDay();
        }

        // Insert attendance records
        $this->info("Inserting " . count($attendanceRecords) . " attendance records...");
        foreach (array_chunk($attendanceRecords, 500) as $chunk) {
            AttendanceRecord::insert($chunk);
        }

        $this->info("\n✅ Successfully added attendance records!");
        $this->info("Employee: {$user->name} ({$user->email})");
        $this->info("Password: password123");
        $this->info("\nSeptember 2025: Poor attendance");
        $this->info("  - Working days: {$septWorkingDays}");
        $this->info("  - Absence days: {$septAbsenceDays}");
        $this->info("\nOctober 2025: Perfect attendance");
        $this->info("  - Working days: {$octWorkingDays}");
        $this->info("  - Perfect days: {$octPerfectDays}");
        $this->info("\nNote: Performance scores will be calculated automatically at the end of each month.");

        return 0;
    }
}
