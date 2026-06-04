<?php

use Illuminate\Support\Facades\Broadcast;

// Public channel for playbook job output (authenticated users can subscribe)
Broadcast::channel('job.{jobId}', function ($user, $jobId) {
    return auth()->check();
});

// Private channel for interactive terminal sessions
Broadcast::channel('terminal.{sessionId}', function ($user, $sessionId) {
    return auth()->check();
});
