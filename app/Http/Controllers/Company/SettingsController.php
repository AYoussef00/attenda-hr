<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Admin\Company;
use App\Models\Company\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Ensure user is company admin
        if (!$user->isCompanyAdmin()) {
            abort(403, 'Only company administrators can access settings.');
        }

        // Get leave types for the company
        $leaveTypes = LeaveType::where('company_id', $company->id)
            ->orderBy('name')
            ->get()
            ->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description,
                    'yearly_balance' => $type->yearly_balance,
                    'created_at' => $type->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Company/Settings/Index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'logo' => $company->logo ? cdn_storage($company->logo) : null,
                'attendance_methods' => $company->attendance_methods ?? ['qr' => true, 'ip' => false],
                'ip_whitelist' => $company->ip_whitelist ?? [],
                'settings' => $company->settings ?? [],
                'status' => $company->status,
            ],
            'leave_types' => $leaveTypes,
        ]);
    }

    /**
     * Update company settings.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Ensure user is company admin
        if (!$user->isCompanyAdmin()) {
            abort(403, 'Only company administrators can update settings.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'attendance_methods' => ['nullable', 'array'],
            'attendance_methods.qr' => ['nullable', 'boolean'],
            'attendance_methods.ip' => ['nullable', 'boolean'],
            'ip_whitelist' => ['nullable', 'array'],
            'ip_whitelist.*' => ['nullable', 'string', 'ip'],
            'settings' => ['nullable', 'array'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                Storage::disk('public')->delete($company->logo);
            }
            $logoPath = $request->file('logo')->store('companies/logos', 'public');
            $validated['logo'] = $logoPath;
        } else {
            unset($validated['logo']);
        }

        // Update company
        $company->update($validated);

        return back()->with('success', 'Settings updated successfully.');
    }
}
