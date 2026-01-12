<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Employee;
use App\Models\Company\Department;
use App\Models\Company\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $employees = Employee::where('company_id', $company->id)
            ->with(['user', 'department', 'shift'])
            ->latest('created_at')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->user->name ?? 'N/A',
                    'email' => $employee->user->email ?? 'N/A',
                    'phone' => $employee->user->phone ?? 'N/A',
                    'employee_code' => $employee->employee_code,
                    'position' => $employee->position,
                    'department' => $employee->department->name ?? 'N/A',
                    'shift' => $employee->shift->name ?? 'N/A',
                    'hire_date' => $employee->hire_date?->format('Y-m-d'),
                    'contract_type' => $employee->contract_type,
                    'status' => $employee->status,
                ];
            })
            ->values()
            ->toArray();

        // Get subscription info for display
        $activeSubscription = $company->activeSubscription();
        $currentEmployeesCount = count($employees);
        $maxEmployees = null;
        $planName = null;

        if ($activeSubscription && $activeSubscription->plan) {
            $maxEmployees = $activeSubscription->plan->max_employees;
            $planName = $activeSubscription->plan->name;
        }

        return Inertia::render('Company/Employees/Index', [
            'employees' => $employees,
            'subscription_info' => [
                'current_employees' => $currentEmployeesCount,
                'max_employees' => $maxEmployees,
                'plan_name' => $planName,
                'can_add_employee' => $maxEmployees === null || $currentEmployeesCount < $maxEmployees,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $departments = Department::where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $shifts = Shift::where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get subscription info
        $activeSubscription = $company->activeSubscription();
        $currentEmployeesCount = Employee::where('company_id', $company->id)->count();
        $maxEmployees = null;
        $planName = null;

        if ($activeSubscription && $activeSubscription->plan) {
            $maxEmployees = $activeSubscription->plan->max_employees;
            $planName = $activeSubscription->plan->name;
        }

        return Inertia::render('Company/Employees/Create', [
            'departments' => $departments,
            'shifts' => $shifts,
            'subscription_info' => [
                'current_employees' => $currentEmployeesCount,
                'max_employees' => $maxEmployees,
                'plan_name' => $planName,
                'can_add_employee' => $maxEmployees === null || $currentEmployeesCount < $maxEmployees,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Check subscription and employee limit
        $activeSubscription = $company->activeSubscription();
        $currentEmployeesCount = Employee::where('company_id', $company->id)->count();
        
        if ($activeSubscription && $activeSubscription->plan) {
            $maxEmployees = $activeSubscription->plan->max_employees;
            
            if ($currentEmployeesCount >= $maxEmployees) {
                return back()->withErrors([
                    'employee_limit' => "You have reached the maximum number of employees ($maxEmployees) allowed by your subscription plan. Please upgrade your plan to add more employees.",
                ])->withInput();
            }
        } else {
            // If no active subscription, check if company has any subscription
            $anySubscription = $company->subscriptions()->latest()->first();
            if ($anySubscription && $anySubscription->plan) {
                // Subscription exists but might be expired
                $maxEmployees = $anySubscription->plan->max_employees;
                if ($currentEmployeesCount >= $maxEmployees) {
                    return back()->withErrors([
                        'employee_limit' => "You have reached the maximum number of employees ($maxEmployees) allowed by your subscription plan. Please renew or upgrade your subscription to add more employees.",
                    ])->withInput();
                }
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:255'],
            'employee_code' => ['required', 'string', 'max:255', 'unique:employees,employee_code'],
            'national_id' => ['required', 'string', 'max:255', 'unique:employees,national_id'],
            'position' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'hire_date' => ['required', 'date'],
            'contract_type' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,terminated'],
        ]);

        // Create user first
        $newUser = \App\Models\User::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => 'employee',
            'status' => 'active',
        ]);

        // Create employee
        Employee::create([
            'user_id' => $newUser->id,
            'company_id' => $company->id,
            'employee_code' => $validated['employee_code'],
            'national_id' => $validated['national_id'],
            'position' => $validated['position'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'shift_id' => $validated['shift_id'] ?? null,
            'hire_date' => $validated['hire_date'],
            'contract_type' => $validated['contract_type'] ?? null,
            'status' => $validated['status'],
            'qr_secret' => Str::random(32),
        ]);

        return redirect()->route('company.employees.index')
            ->with('success', 'Employee created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $employee = Employee::where('company_id', $company->id)
            ->with(['user', 'department', 'shift', 'documents'])
            ->findOrFail($id);

        // Get employee details
        $employeeData = [
            'id' => $employee->id,
            'name' => $employee->user->name ?? 'N/A',
            'email' => $employee->user->email ?? 'N/A',
            'phone' => $employee->user->phone ?? 'N/A',
            'employee_code' => $employee->employee_code,
            'position' => $employee->position,
            'department' => $employee->department->name ?? 'N/A',
            'department_id' => $employee->department_id,
            'shift' => $employee->shift->name ?? 'N/A',
            'shift_id' => $employee->shift_id,
            'hire_date' => $employee->hire_date?->format('Y-m-d'),
            'contract_type' => $employee->contract_type,
            'status' => $employee->status,
            'basic_salary' => $employee->basic_salary,
            'hourly_rate' => $employee->hourly_rate,
            'overtime_rate' => $employee->overtime_rate,
            'allowances_fixed' => $employee->allowances_fixed,
            'deductions_fixed' => $employee->deductions_fixed,
            'working_hours_per_day' => $employee->working_hours_per_day,
            'working_days_per_month' => $employee->working_days_per_month,
            'created_at' => $employee->created_at?->format('Y-m-d H:i:s'),
        ];

        // Get performance history (only completed months)
        $currentMonth = now()->format('Y-m');
        $performanceHistory = \App\Models\Company\PerformanceAttendanceScore::where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->where('month', '<', $currentMonth) // Only completed months
            ->where('working_days', '>', 0) // Only months with actual attendance
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($score) {
                $workingDays = $score->working_days ?? 0;
                $finalScore = $score->score;
                $dailyScore = $workingDays > 0 && $finalScore !== null
                    ? round($finalScore / $workingDays, 2)
                    : null;

                return [
                    'month' => $score->month,
                    'working_days' => $workingDays,
                    'late_count' => $score->late_count ?? 0,
                    'early_leave_count' => $score->early_leave_count ?? 0,
                    'absence_days' => $score->absence_days ?? 0,
                    'perfect_days' => $score->perfect_days ?? 0,
                    'score' => $finalScore,
                    'daily_score' => $dailyScore,
                    'status' => $score->status,
                ];
            })
            ->values()
            ->toArray();

        // Get employee documents
        $documents = $employee->documents()
            ->with(['uploadedBy', 'documentType'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($document) {
                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'document_type_id' => $document->document_type_id,
                    'type' => $document->documentType ? [
                        'id' => $document->documentType->id,
                        'name_ar' => $document->documentType->name_ar,
                        'name_en' => $document->documentType->name_en,
                        'slug' => $document->documentType->slug,
                    ] : null,
                    'file_path' => $document->file_path,
                    'file_type' => $document->file_type,
                    'issued_date' => $document->issued_date?->format('Y-m-d'),
                    'expiry_date' => $document->expiry_date?->format('Y-m-d'),
                    'uploaded_by' => $document->uploaded_by,
                    'uploaded_by_name' => $document->uploadedBy->name ?? 'N/A',
                    'note' => $document->note,
                    'status' => $document->status,
                    'created_at' => $document->created_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->values()
            ->toArray();

        // Get all available document types for this company (default + company specific)
        $availableDocumentTypes = \App\Models\Company\DocumentType::forCompany($company->id)
            ->orderBy('order')
            ->get()
            ->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name_ar' => $type->name_ar,
                    'name_en' => $type->name_en,
                    'slug' => $type->slug,
                    'is_default' => $type->is_default,
                ];
            })
            ->values()
            ->toArray();

        // Get document type IDs that the employee already has
        $employeeDocumentTypeIds = collect($documents)
            ->pluck('document_type_id')
            ->filter()
            ->unique()
            ->toArray();

        // Get missing document types (types that employee doesn't have)
        $missingDocumentTypes = collect($availableDocumentTypes)
            ->filter(function ($type) use ($employeeDocumentTypeIds) {
                return !in_array($type['id'], $employeeDocumentTypeIds);
            })
            ->map(function ($type) use ($employee) {
                // Check if reminder was sent today for this document type
                $todayReminder = \App\Models\Company\Notification::where('employee_id', $employee->id)
                    ->where('document_type_id', $type['id'])
                    ->where('type', 'document')
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                return [
                    ...$type,
                    'reminder_sent_today' => $todayReminder,
                ];
            })
            ->values()
            ->toArray();

        // Get payroll entries (payslips) for this employee
        $payslips = \App\Models\Company\PayrollEntry::where('employee_id', $employee->id)
            ->with(['cycle'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'month' => $entry->cycle->month ?? 'N/A',
                    'basic_salary' => $entry->basic_salary,
                    'total_allowances' => $entry->total_allowances,
                    'total_overtime_amount' => $entry->total_overtime_amount,
                    'total_deductions' => $entry->total_deductions,
                    'net_salary' => $entry->net_salary,
                    'status' => $entry->status,
                    'created_at' => $entry->created_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Company/Employees/Show', [
            'employee' => $employeeData,
            'performanceHistory' => $performanceHistory,
            'documents' => $documents,
            'missingDocumentTypes' => $missingDocumentTypes,
            'payslips' => $payslips,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = request()->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $employee = Employee::where('company_id', $company->id)
            ->with(['user', 'department', 'shift'])
            ->findOrFail($id);

        $departments = Department::where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $shifts = Shift::where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Company/Employees/Edit', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->user->name ?? '',
                'email' => $employee->user->email ?? '',
                'phone' => $employee->user->phone ?? '',
                'employee_code' => $employee->employee_code,
                'national_id' => $employee->national_id,
                'position' => $employee->position,
                'department_id' => $employee->department_id,
                'shift_id' => $employee->shift_id,
                'hire_date' => $employee->hire_date?->format('Y-m-d'),
                'contract_type' => $employee->contract_type,
                'status' => $employee->status,
            ],
            'departments' => $departments,
            'shifts' => $shifts,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $employee = Employee::where('company_id', $company->id)
            ->with('user')
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . ($employee->user?->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:255'],
            'employee_code' => ['required', 'string', 'max:255', 'unique:employees,employee_code,' . $employee->id],
            'national_id' => ['required', 'string', 'max:255', 'unique:employees,national_id,' . $employee->id],
            'position' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'hire_date' => ['required', 'date'],
            'contract_type' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,terminated'],
        ]);

        // Update linked user
        $employeeUser = $employee->user;
        if ($employeeUser) {
            $employeeUser->name = $validated['name'];
            $employeeUser->email = $validated['email'];
            $employeeUser->phone = $validated['phone'] ?? null;
            if (!empty($validated['password'])) {
                $employeeUser->password = Hash::make($validated['password']);
            }
            $employeeUser->save();
        }

        // Update employee
        $employee->update([
            'employee_code' => $validated['employee_code'],
            'national_id' => $validated['national_id'],
            'position' => $validated['position'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'shift_id' => $validated['shift_id'] ?? null,
            'hire_date' => $validated['hire_date'],
            'contract_type' => $validated['contract_type'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('company.employees.show', $employee->id)
            ->with('success', 'Employee updated successfully.');
    }

    /**
     * Update employee salary settings
     */
    public function updateSalarySettings(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $employee = Employee::where('company_id', $company->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0'],
            'allowances_fixed' => ['nullable', 'array'],
            'allowances_fixed.*.type' => ['nullable', 'string', 'max:255'],
            'allowances_fixed.*.amount' => ['nullable', 'numeric', 'min:0'],
            'deductions_fixed' => ['nullable', 'array'],
            'deductions_fixed.*.type' => ['nullable', 'string', 'max:255'],
            'deductions_fixed.*.reason' => ['nullable', 'string', 'max:500'],
            'deductions_fixed.*.amount' => ['nullable', 'numeric', 'min:0'],
            'working_hours_per_day' => ['nullable', 'integer', 'min:1', 'max:24'],
            'working_days_per_month' => ['nullable', 'integer', 'min:1', 'max:31'],
        ]);

        // Filter out empty allowances
        if (isset($validated['allowances_fixed']) && is_array($validated['allowances_fixed'])) {
            $validated['allowances_fixed'] = array_values(array_filter($validated['allowances_fixed'], function ($allowance) {
                return !empty($allowance['type']) && isset($allowance['amount']) && $allowance['amount'] > 0;
            }));
            
            if (empty($validated['allowances_fixed'])) {
                $validated['allowances_fixed'] = null;
            }
        }

        // Filter out empty deductions
        if (isset($validated['deductions_fixed']) && is_array($validated['deductions_fixed'])) {
            $validated['deductions_fixed'] = array_values(array_filter($validated['deductions_fixed'], function ($deduction) {
                return !empty($deduction['type']) && isset($deduction['amount']) && $deduction['amount'] > 0;
            }));
            
            if (empty($validated['deductions_fixed'])) {
                $validated['deductions_fixed'] = null;
            }
        }

        $employee->update($validated);

        return back()->with('success', 'Salary settings updated successfully.');
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $employee = Employee::with(['user', 'attendanceRecords', 'leaveRequests'])
            ->where('company_id', $company->id)
            ->findOrFail($id);

        DB::transaction(function () use ($employee) {
            // Delete related attendance and leave records
            $employee->attendanceRecords()->delete();
            $employee->leaveRequests()->delete();

            // Delete employee record
            $user = $employee->user;
            $employee->delete();

            // Delete linked user account if exists
            if ($user) {
                $user->delete();
            }
        });

        return redirect()
            ->route('company.employees.index')
            ->with('success', 'Employee deleted successfully.');
    }

    /**
     * Send a reminder notification to employee about missing document
     */
    public function remindDocument(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $employee = Employee::where('company_id', $company->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'document_type_id' => ['required', 'exists:document_types,id'],
        ]);

        // Check if reminder was already sent today for this document type
        $todayReminder = \App\Models\Company\Notification::where('employee_id', $employee->id)
            ->where('document_type_id', $validated['document_type_id'])
            ->where('type', 'document')
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($todayReminder) {
            return back()->with('error', 'Reminder already sent today for this document type. Please try again tomorrow.');
        }

        $documentType = \App\Models\Company\DocumentType::findOrFail($validated['document_type_id']);

        // Create notification
        \App\Models\Company\Notification::create([
            'employee_id' => $employee->id,
            'company_id' => $company->id,
            'title' => 'Document Required',
            'message' => 'Please upload your ' . $documentType->name_en . ' document',
            'type' => 'document',
            'document_type_id' => $documentType->id,
            'read' => false,
            'created_by' => $user->id,
        ]);

        return back()->with('success', 'Reminder sent successfully to ' . ($employee->user->name ?? 'employee') . '.');
    }
}
