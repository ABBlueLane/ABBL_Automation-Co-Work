<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineImsSubmission extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'line_chat_source_id',
        'draft_issue_id',
        'submitted_issue_id',
        'webhook_event_id',
        'status',
        'error_message',
        'form_state',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'form_state' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function lineChatSource(): BelongsTo
    {
        return $this->belongsTo(LineChatSource::class);
    }

    public function draftIssue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'draft_issue_id');
    }

    public function submittedIssue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'submitted_issue_id');
    }
}
