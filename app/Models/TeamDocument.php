<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TeamDocument extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'tagged_member_ids',
        'tagged_member_names',
        'manual_doc_path',
        'manual_doc_name',
        'source_code_path',
        'source_code_name',
        'database_path',
        'database_name',
        'final_doc_path',
        'final_doc_name',
    ];

    protected $casts = [
        'tagged_member_ids'   => 'array',
        'tagged_member_names' => 'array',
    ];

    protected $appends = [
        'submitter_name',
        'manual_doc_url',
        'source_code_url',
        'database_url',
        'final_doc_url',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            $fileFields = [
                'manual_doc_path',
                'source_code_path',
                'database_path',
                'final_doc_path',
            ];

            foreach ($fileFields as $field) {
                if ($model->isDirty($field) && $model->getOriginal($field)) {
                    Storage::disk('public')->delete($model->getOriginal($field));
                }
            }
        });

        static::deleted(function ($model) {
            $fileFields = [
                'manual_doc_path',
                'source_code_path',
                'database_path',
                'final_doc_path',
            ];

            foreach ($fileFields as $field) {
                if ($model->$field) {
                    Storage::disk('public')->delete($model->$field);
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getSubmitterNameAttribute(): string
    {
        return $this->user ? $this->user->name : 'Unknown';
    }

    public function getManualDocUrlAttribute()
    {
        return $this->manual_doc_path ? Storage::disk('public')->url($this->manual_doc_path) : null;
    }

    public function getSourceCodeUrlAttribute()
    {
        return $this->source_code_path ? Storage::disk('public')->url($this->source_code_path) : null;
    }

    public function getDatabaseUrlAttribute()
    {
        return $this->database_path ? Storage::disk('public')->url($this->database_path) : null;
    }

    public function getFinalDocUrlAttribute()
    {
        return $this->final_doc_path ? Storage::disk('public')->url($this->final_doc_path) : null;
    }
}
