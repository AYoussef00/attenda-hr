<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Asset;
use App\Models\Company\AssetAssignment;
use App\Models\Company\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetAssignmentController extends Controller
{
    /**
     * Display the assignments page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get available assets (status = Available and no active assignment)
        $availableAssets = Asset::where('company_id', $company->id)
            ->where('status', 'Available')
            ->whereDoesntHave('assignments', function ($query) {
                $query->whereNull('return_date');
            })
            ->get()
            ->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'type' => $asset->type,
                    'model' => $asset->model,
                ];
            })
            ->values()
            ->toArray();

        // Get current assignments (not returned)
        $currentAssignments = AssetAssignment::whereHas('asset', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->whereNull('return_date')
            ->with(['asset', 'employee.user'])
            ->orderBy('assign_date', 'desc')
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'asset_id' => $assignment->asset_id,
                    'asset_code' => $assignment->asset->asset_code,
                    'asset_type' => $assignment->asset->type,
                    'asset_model' => $assignment->asset->model,
                    'employee_id' => $assignment->employee_id,
                    'employee_name' => $assignment->employee->user->name ?? 'N/A',
                    'employee_code' => $assignment->employee->employee_code,
                    'assign_date' => $assignment->assign_date->format('Y-m-d'),
                    'condition_on_assign' => $assignment->condition_on_assign,
                ];
            })
            ->values()
            ->toArray();

        // Get all employees for dropdown
        $employees = Employee::where('company_id', $company->id)
            ->with('user')
            ->where('status', 'active')
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

        return Inertia::render('Company/Assets/Assignments/Index', [
            'availableAssets' => $availableAssets,
            'currentAssignments' => $currentAssignments,
            'employees' => $employees,
        ]);
    }

    /**
     * Assign asset to employee.
     */
    public function assign(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'employee_id' => ['required', 'exists:employees,id'],
            'assign_date' => ['required', 'date'],
            'condition_on_assign' => ['required', 'string', 'max:255'],
        ]);

        $asset = Asset::where('company_id', $company->id)->findOrFail($validated['asset_id']);

        // Check if asset is available
        if ($asset->status !== 'Available') {
            return back()->withErrors(['asset_id' => 'Asset is not available for assignment.']);
        }

        // Check if asset already has active assignment
        if ($asset->currentAssignment()) {
            return back()->withErrors(['asset_id' => 'Asset is already assigned.']);
        }

        // Verify employee belongs to company
        $employee = Employee::where('company_id', $company->id)
            ->findOrFail($validated['employee_id']);

        // Create assignment
        AssetAssignment::create($validated);

        // Update asset status
        $asset->update(['status' => 'Assigned']);

        return redirect()->route('company.assets.index')
            ->with('success', 'Asset assigned successfully.');
    }

    /**
     * Return asset from employee.
     */
    public function return(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $validated = $request->validate([
            'return_date' => ['required', 'date'],
            'condition_on_return' => ['required', 'string', 'max:255'],
        ]);

        $assignment = AssetAssignment::whereHas('asset', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->findOrFail($id);

        // Check if already returned
        if ($assignment->return_date) {
            return back()->withErrors(['error' => 'Asset is already returned.']);
        }

        // Update assignment
        $assignment->update([
            'return_date' => $validated['return_date'],
            'condition_on_return' => $validated['condition_on_return'],
        ]);

        // Update asset status
        $asset = $assignment->asset;
        $asset->update(['status' => 'Available']);

        return redirect()->route('company.assets.index')
            ->with('success', 'Asset returned successfully.');
    }

    /**
     * Get all current assignments.
     */
    public function currentAssignments(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $assignments = AssetAssignment::whereHas('asset', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->whereNull('return_date')
            ->with(['asset', 'employee.user'])
            ->orderBy('assign_date', 'desc')
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'asset_code' => $assignment->asset->asset_code,
                    'asset_type' => $assignment->asset->type,
                    'asset_model' => $assignment->asset->model,
                    'employee_name' => $assignment->employee->user->name ?? 'N/A',
                    'employee_code' => $assignment->employee->employee_code,
                    'assign_date' => $assignment->assign_date->format('Y-m-d'),
                    'condition_on_assign' => $assignment->condition_on_assign,
                ];
            })
            ->values()
            ->toArray();

        return response()->json($assignments);
    }
}
