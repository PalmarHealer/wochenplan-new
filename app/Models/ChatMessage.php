<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $touches = ['conversation'];

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tool_calls',
        'tool_call_id',
        'tool_name',
        'pending_action',
        'action_status',
    ];

    protected function casts(): array
    {
        return [
            'tool_calls' => 'array',
            'pending_action' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }
}
