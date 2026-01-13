<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectBitbucketRepository extends Model
{
    protected $fillable = [
        'project_id',
        'repo_slug',
        'repo_name',
        'workspace',
        'repo_uuid',
        'last_sync_at',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
    ];

    /**
     * Get the project that owns this repository link.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get commits for this repository.
     */
    public function commits(): HasMany
    {
        return $this->hasMany(BitbucketCommit::class, 'repo_slug', 'repo_slug')
            ->where('project_id', $this->project_id);
    }

    /**
     * Get the display name (repo_name or repo_slug).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->repo_name ?: $this->repo_slug;
    }
}
