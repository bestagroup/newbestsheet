<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaFile extends Model
{
    use HasFactory, Sluggable, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'original_name', 'type', 'file_path', 'size', 'user_id',
        'project_id', 'mime', 'subject_id', 'role', 'company_id', 'status',
        'project_stage_instance_id', 'document_requirement_id', 'disk', 'sha256',
        'scan_status', 'scanned_at', 'version', 'supersedes_id', 'uploaded_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'version' => 'integer',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return route('media.download', ['media' => $this->getKey()]);
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'onUpdate' => $this->shouldSlug(),
            ],
        ];
    }

    protected function shouldSlug()
    {
        return $this->id != 1;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(subject_file::class, 'subject_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStageInstance::class, 'project_stage_instance_id');
    }

    public function documentRequirement(): BelongsTo
    {
        return $this->belongsTo(InvestmentStepDocumentRequirement::class, 'document_requirement_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }
}
