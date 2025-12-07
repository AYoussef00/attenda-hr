<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ChatMessage;
use App\Models\Admin\ChatThread;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ChatController extends Controller
{
    /**
     * Show all chat threads and messages for the selected thread.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Only show OPEN chats in the left sidebar.
        // If a chat is assigned to a specific admin, hide it from others.
        $threadsQuery = ChatThread::where('status', 'open')
            ->where(function ($query) use ($user) {
                $query->whereNull('assigned_admin_id');

                if ($user) {
                    $query->orWhere('assigned_admin_id', $user->id);
                }
            })
            ->with(['messages' => function ($query) {
                $query->latest();
            }])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at');

        $threads = $threadsQuery
            ->get()
            ->map(function (ChatThread $thread) {
                $lastMessage = $thread->messages->first();

                return [
                    'id' => $thread->id,
                    'visitor_id' => $thread->visitor_id,
                    'assigned_admin_id' => $thread->assigned_admin_id,
                    'status' => $thread->status,
                    'unread_admin_count' => $thread->unread_admin_count,
                    'last_message_at' => optional($thread->last_message_at)->toDateTimeString(),
                    'last_message_preview' => $lastMessage
                        ? Str::limit($lastMessage->message, 80)
                        : null,
                ];
            })
            ->values()
            ->toArray();

        $activeThreadId = $request->integer('thread_id') ?: ($threads[0]['id'] ?? null);
        $activeThread = null;
        $messages = [];

        if ($activeThreadId) {
            $thread = ChatThread::find($activeThreadId);

            if ($thread) {
                // Don't allow seeing chats assigned to another admin
                if ($thread->assigned_admin_id && $user && $thread->assigned_admin_id !== $user->id) {
                    $thread = null;
                }
            }

            if ($thread) {
                // Mark messages as read for admin when opening this thread
                if ($thread->unread_admin_count > 0) {
                    $thread->forceFill(['unread_admin_count' => 0])->save();
                }

                $activeThread = [
                    'id' => $thread->id,
                    'visitor_id' => $thread->visitor_id,
                    'assigned_admin_id' => $thread->assigned_admin_id,
                    'status' => $thread->status,
                ];

                // Only load messages once the chat is assigned (accepted) to this admin
                if ($thread->assigned_admin_id && $user && $thread->assigned_admin_id === $user->id) {
                    $messages = ChatMessage::where('chat_thread_id', $thread->id)
                        ->orderBy('created_at')
                        ->get()
                        ->map(function (ChatMessage $message) {
                            return [
                                'id' => $message->id,
                                'from' => $message->sender_type,
                                'text' => $message->message,
                                'created_at' => $message->created_at->toDateTimeString(),
                                'created_at_human' => $message->created_at->diffForHumans(),
                            ];
                        })
                        ->values()
                        ->toArray();
                }
            }
        }

        return Inertia::render('Admin/Chat/Index', [
            'threads' => $threads,
            'activeThread' => $activeThread,
            'messages' => $messages,
        ]);
    }

    /**
     * Store a new admin reply in an existing thread.
     */
    public function store(Request $request, ChatThread $thread)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $admin = $request->user();

        // If thread already assigned to another admin, block replying
        if ($thread->assigned_admin_id && $thread->assigned_admin_id !== $admin->id) {
            return redirect()
                ->route('admin.chat.index', ['thread_id' => $thread->id])
                ->with('error', 'This chat is already assigned to another agent.');
        }

        // If no one is assigned yet, assign this admin
        if (! $thread->assigned_admin_id) {
            $thread->assigned_admin_id = $admin->id;
        }

        $message = $thread->messages()->create([
            'sender_type' => 'admin',
            'message' => $data['message'],
        ]);

        $thread->forceFill([
            'last_message_at' => $message->created_at,
            'status' => 'open',
        ])->save();

        return redirect()->route('admin.chat.index', ['thread_id' => $thread->id]);
    }

    /**
     * Explicitly accept (assign) a chat thread to the current admin user.
     */
    public function accept(Request $request, ChatThread $thread)
    {
        $admin = $request->user();

        // If chat already assigned to another admin, prevent re-assignment
        if ($thread->assigned_admin_id && $thread->assigned_admin_id !== $admin->id) {
            return redirect()
                ->route('admin.chat.index')
                ->with('error', 'This chat is already assigned to another agent.');
        }

        // Assign to this admin if not yet assigned
        if (! $thread->assigned_admin_id) {
            $thread->assigned_admin_id = $admin->id;
            $thread->save();
        }

        return redirect()->route('admin.chat.index', ['thread_id' => $thread->id]);
    }

    /**
     * End a chat thread and store resolution info.
     */
    public function end(Request $request, ChatThread $thread)
    {
        $data = $request->validate([
            'resolved' => ['required', 'boolean'],
            'issue' => ['nullable', 'string', 'max:1000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        // Create closing message
        $closingText = 'Thank you for chatting with Attenda support. If you need anything else, just reach out any time. Have a great day! ✨';

        $thread->messages()->create([
            'sender_type' => 'admin',
            'message' => $closingText,
        ]);

        $thread->forceFill([
            'status' => 'closed',
            'unread_admin_count' => 0,
            'ended_at' => now(),
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'resolved' => $data['resolved'],
            'issue_summary' => $data['issue'] ?? null,
            'last_message_at' => now(),
        ])->save();

        // بعد إنهاء الشات، نرجع للصفحة بدون تحديد thread_id
        // عشان الشات المقفول يختفي من السايد بار وكمان من منطقة المحادثة
        return redirect()->route('admin.chat.index')
            ->with('success', 'Chat ended and feedback saved.');
    }

    /**
     * Generate an AI-like summary for the conversation in this thread.
     *
     * You can later replace the simple summarisation below with a real LLM call
     * (e.g. OpenAI, Azure OpenAI, etc.) if you want true AI summaries.
     */
    public function summarize(ChatThread $thread)
    {
        $messages = ChatMessage::where('chat_thread_id', $thread->id)
            ->orderBy('created_at')
            ->get();

        // Separate visitor vs admin messages
        $visitorMessages = $messages->where('sender_type', 'visitor')->pluck('message')->implode(' ');
        $adminMessages = $messages->where('sender_type', 'admin')->pluck('message')->implode(' ');

        // Very simple "summary": prefer what the visitor wrote (their need),
        // fallback to the whole conversation, then trim.
        $raw = trim($visitorMessages) ?: trim($visitorMessages . ' ' . $adminMessages);

        $summary = $raw
            ? Str::limit($raw, 280)
            : 'Customer asked a general question and the support team responded.';

        // Example placeholder for real AI integration:
        //
        // $summary = app(\App\Services\ChatSummaryService::class)->summarize($messages);

        return response()->json([
            'summary' => $summary,
        ]);
    }

    /**
     * Return messages for a given thread as JSON (for live updates).
     */
    public function messages(ChatThread $thread)
    {
        $messages = ChatMessage::where('chat_thread_id', $thread->id)
            ->orderBy('created_at')
            ->get()
            ->map(function (ChatMessage $message) {
                return [
                    'id' => $message->id,
                    'from' => $message->sender_type,
                    'text' => $message->message,
                    'created_at' => $message->created_at->toDateTimeString(),
                    'created_at_human' => $message->created_at->diffForHumans(),
                ];
            })
            ->values()
            ->toArray();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Return latest chat threads as JSON (for live updates in sidebar).
     */
    public function threads()
    {
        $user = request()->user();

        // Used by polling in the sidebar: only return OPEN chats
        // and hide chats assigned to other admins.
        $threads = ChatThread::where('status', 'open')
            ->where(function ($query) use ($user) {
                $query->whereNull('assigned_admin_id');

                if ($user) {
                    $query->orWhere('assigned_admin_id', $user->id);
                }
            })
            ->with(['messages' => function ($query) {
                $query->latest();
            }])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ChatThread $thread) {
                $lastMessage = $thread->messages->first();

                return [
                    'id' => $thread->id,
                    'visitor_id' => $thread->visitor_id,
                    'assigned_admin_id' => $thread->assigned_admin_id,
                    'status' => $thread->status,
                    'unread_admin_count' => $thread->unread_admin_count,
                    'last_message_at' => optional($thread->last_message_at)->toDateTimeString(),
                    'last_message_preview' => $lastMessage
                        ? Str::limit($lastMessage->message, 80)
                        : null,
                ];
            })
            ->values()
            ->toArray();

        return response()->json([
            'threads' => $threads,
        ]);
    }

    /**
     * Return total unread admin messages count (for sidebar badge).
     */
    public function unreadCount()
    {
        $count = ChatThread::sum('unread_admin_count');

        return response()->json([
            'count' => (int) $count,
        ]);
    }
}


