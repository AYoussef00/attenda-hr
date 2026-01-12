<?php

namespace App\Console\Commands;

use App\Models\Company\AttendanceRecord;
use App\Models\Company\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AddAttendanceNovember2025 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:add-november-2025-attendance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add attendance records for Ali Test Employee in November 2025 from start of month until today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Adding attendance records for November 2025...');

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

        // Delete existing records for November 2025
        $monthStart = Carbon::create(2025, 11, 1)->startOfMonth();
        $today = Carbon::today();

        $deleted = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('datetime', [$monthStart, $today->endOfDay()])
            ->delete();

        $this->info("Deleted {$deleted} existing records for November 2025");

        // Get shift times
        $shiftStart = $employee->shift ? Carbon::parse($employee->shift->start_time) : Carbon::parse('09:00');
        $shiftEnd = $employee->shift ? Carbon::parse($employee->shift->end_time) : Carbon::parse('17:00');

        $this->info("Shift: {$shiftStart->format('H:i')} - {$shiftEnd->format('H:i')}");
        $this->info("Adding records from {$monthStart->format('Y-m-d')} to {$today->format('Y-m-d')}");

        $attendanceRecords = [];
        $workingDays = 0;

        // Generate attendance from start of month until today
        $currentDate = $monthStart->copy();
        while ($currentDate->lte($today)) {
            // Skip weekends
            if ($currentDate->dayOfWeek === 0 || $currentDate->dayOfWeek === 6) {
                $currentDate->addDay();
                continue;
            }

            $workingDays++;

            // Perfect attendance: on time check-in and check-out
            $checkInTime = $currentDate->copy()
                ->setTime($shiftStart->hour, $shiftStart->minute)
                ->subMinutes(rand(0, 2)); // 0-2 minutes early

            $checkOutTime = $currentDate->copy()
                ->setTime($shiftEnd->hour, $shiftEnd->minute)
                ->addMinutes(rand(0, 5)); // 0-5 minutes late

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

        $this->info("\n✅ Successfully added attendance records for November 2025!");
        $this->info("Employee: {$employee->user->name}");
        $this->info("Working days: {$workingDays}");
        $this->info("Total records: " . count($attendanceRecords));
        $this->info("Date range: {$monthStart->format('Y-m-d')} to {$today->format('Y-m-d')}");
        $this->info("\nNote: Performance score will be calculated automatically at the end of the month.");

        return 0;
    }
}
