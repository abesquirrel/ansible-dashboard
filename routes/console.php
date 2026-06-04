<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\ScheduledJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run enabled scheduled playbook jobs
Schedule::call(function () {
    ScheduledJob::where('enabled', true)
        ->where(function ($q) {
            $q->whereNull('next_run_at')
              ->orWhere('next_run_at', '<=', now());
        })
        ->each(function (ScheduledJob $scheduled) {
            $ansible = app(\App\Services\AnsibleService::class);
            $job = $ansible->runPlaybook(
                playbook:  $scheduled->playbook,
                inventory: $scheduled->inventory ?? '',
                extraVars: $scheduled->extra_vars ?? [],
                tags:      $scheduled->tags ? explode(',', $scheduled->tags) : [],
                limit:     $scheduled->limit ?? '',
                userId:    $scheduled->user_id,
            );

            $scheduled->update([
                'last_run_at' => now(),
                'next_run_at' => \Cron\CronExpression::factory($scheduled->cron_expression)->getNextRunDate(),
            ]);
        });
})->everyMinute()->name('ansible-scheduled-jobs')->withoutOverlapping();

// Prune old job output lines (keep 30 days)
Schedule::call(function () {
    \App\Models\JobOutputLine::whereHas('job', function ($q) {
        $q->where('created_at', '<', now()->subDays(30));
    })->delete();
})->daily()->name('prune-job-output');

// Prune old audit logs (keep 90 days)
Schedule::call(function () {
    \App\Models\AuditLog::where('created_at', '<', now()->subDays(90))->delete();
})->weekly()->name('prune-audit-logs');
