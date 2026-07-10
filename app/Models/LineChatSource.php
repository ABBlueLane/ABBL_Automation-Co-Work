<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'source_type',
    'source_id',
    'display_name',
    'business_id',
    'form_type',
    'draft_issue_id',
    'form_state',
    'is_collecting',
    'started_by_user_id',
    'started_at',
    'stopped_by_user_id',
    'stopped_at',
])]
class LineChatSource extends Model
{
    public const FORM_TYPE_ISSUE_CREATE = 'issue_create';

    protected function casts(): array
    {
        return [
            'is_collecting' => 'boolean',
            'form_state' => 'array',
            'started_at' => 'datetime',
            'stopped_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LineChatMessage::class);
    }

    public function draftIssue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'draft_issue_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function formState(): array
    {
        return $this->form_state ?? [];
    }
}
