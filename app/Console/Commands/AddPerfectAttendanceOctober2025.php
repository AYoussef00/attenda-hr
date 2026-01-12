<?php

namespace App\Console\Commands;

use App\Models\Company\AttendanceRecord;
use App\Models\Company\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AddPerfectAttendanceOctober2025 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:add-perfect-attendance-october-2025 {employee_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add perfect attendance records for October 2025 for a specific employee';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $employeeId = $this->argument('employee_id');
        
        // If no employee ID provided, use Mohamed Test Employee (ID: 7)
        if (!$employeeId) {
            $employeeId = 7; // Mohamed Test Employee
        }

        $employee = Employee::where('id', $employeeId)
            ->where('company_id', 2) // Microsoft company
            ->with(['shift', 'user'])
            ->first();

        if (!$employee) {
            $this->error("Employee with ID {$employeeId} not found in Microsoft company!");
            return 1;
        }

        $this->info("Adding perfect attendance for: {$employee->user->name} (ID: {$employee->id})");

        // Delete existing records for October 2025
        $monthStart = Carbon::create(2025, 10, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $deleted = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('datetime', [$monthStart, $monthEnd])
            ->delete();

        $this->info("Deleted {$deleted} existing records for October 2025");

        // Get shift times
        $shiftStart = $employee->shift ? Carbon::parse($employee->shift->start_time) : Carbon::parse('09:00');
        $shiftEnd = $employee->shift ? Carbon::parse($employee->shift->end_time) : Carbon::parse('17:00');

        $this->info("Shift: {$shiftStart->format('H:i')} - {$shiftEnd->format('H:i')}");

        $attendanceRecords = [];
        $workingDays = 0;
        $perfectDays = 0;

        // Generate perfect attendance for all weekdays in October 2025
        $currentDate = $monthStart->copy();
        while ($currentDate->lte($monthEnd)) {
            // Skip weekends
            if ($currentDate->dayOfWeek === 0 || $currentDate->dayOfWeek === 6) {
                $currentDate->addDay();
                continue;
            }

            $workingDays++;
            $perfectDays++;

            // Perfect check-in: exactly on time (or 1-2 minutes early)
            $checkInTime = $currentDate->copy()
                ->setTime($shiftStart->hour, $shiftStart->minute)
                ->subMinutes(rand(0, 2)); // 0-2 minutes early

            // Perfect check-out: exactly on time (or 1-5 minutes late)
            $checkOutTime = $currentDate->copy()
                ->setTime($shiftEnd->hour, $shiftEnd->minute)
                ->addMinutes(rand(0, 5)); // 0-5 minutes late

            // Add check-in record
            $attendanceRecords[] = [
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'type' => 'in',
                'datetime' => $checkInTime,
                'method' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Add check-out record
            $attendanceRecords[] = [
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'type' => 'out',
                'datetime' => $checkOutTime,
                'method' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $currentDate->addDay();
        }

        // Insert attendance records in batches
        $this->info("Inserting " . count($attendanceRecords) . " attendance records...");
        foreach (array_chunk($attendanceRecords, 500) as $chunk) {
            AttendanceRecord::insert($chunk);
        }

        $this->info("\n✅ Successfully added attendance records for October 2025!");
        $this->info("Employee: {$employee->user->name}");
        $this->info("Total records: " . count($attendanceRecords));
        $this->info("Working days: {$workingDays}");
        $this->info("Perfect attendance days: {$perfectDays}");
        $this->info("\nNote: Performance scores will be calculated automatically at the end of the month.");

        return 0;
    }
}
