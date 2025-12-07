<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Asset;
use App\Models\Company\AssetMaintenance;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetMaintenanceController extends Controller
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

        $query = AssetMaintenance::whereHas('asset', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->with('asset');

        // Apply filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('maintenance_type') && $request->maintenance_type) {
            $query->where('maintenance_type', $request->maintenance_type);
        }

        if ($request->has('asset_id') && $request->asset_id) {
            $query->where('asset_id', $request->asset_id);
        }

        $maintenance = $query->latest('start_date')
            ->get()
            ->map(function ($maintenance) {
                return [
                    'id' => $maintenance->id,
                    'asset_id' => $maintenance->asset_id,
                    'asset_code' => $maintenance->asset->asset_code,
                    'asset_type' => $maintenance->asset->type,
                    'asset_model' => $maintenance->asset->model,
                    'maintenance_type' => $maintenance->maintenance_type,
                    'problem_description' => $maintenance->problem_description,
                    'cost' => number_format($maintenance->cost, 2),
                    'vendor' => $maintenance->vendor,
                    'start_date' => $maintenance->start_date->format('Y-m-d'),
                    'completion_date' => $maintenance->completion_date?->format('Y-m-d'),
                    'status' => $maintenance->status,
                ];
            })
            ->values()
            ->toArray();

        // Get assets for filter
        $assets = Asset::where('company_id', $company->id)
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

        return Inertia::render('Company/Assets/Maintenance/Index', [
            'maintenance' => $maintenance,
            'filters' => $request->only(['status', 'maintenance_type', 'asset_id']),
            'assets' => $assets,
            'statuses' => ['Open', 'In_Progress', 'Completed'],
            'maintenance_types' => ['Repair', 'Scheduled'],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = request()->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $assets = Asset::where('company_id', $company->id)
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

        return Inertia::render('Company/Assets/Maintenance/Create', [
            'assets' => $assets,
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

        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'maintenance_type' => ['required', 'in:Repair,Scheduled'],
            'problem_description' => ['required', 'string'],
            'cost' => ['required', 'numeric', 'min:0'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'status' => ['required', 'in:Open,In_Progress,Completed'],
        ]);

        // Verify asset belongs to company
        $asset = Asset::where('company_id', $company->id)->findOrFail($validated['asset_id']);

        // Create maintenance record
        AssetMaintenance::create($validated);

        // Update asset status to Under_Maintenance if not already
        if ($asset->status !== 'Under_Maintenance') {
            $asset->update(['status' => 'Under_Maintenance']);
        }

        return redirect()->route('company.assets.index')
            ->with('success', 'Maintenance ticket created successfully.');
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
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $maintenance = AssetMaintenance::whereHas('asset', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);

        $validated = $request->validate([
            'maintenance_type' => ['required', 'in:Repair,Scheduled'],
            'problem_description' => ['required', 'string'],
            'cost' => ['required', 'numeric', 'min:0'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'completion_date' => ['nullable', 'date'],
            'status' => ['required', 'in:Open,In_Progress,Completed'],
        ]);

        $maintenance->update($validated);

        // If completed, update asset status
        if ($validated['status'] === 'Completed') {
            $asset = $maintenance->asset;
            // Check if asset has active assignment
            if ($asset->currentAssignment()) {
                $asset->update(['status' => 'Assigned']);
            } else {
                $asset->update(['status' => 'Available']);
            }
        }

        return redirect()->route('company.assets.index')
            ->with('success', 'Maintenance ticket updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = request()->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $maintenance = AssetMaintenance::whereHas('asset', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);

        $maintenance->delete();

        return redirect()->route('company.assets.index')
            ->with('success', 'Maintenance ticket deleted successfully.');
    }

    /**
     * Mark maintenance as completed.
     */
    public function complete(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $validated = $request->validate([
            'completion_date' => ['required', 'date'],
        ]);

        $maintenance = AssetMaintenance::whereHas('asset', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);

        $maintenance->update([
            'status' => 'Completed',
            'completion_date' => $validated['completion_date'],
        ]);

        // Update asset status
        $asset = $maintenance->asset;
        if ($asset->currentAssignment()) {
            $asset->update(['status' => 'Assigned']);
        } else {
            $asset->update(['status' => 'Available']);
        }

        return redirect()->route('company.assets.index')
            ->with('success', 'Maintenance marked as completed.');
    }
}
