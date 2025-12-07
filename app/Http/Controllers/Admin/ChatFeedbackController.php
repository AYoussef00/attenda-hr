<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ChatThread;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatFeedbackController extends Controller
{
    /**
     * Display a listing of chat threads with feedback details.
     */
    public function index(Request $request)
    {
        $threads = ChatThread::query()
            ->with(['assignedAdmin'])
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (ChatThread $thread) {
                return [
                    'id' => $thread->id,
                    'visitor_id' => $thread->visitor_id,
                    'assigned_admin_id' => $thread->assigned_admin_id,
                    'assigned_admin_name' => optional($thread->assignedAdmin)->name,
                    'assigned_admin_email' => optional($thread->assignedAdmin)->email,
                    'status' => $thread->status,
                    'unread_admin_count' => $thread->unread_admin_count,
                    'last_message_at' => optional($thread->last_message_at)->toDateTimeString(),
                    'ended_at' => optional($thread->ended_at)->toDateTimeString(),
                    'duration_seconds' => $thread->duration_seconds,
                    'resolved' => $thread->resolved,
                    'issue_summary' => $thread->issue_summary,
                    'messages_count' => $thread->messages_count,
                ];
            });

        return Inertia::render('Admin/Chat/Feedback', [
            'threads' => $threads,
        ]);
    }
}


