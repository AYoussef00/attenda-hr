<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\LeaveRequest;
use App\Models\Company\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeaveController extends Controller
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

        // Get filter parameters
        $status = $request->get('status'); // 'pending', 'approved', 'rejected'
        $employeeId = $request->get('employee_id');
        $leaveTypeId = $request->get('leave_type_id');

        $query = LeaveRequest::whereHas('employee', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->with(['employee.user', 'leaveType', 'approver']);

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        // Filter by employee
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        // Filter by leave type
        if ($leaveTypeId) {
            $query->where('leave_type_id', $leaveTypeId);
        }

        $leaveRequests = $query
            ->latest('created_at')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'employee_name' => $request->employee->user->name ?? 'N/A',
                    'employee_code' => $request->employee->employee_code ?? 'N/A',
                    'leave_type' => $request->leaveType->name ?? 'N/A',
                    'start_date' => $request->start_date->format('Y-m-d'),
                    'end_date' => $request->end_date->format('Y-m-d'),
                    'start_date_formatted' => $request->start_date->format('F d, Y'),
                    'end_date_formatted' => $request->end_date->format('F d, Y'),
                    'days' => $request->days,
                    'status' => $request->status,
                    'approved_by' => $request->approver ? [
                        'name' => $request->approver->name,
                        'email' => $request->approver->email,
                    ] : null,
                    'note' => $request->note,
                    'created_at' => $request->created_at->format('Y-m-d H:i:s'),
                    'created_at_formatted' => $request->created_at->diffForHumans(),
                ];
            })
            ->values()
            ->toArray();

        // Get employees for filter dropdown
        $employees = \App\Models\Company\Employee::where('company_id', $company->id)
            ->with('user')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->user->name ?? 'N/A',
                    'employee_code' => $employee->employee_code,
                ];
            })
            ->values()
            ->toArray();

        // Get leave types for filter dropdown
        $leaveTypes = LeaveType::where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        return Inertia::render('Company/Leaves/Index', [
            'leaveRequests' => $leaveRequests,
            'employees' => $employees,
            'leaveTypes' => $leaveTypes,
            'filters' => [
                'status' => $status,
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Approve a leave request.
     */
    public function approve(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $leaveRequest = LeaveRequest::whereHas('employee', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => $user->id,
        ]);

        return back()->with('success', 'Leave request approved successfully.');
    }

    /**
     * Reject a leave request.
     */
    public function reject(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        $leaveRequest = LeaveRequest::whereHas('employee', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'note' => $validated['note'] ?? $leaveRequest->note,
        ]);

        return back()->with('success', 'Leave request rejected successfully.');
    }
}
