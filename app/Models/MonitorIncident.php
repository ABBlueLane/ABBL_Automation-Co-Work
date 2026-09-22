<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorIncident extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'target_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'status',
        'trigger_http_status',
        'trigger_error',
        'checks_failed_count',
        'notified_down_at',
        'notified_recovered_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'notified_down_at' => 'datetime',
            'notified_recovered_at' => 'datetime',
        ];
    }

    public function target()
    {
        return $this->belongsTo(MonitorTarget::class, 'target_id');
    }
}
