<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlaybookFinished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $jobId,
        public readonly int $exitCode
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("job.{$this->jobId}");
    }

    public function broadcastAs(): string
    {
        return 'job.finished';
    }
}
