<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\HR\Models\Employee;

class BitbucketCommit extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'repo_slug',
        'commit_hash',
        'short_hash',
        'message',
        'author_name',
        'author_email',
        'author_username',
        'committed_at',
        'branch',
        'additions',
        'deletions',
        'files_changed',
        'bitbucket_url',
    ];

    protected $casts = [
        'committed_at' => 'datetime',
        'files_changed' => 'array',
        'additions' => 'integer',
        'deletions' => 'integer',
    ];

    /**
     * Get the project that owns this commit.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Try to find the associated employee by email.
     */
    public function getEmployeeAttribute()
    {
        if (!$this->author_email) {
            return null;
        }

        return Employee::whereHas('user', function ($query) {
            $query->where('email', $this->author_email);
        })->first();
    }

    /**
     * Get the first line of the commit message.
     */
    public function getMessageSummaryAttribute(): string
    {
        $firstLine = strtok($this->message, "\n");
        return strlen($firstLine) > 80 ? substr($firstLine, 0, 77) . '...' : $firstLine;
    }

    /**
     * Get total lines changed.
     */
    public function getTotalChangesAttribute(): int
    {
        return $this->additions + $this->deletions;
    }

    /**
     * Get files changed count.
     */
    public function getFilesCountAttribute(): int
    {
        return is_array($this->files_changed) ? count($this->files_changed) : 0;
    }

    /**
     * Scope for commits in a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('committed_at', [$startDate, $endDate]);
    }

    /**
     * Scope for commits by author email.
     */
    public function scopeByAuthor($query, $email)
    {
        return $query->where('author_email', $email);
    }

    /**
     * Scope for recent commits.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('committed_at', '>=', now()->subDays($days));
    }
}
