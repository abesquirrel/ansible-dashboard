<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledJob extends Model
{
    protected $fillable = [
        'user_id', 'name', 'playbook', 'inventory',
        'extra_vars', 'tags', 'limit', 'cron_expression',
        'enabled', 'last_run_at', 'next_run_at',
    ];

    protected $casts = [
        'enabled'     => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'extra_vars'  => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
