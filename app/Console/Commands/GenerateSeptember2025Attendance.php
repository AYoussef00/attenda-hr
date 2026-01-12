<?php

namespace App\Console\Commands;

use App\Models\Company\Employee;
use App\Models\Company\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateSeptember2025Attendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:generate-september-2025';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate attendance records for all employees for September 2025 with late arrivals';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating attendance records for September 2025...');

        // September 2025 date range
        $startDate = Carbon::create(2025, 9, 1)->startOfDay();
        $endDate = Carbon::create(2025, 9, 30)->endOfDay();

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

            // Generate attendance for each day in September 2025
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

                // Generate check-in time with late arrival (variation: 5 to 60 minutes late)
                $lateMinutes = rand(5, 60);
                $checkInTime = $currentDate->copy()
                    ->setTime($checkInHour, $checkInMinute, 0)
                    ->addMinutes($lateMinutes);

                // Generate check-out time (8 hours after check-in, with some variation: -10 to +30 minutes)
                $checkOutVariation = rand(-10, 30);
                $checkOutTime = $checkInTime->copy()
                    ->addHours($workingHours)
                    ->addMinutes($checkOutVariation);

                // Ensure checkout is after check-in
                if ($checkOutTime->lte($checkInTime)) {
                    $checkOutTime = $checkInTime->copy()->addHours($workingHours);
                }

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
        $this->info("Successfully generated {$totalRecords} attendance records for September 2025.");
        
        return 0;
    }
}
