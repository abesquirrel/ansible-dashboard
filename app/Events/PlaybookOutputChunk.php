<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlaybookOutputChunk implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $jobId,
        public readonly string $chunk
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("job.{$this->jobId}");
    }

    public function broadcastAs(): string
    {
        return 'output.chunk';
    }
}
