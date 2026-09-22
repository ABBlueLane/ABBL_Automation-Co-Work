<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'method',
        'interval_seconds',
        'timeout_seconds',
        'failure_threshold',
        'success_threshold',
        'expected_status',
        'is_active',
        'last_status',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_status' => 'array',
            'is_active' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    public function checks()
    {
        return $this->hasMany(MonitorCheck::class, 'target_id');
    }

    public function incidents()
    {
        return $this->hasMany(MonitorIncident::class, 'target_id');
    }
}
