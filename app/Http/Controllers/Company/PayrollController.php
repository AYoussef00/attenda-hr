<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Employee;
use App\Models\Company\PayrollCycle;
use App\Models\Company\PayrollEntry;
use App\Models\Company\PayrollSetting;
use App\Models\Company\AttendanceRecord;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PayrollController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Display a listing of payroll cycles
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get all months with attendance records
        $monthsWithAttendance = AttendanceRecord::where('company_id', $company->id)
            ->selectRaw('DATE_FORMAT(datetime, "%Y-%m") as month')
            ->distinct()
            ->orderBy('month', 'desc')
            ->pluck('month');

        // Get existing payroll cycles
        $existingCycles = PayrollCycle::where('company_id', $company->id)
            ->withCount('entries')
            ->get()
            ->keyBy('month');

        // Build cycles array with all months that have attendance
        $cycles = collect($monthsWithAttendance)->map(function ($month) use ($existingCycles, $company) {
            $cycle = $existingCycles->get($month);

            // Count employees who had attendance in this month
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

            $employeesWithAttendance = AttendanceRecord::where('company_id', $company->id)
                ->whereBetween('datetime', [$startDate, $endDate])
                ->groupBy('employee_id')
                ->pluck('employee_id')
                ->count();

            if ($cycle) {
                // Cycle exists
                return [
                    'id' => $cycle->id,
                    'month' => $month,
                    'status' => $cycle->status,
                    'generated_at' => $cycle->generated_at?->format('Y-m-d H:i:s'),
                    'paid_at' => $cycle->paid_at?->format('Y-m-d H:i:s'),
                    'entries_count' => $cycle->entries_count,
                    'created_at' => $cycle->created_at?->format('Y-m-d H:i:s'),
                    'has_cycle' => true,
                    'employees_with_attendance' => $employeesWithAttendance,
                ];
            } else {
                // No cycle exists, but has attendance
                return [
                    'id' => null,
                    'month' => $month,
                    'status' => 'not_generated',
                    'generated_at' => null,
                    'paid_at' => null,
                    'entries_count' => 0,
                    'created_at' => null,
                    'has_cycle' => false,
                    'employees_with_attendance' => $employeesWithAttendance,
                ];
            }
        })->values();

        // Calculate statistics
        $currentMonth = Carbon::now()->format('Y-m');

        // 1. عدد الموظفين النشطين
        $activeEmployeesCount = Employee::where('company_id', $company->id)
            ->where('status', 'active')
            ->count();

        // 2. عدد الدورات الشهرية للرواتب
        $totalCyclesCount = PayrollCycle::where('company_id', $company->id)->count();

        // 3. إجمالي الرواتب المستحقة للشهر الحالي
        $currentMonthCycle = PayrollCycle::where('company_id', $company->id)
            ->where('month', $currentMonth)
            ->with('entries')
            ->first();

        $currentMonthNetSalary = 0;
        if ($currentMonthCycle) {
            $currentMonthNetSalary = $currentMonthCycle->entries()->sum('net_salary');
        }

        // 4. إجمالي البدلات (Fixed + Variable) - من آخر دورة
        $latestCycle = PayrollCycle::where('company_id', $company->id)
            ->with('entries')
            ->orderBy('month', 'desc')
            ->first();

        $totalAllowances = 0;
        if ($latestCycle) {
            $totalAllowances = $latestCycle->entries()->sum('total_allowances');
        }

        // 5. إجمالي الخصومات (ثابتة + يدوية + تأخيرات) - من آخر دورة
        $totalDeductions = 0;
        if ($latestCycle) {
            $totalDeductions = $latestCycle->entries()->sum('total_deductions');
        }

        // 6. إجمالي الأوفر تايم - من آخر دورة
        $totalOvertime = 0;
        if ($latestCycle) {
            $totalOvertime = $latestCycle->entries()->sum('total_overtime_amount');
        }

        return Inertia::render('Company/Payroll/Index', [
            'cycles' => $cycles,
            'statistics' => [
                'active_employees_count' => $activeEmployeesCount,
                'total_cycles_count' => $totalCyclesCount,
                'current_month_net_salary' => $currentMonthNetSalary,
                'total_allowances' => $totalAllowances,
                'total_deductions' => $totalDeductions,
                'total_overtime' => $totalOvertime,
            ],
        ]);
    }

    /**
     * Generate payroll for a specific month
     */
    public function generateCycle(Request $request, string $month)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Validate month format
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return back()->withErrors(['month' => 'Invalid month format. Expected YYYY-MM']);
        }

        try {
            $result = $this->payrollService->generatePayroll($company->id, $month);

            Log::info("Payroll generated by user {$user->id} for company {$company->id}, month {$month}");

            return redirect()
                ->route('company.payroll.cycle', $result['cycle']->id)
                ->with('success', "Payroll generated successfully for {$month}. Processed {$result['summary']['processed_employees']} employees.");
        } catch (\Exception $e) {
            Log::error("Payroll generation failed: " . $e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete and regenerate payroll cycle
     */
    public function regenerateCycle(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $cycle = PayrollCycle::where('company_id', $company->id)
            ->findOrFail($id);

        $month = $cycle->month;

        // Delete the cycle (entries will be deleted via cascade)
        $cycle->delete();

        // Regenerate the cycle
        try {
            $result = $this->payrollService->generatePayroll($company->id, $month);

            Log::info("Payroll cycle regenerated for company {$company->id}, month {$month}");

            return back()->with('success', "Payroll cycle for {$month} has been deleted and regenerated successfully.");
        } catch (\Exception $e) {
            Log::error("Failed to regenerate payroll cycle: " . $e->getMessage());
            return back()->with('error', 'Failed to regenerate payroll cycle: ' . $e->getMessage());
        }
    }

    /**
     * View payroll cycle details
     */
    public function viewCycle(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $cycle = PayrollCycle::where('company_id', $company->id)
            ->with(['entries.employee.user', 'company'])
            ->findOrFail($id);

        $entries = $cycle->entries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'employee_id' => $entry->employee_id,
                'employee_name' => $entry->employee->user->name ?? 'N/A',
                'employee_code' => $entry->employee->employee_code ?? 'N/A',
                'basic_salary' => $entry->basic_salary,
                'total_allowances' => $entry->total_allowances,
                'total_overtime_amount' => $entry->total_overtime_amount,
                'total_deductions' => $entry->total_deductions,
                'attendance_deductions' => $entry->attendance_deductions ?? 0,
                'leave_deductions' => $entry->leave_deductions ?? 0,
                'manual_deductions' => $entry->manual_deductions ?? 0,
                'fixed_deductions' => $entry->fixed_deductions ?? 0,
                'net_salary' => $entry->net_salary,
                'notes' => $entry->notes,
                'status' => $entry->status,
                'created_at' => $entry->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        $summary = [
            'total_employees' => $entries->count(),
            'total_basic_salary' => $entries->sum('basic_salary'),
            'total_allowances' => $entries->sum('total_allowances'),
            'total_overtime' => $entries->sum('total_overtime_amount'),
            'total_deductions' => $entries->sum('total_deductions'),
            'total_net_salary' => $entries->sum('net_salary'),
        ];

        return Inertia::render('Company/Payroll/Cycle', [
            'cycle' => [
                'id' => $cycle->id,
                'month' => $cycle->month,
                'status' => $cycle->status,
                'generated_at' => $cycle->generated_at?->format('Y-m-d H:i:s'),
                'paid_at' => $cycle->paid_at?->format('Y-m-d H:i:s'),
            ],
            'entries' => $entries,
            'summary' => $summary,
        ]);
    }

    /**
     * Approve a payroll entry
     */
    public function approveEntry(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $entry = PayrollEntry::whereHas('employee', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);

        $entry->update(['status' => 'approved']);

        Log::info("Payroll entry {$id} approved by user {$user->id}");

        return back()->with('success', 'Payroll entry approved successfully.');
    }

    /**
     * Mark payroll entry as paid
     */
    public function markPaid(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $entry = PayrollEntry::whereHas('employee', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);

        $entry->update(['status' => 'paid']);

        // Check if all entries in the cycle are paid
        $cycle = $entry->cycle;
        $allPaid = $cycle->entries()->where('status', '!=', 'paid')->count() === 0;

        if ($allPaid) {
            $cycle->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        Log::info("Payroll entry {$id} marked as paid by user {$user->id}");

        return back()->with('success', 'Payroll entry marked as paid.');
    }

    /**
     * Mark entire cycle as paid
     */
    public function markCyclePaid(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $cycle = PayrollCycle::where('company_id', $company->id)
            ->findOrFail($id);

        $cycle->entries()->update(['status' => 'paid']);

        $cycle->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        Log::info("Payroll cycle {$id} marked as paid by user {$user->id}");

        return back()->with('success', 'All payroll entries marked as paid.');
    }

    /**
     * Display payroll settings page
     */
    public function settings(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get or create payroll settings
        $settings = PayrollSetting::firstOrCreate(
            ['company_id' => $company->id],
            [
                'cycle_type' => 'monthly',
                'salary_calculation_method' => 'fixed_salary',
                'overtime_multiplier' => 1.25,
                'currency' => 'USD',
                'default_salary_release_day' => 27,
                'late_deduction_enabled' => true,
                'late_grace_minutes' => 15,
                'late_calculation_unit' => 'minute',
                'absence_deduction_type' => 'full_day',
                'absence_deduction_percentage' => null,
                'absence_termination_days' => null,
                'early_leave_deduction_enabled' => true,
                'missing_punch_handling' => 'deduct',
                'attendance_bonus_enabled' => false,
                'attendance_bonus_type' => null,
                'attendance_bonus_amount' => null,
                'attendance_bonus_condition' => null,
                'attendance_bonus_min_days' => null,
                'overtime_enabled' => true,
                'overtime_requires_approval' => false,
                'overtime_normal_rate' => 1.25,
                'overtime_weekend_rate' => 1.5,
                'overtime_holiday_rate' => 2.0,
                'overtime_max_per_day' => null,
                'overtime_max_per_month' => null,
            ]
        );

        return Inertia::render('Company/Payroll/Settings', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name ?? 'Company',
            ],
            'settings' => $settings,
        ]);
    }

    /**
     * Update payroll settings
     */
    public function updateSettings(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $validated = $request->validate([
            // General Settings
            'cycle_type' => ['required', 'in:monthly,bi-weekly,weekly'],
            'salary_calculation_method' => ['required', 'in:fixed_salary,daily_rate,hourly_rate'],
            'overtime_multiplier' => ['required', 'numeric', 'min:1', 'max:3'],
            'currency' => ['required', 'string', 'max:10'],
            'default_salary_release_day' => ['required', 'integer', 'min:1', 'max:31'],
            // Attendance & Deduction Settings
            'late_deduction_enabled' => ['boolean'],
            'late_grace_minutes' => ['required', 'integer', 'min:0'],
            'late_calculation_unit' => ['required', 'in:hour,minute'],
            'absence_deduction_type' => ['required', 'in:full_day,percentage'],
            'absence_deduction_percentage' => ['nullable', 'numeric', 'min:0', 'max:100', 'required_if:absence_deduction_type,percentage'],
            'absence_termination_days' => ['nullable', 'integer', 'min:1'],
            'early_leave_deduction_enabled' => ['boolean'],
            'missing_punch_handling' => ['required', 'in:deduct,ignore'],
            // Attendance Bonus Settings
            'attendance_bonus_enabled' => ['boolean'],
            'attendance_bonus_type' => ['nullable', 'in:fixed_amount,percentage,per_day', 'required_if:attendance_bonus_enabled,true'],
            'attendance_bonus_amount' => ['nullable', 'numeric', 'min:0', 'required_if:attendance_bonus_enabled,true'],
            'attendance_bonus_condition' => ['nullable', 'in:perfect_attendance,no_late,no_absence,custom_days', 'required_if:attendance_bonus_enabled,true'],
            'attendance_bonus_min_days' => ['nullable', 'integer', 'min:1', 'required_if:attendance_bonus_condition,custom_days'],
            // Overtime Settings
            'overtime_enabled' => ['boolean'],
            'overtime_requires_approval' => ['boolean'],
            'overtime_normal_rate' => ['required', 'numeric', 'min:1', 'max:3'],
            'overtime_weekend_rate' => ['required', 'numeric', 'min:1', 'max:3'],
            'overtime_holiday_rate' => ['required', 'numeric', 'min:1', 'max:3'],
            'overtime_max_per_day' => ['nullable', 'integer', 'min:1'],
            'overtime_max_per_month' => ['nullable', 'integer', 'min:1'],
        ]);

        $settings = PayrollSetting::updateOrCreate(
            ['company_id' => $company->id],
            $validated
        );

        return back()->with('success', 'Payroll settings updated successfully.');
    }

    /**
     * View deductions breakdown for a payroll cycle
     */
    public function viewDeductions(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;
        $employeeId = $request->get('employee_id');

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $cycle = PayrollCycle::where('company_id', $company->id)
            ->with(['entries.employee.user', 'company'])
            ->findOrFail($id);

        $entries = $cycle->entries
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->map(function ($entry) {
                $attendanceDetails = $this->buildAttendanceDeductionDetails($entry);

                return [
                    'id' => $entry->id,
                    'employee_id' => $entry->employee_id,
                    'employee_name' => $entry->employee->user->name ?? 'N/A',
                    'employee_code' => $entry->employee->employee_code ?? 'N/A',
                    'total_deductions' => $entry->total_deductions,
                    'attendance_deductions' => $entry->attendance_deductions ?? 0,
                    'leave_deductions' => $entry->leave_deductions ?? 0,
                    'manual_deductions' => $entry->manual_deductions ?? 0,
                    'fixed_deductions' => $entry->fixed_deductions ?? 0,
                    'attendance_details' => $attendanceDetails,
                ];
            })->values();

        // Convert to plain array for Inertia to avoid any collection serialization quirks
        $entriesArray = $entries->values()->toArray();

        $summary = [
            'total_attendance_deductions' => collect($entriesArray)->sum('attendance_deductions'),
            'total_leave_deductions' => collect($entriesArray)->sum('leave_deductions'),
            'total_manual_deductions' => collect($entriesArray)->sum('manual_deductions'),
            'total_fixed_deductions' => collect($entriesArray)->sum('fixed_deductions'),
            'total_deductions' => collect($entriesArray)->sum('total_deductions'),
        ];

        return Inertia::render('Company/Payroll/Deductions', [
            'cycle' => [
                'id' => $cycle->id,
                'month' => $cycle->month,
            ],
            'company' => [
                'name' => $company->name ?? 'Company',
            ],
            'entries' => $entriesArray,
            'summary' => $summary,
            'filters' => [
                'employee_id' => $employeeId,
            ],
        ]);
    }

    /**
     * Build per-day attendance deduction details for a payroll entry
     */
    protected function buildAttendanceDeductionDetails(\App\Models\Company\PayrollEntry $entry): array
    {
        $employee = $entry->employee;
        $cycle = $entry->cycle;

        if (!$employee || !$cycle) {
            return [];
        }

        $settings = \App\Models\Company\PayrollSetting::where('company_id', $employee->company_id)->first();
        $shift = $employee->shift;

        $workingHoursPerDay = $employee->working_hours_per_day ?? 8;
        $workingDaysPerMonth = $employee->working_days_per_month ?? 26;
        $hourlyRate = $employee->hourly_rate;
        if ($hourlyRate === null && $employee->basic_salary) {
            $hourlyRate = $employee->basic_salary / ($workingDaysPerMonth * $workingHoursPerDay);
        }
        if ($hourlyRate === null) {
            $hourlyRate = 0; // fallback so we still return details even if salary not set
        }

        $startDate = Carbon::createFromFormat('Y-m', $cycle->month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $cycle->month)->endOfMonth();

        $shiftStartTime = $shift && $shift->start_time ? Carbon::parse($shift->start_time) : Carbon::parse('09:00');
        $shiftEndTime = $shift && $shift->end_time ? Carbon::parse($shift->end_time) : Carbon::parse('17:00');
        $lateGraceMinutes = $settings->late_grace_minutes ?? ($shift->late_grace_minutes ?? 0);

        $attendanceRecords = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('datetime', [$startDate, $endDate])
            ->orderBy('datetime')
            ->get()
            ->groupBy(function ($record) {
                return $record->datetime->format('Y-m-d');
            });

        $details = [];

        foreach ($attendanceRecords as $date => $records) {
            $checkIn = $records->where('type', 'in')->first();
            $checkOut = $records->where('type', 'out')->last();

            $lateMinutes = 0;
            $earlyMinutes = 0;
            $deduction = 0;
            $notes = [];

            if ($checkIn) {
                $checkInTime = Carbon::parse($checkIn->datetime);
                $expectedStart = Carbon::parse($date . ' ' . $shiftStartTime->format('H:i:s'));
                $graceEndTime = $expectedStart->copy()->addMinutes($lateGraceMinutes);

                if ($checkInTime->gt($graceEndTime)) {
                    $lateMinutes = $expectedStart->diffInMinutes($checkInTime, false);
                    $deductibleMinutes = $lateMinutes - $lateGraceMinutes;
                    if ($deductibleMinutes > 0) {
                        if ($settings && $settings->late_calculation_unit === 'hour') {
                            $deductibleHours = ceil($deductibleMinutes / 60);
                            $deduction += $deductibleHours * $hourlyRate;
                        } else {
                            $deductibleHours = $deductibleMinutes / 60;
                            $deduction += $deductibleHours * $hourlyRate;
                        }
                        $notes[] = "Late by {$lateMinutes} min (grace {$lateGraceMinutes} min)";
                    }
                }
            }

            if ($checkOut) {
                $checkOutTime = Carbon::parse($checkOut->datetime);
                $expectedEnd = Carbon::parse($date . ' ' . $shiftEndTime->format('H:i:s'));

                if ($checkOutTime->lt($expectedEnd)) {
                    $earlyMinutes = $expectedEnd->diffInMinutes($checkOutTime);
                    if ($earlyMinutes > 15) {
                        $earlyHours = $earlyMinutes / 60;
                        $deduction += $earlyHours * $hourlyRate;
                        $notes[] = "Early leave by {$earlyMinutes} min";
                    }
                }
            }

            if ($lateMinutes || $earlyMinutes || $deduction > 0) {
                $details[] = [
                    'date' => $date,
                    'check_in' => $checkIn ? Carbon::parse($checkIn->datetime)->format('H:i') : null,
                    'check_out' => $checkOut ? Carbon::parse($checkOut->datetime)->format('H:i') : null,
                    'late_minutes' => $lateMinutes ?: null,
                    'early_minutes' => $earlyMinutes ?: null,
                    'deduction' => round($deduction, 2),
                    'notes' => $notes,
                ];
            }
        }

        return $details;
    }

    /**
     * Export payslip as PDF
     */
    public function exportPayslip(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $entry = PayrollEntry::whereHas('employee', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->with(['employee.user', 'employee.department', 'cycle'])
            ->findOrFail($id);

        // For now, return JSON data. PDF generation can be added later with a library like DomPDF
        return Inertia::render('Company/Payroll/Payslip', [
            'entry' => [
                'id' => $entry->id,
                'employee' => [
                    'id' => $entry->employee->id,
                    'name' => $entry->employee->user->name ?? 'N/A',
                    'employee_code' => $entry->employee->employee_code ?? 'N/A',
                    'department' => $entry->employee->department->name ?? 'N/A',
                ],
                'cycle' => [
                    'month' => $entry->cycle->month,
                ],
                'basic_salary' => $entry->basic_salary,
                'total_allowances' => $entry->total_allowances,
                'total_overtime_amount' => $entry->total_overtime_amount,
                'total_deductions' => $entry->total_deductions,
                'attendance_deductions' => $entry->attendance_deductions ?? 0,
                'leave_deductions' => $entry->leave_deductions ?? 0,
                'manual_deductions' => $entry->manual_deductions ?? 0,
                'fixed_deductions' => $entry->fixed_deductions ?? 0,
                'net_salary' => $entry->net_salary,
                'notes' => $entry->notes,
                'status' => $entry->status,
            ],
            'company' => [
                'name' => $company->name ?? 'Company',
            ],
        ]);
    }
}

