<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Company;
use App\Models\Admin\CompanySubscription;
use App\Models\Admin\Plan;
use App\Models\Admin\User;
use App\Models\Company\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companies = Company::with(['subscriptions.plan'])
            ->latest('created_at')
            ->get()
            ->map(function ($company) {
                $activeSubscription = $company->activeSubscription();
                $employeeCount = Employee::where('company_id', $company->id)->count();

                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'employee_count' => $employeeCount,
                    'subscription_status' => $activeSubscription ? $activeSubscription->status : 'none',
                    'plan_name' => $activeSubscription && $activeSubscription->plan 
                        ? $activeSubscription->plan->name 
                        : null,
                    'status' => $company->status,
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Admin/Companies/Index', [
            'companies' => $companies,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $plans = Plan::orderBy('name')
            ->get(['id', 'name', 'price', 'max_employees'])
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => number_format($plan->price, 2),
                    'max_employees' => $plan->max_employees,
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Admin/Companies/Create', [
            'plans' => $plans,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'attendance_methods' => ['nullable', 'array'],
            'ip_whitelist' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'status' => ['required', 'in:active,inactive'],
            // Subscription fields
            'plan_id' => ['nullable', 'exists:plans,id'],
            'subscription_start_date' => ['nullable', 'date', 'required_with:plan_id'],
            'subscription_end_date' => ['nullable', 'date', 'after:subscription_start_date', 'required_with:plan_id'],
            // Admin user fields
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Handle logo upload
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('companies/logos', 'public');
        }

        // Create company
        $company = Company::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'logo' => $logoPath,
            'attendance_methods' => $validated['attendance_methods'] ?? ['qr' => true, 'ip' => false],
            'status' => $validated['status'],
        ]);

        // Create admin user for the company
        User::create([
            'company_id' => $company->id,
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => Hash::make($validated['admin_password']),
            'role' => 'company_admin',
            'status' => 'active',
        ]);

        // Create subscription if plan is selected
        if (!empty($validated['plan_id'])) {
            CompanySubscription::create([
                'company_id' => $company->id,
                'plan_id' => $validated['plan_id'],
                'start_date' => $validated['subscription_start_date'],
                'end_date' => $validated['subscription_end_date'],
                'status' => 'active',
            ]);
        }

        return redirect()->route('admin.companies.index')
            ->with('success', 'Company, admin user' . (!empty($validated['plan_id']) ? ', and subscription' : '') . ' created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $company = Company::with(['subscriptions.plan', 'users'])
            ->findOrFail($id);
        
        $employeeCount = Employee::where('company_id', $company->id)->count();
        $activeSubscription = $company->activeSubscription();

        return Inertia::render('Admin/Companies/Show', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'logo' => $company->logo,
                'attendance_methods' => $company->attendance_methods,
                'ip_whitelist' => $company->ip_whitelist,
                'settings' => $company->settings,
                'status' => $company->status,
                'employee_count' => $employeeCount,
                'subscription_status' => $activeSubscription ? $activeSubscription->status : 'none',
                'plan_name' => $activeSubscription && $activeSubscription->plan 
                    ? $activeSubscription->plan->name 
                    : null,
                'created_at' => $company->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $company->updated_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $company = Company::findOrFail($id);

        return Inertia::render('Admin/Companies/Edit', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'logo' => $company->logo ? asset('storage/' . $company->logo) : null,
                'attendance_methods' => $company->attendance_methods ?? ['qr' => true, 'ip' => false],
                'ip_whitelist' => $company->ip_whitelist ?? [],
                'status' => $company->status,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $company = Company::findOrFail($id);

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

        $company->update($validated);

        return redirect()->route('admin.companies.index')
            ->with('success', 'Company updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return redirect()->route('admin.companies.index')
            ->with('success', 'Company deleted successfully.');
    }
}
