<?php

namespace App\Console\Commands;

use App\Models\Admin\Company;
use App\Models\Admin\User;
use App\Models\Company\Employee;
use App\Models\Company\AttendanceRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GenerateTestPayrollData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:generate-test-data {--company-id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate 20 test employees with attendance records from August to October 2025';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->option('company-id');
        $company = Company::find($companyId);

        if (!$company) {
            $this->error("Company with ID {$companyId} not found!");
            return 1;
        }

        $this->info("Generating test data for company: {$company->name}");

        // Delete existing test employees and their data
        $this->info("Cleaning up existing test data...");
        $existingEmployees = Employee::where('company_id', $company->id)
            ->whereHas('user', function($q) {
                $q->where('email', 'like', '%@test.com');
            })
            ->get();

        foreach ($existingEmployees as $emp) {
            // Delete attendance records
            AttendanceRecord::where('employee_id', $emp->id)->delete();
            // Delete user
            if ($emp->user) {
                $emp->user->delete();
            }
            // Delete employee
            $emp->delete();
        }

        $this->info("Deleted " . count($existingEmployees) . " existing test employees");

        // Get or create a shift (default shift)
        $shift = \App\Models\Company\Shift::where('company_id', $company->id)->first();
        if (!$shift) {
            $shift = \App\Models\Company\Shift::create([
                'company_id' => $company->id,
                'name' => 'Default Shift',
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'break_minutes' => 60,
                'late_grace_minutes' => 15,
                'overtime_after' => 8,
            ]);
        }

        // Get or create a department
        $department = \App\Models\Company\Department::where('company_id', $company->id)->first();
        if (!$department) {
            $department = \App\Models\Company\Department::create([
                'company_id' => $company->id,
                'name' => 'General',
            ]);
        }

        // Salary ranges
        $salaryRanges = [
            ['min' => 2000, 'max' => 3000],   // Junior
            ['min' => 3000, 'max' => 5000],   // Mid
            ['min' => 5000, 'max' => 8000],   // Senior
            ['min' => 8000, 'max' => 12000],  // Manager
        ];

        $positions = [
            'Software Developer', 'Senior Developer', 'Project Manager',
            'HR Manager', 'Accountant', 'Sales Representative',
            'Marketing Specialist', 'Designer', 'QA Engineer',
            'DevOps Engineer', 'Business Analyst', 'Data Analyst',
        ];

        $firstNames = [
            'Ahmed', 'Mohamed', 'Ali', 'Hassan', 'Omar', 'Khaled',
            'Youssef', 'Mahmoud', 'Ibrahim', 'Tarek', 'Amr', 'Hany',
            'Sara', 'Fatima', 'Aisha', 'Mariam', 'Nour', 'Layla',
            'Zeinab', 'Dina', 'Rania', 'Nada', 'Heba', 'Yasmin',
        ];

        $lastNames = [
            'Ali', 'Hassan', 'Mohamed', 'Ibrahim', 'Ahmed', 'Omar',
            'Khaled', 'Mahmoud', 'Youssef', 'Tarek', 'Amr', 'Hany',
        ];

        $employees = [];
        $bar = $this->output->createProgressBar(20);
        $bar->start();

        // Create 20 employees
        for ($i = 1; $i <= 20; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $name = "{$firstName} {$lastName}";
            $email = strtolower("{$firstName}.{$lastName}.{$i}@test.com");
            
            // Ensure unique email
            while (User::where('email', $email)->exists()) {
                $email = strtolower("{$firstName}.{$lastName}.{$i}." . rand(1000, 9999) . "@test.com");
            }

            // Random salary
            $salaryRange = $salaryRanges[array_rand($salaryRanges)];
            $basicSalary = rand($salaryRange['min'] * 100, $salaryRange['max'] * 100) / 100;
            
            // Calculate hourly rate (assuming 8 hours/day, 26 days/month)
            $workingHoursPerDay = 8;
            $workingDaysPerMonth = 26;
            $hourlyRate = round($basicSalary / ($workingDaysPerMonth * $workingHoursPerDay), 2);

            // Create user
            $user = User::create([
                'company_id' => $company->id,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password123'),
                'phone' => '+2010' . rand(10000000, 99999999),
                'role' => 'employee',
                'status' => 'active',
            ]);

            // Create employee
            $employee = Employee::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'employee_code' => 'EMP' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'position' => $positions[array_rand($positions)],
                'department_id' => $department->id,
                'shift_id' => $shift->id,
                'hire_date' => Carbon::now()->subMonths(rand(1, 12)),
                'contract_type' => rand(0, 1) ? 'Full-time' : 'Part-time',
                'status' => 'active',
                'basic_salary' => $basicSalary,
                'hourly_rate' => $hourlyRate,
                'overtime_rate' => 1.25,
                'allowances_fixed' => rand(0, 500),
                'deductions_fixed' => rand(0, 200),
                'working_hours_per_day' => $workingHoursPerDay,
                'working_days_per_month' => $workingDaysPerMonth,
                'qr_secret' => Str::random(32),
            ]);

            $employees[] = $employee;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Created 20 employees");

        // Generate attendance records
        $this->info("Generating attendance records...");
        
        $startDate = Carbon::create(2025, 8, 1)->startOfMonth();
        $endDate = Carbon::create(2025, 10, 31)->endOfMonth();
        
        $shiftStart = Carbon::parse($shift->start_time);
        $shiftEnd = Carbon::parse($shift->end_time);
        
        $attendanceBar = $this->output->createProgressBar(count($employees));
        $attendanceBar->start();

        foreach ($employees as $index => $employee) {
            // Determine employee commitment level
            // First 10 employees are committed, last 10 are less committed
            $isCommitted = $index < 10;
            
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate) {
                // Skip weekends (Saturday = 6, Sunday = 0)
                if ($currentDate->dayOfWeek === 0 || $currentDate->dayOfWeek === 6) {
                    $currentDate->addDay();
                    continue;
                }

                // Committed employees: 95% attendance, rarely late
                // Less committed: 70% attendance, often late, sometimes absent
                $attendanceChance = $isCommitted ? 0.95 : 0.70;
                
                if (rand(1, 100) / 100 <= $attendanceChance) {
                    // Employee attended
                    
                    // Check-in time
                    if ($isCommitted) {
                        // Committed: arrive between 8:45 and 9:15 (mostly on time)
                        $checkInHour = 8;
                        $checkInMinute = rand(45, 75); // 8:45 to 9:15
                    } else {
                        // Less committed: arrive between 8:30 and 10:00 (often late)
                        $checkInHour = 8;
                        $checkInMinute = rand(30, 120); // 8:30 to 10:00
                    }
                    
                    if ($checkInMinute >= 60) {
                        $checkInHour += 1;
                        $checkInMinute -= 60;
                    }
                    
                    $checkInTime = $currentDate->copy()
                        ->setTime($checkInHour, $checkInMinute, rand(0, 59));
                    
                    // Check-out time
                    $workDuration = $isCommitted 
                        ? rand(8 * 60, 10 * 60) // 8-10 hours for committed
                        : rand(6 * 60, 8 * 60); // 6-8 hours for less committed
                    
                    $checkOutTime = $checkInTime->copy()->addMinutes($workDuration);
                    
                    // Ensure checkout is after 4 PM at minimum
                    if ($checkOutTime->format('H') < 16) {
                        $checkOutTime = $currentDate->copy()->setTime(16, rand(0, 59), rand(0, 59));
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
                }
                
                $currentDate->addDay();
            }
            
            $attendanceBar->advance();
        }

        $attendanceBar->finish();
        $this->newLine();
        $this->info("Generated attendance records from August to October 2025");
        
        $this->newLine();
        $this->info("✅ Test data generated successfully!");
        $this->info("   - 20 employees created");
        $this->info("   - Attendance records from August to October 2025");
        $this->info("   - First 10 employees are committed (95% attendance)");
        $this->info("   - Last 10 employees are less committed (70% attendance)");
        $this->info("   - Default password for all users: password123");

        return 0;
    }
}
