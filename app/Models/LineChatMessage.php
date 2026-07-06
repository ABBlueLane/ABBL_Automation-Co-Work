<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'line_chat_source_id',
    'webhook_event_id',
    'reply_token',
    'message_id',
    'message_type',
    'text',
    'sender_user_id',
    'sent_at',
    'raw_event',
])]
class LineChatMessage extends Model
{
    protected function casts(): array
    {
        return [
            'raw_event' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LineChatSource::class, 'line_chat_source_id');
    }
}
