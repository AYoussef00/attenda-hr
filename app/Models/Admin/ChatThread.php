<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_id',
        'assigned_admin_id',
        'status',
        'unread_admin_count',
        'last_message_at',
        'ended_at',
        'duration_seconds',
        'resolved',
        'issue_summary',
    ];

    protected $casts = [
        'unread_admin_count' => 'integer',
        'duration_seconds' => 'integer',
        'resolved' => 'boolean',
        'last_message_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Messages for this chat thread.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Admin user who handled / was assigned to this chat thread.
     */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }
}


