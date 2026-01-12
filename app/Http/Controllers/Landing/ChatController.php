<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\Admin\ChatMessage;
use App\Models\Admin\ChatThread;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Get full message history for a visitor.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'visitor_id' => ['required', 'string', 'max:255'],
        ]);

        $thread = ChatThread::where('visitor_id', $data['visitor_id'])->first();

        if (! $thread) {
            return response()->json([
                'messages' => [],
            ]);
        }

        $messages = $thread->messages()
            ->orderBy('created_at')
            ->get()
            ->map(function (ChatMessage $message) {
                return [
                    'id' => $message->id,
                    'from' => $message->sender_type, // visitor | admin
                    'text' => $message->message,
                    'created_at' => $message->created_at->toDateTimeString(),
                ];
            })
            ->values()
            ->toArray();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Store a new visitor message and create a thread if needed.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'visitor_id' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // Find or create thread for this visitor
        $thread = ChatThread::firstOrCreate(
            ['visitor_id' => $data['visitor_id']],
            ['status' => 'open', 'last_message_at' => now()]
        );

        /** @var ChatMessage $message */
        $message = $thread->messages()->create([
            'sender_type' => 'visitor',
            'message' => $data['message'],
        ]);

        $thread->forceFill([
            'last_message_at' => $message->created_at,
        ])->increment('unread_admin_count');

        return response()->json([
            'success' => true,
            'thread_id' => $thread->id,
        ]);
    }
}


