<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Api\ApiController;
use App\Models\Company\Employee;
use App\Models\Company\PayrollEntry;
use Illuminate\Http\Request;

class PayslipController extends ApiController
{
    protected function resolveEmployee(Request $request): ?Employee
    {
        $user = $request->user();

        if (! $user || ! $user->company || ! $user->isEmployee() || ! $user->isActive()) {
            return null;
        }

        return Employee::where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->first();
    }

    /**
     * List payslips for the authenticated employee.
     */
    public function index(Request $request)
    {
        $employee = $this->resolveEmployee($request);

        if (! $employee || ! $employee->isActive()) {
            return $this->error('Employee not found or inactive.', 403);
        }

        $entries = PayrollEntry::where('employee_id', $employee->id)
            ->with('cycle')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (PayrollEntry $entry) {
                $viewUrl = route('company.payroll.entry.payslip', ['id' => $entry->id]);

                $basicSalary = (float) $entry->basic_salary;
                $allowances = (float) $entry->total_allowances;
                $overtime = (float) $entry->total_overtime_amount;
                $totalEarnings = $basicSalary + $allowances + $overtime;

                $attendanceDeductions = (float) $entry->attendance_deductions;
                $totalDeductions = (float) $entry->total_deductions;

                return [
                    'id' => $entry->id,
                    'date' => $entry->created_at?->toDateString(),
                    'amount' => (float) $entry->net_salary,
                    'month' => $entry->cycle?->month,
                    'view_url' => $viewUrl,
                    'earnings' => [
                        'basic_salary' => $basicSalary,
                        'allowances' => $allowances,
                        'overtime' => $overtime,
                        'total_earnings' => $totalEarnings,
                    ],
                    'deductions' => [
                        'attendance_deductions' => $attendanceDeductions,
                        'total_deductions' => $totalDeductions,
                    ],
                ];
            })
            ->values();

        return $this->success([
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
            ],
            'payslips' => $entries,
        ]);
    }

    /**
     * Get basic info + PDF download URL for a specific payslip.
     *
     * The PDF itself is served via the existing company route that renders the payslip.
     */
    public function show(Request $request, string $id)
    {
        $employee = $this->resolveEmployee($request);

        if (! $employee || ! $employee->isActive()) {
            return $this->error('Employee not found or inactive.', 403);
        }

        $entry = PayrollEntry::where('employee_id', $employee->id)
            ->with('cycle')
            ->findOrFail($id);

        $viewUrl = route('company.payroll.entry.payslip', ['id' => $entry->id]);

        $basicSalary = (float) $entry->basic_salary;
        $allowances = (float) $entry->total_allowances;
        $overtime = (float) $entry->total_overtime_amount;
        $totalEarnings = $basicSalary + $allowances + $overtime;

        $attendanceDeductions = (float) $entry->attendance_deductions;
        $totalDeductions = (float) $entry->total_deductions;

        return $this->success([
            'id' => $entry->id,
            'date' => $entry->created_at?->toDateString(),
            'amount' => (float) $entry->net_salary,
            'month' => $entry->cycle?->month,
            'view_url' => $viewUrl,
            'earnings' => [
                'basic_salary' => $basicSalary,
                'allowances' => $allowances,
                'overtime' => $overtime,
                'total_earnings' => $totalEarnings,
            ],
            'deductions' => [
                'attendance_deductions' => $attendanceDeductions,
                'total_deductions' => $totalDeductions,
            ],
        ]);
    }
}


