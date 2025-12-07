<?php

namespace App\Http\Controllers\Company\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin\Company;
use App\Models\Admin\CompanySubscription;
use App\Models\Admin\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class RegisterController extends Controller
{
    /**
     * Show the company registration form (multi-step).
     */
    public function create(Request $request)
    {
        $plans = Plan::orderBy('price', 'asc')
            ->get()
            ->map(function (Plan $plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => number_format($plan->price, 0),
                    'price_raw' => $plan->price,
                    'yearly_price' => $plan->yearly_price !== null ? number_format($plan->yearly_price, 0) : null,
                    'yearly_price_raw' => $plan->yearly_price,
                    'max_employees' => $plan->max_employees,
                    'features' => $plan->features ?? [],
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Company/Auth/Register', [
            'plans' => $plans,
        ]);
    }

    /**
     * Handle new company registration.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            // Company information
            'company.name' => ['required', 'string', 'max:255'],
            'company.industry' => ['nullable', 'string', 'max:255'],
            'company.industry_other' => ['nullable', 'string', 'max:255'],
            'company.commercial_registration_no' => ['required', 'string', 'max:255'],
            'company.country' => ['nullable', 'string', 'max:255'],
            'company.city' => ['nullable', 'string', 'max:255'],
            'company.address' => ['nullable', 'string', 'max:500'],
            'company.phone' => ['nullable', 'string', 'max:50'],
            'company.email' => ['required', 'email', 'max:255', 'unique:companies,email'],
            // size is a range label (e.g., "1-10", "30-50")
            'company.size' => ['nullable', 'string', 'max:50'],

            // Admin / owner
            'admin.name' => ['required', 'string', 'max:255'],
            'admin.email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin.phone' => ['nullable', 'string', 'max:50'],
            'admin.password' => ['required', 'string', 'min:8', 'confirmed'],

            // Plan
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $plan = Plan::findOrFail($data['plan_id']);

        DB::beginTransaction();

        try {
            $industry = $data['company']['industry'] === 'Other'
                ? ($data['company']['industry_other'] ?? null)
                : ($data['company']['industry'] ?? null);

            $company = Company::create([
                'name' => $data['company']['name'],
                'email' => $data['company']['email'],
                'phone' => $data['company']['phone'] ?? null,
                'address' => $data['company']['address'] ?? null,
                'settings' => [
                    'industry' => $industry,
                    'commercial_registration_no' => $data['company']['commercial_registration_no'],
                    'country' => $data['company']['country'] ?? null,
                    'city' => $data['company']['city'] ?? null,
                    'size' => $data['company']['size'] ?? null,
                ],
                'status' => 'active',
            ]);

            $user = User::create([
                'company_id' => $company->id,
                'name' => $data['admin']['name'],
                'email' => $data['admin']['email'],
                'phone' => $data['admin']['phone'] ?? null,
                'password' => Hash::make($data['admin']['password']),
                'role' => 'company_admin',
                'status' => 'active',
            ]);

            CompanySubscription::create([
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'status' => 'active',
            ]);

            Auth::login($user);

            DB::commit();

            return redirect()->route('company.dashboard');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withErrors(['general' => 'Something went wrong while creating your company. Please try again.'])
                ->withInput();
        }
    }
}


