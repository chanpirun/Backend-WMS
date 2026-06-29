<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GroupContribution extends Model
{
    protected $fillable = [
        'project_submission_id',
        'user_id',
        'category',
        'file_path',
        'file_name',
    ];

    protected $appends = ['user_name', 'file_url'];

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            if ($model->isDirty('file_path') && $model->getOriginal('file_path')) {
                Storage::disk('public')->delete($model->getOriginal('file_path'));
            }
        });

        static::deleted(function ($model) {
            if ($model->file_path) {
                Storage::disk('public')->delete($model->file_path);
            }
        });
    }

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

    public function getFileUrlAttribute()
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }
}
