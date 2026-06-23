<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupContribution extends Model
{
    protected $fillable = [
        'project_submission_id',
        'user_id',
        'category',
        'file_path',
        'file_name',
    ];

    protected $appends = ['user_name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function projectSubmission()
    {
        return $this->belongsTo(ProjectSubmission::class);
    }

    public function getUserNameAttribute(): string
    {
        return $this->user ? $this->user->name : 'Unknown User';
    }
}
