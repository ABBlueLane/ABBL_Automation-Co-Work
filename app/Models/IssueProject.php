<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IssueProject extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'responsible_user_id',
    ];

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function issues()
    {
        return $this->hasMany(Issue::class, 'issue_project_id');
    }
}
