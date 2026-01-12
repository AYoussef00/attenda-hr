<?php

namespace App\Console\Commands;

use App\Models\Company\AttendanceRecord;
use App\Models\Company\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AddAttendanceAugust2025 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:add-august-2025-attendance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add attendance records for Ali Test Employee in August 2025';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Adding attendance records for August 2025...');

        // Find Ali Test Employee
        $employee = Employee::where('company_id', 2) // Microsoft
            ->whereHas('user', function ($query) {
                $query->where('email', 'ali.test@microsoft.com');
            })
            ->with(['shift', 'user'])
            ->first();

        if (!$employee) {
            $this->error('Ali Test Employee not found!');
            return 1;
        }

        $this->info("Found employee: {$employee->user->name} (ID: {$employee->id})");

        // Delete existing records for August 2025
        $monthStart = Carbon::create(2025, 8, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $deleted = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('datetime', [$monthStart, $monthEnd])
            ->delete();

        $this->info("Deleted {$deleted} existing records for August 2025");

        // Get shift times
        $shiftStart = $employee->shift ? Carbon::parse($employee->shift->start_time) : Carbon::parse('09:00');
        $shiftEnd = $employee->shift ? Carbon::parse($employee->shift->end_time) : Carbon::parse('17:00');

        $this->info("Shift: {$shiftStart->format('H:i')} - {$shiftEnd->format('H:i')}");

        $attendanceRecords = [];
        $workingDays = 0;

        // Generate attendance for all weekdays in August 2025
        $currentDate = $monthStart->copy();
        while ($currentDate->lte($monthEnd)) {
            // Skip weekends
            if ($currentDate->dayOfWeek === 0 || $currentDate->dayOfWeek === 6) {
                $currentDate->addDay();
                continue;
            }

            $workingDays++;

            // Varied attendance: sometimes on time, sometimes late, sometimes early leave
            $isLate = rand(1, 100) <= 40; // 40% chance of being late
            $isEarlyLeave = rand(1, 100) <= 30; // 30% chance of early leave

            if ($isLate) {
                // Late by 15-60 minutes
                $lateMinutes = rand(15, 60);
                $checkInTime = $currentDate->copy()->setTime($shiftStart->hour, $shiftStart->minute)->addMinutes($lateMinutes);
            } else {
                // On time or slightly early
                $checkInTime = $currentDate->copy()->setTime($shiftStart->hour, $shiftStart->minute)->subMinutes(rand(0, 10));
            }

            if ($isEarlyLeave) {
                // Leave early by 20-90 minutes
                $earlyMinutes = rand(20, 90);
                $checkOutTime = $currentDate->copy()->setTime($shiftEnd->hour, $shiftEnd->minute)->subMinutes($earlyMinutes);
            } else {
                // On time or slightly late
                $checkOutTime = $currentDate->copy()->setTime($shiftEnd->hour, $shiftEnd->minute)->addMinutes(rand(0, 15));
            }

            $attendanceRecords[] = [
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'type' => 'in',
                'datetime' => $checkInTime,
                'method' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ];

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

        // Insert attendance records
        $this->info("Inserting " . count($attendanceRecords) . " attendance records...");
        foreach (array_chunk($attendanceRecords, 500) as $chunk) {
            AttendanceRecord::insert($chunk);
        }

        $this->info("\n✅ Successfully added attendance records for August 2025!");
        $this->info("Employee: {$employee->user->name}");
        $this->info("Working days: {$workingDays}");
        $this->info("Total records: " . count($attendanceRecords));
        $this->info("\nNote: Performance score will be calculated automatically at the end of the month.");

        return 0;
    }
}
