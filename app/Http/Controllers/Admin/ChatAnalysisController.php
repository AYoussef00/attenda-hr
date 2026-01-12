<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ChatThread;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatAnalysisController extends Controller
{
    /**
     * Show high-level chat performance metrics.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        // Base query: closed chats for today
        $baseQuery = ChatThread::where('status', 'closed')
            ->whereDate('ended_at', $today);

        // For regular "user" role, limit analysis to chats assigned to that user only
        if ($user && $user->role === 'user') {
            $baseQuery->where('assigned_admin_id', $user->id);
        }

        // Clone base query for each metric to avoid builder side effects
        $todayClosed = (clone $baseQuery)->count();

        // Resolved / unresolved today (based on feedback)
        $todayResolved = (clone $baseQuery)
            ->where('resolved', true)
            ->count();

        $todayUnresolved = (clone $baseQuery)
            ->where('resolved', false)
            ->count();

        // Average duration for chats closed today (in seconds)
        $avgDurationSeconds = (clone $baseQuery)
            ->whereNotNull('duration_seconds')
            ->avg('duration_seconds');

        return Inertia::render('Admin/Chat/Analysis', [
            'stats' => [
                'today_closed' => $todayClosed,
                'today_resolved' => $todayResolved,
                'today_unresolved' => $todayUnresolved,
                'avg_duration_seconds' => $avgDurationSeconds ? (int) round($avgDurationSeconds) : null,
            ],
        ]);
    }
}


