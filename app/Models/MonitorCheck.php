<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorCheck extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'target_id',
        'checked_at',
        'ok',
        'http_status',
        'latency_ms',
        'error_message',
        'probe_host',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'ok' => 'boolean',
        ];
    }

    public function target()
    {
        return $this->belongsTo(MonitorTarget::class, 'target_id');
    }
}
