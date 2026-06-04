<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOutputLine extends Model
{
    public $timestamps = false;
    protected $fillable = ['job_id', 'line', 'type', 'created_at'];
    protected $casts    = ['created_at' => 'datetime'];

    public function job(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlaybookJob::class, 'job_id');
    }
}
