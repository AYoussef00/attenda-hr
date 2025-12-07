<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\LeaveType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveTypeController extends Controller
{
    /**
     * Store a newly created leave type.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Ensure user is company admin
        if (!$user->isCompanyAdmin()) {
            abort(403, 'Only company administrators can create leave types.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'yearly_balance' => ['required', 'integer', 'min:0'],
        ]);

        // Create leave type
        LeaveType::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'yearly_balance' => $validated['yearly_balance'],
        ]);

        return back()->with('success', 'Leave type created successfully.');
    }

    /**
     * Update the specified leave type.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Ensure user is company admin
        if (!$user->isCompanyAdmin()) {
            abort(403, 'Only company administrators can update leave types.');
        }

        $leaveType = LeaveType::where('id', $id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'yearly_balance' => ['required', 'integer', 'min:0'],
        ]);

        $leaveType->update($validated);

        return back()->with('success', 'Leave type updated successfully.');
    }

    /**
     * Remove the specified leave type.
     */
    public function destroy(string $id)
    {
        $user = request()->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Ensure user is company admin
        if (!$user->isCompanyAdmin()) {
            abort(403, 'Only company administrators can delete leave types.');
        }

        $leaveType = LeaveType::where('id', $id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        // Check if leave type has leave requests
        if ($leaveType->leaveRequests()->count() > 0) {
            return back()->with('error', 'Cannot delete leave type with existing leave requests.');
        }

        $leaveType->delete();

        return back()->with('success', 'Leave type deleted successfully.');
    }
}
