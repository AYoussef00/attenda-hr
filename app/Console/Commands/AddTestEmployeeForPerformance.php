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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AddTestEmployeeForPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:add-performance-employee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a test employee with attendance records for performance testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to add test employee for performance testing...');

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
            $this->info("Created department: {$department->name}");
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
            $this->info("Created shift: {$shift->name} (09:00 - 17:00)");
        }

        // Check if employee already exists
        $existingEmployee = Employee::where('company_id', $company->id)
            ->whereHas('user', function ($query) {
                $query->where('email', 'ahmed.test@microsoft.com');
            })
            ->first();

        if ($existingEmployee) {
            $this->warn('Employee already exists. Deleting old records...');
            // Delete old attendance records
            AttendanceRecord::where('employee_id', $existingEmployee->id)->delete();
            PerformanceAttendanceScore::where('employee_id', $existingEmployee->id)->delete();
            $employee = $existingEmployee;
            $user = $employee->user;
        } else {
            // Create user
            $user = User::create([
                'company_id' => $company->id,
                'name' => 'Ahmed Test Employee',
                'email' => 'ahmed.test@microsoft.com',
                'password' => Hash::make('password123'),
                'phone' => '+201234567890',
                'role' => 'employee',
                'status' => 'active',
            ]);

            // Create employee
            $employee = Employee::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'employee_code' => 'MSFT-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'position' => 'Software Engineer',
                'department_id' => $department->id,
                'shift_id' => $shift->id,
                'hire_date' => Carbon::now()->subMonths(3),
                'contract_type' => 'full-time',
                'status' => 'active',
            ]);

            $this->info("Created employee: {$user->name} (Code: {$employee->employee_code})");
        }

        // Generate attendance records for the last 2 months
        $startDate = Carbon::now()->subMonths(2)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        $currentDate = $startDate->copy();

        $shiftStart = Carbon::parse($shift->start_time);
        $shiftEnd = Carbon::parse($shift->end_time);

        $attendanceRecords = [];
        $workingDays = 0;
        $lateCount = 0;
        $earlyLeaveCount = 0;
        $absenceDays = 0;
        $perfectDays = 0;

        $this->info("Generating attendance records from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}...");

        while ($currentDate->lte($endDate)) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($currentDate->dayOfWeek === 0 || $currentDate->dayOfWeek === 6) {
                $currentDate->addDay();
                continue;
            }

            // Randomly skip some days (absence) - about 5% chance
            if (rand(1, 100) <= 5) {
                $absenceDays++;
                $currentDate->addDay();
                continue;
            }

            $workingDays++;

            // Determine if late (30% chance)
            $isLate = rand(1, 100) <= 30;
            // Determine if early leave (20% chance)
            $isEarlyLeave = rand(1, 100) <= 20;

            // Check-in time
            if ($isLate) {
                // Late by 10-60 minutes
                $lateMinutes = rand(10, 60);
                $checkInTime = $currentDate->copy()->setTimeFromTimeString($shift->start_time)->addMinutes($lateMinutes);
                $lateCount++;
            } else {
                // On time or early (up to 15 minutes early)
                $earlyMinutes = rand(0, 15);
                $checkInTime = $currentDate->copy()->setTimeFromTimeString($shift->start_time)->subMinutes($earlyMinutes);
            }

            // Check-out time
            if ($isEarlyLeave) {
                // Leave early by 15-90 minutes
                $earlyMinutes = rand(15, 90);
                $checkOutTime = $currentDate->copy()->setTimeFromTimeString($shift->end_time)->subMinutes($earlyMinutes);
                $earlyLeaveCount++;
            } else {
                // On time or slightly late (up to 30 minutes)
                $lateMinutes = rand(0, 30);
                $checkOutTime = $currentDate->copy()->setTimeFromTimeString($shift->end_time)->addMinutes($lateMinutes);
            }

            // Perfect day: on time check-in and on time check-out
            if (!$isLate && !$isEarlyLeave) {
                $perfectDays++;
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

        // Insert attendance records in batches
        $this->info("Inserting " . count($attendanceRecords) . " attendance records...");
        foreach (array_chunk($attendanceRecords, 500) as $chunk) {
            AttendanceRecord::insert($chunk);
        }

        $this->info("✓ Inserted attendance records");
        $this->info("  - Working days: {$workingDays}");
        $this->info("  - Late count: {$lateCount}");
        $this->info("  - Early leave count: {$earlyLeaveCount}");
        $this->info("  - Absence days: {$absenceDays}");
        $this->info("  - Perfect days: {$perfectDays}");

        // Calculate performance scores for each month
        $months = [];
        $monthStart = $startDate->copy()->startOfMonth();
        while ($monthStart->lte($endDate)) {
            $monthEnd = $monthStart->copy()->endOfMonth();
            $months[] = [
                'start' => $monthStart->copy(),
                'end' => $monthEnd->copy(),
                'month' => $monthStart->format('Y-m'),
            ];
            $monthStart->addMonth();
        }

        foreach ($months as $monthData) {
            // Count records for this month
            $monthRecords = AttendanceRecord::where('employee_id', $employee->id)
                ->whereBetween('datetime', [$monthData['start'], $monthData['end']])
                ->get();

            $monthWorkingDays = 0;
            $monthLateCount = 0;
            $monthEarlyLeaveCount = 0;
            $monthAbsenceDays = 0;
            $monthPerfectDays = 0;

            $datesWithCheckIn = [];
            foreach ($monthRecords->where('type', 'in') as $checkIn) {
                $date = $checkIn->datetime->format('Y-m-d');
                if (!in_array($date, $datesWithCheckIn)) {
                    $datesWithCheckIn[] = $date;
                    $monthWorkingDays++;

                    // Check if late
                    $checkInTime = $checkIn->datetime->format('H:i');
                    $shiftStartTime = $shift->start_time;
                    if ($checkInTime > $shiftStartTime) {
                        $checkInCarbon = Carbon::parse($checkIn->datetime->format('Y-m-d') . ' ' . $checkInTime);
                        $shiftStartCarbon = Carbon::parse($checkIn->datetime->format('Y-m-d') . ' ' . $shiftStartTime);
                        if ($checkInCarbon->diffInMinutes($shiftStartCarbon) > $shift->late_grace_minutes) {
                            $monthLateCount++;
                        }
                    }

                    // Check if perfect day (on time check-in and on time check-out)
                    $checkOut = $monthRecords->where('type', 'out')
                        ->where('datetime', '>=', $checkIn->datetime->startOfDay())
                        ->where('datetime', '<=', $checkIn->datetime->endOfDay())
                        ->first();

                    if ($checkOut) {
                        $checkOutTime = $checkOut->datetime->format('H:i');
                        $shiftEndTime = $shift->end_time;
                        $checkInTime = $checkIn->datetime->format('H:i');
                        
                        $isOnTimeCheckIn = $checkInTime <= $shiftStartTime || 
                            Carbon::parse($checkIn->datetime->format('Y-m-d') . ' ' . $checkInTime)
                                ->diffInMinutes(Carbon::parse($checkIn->datetime->format('Y-m-d') . ' ' . $shiftStartTime)) <= $shift->late_grace_minutes;
                        $isOnTimeCheckOut = $checkOutTime >= $shiftEndTime;

                        if ($isOnTimeCheckIn && $isOnTimeCheckOut) {
                            $monthPerfectDays++;
                        }

                        // Check if early leave
                        if ($checkOutTime < $shiftEndTime) {
                            $checkOutCarbon = Carbon::parse($checkOut->datetime->format('Y-m-d') . ' ' . $checkOutTime);
                            $shiftEndCarbon = Carbon::parse($checkOut->datetime->format('Y-m-d') . ' ' . $shiftEndTime);
                            if ($shiftEndCarbon->diffInMinutes($checkOutCarbon) > 15) {
                                $monthEarlyLeaveCount++;
                            }
                        }
                    }
                }
            }

            // Calculate absence days (weekdays in month - working days)
            $weekdaysInMonth = 0;
            $tempDate = $monthData['start']->copy();
            while ($tempDate->lte($monthData['end'])) {
                if ($tempDate->dayOfWeek !== 0 && $tempDate->dayOfWeek !== 6) {
                    $weekdaysInMonth++;
                }
                $tempDate->addDay();
            }
            $monthAbsenceDays = $weekdaysInMonth - $monthWorkingDays;

            // Calculate score (0-100)
            // Base: perfect days = 100, working days with issues = 80, absences = 0
            if ($monthWorkingDays > 0) {
                $baseScore = ($monthPerfectDays * 100) + 
                            (($monthWorkingDays - $monthPerfectDays - $monthLateCount - $monthEarlyLeaveCount) * 80);
                
                // Penalties
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

            // Create or update performance score
            PerformanceAttendanceScore::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'month' => $monthData['month'],
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

            $this->info("✓ Created performance score for {$monthData['month']}:");
            $this->info("  - Score: " . round($score, 2) . " ({$status})");
            $this->info("  - Working days: {$monthWorkingDays}, Perfect: {$monthPerfectDays}, Late: {$monthLateCount}, Early leave: {$monthEarlyLeaveCount}, Absence: {$monthAbsenceDays}");
        }

        $this->info("\n✅ Successfully added test employee with performance data!");
        $this->info("Employee: {$user->name} ({$user->email})");
        $this->info("Password: password123");
        $this->info("You can now check the performance dashboard for this employee.");

        return 0;
    }
}
