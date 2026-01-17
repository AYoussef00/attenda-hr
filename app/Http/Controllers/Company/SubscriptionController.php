<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    /**
     * Display the company subscription details.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get active subscription
        $activeSubscription = $company->activeSubscription();
        
        // Get all subscriptions history
        $subscriptions = $company->subscriptions()
            ->with('plan')
            ->latest('created_at')
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'plan_name' => $subscription->plan->name ?? 'N/A',
                    'plan_price' => number_format($subscription->plan->price ?? 0, 2),
                    'max_employees' => $subscription->plan->max_employees ?? 0,
                    'features' => $subscription->plan->features ?? [],
                    'start_date' => $subscription->start_date->format('Y-m-d'),
                    'end_date' => $subscription->end_date->format('Y-m-d'),
                    'status' => $subscription->status,
                    'days_remaining' => (int) now()->diffInDays($subscription->end_date, false),
                    'is_active' => $subscription->isActive(),
                    'is_expired' => $subscription->isExpired(),
                    'created_at' => $subscription->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->values()
            ->toArray();

        // Get current employee count
        $currentEmployeesCount = Employee::where('company_id', $company->id)->count();

        // Prepare subscription details
        $subscriptionDetails = null;
        if ($activeSubscription && $activeSubscription->plan) {
            $subscriptionDetails = [
                'id' => $activeSubscription->id,
                'plan_name' => $activeSubscription->plan->name,
                'plan_price' => number_format($activeSubscription->plan->price, 2),
                'max_employees' => $activeSubscription->plan->max_employees,
                'features' => $activeSubscription->plan->features ?? [],
                'start_date' => $activeSubscription->start_date->format('Y-m-d'),
                'end_date' => $activeSubscription->end_date->format('Y-m-d'),
                'start_date_formatted' => $activeSubscription->start_date->format('F d, Y'),
                'end_date_formatted' => $activeSubscription->end_date->format('F d, Y'),
                'status' => $activeSubscription->status,
                'days_remaining' => (int) now()->diffInDays($activeSubscription->end_date, false),
                'is_active' => $activeSubscription->isActive(),
                'is_expired' => $activeSubscription->isExpired(),
                'created_at' => $activeSubscription->created_at->format('Y-m-d H:i:s'),
            ];
        }

        return Inertia::render('Company/Subscription/Index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo ? cdn_storage($company->logo) : null,
            ],
            'subscription' => $subscriptionDetails,
            'subscriptions_history' => $subscriptions,
            'current_employees_count' => $currentEmployeesCount,
        ]);
    }
}
