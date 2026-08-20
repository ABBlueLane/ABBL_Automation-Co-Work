<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CursorAutofixRun extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'issue_id',
        'status',
        'repo_key',
        'repo_name',
        'repo_path',
        'matched_host',
        'git_branch',
        'prompt',
        'command',
        'stdout',
        'stderr',
        'exit_code',
        'error_message',
        'dry_run',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'command' => 'array',
            'dry_run' => 'boolean',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }
}
