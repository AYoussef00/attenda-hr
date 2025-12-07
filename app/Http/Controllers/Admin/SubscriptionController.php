<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Company;
use App\Models\Admin\CompanySubscription;
use App\Models\Admin\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subscriptions = CompanySubscription::with(['company', 'plan'])
            ->latest('created_at')
            ->get()
            ->map(function ($subscription) {
                $planPrice = (float) ($subscription->plan->price ?? 0);

                // Determine billing period:
                // 1) Prefer value saved on company.settings['subscription_billing_period']
                // 2) Fallback to duration between start and end dates
                $billingPeriod = $subscription->company?->settings['subscription_billing_period'] ?? null;

                if (!in_array($billingPeriod, ['monthly', 'yearly'], true)) {
                    $months = $subscription->start_date && $subscription->end_date
                        ? $subscription->start_date->diffInMonths($subscription->end_date)
                        : 1;
                    $billingPeriod = $months >= 12 ? 'yearly' : 'monthly';
                }

                if ($billingPeriod === 'yearly') {
                    // Yearly price with 50% discount on 12 months
                    $price = $planPrice * 12 * 0.5;
                } else {
                    $price = $planPrice;
                }

                return [
                    'id' => $subscription->id,
                    'company_name' => $subscription->company->name ?? 'N/A',
                    'company_id' => $subscription->company_id,
                    'plan_name' => $subscription->plan->name ?? 'N/A',
                    'plan_id' => $subscription->plan_id,
                    'start_date' => $subscription->start_date->format('Y-m-d'),
                    'end_date' => $subscription->end_date->format('Y-m-d'),
                    'status' => $subscription->status,
                    'price' => number_format($price, 0), // match landing display (no decimals)
                    'billing_period' => $billingPeriod,
                    'max_employees' => $subscription->plan->max_employees ?? 0,
                    'created_at' => $subscription->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = Company::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

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

        return Inertia::render('Admin/Subscriptions/Create', [
            'companies' => $companies,
            'plans' => $plans,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:active,expired,cancelled'],
        ]);

        // Check if company already has an active subscription
        $existingActive = CompanySubscription::where('company_id', $validated['company_id'])
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->first();

        if ($existingActive && $validated['status'] === 'active') {
            return back()->withErrors([
                'company_id' => 'This company already has an active subscription. Please cancel or expire the existing one first.',
            ])->withInput();
        }

        CompanySubscription::create($validated);

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Subscription created successfully.');
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
        $subscription = CompanySubscription::findOrFail($id);
        $subscription->delete();

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Subscription deleted successfully.');
    }

    /**
     * Approve a pending subscription (activate company & users).
     */
    public function approve(CompanySubscription $subscription)
    {
        if ($subscription->status !== 'pending') {
            return redirect()->route('admin.subscriptions.index')
                ->with('error', 'Only pending subscriptions can be approved.');
        }

        DB::transaction(function () use ($subscription) {
            // Activate subscription
            $subscription->update([
                'status' => 'active',
                'start_date' => now()->toDateString(),
                'end_date' => now()->copy()->addMonth()->toDateString(),
            ]);

            // Activate company and its users if they are pending
            $company = $subscription->company;
            if ($company) {
                if ($company->status === 'pending') {
                    $company->status = 'active';
                    $company->save();
                }

                // Activate all pending users for this company
                $company->users()
                    ->where('status', 'pending')
                    ->update(['status' => 'active']);
            }
        });

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Subscription approved and company activated successfully.');
    }

    /**
     * Reject a pending subscription (cancel & optionally inactivate company).
     */
    public function reject(CompanySubscription $subscription)
    {
        if ($subscription->status !== 'pending') {
            return redirect()->route('admin.subscriptions.index')
                ->with('error', 'Only pending subscriptions can be rejected.');
        }

        DB::transaction(function () use ($subscription) {
            // Mark subscription as cancelled
            $subscription->update([
                'status' => 'cancelled',
            ]);

            $company = $subscription->company;
            if ($company) {
                // If company is still pending, inactivate it
                if ($company->status === 'pending') {
                    $company->status = 'inactive';
                    $company->save();

                    // Inactivate all pending users
                    $company->users()
                        ->where('status', 'pending')
                        ->update(['status' => 'inactive']);
                }
            }
        });

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Subscription rejected successfully.');
    }
}
