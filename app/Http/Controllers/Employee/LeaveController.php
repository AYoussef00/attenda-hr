<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Company\Employee;
use App\Models\Company\LeaveRequest;
use App\Models\Company\LeaveType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class LeaveController extends Controller
{
    /**
     * Display the employee leave requests.
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
            ->with(['department', 'shift', 'user'])
            ->first();

        if (!$employee) {
            abort(403, 'Employee record not found.');
        }

        // Get filter parameters
        $status = $request->get('status'); // 'pending', 'approved', 'rejected'

        $query = LeaveRequest::where('employee_id', $employee->id)
            ->with(['leaveType', 'approver']);

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        $leaveRequests = $query
            ->latest('created_at')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'leave_type' => $request->leaveType->name ?? 'N/A',
                    'start_date' => $request->start_date->format('Y-m-d'),
                    'end_date' => $request->end_date->format('Y-m-d'),
                    'start_date_formatted' => $request->start_date->format('F d, Y'),
                    'end_date_formatted' => $request->end_date->format('F d, Y'),
                    'days' => $request->days,
                    'status' => $request->status,
                    'note' => $request->note,
                    'approved_by' => $request->approver ? [
                        'name' => $request->approver->name,
                        'email' => $request->approver->email,
                    ] : null,
                    'created_at' => $request->created_at->format('Y-m-d H:i:s'),
                    'created_at_formatted' => $request->created_at->diffForHumans(),
                ];
            })
            ->values()
            ->toArray();

        // Get statistics
        $stats = [
            'total' => LeaveRequest::where('employee_id', $employee->id)->count(),
            'pending' => LeaveRequest::where('employee_id', $employee->id)->where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('employee_id', $employee->id)->where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('employee_id', $employee->id)->where('status', 'rejected')->count(),
        ];

        // Leave balances (Annual / Vacation & Sick) for current year
        $currentYear = now()->year;
        $leaveBalances = [];

        $leaveTypes = LeaveType::where('company_id', $company->id)
            ->get(['id', 'name', 'yearly_balance']);

        $annualType = $leaveTypes->first(function ($type) {
            return strtolower($type->name) === 'annual leave / vacation';
        });

        $sickType = $leaveTypes->first(function ($type) {
            return strtolower($type->name) === 'sick leave';
        });

        if ($annualType) {
            $usedAnnual = LeaveRequest::where('employee_id', $employee->id)
                ->where('leave_type_id', $annualType->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $currentYear)
                ->sum('days');

            $totalAnnual = $annualType->yearly_balance ?? 0;
            $leaveBalances[] = [
                'key' => 'annual',
                'name' => $annualType->name,
                'total' => $totalAnnual,
                'used' => $usedAnnual,
                'remaining' => max(0, $totalAnnual - $usedAnnual),
            ];
        }

        if ($sickType) {
            $usedSick = LeaveRequest::where('employee_id', $employee->id)
                ->where('leave_type_id', $sickType->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $currentYear)
                ->sum('days');

            $totalSick = $sickType->yearly_balance ?? 0;
            $leaveBalances[] = [
                'key' => 'sick',
                'name' => $sickType->name,
                'total' => $totalSick,
                'used' => $usedSick,
                'remaining' => max(0, $totalSick - $usedSick),
            ];
        }

        return Inertia::render('Employee/Leaves/Index', [
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'name' => $employee->user->name ?? 'N/A',
                'position' => $employee->position,
                'department' => $employee->department->name ?? 'N/A',
                'shift' => $employee->shift->name ?? 'N/A',
            ],
            'leave_requests' => $leaveRequests,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
            ],
            'leave_balances' => $leaveBalances,
        ]);
    }

    /**
     * Show the form for creating a new leave request.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get employee record
        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->with(['department', 'shift', 'user'])
            ->first();

        if (!$employee) {
            abort(403, 'Employee record not found.');
        }

        // Get available leave types for the company
        $leaveTypesCollection = LeaveType::where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'yearly_balance']);

        $leaveTypes = $leaveTypesCollection
            ->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description,
                    'yearly_balance' => $type->yearly_balance,
                ];
            })
            ->values()
            ->toArray();

        // Calculate leave balances for specific types (Annual/Vacation & Sick)
        $currentYear = now()->year;
        $leaveBalances = [];

        $annualType = $leaveTypesCollection->first(function ($type) {
            return strtolower($type->name) === 'annual leave / vacation';
        });

        $sickType = $leaveTypesCollection->first(function ($type) {
            return strtolower($type->name) === 'sick leave';
        });

        if ($annualType) {
            $usedAnnual = LeaveRequest::where('employee_id', $employee->id)
                ->where('leave_type_id', $annualType->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $currentYear)
                ->sum('days');

            $totalAnnual = $annualType->yearly_balance ?? 0;
            $leaveBalances[] = [
                'key' => 'annual',
                'name' => $annualType->name,
                'total' => $totalAnnual,
                'used' => $usedAnnual,
                'remaining' => max(0, $totalAnnual - $usedAnnual),
            ];
        }

        if ($sickType) {
            $usedSick = LeaveRequest::where('employee_id', $employee->id)
                ->where('leave_type_id', $sickType->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $currentYear)
                ->sum('days');

            $totalSick = $sickType->yearly_balance ?? 0;
            $leaveBalances[] = [
                'key' => 'sick',
                'name' => $sickType->name,
                'total' => $totalSick,
                'used' => $usedSick,
                'remaining' => max(0, $totalSick - $usedSick),
            ];
        }

        return Inertia::render('Employee/Leaves/Create', [
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'name' => $employee->user->name ?? 'N/A',
                'position' => $employee->position,
                'department' => $employee->department->name ?? 'N/A',
                'shift' => $employee->shift->name ?? 'N/A',
            ],
            'leave_types' => $leaveTypes,
            'leave_balances' => $leaveBalances,
        ]);
    }

    /**
     * Store a newly created leave request.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get employee record
        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->first();

        if (!$employee) {
            abort(403, 'Employee record not found.');
        }

        $validated = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'medical_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        // Calculate days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $days = $startDate->diffInDays($endDate) + 1; // Include both start and end dates

        // Check if leave type belongs to company
        $leaveType = LeaveType::where('id', $validated['leave_type_id'])
            ->where('company_id', $company->id)
            ->first();

        if (!$leaveType) {
            return back()->withErrors([
                'leave_type_id' => 'Invalid leave type selected.',
            ])->withInput();
        }

        // Restrict annual leave until employee completes 1 year from hire date
        if ($leaveType && strtolower($leaveType->name) === 'annual leave / vacation') {
            $hireDate = $employee->hire_date;
            $oneYearAgo = now()->subYear();

            if (! $hireDate || $hireDate->gt($oneYearAgo)) {
                return back()->withErrors([
                    'leave_type_id' => 'You can request Annual Leave / Vacation only after completing one year of service.',
                ])->withInput();
            }
        }

        // Require medical certificate if leave type is Sick Leave or Maternity/Paternity Leave
        $leaveName = strtolower($leaveType->name);
        if (in_array($leaveName, ['sick leave', 'maternity/paternity leave'], true) && ! $request->hasFile('medical_certificate')) {
            return back()->withErrors([
                'medical_certificate' => 'Medical certificate is required for this leave type.',
            ])->withInput();
        }

        // Check for overlapping approved leave requests
        // Overlap occurs when: existing_start <= new_end AND existing_end >= new_start
        $overlappingLeaves = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $endDate->toDateString())
            ->where('end_date', '>=', $startDate->toDateString())
            ->with('leaveType')
            ->get();

        if ($overlappingLeaves->count() > 0) {
            $overlappingInfo = $overlappingLeaves->map(function ($leave) {
                return $leave->leaveType->name . ': ' . $leave->start_date->format('Y-m-d') . ' to ' . $leave->end_date->format('Y-m-d');
            })->implode('; ');

            return back()->withErrors([
                'start_date' => 'You already have an approved leave request that overlaps with the selected dates. Overlapping dates: ' . $overlappingInfo,
                'end_date' => 'These dates overlap with an existing approved leave request.',
            ])->withInput();
        }

        // Handle medical certificate upload
        $medicalCertificatePath = null;
        if ($request->hasFile('medical_certificate')) {
            $medicalCertificatePath = $request->file('medical_certificate')
                ->store('employee_medical_certificates', 'public');
        }

        // Create leave request
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days' => $days,
            'status' => 'pending',
            'note' => $validated['note'] ?? null,
            'medical_certificate_path' => $medicalCertificatePath,
        ]);

        return redirect()->route('employee.leaves.index')
            ->with('success', 'Leave request submitted successfully.');
    }
}
