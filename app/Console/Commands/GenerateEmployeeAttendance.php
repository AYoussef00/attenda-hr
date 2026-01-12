<?php

namespace App\Console\Commands;

use App\Models\Company\Employee;
use App\Models\Company\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateEmployeeAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:generate-employee {employee_id} {month} {year} {--late : Generate attendance with late arrivals} {--mixed : Generate attendance with mix of on-time and late arrivals}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate attendance records for a specific employee for a specific month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $employeeId = $this->argument('employee_id');
        $month = $this->argument('month');
        $year = $this->argument('year');
        $withLate = $this->option('late');

        $employee = Employee::with(['shift', 'company'])->find($employeeId);

        if (!$employee) {
            $this->error("Employee with ID {$employeeId} not found.");
            return 1;
        }

        $company = $employee->company;
        
        if (!$company) {
            $this->error("Employee does not belong to any company.");
            return 1;
        }

        $employeeName = $employee->user->name ?? 'N/A';
        $this->info("Generating attendance records for: {$employeeName}");
        $this->info("Month: {$month}/{$year}");
        $withMixed = $this->option('mixed');
        if ($withMixed) {
            $this->info("Mode: Mixed (on-time and late arrivals)");
        } else {
            $this->info("With late arrivals: " . ($withLate ? 'Yes' : 'No'));
        }

        // Get shift times or use defaults
        $shift = $employee->shift;
        $checkInHour = 9;
        $checkInMinute = 0;
        $checkOutHour = 17;
        $checkOutMinute = 0;
        $workingHours = 8;

        if ($shift) {
            $startTime = Carbon::parse($shift->start_time);
            $checkInHour = $startTime->hour;
            $checkInMinute = $startTime->minute;

            $endTime = Carbon::parse($shift->end_time);
            $checkOutHour = $endTime->hour;
            $checkOutMinute = $endTime->minute;

            $workingHours = $startTime->diffInHours($endTime);
        }

        // Date range
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        $this->info("Date range: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        // Delete existing records for this month
        $deleted = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('datetime', [$startDate, $endDate])
            ->delete();
        
        if ($deleted > 0) {
            $this->info("Deleted {$deleted} existing attendance records for this month.");
        }

        $totalRecords = 0;
        $currentDate = $startDate->copy();

        $progressBar = $this->output->createProgressBar($endDate->day);
        $progressBar->start();

        while ($currentDate <= $endDate) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($currentDate->dayOfWeek === 0 || $currentDate->dayOfWeek === 6) {
                $currentDate->addDay();
                $progressBar->advance();
                continue;
            }

            if ($withMixed) {
                // Mixed mode: randomly decide if on-time or late (70% on-time, 30% late)
                $isLate = rand(1, 100) <= 30; // 30% chance of being late
                
                if ($isLate) {
                    // Generate check-in time with late arrival (5 to 60 minutes late)
                    $lateMinutes = rand(5, 60);
                    $checkInTime = $currentDate->copy()
                        ->setTime($checkInHour, $checkInMinute, 0)
                        ->addMinutes($lateMinutes);

                    // Generate check-out time (with some variation: -10 to +30 minutes)
                    $checkOutVariation = rand(-10, 30);
                    $checkOutTime = $checkInTime->copy()
                        ->addHours($workingHours)
                        ->addMinutes($checkOutVariation);

                    if ($checkOutTime->lte($checkInTime)) {
                        $checkOutTime = $checkInTime->copy()->addHours($workingHours);
                    }
                } else {
                    // Generate check-in time (exact time, no variation)
                    $checkInTime = $currentDate->copy()
                        ->setTime($checkInHour, $checkInMinute, 0);

                    // Generate check-out time (exact time, no variation)
                    $checkOutTime = $currentDate->copy()
                        ->setTime($checkOutHour, $checkOutMinute, 0);
                }
            } elseif ($withLate) {
                // Generate check-in time with late arrival (5 to 60 minutes late)
                $lateMinutes = rand(5, 60);
                $checkInTime = $currentDate->copy()
                    ->setTime($checkInHour, $checkInMinute, 0)
                    ->addMinutes($lateMinutes);

                // Generate check-out time (with some variation: -10 to +30 minutes)
                $checkOutVariation = rand(-10, 30);
                $checkOutTime = $checkInTime->copy()
                    ->addHours($workingHours)
                    ->addMinutes($checkOutVariation);

                if ($checkOutTime->lte($checkInTime)) {
                    $checkOutTime = $checkInTime->copy()->addHours($workingHours);
                }
            } else {
                // Generate check-in time (exact time, no variation)
                $checkInTime = $currentDate->copy()
                    ->setTime($checkInHour, $checkInMinute, 0);

                // Generate check-out time (exact time, no variation)
                $checkOutTime = $currentDate->copy()
                    ->setTime($checkOutHour, $checkOutMinute, 0);
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
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Successfully generated {$totalRecords} attendance records.");
        
        return 0;
    }
}

