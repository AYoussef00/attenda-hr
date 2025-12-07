<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Company\Employee;
use App\Models\Company\PayrollEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayslipController extends Controller
{
    /**
     * Display the employee payslips.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get employee record
        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->with(['user'])
            ->first();

        if (!$employee) {
            abort(403, 'Employee record not found.');
        }

        // Get all payslips for this employee, grouped by month
        $payslips = PayrollEntry::where('employee_id', $employee->id)
            ->with(['cycle'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($entry) {
                return $entry->cycle->month;
            })
            ->map(function ($entries, $month) {
                return [
                    'month' => $month,
                    'month_formatted' => \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y'),
                    'entries' => $entries->map(function ($entry) {
                        return [
                            'id' => $entry->id,
                            'month' => $entry->cycle->month,
                            'basic_salary' => $entry->basic_salary,
                            'total_allowances' => $entry->total_allowances,
                            'total_overtime_amount' => $entry->total_overtime_amount,
                            'total_deductions' => $entry->total_deductions,
                            'net_salary' => $entry->net_salary,
                            'status' => $entry->status,
                            'created_at' => $entry->created_at->format('Y-m-d'),
                        ];
                    })->values(),
                ];
            })
            ->values();

        return Inertia::render('Employee/Payslips/Index', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->user->name ?? 'N/A',
                'employee_code' => $employee->employee_code ?? 'N/A',
            ],
            'payslips' => $payslips,
            'company' => [
                'id' => $company->id,
                'name' => $company->name ?? 'Company',
            ],
        ]);
    }

    /**
     * Display a specific payslip.
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get employee record
        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->with(['user', 'department'])
            ->first();

        if (!$employee) {
            abort(403, 'Employee record not found.');
        }

        // Get the payslip entry
        $entry = PayrollEntry::where('id', $id)
            ->where('employee_id', $employee->id)
            ->with(['cycle', 'employee.user', 'employee.department'])
            ->firstOrFail();

        return Inertia::render('Employee/Payslips/Show', [
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
                    'month_formatted' => \Carbon\Carbon::createFromFormat('Y-m', $entry->cycle->month)->format('F Y'),
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
                'id' => $company->id,
                'name' => $company->name ?? 'Company',
            ],
        ]);
    }
}

