<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'source_type',
    'source_id',
    'display_name',
    'is_collecting',
    'started_by_user_id',
    'started_at',
    'stopped_by_user_id',
    'stopped_at',
])]
class LineChatSource extends Model
{
    protected function casts(): array
    {
        return [
            'is_collecting' => 'boolean',
            'started_at' => 'datetime',
            'stopped_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LineChatMessage::class);
    }
}
