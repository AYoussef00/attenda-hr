<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $plans = Plan::withCount('companySubscriptions')
            ->latest('created_at')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => number_format($plan->price, 2),
                    'yearly_price' => $plan->yearly_price !== null ? number_format($plan->yearly_price, 2) : null,
                    'max_employees' => $plan->max_employees,
                    'features' => $plan->features ?? [],
                    'subscriptions_count' => $plan->company_subscriptions_count,
                    'created_at' => $plan->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Plans/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'yearly_price' => ['nullable', 'numeric', 'min:0'],
            'max_employees' => ['required', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
        ]);

        Plan::create($validated);

        // Clear cache
        Cache::forget('landing_plans');

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan created successfully.');
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
        $plan = Plan::findOrFail($id);

        return Inertia::render('Admin/Plans/Edit', [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $plan->price,
                'yearly_price' => $plan->yearly_price,
                'max_employees' => $plan->max_employees,
                'features' => $plan->features ?? [],
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'yearly_price' => ['nullable', 'numeric', 'min:0'],
            'max_employees' => ['required', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
        ]);

        $plan->update($validated);

        // Clear cache
        Cache::forget('landing_plans');

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $plan = Plan::findOrFail($id);

        // Check if plan has subscriptions
        if ($plan->companySubscriptions()->count() > 0) {
            return back()->with('error', 'Cannot delete plan with active subscriptions. Please cancel or reassign subscriptions first.');
        }

        $plan->delete();

        // Clear cache
        Cache::forget('landing_plans');

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan deleted successfully.');
    }
}
