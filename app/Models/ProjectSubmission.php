<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProjectSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_type_id',
        'title',
        'tags',
        'owner_type',
        'owner_name',
        'team_members',
        'team_member_ids',
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
        'tags'               => 'array',
        'team_members'       => 'array',
        'team_member_ids'    => 'array',
        'project_image_paths'=> 'array',
        'document_paths'     => 'array',
        'source_code_paths'  => 'array',
        'dataset_paths'      => 'array',
        'reviewed_at'        => 'datetime',
    ];

    protected $appends = [
        'cover_image_url',
        'document_url',
        'document_urls',
        'source_code_url',
        'source_code_urls',
        'dataset_url',
        'dataset_urls',
        'project_image_urls',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function projectType()
    {
        return $this->belongsTo(ProjectType::class);
    }

    public function contributions()
    {
        return $this->hasMany(GroupContribution::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            $fileFields = [
                'cover_image_path',
                'document_path',
                'source_code_path',
                'dataset_path',
            ];

            foreach ($fileFields as $field) {
                if ($model->isDirty($field) && $model->getOriginal($field)) {
                    Storage::disk('public')->delete($model->getOriginal($field));
                }
            }

            $arrayFileFields = [
                'document_paths',
                'source_code_paths',
                'dataset_paths',
                'project_image_paths',
            ];

            foreach ($arrayFileFields as $field) {
                if ($model->isDirty($field)) {
                    $original = $model->getOriginal($field);
                    $original = is_array($original) ? $original : [];
                    $current = $model->$field;
                    $current = is_array($current) ? $current : [];
                    $deleted = array_diff($original, $current);
                    foreach ($deleted as $path) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
        });

        static::deleted(function ($model) {
            $documentPaths = is_array($model->document_paths) ? $model->document_paths : [];
            $sourceCodePaths = is_array($model->source_code_paths) ? $model->source_code_paths : [];
            $datasetPaths = is_array($model->dataset_paths) ? $model->dataset_paths : [];
            $projectImagePaths = is_array($model->project_image_paths) ? $model->project_image_paths : [];

            $paths = array_filter([
                $model->cover_image_path,
                $model->document_path,
                $model->source_code_path,
                $model->dataset_path,
                ...$documentPaths,
                ...$sourceCodePaths,
                ...$datasetPaths,
                ...$projectImagePaths,
            ]);

            foreach ($paths as $path) {
                Storage::disk('public')->delete($path);
            }
        });
    }

    public function getCoverImageUrlAttribute()
    {
        return $this->cover_image_path ? Storage::disk('public')->url($this->cover_image_path) : null;
    }

    public function getDocumentUrlAttribute()
    {
        return $this->document_path ? Storage::disk('public')->url($this->document_path) : null;
    }

    public function getDocumentUrlsAttribute()
    {
        $paths = $this->document_paths;
        if (empty($paths) || !is_array($paths)) {
            return [];
        }
        return array_map(function ($path) {
            return Storage::disk('public')->url($path);
        }, $paths);
    }

    public function getSourceCodeUrlAttribute()
    {
        return $this->source_code_path ? Storage::disk('public')->url($this->source_code_path) : null;
    }

    public function getSourceCodeUrlsAttribute()
    {
        $paths = $this->source_code_paths;
        if (empty($paths) || !is_array($paths)) {
            return [];
        }
        return array_map(function ($path) {
            return Storage::disk('public')->url($path);
        }, $paths);
    }

    public function getDatasetUrlAttribute()
    {
        return $this->dataset_path ? Storage::disk('public')->url($this->dataset_path) : null;
    }

    public function getDatasetUrlsAttribute()
    {
        $paths = $this->dataset_paths;
        if (empty($paths) || !is_array($paths)) {
            return [];
        }
        return array_map(function ($path) {
            return Storage::disk('public')->url($path);
        }, $paths);
    }

    public function getProjectImageUrlsAttribute()
    {
        $paths = $this->project_image_paths;
        if (empty($paths) || !is_array($paths)) {
            return [];
        }
        return array_map(function ($path) {
            return Storage::disk('public')->url($path);
        }, $paths);
    }
}
