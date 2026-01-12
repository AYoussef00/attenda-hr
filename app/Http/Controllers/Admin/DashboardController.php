<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ActivityLog;
use App\Models\Admin\Company;
use App\Models\Admin\CompanySubscription;
use App\Models\Admin\User;
use App\Models\Company\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(Request $request)
    {
        // Subscription chart data
        $activeSubscriptions = CompanySubscription::where('status', 'active')
            ->where('end_date', '>=', now())
            ->count();
        $expiredSubscriptions = CompanySubscription::where(function ($query) {
            $query->where('status', 'expired')
                ->orWhere('end_date', '<', now());
        })->count();
        $cancelledSubscriptions = CompanySubscription::where('status', 'cancelled')->count();

        // Employee Distribution by Company (Top 10)
        $employeeDistribution = Employee::selectRaw('company_id, COUNT(*) as employee_count')
            ->groupBy('company_id')
            ->with('company:id,name')
            ->orderByDesc('employee_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'company_name' => $item->company->name ?? 'Unknown',
                    'employee_count' => $item->employee_count,
                ];
            })
            ->values()
            ->toArray();

        // Subscription Stats by Plan
        $subscriptionStatsByPlan = CompanySubscription::selectRaw('plan_id, COUNT(*) as subscription_count')
            ->groupBy('plan_id')
            ->with('plan:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'plan_name' => $item->plan->name ?? 'Unknown',
                    'subscription_count' => $item->subscription_count,
                ];
            })
            ->values()
            ->toArray();

        // Activity Stats (Daily for last 7 days)
        $activityStats = ActivityLog::selectRaw('DATE(created_at) as date, COUNT(*) as activity_count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'activity_count' => $item->activity_count,
                    'formatted_date' => \Carbon\Carbon::parse($item->date)->format('M d'),
                ];
            })
            ->values()
            ->toArray();

        // Companies with subscription status and employee count
        $companies = Company::with(['subscriptions.plan', 'users'])
            ->latest('created_at')
            ->limit(10)
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
                ];
            })
            ->values()
            ->toArray();

        // Subscriptions table data
        $subscriptions = CompanySubscription::with(['company', 'plan'])
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'company_name' => $subscription->company->name,
                    'plan_name' => $subscription->plan->name,
                    'start_date' => $subscription->start_date->format('Y-m-d'),
                    'end_date' => $subscription->end_date->format('Y-m-d'),
                    'status' => $subscription->status,
                ];
            })
            ->values()
            ->toArray();

        // Calculate Income from Active Subscriptions
        $totalIncome = CompanySubscription::where('status', 'active')
            ->where('end_date', '>=', now())
            ->with('plan')
            ->get()
            ->sum(function ($subscription) {
                return $subscription->plan->price ?? 0;
            });

        // Monthly Income (from active subscriptions this month)
        $monthlyIncome = CompanySubscription::where('status', 'active')
            ->where('end_date', '>=', now())
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->with('plan')
            ->get()
            ->sum(function ($subscription) {
                return $subscription->plan->price ?? 0;
            });

        // Annual Income (from active subscriptions this year)
        $annualIncome = CompanySubscription::where('status', 'active')
            ->where('end_date', '>=', now())
            ->whereYear('start_date', now()->year)
            ->with('plan')
            ->get()
            ->sum(function ($subscription) {
                return $subscription->plan->price ?? 0;
            });

        $stats = [
            'total_companies' => Company::count(),
            'active_subscriptions' => $activeSubscriptions,
            'total_employees' => Employee::count(),
            'total_admin_users' => User::where('role', '!=', 'employee')->count(),
            'pending_subscriptions' => CompanySubscription::where('status', 'pending')->count(),
            'total_income' => round($totalIncome, 2),
            'monthly_income' => round($monthlyIncome, 2),
            'annual_income' => round($annualIncome, 2),
            'subscription_chart' => [
                'active' => $activeSubscriptions,
                'expired' => $expiredSubscriptions,
                'cancelled' => $cancelledSubscriptions,
            ],
            'recent_companies' => $companies,
            'recent_subscriptions' => $subscriptions,
            'employee_distribution' => $employeeDistribution,
            'subscription_stats_by_plan' => $subscriptionStatsByPlan,
            'activity_stats' => $activityStats,
            'recent_activity' => ActivityLog::with(['user', 'company'])
                ->latest('created_at')
                ->limit(10)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'action' => $log->action,
                        'model' => $log->model,
                        'user' => $log->user ? [
                            'name' => $log->user->name,
                            'email' => $log->user->email,
                        ] : null,
                        'company' => $log->company ? [
                            'name' => $log->company->name,
                        ] : null,
                        'created_at' => $log->created_at->diffForHumans(),
                    ];
                })
                ->values()
                ->toArray(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
        ]);
    }
}

