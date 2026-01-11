<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        // Get pending demo requests count & new chat threads (only for authenticated super admins)
        $pendingRequestsCount = 0;
        $newChatsCount = 0;
        if ($request->user() && $request->user()->isSuperAdmin()) {
            $pendingRequestsCount = \App\Models\Admin\DemoRequest::where('status', 'pending')->count();
            $newChatsCount = \App\Models\Admin\ChatThread::sum('unread_admin_count');
        }

        // Check if company subscription is expired
        $subscriptionExpired = false;
        if ($request->user() && $request->user()->company) {
            $company = $request->user()->company;
            
            // If bypass_expired is enabled, don't mark as expired
            if ($company->bypass_expired) {
                $subscriptionExpired = false;
            } else {
                $activeSubscription = $company->activeSubscription();
                // If no active subscription exists, the subscription is expired (or doesn't exist)
                // We consider it expired if the company has any subscriptions that are expired
                if (!$activeSubscription) {
                    $latestSubscription = $company->subscriptions()
                        ->latest('created_at')
                        ->first();
                    // If there's a subscription and it's expired, mark as expired
                    if ($latestSubscription && $latestSubscription->isExpired()) {
                        $subscriptionExpired = true;
                    }
                }
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'pendingRequestsCount' => $pendingRequestsCount,
            'newChatsCount' => $newChatsCount,
            'subscriptionExpired' => $subscriptionExpired,
            'csrf_token' => csrf_token(),
        ];
    }
}
