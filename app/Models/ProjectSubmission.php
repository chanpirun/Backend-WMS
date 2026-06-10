<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'tags',
        'owner_type',
        'owner_name',
        'team_members',
        'description',
        'cover_image_path',
        'document_path',
        'document_paths',
        'source_code_path',
        'source_code_paths',
        'dataset_path',
        'dataset_paths',
        'project_image_paths',
        'demo_link',
        'status',
        'review_comment',
        'reviewed_by_role',
        'reviewed_at',
        'visibility',
    ];

    protected $casts = [
        'tags' => 'array',
        'team_members' => 'array',
        'project_image_paths' => 'array',
        'document_paths' => 'array',
        'source_code_paths' => 'array',
        'dataset_paths' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
