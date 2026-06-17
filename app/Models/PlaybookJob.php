<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlaybookJob extends Model
{
    protected $fillable = [
        'user_id', 'playbook', 'inventory', 'command',
        'extra_vars', 'tags', 'limit', 'check_mode',
        'status', 'started_at', 'finished_at', 'exit_code',
        'summary', 'hosts_ok', 'hosts_changed',
        'hosts_unreachable', 'hosts_failed', 'hosts_skipped',
    ];

    protected $casts = [
        'check_mode'  => 'boolean',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
        'extra_vars'  => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function outputLines(): HasMany
    {
        return $this->hasMany(JobOutputLine::class, 'job_id')->orderBy('id');
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at || !$this->finished_at) return null;
        $secs = $this->started_at->diffInSeconds($this->finished_at);
        return gmdate('H:i:s', $secs);
    }

    public function isRunning(): bool
    {
        return in_array($this->status, ['queued', 'running']);
    }

    public function scopeRecent($query, int $limit = 20)
    {
        return $query->latest()->limit($limit);
    }

    public function getAssessmentAttribute(): array
    {
        return \App\Services\AssessmentParser::parse($this);
    }

    public function hasAssessment(): bool
    {
        return !empty($this->assessment['hosts']);
    }
}
