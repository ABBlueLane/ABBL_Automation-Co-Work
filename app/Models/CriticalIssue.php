<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CriticalIssue extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'problem',
        'solution',
        'tools',
        'created_by',
    ];

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
