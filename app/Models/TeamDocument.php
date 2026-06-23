<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    protected $appends = ['submitter_name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getSubmitterNameAttribute(): string
    {
        return $this->user ? $this->user->name : 'Unknown';
    }
}
