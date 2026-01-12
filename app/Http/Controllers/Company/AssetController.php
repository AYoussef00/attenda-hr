<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Asset;
use App\Models\Company\AssetAssignment;
use App\Models\Company\AssetMaintenance;
use App\Models\Company\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetController extends Controller
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

        $query = Asset::where('company_id', $company->id)
            ->with(['assignments.employee.user']);

        // Apply filters
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('model') && $request->model) {
            $query->where('model', 'like', '%' . $request->model . '%');
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('asset_code', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('model', 'like', '%' . $search . '%')
                    ->orWhere('serial_number', 'like', '%' . $search . '%');
            });
        }

        if ($request->has('purchase_date_from') && $request->purchase_date_from) {
            $query->where('purchase_date', '>=', $request->purchase_date_from);
        }

        if ($request->has('purchase_date_to') && $request->purchase_date_to) {
            $query->where('purchase_date', '<=', $request->purchase_date_to);
        }

        $assets = $query->latest('created_at')
            ->get()
            ->map(function ($asset) {
                $currentAssignment = $asset->assignments()
                    ->whereNull('return_date')
                    ->with('employee.user')
                    ->latest('assign_date')
                    ->first();
                
                return [
                    'id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'type' => $asset->type,
                    'model' => $asset->model,
                    'serial_number' => $asset->serial_number,
                    'purchase_date' => $asset->purchase_date?->format('Y-m-d'),
                    'cost' => number_format($asset->cost, 2),
                    'status' => $asset->status,
                    'warranty_end' => $asset->warranty_end?->format('Y-m-d'),
                    'notes' => $asset->notes,
                    'assigned_to' => $currentAssignment && $currentAssignment->employee ? [
                        'employee_id' => $currentAssignment->employee_id,
                        'employee_name' => $currentAssignment->employee->user->name ?? 'N/A',
                    ] : null,
                ];
            })
            ->values()
            ->toArray();

        // Get unique types for filter
        $types = Asset::where('company_id', $company->id)
            ->distinct()
            ->pluck('type')
            ->sort()
            ->values()
            ->toArray();

        // Get available assets for assignments
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

        // Get current assignments
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

        // Get employees for assignment dropdown
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

        // Get maintenance tickets
        $maintenanceQuery = AssetMaintenance::whereHas('asset', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->with('asset');

        if ($request->has('maintenance_status') && $request->maintenance_status) {
            $maintenanceQuery->where('status', $request->maintenance_status);
        }

        if ($request->has('maintenance_type') && $request->maintenance_type) {
            $maintenanceQuery->where('maintenance_type', $request->maintenance_type);
        }

        $maintenance = $maintenanceQuery->latest('start_date')
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

        // Get assets for maintenance dropdown
        $assetsForMaintenance = Asset::where('company_id', $company->id)
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

        // Get reports data
        $assetsByStatus = Asset::where('company_id', $company->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $highMaintenanceAssets = Asset::where('company_id', $company->id)
            ->withSum('maintenance', 'cost')
            ->having('maintenance_sum_cost', '>', 0)
            ->orderBy('maintenance_sum_cost', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($asset) {
                return [
                    'asset_code' => $asset->asset_code,
                    'type' => $asset->type,
                    'model' => $asset->model,
                    'total_maintenance_cost' => number_format($asset->maintenance_sum_cost ?? 0, 2),
                ];
            })
            ->toArray();

        $nearingWarrantyExpiration = Asset::where('company_id', $company->id)
            ->whereNotNull('warranty_end')
            ->where('warranty_end', '>=', now())
            ->where('warranty_end', '<=', now()->addDays(90))
            ->orderBy('warranty_end', 'asc')
            ->get()
            ->map(function ($asset) {
                $daysRemaining = now()->diffInDays($asset->warranty_end, false);
                return [
                    'asset_code' => $asset->asset_code,
                    'type' => $asset->type,
                    'model' => $asset->model,
                    'warranty_end' => $asset->warranty_end->format('Y-m-d'),
                    'days_remaining' => $daysRemaining,
                ];
            })
            ->toArray();

        return Inertia::render('Company/Assets/Index', [
            'assets' => $assets,
            'filters' => $request->only(['type', 'status', 'model', 'search', 'purchase_date_from', 'purchase_date_to']),
            'types' => $types,
            'statuses' => ['Available', 'Assigned', 'Under_Maintenance', 'Damaged', 'Retired'],
            // Assignments data
            'availableAssets' => $availableAssets,
            'currentAssignments' => $currentAssignments,
            'employees' => $employees,
            // Maintenance data
            'maintenance' => $maintenance,
            'maintenanceFilters' => $request->only(['maintenance_status', 'maintenance_type']),
            'assetsForMaintenance' => $assetsForMaintenance,
            'maintenanceStatuses' => ['Open', 'In_Progress', 'Completed'],
            'maintenanceTypes' => ['Repair', 'Scheduled'],
            // Reports data
            'assetsByStatus' => $assetsByStatus,
            'highMaintenanceAssets' => $highMaintenanceAssets,
            'nearingWarrantyExpiration' => $nearingWarrantyExpiration,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Company/Assets/Create');
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
            'asset_code' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'cost' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:Available,Assigned,Under_Maintenance,Damaged,Retired'],
            'warranty_end' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        // Auto-generate asset_code if not provided
        if (empty($validated['asset_code'])) {
            $lastAsset = Asset::where('company_id', $company->id)
                ->where('asset_code', 'like', 'ASSET-%')
                ->orderByRaw('CAST(SUBSTRING(asset_code, 7) AS UNSIGNED) DESC')
                ->first();

            if ($lastAsset) {
                $lastNumber = (int) substr($lastAsset->asset_code, 6);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            $validated['asset_code'] = 'ASSET-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        // Check if asset_code is unique
        $exists = Asset::where('company_id', $company->id)
            ->where('asset_code', $validated['asset_code'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['asset_code' => 'Asset code already exists.']);
        }

        // Check if asset with same serial_number already exists (if serial_number is provided)
        if (!empty($validated['serial_number'])) {
            $existsBySerial = Asset::where('company_id', $company->id)
                ->where('serial_number', $validated['serial_number'])
                ->exists();

            if ($existsBySerial) {
                return back()->withErrors(['serial_number' => 'An asset with this serial number already exists.']);
            }
        }

        // Check if same asset (type + model + serial_number) already exists
        $duplicateQuery = Asset::where('company_id', $company->id)
            ->where('type', $validated['type'])
            ->where('model', $validated['model']);

        if (!empty($validated['serial_number'])) {
            $duplicateQuery->where('serial_number', $validated['serial_number']);
        } else {
            // If no serial number, check if there's an asset with same type and model but also no serial number
            $duplicateQuery->whereNull('serial_number');
        }

        $duplicateExists = $duplicateQuery->exists();

        if ($duplicateExists) {
            if (!empty($validated['serial_number'])) {
                return back()->withErrors(['serial_number' => 'An asset with the same type, model, and serial number already exists.']);
            } else {
                return back()->withErrors(['model' => 'An asset with the same type and model already exists. Please provide a serial number to differentiate.']);
            }
        }

        $validated['company_id'] = $company->id;

        Asset::create($validated);

        return redirect()->route('company.assets.index')
            ->with('success', 'Asset created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = request()->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $asset = Asset::where('company_id', $company->id)
            ->with(['assignments.employee.user', 'maintenance'])
            ->findOrFail($id);

        $history = [
            'assignments' => $asset->assignments()
                ->with('employee.user')
                ->orderBy('assign_date', 'desc')
                ->get()
                ->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'employee_name' => $assignment->employee->user->name ?? 'N/A',
                        'assign_date' => $assignment->assign_date->format('Y-m-d'),
                        'return_date' => $assignment->return_date?->format('Y-m-d'),
                        'condition_on_assign' => $assignment->condition_on_assign,
                        'condition_on_return' => $assignment->condition_on_return,
                        'is_active' => $assignment->isActive(),
                    ];
                })
                ->toArray(),
            'maintenance' => $asset->maintenance()
                ->orderBy('start_date', 'desc')
                ->get()
                ->map(function ($maintenance) {
                    return [
                        'id' => $maintenance->id,
                        'maintenance_type' => $maintenance->maintenance_type,
                        'problem_description' => $maintenance->problem_description,
                        'cost' => number_format($maintenance->cost, 2),
                        'vendor' => $maintenance->vendor,
                        'start_date' => $maintenance->start_date->format('Y-m-d'),
                        'completion_date' => $maintenance->completion_date?->format('Y-m-d'),
                        'status' => $maintenance->status,
                    ];
                })
                ->toArray(),
        ];

        return Inertia::render('Company/Assets/Show', [
            'asset' => [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'type' => $asset->type,
                'model' => $asset->model,
                'serial_number' => $asset->serial_number,
                'purchase_date' => $asset->purchase_date->format('Y-m-d'),
                'cost' => number_format($asset->cost, 2),
                'status' => $asset->status,
                'warranty_end' => $asset->warranty_end?->format('Y-m-d'),
                'notes' => $asset->notes,
                'created_at' => $asset->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $asset->updated_at->format('Y-m-d H:i:s'),
            ],
            'history' => $history,
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

        $asset = Asset::where('company_id', $company->id)->findOrFail($id);

        return Inertia::render('Company/Assets/Edit', [
            'asset' => [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'type' => $asset->type,
                'model' => $asset->model,
                'serial_number' => $asset->serial_number,
                'purchase_date' => $asset->purchase_date->format('Y-m-d'),
                'cost' => $asset->cost,
                'status' => $asset->status,
                'warranty_end' => $asset->warranty_end?->format('Y-m-d'),
                'notes' => $asset->notes,
            ],
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

        $asset = Asset::where('company_id', $company->id)->findOrFail($id);

        $validated = $request->validate([
            'asset_code' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'cost' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:Available,Assigned,Under_Maintenance,Damaged,Retired'],
            'warranty_end' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        // Check if asset_code is unique (excluding current asset)
        $exists = Asset::where('company_id', $company->id)
            ->where('asset_code', $validated['asset_code'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['asset_code' => 'Asset code already exists.']);
        }

        $asset->update($validated);

        return redirect()->route('company.assets.index')
            ->with('success', 'Asset updated successfully.');
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

        $asset = Asset::where('company_id', $company->id)->findOrFail($id);

        // Check if asset has active assignments
        if ($asset->currentAssignment()) {
            return back()->with('error', 'Cannot delete asset with active assignment. Please return it first.');
        }

        $asset->delete();

        return redirect()->route('company.assets.index')
            ->with('success', 'Asset deleted successfully.');
    }

    /**
     * Update asset status.
     */
    public function updateStatus(Request $request, string $id)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $asset = Asset::where('company_id', $company->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:Available,Assigned,Under_Maintenance,Damaged,Retired'],
        ]);

        $asset->update(['status' => $validated['status']]);

        return back()->with('success', 'Asset status updated successfully.');
    }

    /**
     * Get asset history.
     */
    public function history(string $id)
    {
        $user = request()->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        $asset = Asset::where('company_id', $company->id)
            ->with(['assignments.employee.user', 'maintenance'])
            ->findOrFail($id);

        $history = [
            'assignments' => $asset->assignments()
                ->with('employee.user')
                ->orderBy('assign_date', 'desc')
                ->get()
                ->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'employee_name' => $assignment->employee->user->name ?? 'N/A',
                        'assign_date' => $assignment->assign_date->format('Y-m-d'),
                        'return_date' => $assignment->return_date?->format('Y-m-d'),
                        'condition_on_assign' => $assignment->condition_on_assign,
                        'condition_on_return' => $assignment->condition_on_return,
                        'is_active' => $assignment->isActive(),
                    ];
                })
                ->toArray(),
            'maintenance' => $asset->maintenance()
                ->orderBy('start_date', 'desc')
                ->get()
                ->map(function ($maintenance) {
                    return [
                        'id' => $maintenance->id,
                        'maintenance_type' => $maintenance->maintenance_type,
                        'problem_description' => $maintenance->problem_description,
                        'cost' => number_format($maintenance->cost, 2),
                        'vendor' => $maintenance->vendor,
                        'start_date' => $maintenance->start_date->format('Y-m-d'),
                        'completion_date' => $maintenance->completion_date?->format('Y-m-d'),
                        'status' => $maintenance->status,
                    ];
                })
                ->toArray(),
        ];

        return response()->json($history);
    }
}
