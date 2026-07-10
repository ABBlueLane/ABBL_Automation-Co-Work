<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'issue_id',
        'user_id',
        'comment',
        'files',
    ];

    protected function casts(): array
    {
        return [
            'files' => 'array',
        ];
    }

    public function issue()
    {
        return $this->belongsTo(Issue::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
