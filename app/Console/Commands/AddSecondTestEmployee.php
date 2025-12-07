<?php

namespace App\Console\Commands;

use App\Models\Admin\Company;
use App\Models\Admin\User;
use App\Models\Company\AttendanceRecord;
use App\Models\Company\Department;
use App\Models\Company\Employee;
use App\Models\Company\PerformanceAttendanceScore;
use App\Models\Company\Shift;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AddSecondTestEmployee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:add-second-employee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a second test employee with varied attendance records for August and October';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to add second test employee...');

        // Find Microsoft company
        $company = Company::where('name', 'LIKE', '%microsoft%')
            ->orWhere('name', 'LIKE', '%Microsoft%')
            ->first();

        if (!$company) {
            $this->error('Microsoft company not found!');
            return 1;
        }

        $this->info("Found company: {$company->name} (ID: {$company->id})");

        // Get or create a department
        $department = Department::where('company_id', $company->id)->first();
        if (!$department) {
            $department = Department::create([
                'company_id' => $company->id,
                'name' => 'Engineering',
                'description' => 'Engineering Department',
            ]);
        }

        // Get or create a shift (9 AM to 5 PM)
        $shift = Shift::where('company_id', $company->id)->first();
        if (!$shift) {
            $shift = Shift::create([
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
                $query->where('email', 'mohamed.test@microsoft.com');
            })
            ->first();

        if ($existingEmployee) {
            $this->warn('Employee already exists. Deleting old records...');
            AttendanceRecord::where('employee_id', $existingEmployee->id)->delete();
            PerformanceAttendanceScore::where('employee_id', $existingEmployee->id)->delete();
            $employee = $existingEmployee;
            $user = $employee->user;
        } else {
            // Create user
            $user = User::create([
                'company_id' => $company->id,
                'name' => 'Mohamed Test Employee',
                'email' => 'mohamed.test@microsoft.com',
                'password' => Hash::make('password123'),
                'phone' => '+201234567891',
                'role' => 'employee',
                'status' => 'active',
            ]);

            // Create employee
            $employee = Employee::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'employee_code' => 'MSFT-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'position' => 'Senior Developer',
                'department_id' => $department->id,
                'shift_id' => $shift->id,
                'hire_date' => Carbon::now()->subMonths(4),
                'contract_type' => 'full-time',
                'status' => 'active',
            ]);

            $this->info("Created employee: {$user->name} (Code: {$employee->employee_code})");
        }

        // Generate attendance records for August and October 2024
        $months = [
            ['year' => 2024, 'month' => 8], // August
            ['year' => 2024, 'month' => 10], // October
        ];

        $shiftStart = Carbon::parse($shift->start_time);
        $shiftEnd = Carbon::parse($shift->end_time);

        $attendanceRecords = [];

        foreach ($months as $monthData) {
            $startDate = Carbon::create($monthData['year'], $monthData['month'], 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            $currentDate = $startDate->copy();

            $this->info("Generating records for {$startDate->format('F Y')}...");

            $monthWorkingDays = 0;
            $monthLateCount = 0;
            $monthEarlyLeaveCount = 0;
            $monthAbsenceDays = 0;
            $monthPerfectDays = 0;

            while ($currentDate->lte($endDate)) {
                // Skip weekends
                if ($currentDate->dayOfWeek === 0 || $currentDate->dayOfWeek === 6) {
                    $currentDate->addDay();
                    continue;
                }

                // Varied attendance patterns:
                // - 60% perfect attendance (on time)
                // - 20% late arrival
                // - 10% early leave
                // - 10% absence

                $random = rand(1, 100);

                if ($random <= 10) {
                    // 10% absence
                    $monthAbsenceDays++;
                    $currentDate->addDay();
                    continue;
                }

                $monthWorkingDays++;

                // Determine attendance pattern
                if ($random <= 70) {
                    // 60% perfect attendance (on time check-in and check-out)
                    $checkInTime = $currentDate->copy()->setTimeFromTimeString($shift->start_time);
                    // Small variation: -5 to +5 minutes (within grace period)
                    $checkInTime->addMinutes(rand(-5, 5));
                    
                    $checkOutTime = $currentDate->copy()->setTimeFromTimeString($shift->end_time);
                    // Small variation: -5 to +10 minutes
                    $checkOutTime->addMinutes(rand(-5, 10));
                    
                    $monthPerfectDays++;
                } elseif ($random <= 90) {
                    // 20% late arrival (but still check out on time)
                    $lateMinutes = rand(16, 90); // After grace period
                    $checkInTime = $currentDate->copy()->setTimeFromTimeString($shift->start_time)->addMinutes($lateMinutes);
                    
                    $checkOutTime = $currentDate->copy()->setTimeFromTimeString($shift->end_time);
                    $checkOutTime->addMinutes(rand(0, 15));
                    
                    $monthLateCount++;
                } else {
                    // 10% early leave (but check in on time)
                    $checkInTime = $currentDate->copy()->setTimeFromTimeString($shift->start_time);
                    $checkInTime->addMinutes(rand(-5, 5));
                    
                    $earlyMinutes = rand(30, 120); // Leave 30-120 minutes early
                    $checkOutTime = $currentDate->copy()->setTimeFromTimeString($shift->end_time)->subMinutes($earlyMinutes);
                    
                    $monthEarlyLeaveCount++;
                }

                // Add check-in record
                $attendanceRecords[] = [
                    'employee_id' => $employee->id,
                    'company_id' => $company->id,
                    'type' => 'in',
                    'datetime' => $checkInTime,
                    'method' => 'manual',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Add check-out record
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

            // Calculate score for this month
            if ($monthWorkingDays > 0) {
                $baseScore = ($monthPerfectDays * 100) + 
                            (($monthWorkingDays - $monthPerfectDays - $monthLateCount - $monthEarlyLeaveCount) * 80);
                
                $penalty = ($monthLateCount * 5) + ($monthEarlyLeaveCount * 5) + ($monthAbsenceDays * 20);
                
                $score = max(0, min(100, ($baseScore - $penalty) / $monthWorkingDays));
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

            // Create performance score
            PerformanceAttendanceScore::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'month' => $startDate->format('Y-m'),
                ],
                [
                    'working_days' => $monthWorkingDays,
                    'late_count' => $monthLateCount,
                    'early_leave_count' => $monthEarlyLeaveCount,
                    'absence_days' => $monthAbsenceDays,
                    'perfect_days' => $monthPerfectDays,
                    'score' => round($score, 2),
                    'status' => $status,
                ]
            );

            $this->info("✓ Created performance score for {$startDate->format('Y-m')}:");
            $this->info("  - Score: " . round($score, 2) . " ({$status})");
            $this->info("  - Working days: {$monthWorkingDays}, Perfect: {$monthPerfectDays}, Late: {$monthLateCount}, Early leave: {$monthEarlyLeaveCount}, Absence: {$monthAbsenceDays}");
        }

        // Insert attendance records in batches
        $this->info("Inserting " . count($attendanceRecords) . " attendance records...");
        foreach (array_chunk($attendanceRecords, 500) as $chunk) {
            AttendanceRecord::insert($chunk);
        }

        $this->info("\n✅ Successfully added second test employee with performance data!");
        $this->info("Employee: {$user->name} ({$user->email})");
        $this->info("Password: password123");
        $this->info("Months: August 2024 and October 2024");

        return 0;
    }
}
