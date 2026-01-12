<?php

namespace App\Console\Commands;

use App\Models\Company\Employee;
use App\Models\Company\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateOctober2025Attendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:generate-october-2025';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate attendance records for all employees for October 2025';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating attendance records for October 2025...');

        // October 2025 date range
        $startDate = Carbon::create(2025, 10, 1)->startOfDay();
        $endDate = Carbon::create(2025, 10, 31)->endOfDay();

        // Get all employees
        $employees = Employee::with(['shift', 'company'])->get();

        if ($employees->isEmpty()) {
            $this->warn('No employees found in the system.');
            return 0;
        }

        $this->info("Found {$employees->count()} employees.");

        $progressBar = $this->output->createProgressBar($employees->count());
        $progressBar->start();

        $totalRecords = 0;

        foreach ($employees as $employee) {
            $company = $employee->company;
            
            if (!$company) {
                $progressBar->advance();
                continue;
            }

            // Get shift times or use defaults
            $shift = $employee->shift;
            $checkInHour = 9;
            $checkInMinute = 0;
            $checkOutHour = 17;
            $checkOutMinute = 0;
            $workingHours = 8;

            if ($shift) {
                // Parse shift start time (format: HH:MM:SS or HH:MM)
                $startTime = Carbon::parse($shift->start_time);
                $checkInHour = $startTime->hour;
                $checkInMinute = $startTime->minute;

                // Parse shift end time
                $endTime = Carbon::parse($shift->end_time);
                $checkOutHour = $endTime->hour;
                $checkOutMinute = $endTime->minute;

                // Calculate working hours
                $workingHours = $startTime->diffInHours($endTime);
            }

            // Generate attendance for each day in October 2025
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                // Skip weekends (Saturday = 6, Sunday = 0)
                if ($currentDate->dayOfWeek === 0 || $currentDate->dayOfWeek === 6) {
                    $currentDate->addDay();
                    continue;
                }

                // Check if record already exists for this date
                $existingRecord = AttendanceRecord::where('employee_id', $employee->id)
                    ->whereDate('datetime', $currentDate->toDateString())
                    ->exists();

                if ($existingRecord) {
                    $currentDate->addDay();
                    continue;
                }

                // Generate check-in time (exact time, no variation)
                $checkInTime = $currentDate->copy()
                    ->setTime($checkInHour, $checkInMinute, 0);

                // Generate check-out time (exact time based on working hours, no variation)
                $checkOutTime = $currentDate->copy()
                    ->setTime($checkOutHour, $checkOutMinute, 0);

                // Create check-in record
                AttendanceRecord::create([
                    'employee_id' => $employee->id,
                    'company_id' => $company->id,
                    'type' => 'in',
                    'datetime' => $checkInTime,
                    'method' => 'manual',
                ]);

                // Create check-out record
                AttendanceRecord::create([
                    'employee_id' => $employee->id,
                    'company_id' => $company->id,
                    'type' => 'out',
                    'datetime' => $checkOutTime,
                    'method' => 'manual',
                ]);

                $totalRecords += 2;
                $currentDate->addDay();
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Successfully generated {$totalRecords} attendance records for October 2025.");
        
        return 0;
    }
}
