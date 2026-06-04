<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PlaybookJob;
use App\Models\JobOutputLine;
use App\Services\AnsibleSSHService;
use App\Events\PlaybookOutputChunk;
use App\Events\PlaybookFinished;
use Illuminate\Support\Facades\Log;

class RunPlaybookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 hour max
    public int $tries   = 1;

    public function __construct(public readonly int $jobId) {}

    public function handle(AnsibleSSHService $ssh): void
    {
        $job = PlaybookJob::findOrFail($this->jobId);

        $job->update([
            'status'     => 'running',
            'started_at' => now(),
        ]);

        $lineBuffer = '';
        $exitCode   = 0;

        try {
            $exitCode = $ssh->execStreaming(
                $job->command,
                function (string $chunk) use ($job, &$lineBuffer) {
                    // Broadcast raw chunk immediately for terminal display
                    broadcast(new PlaybookOutputChunk($job->id, $chunk))->toOthers();

                    // Buffer for line-by-line DB storage
                    $lineBuffer .= $chunk;
                    while (($pos = strpos($lineBuffer, "\n")) !== false) {
                        $line        = substr($lineBuffer, 0, $pos);
                        $lineBuffer  = substr($lineBuffer, $pos + 1);
                        $this->storeLine($job->id, $line);
                    }
                },
                $job->user_id
            );

            // Flush remaining buffer
            if ($lineBuffer !== '') {
                $this->storeLine($job->id, $lineBuffer);
            }

            $summary = $this->parseSummary($job);
            $status  = ($exitCode === 0) ? 'success' : 'failed';

            $job->update(array_merge([
                'status'      => $status,
                'exit_code'   => $exitCode,
                'finished_at' => now(),
            ], $summary));

        } catch (\Throwable $e) {
            Log::error('Playbook job failed', ['job_id' => $job->id, 'error' => $e->getMessage()]);
            $job->update([
                'status'      => 'error',
                'exit_code'   => -1,
                'finished_at' => now(),
                'summary'     => $e->getMessage(),
            ]);
            $exitCode = -1;
        }

        broadcast(new PlaybookFinished($job->id, $exitCode));
    }

    protected function storeLine(int $jobId, string $line): void
    {
        $type = 'output';
        if (str_contains($line, 'ok=') && str_contains($line, 'changed=')) {
            $type = 'recap';
        } elseif (str_contains($line, 'PLAY RECAP')) {
            $type = 'recap';
        } elseif (str_contains($line, 'FAILED') || str_contains($line, 'ERROR')) {
            $type = 'error';
        } elseif (str_contains($line, 'changed')) {
            $type = 'changed';
        } elseif (str_contains($line, 'ok:')) {
            $type = 'ok';
        }

        JobOutputLine::insert([
            'job_id'     => $jobId,
            'line'       => $line,
            'type'       => $type,
            'created_at' => now(),
        ]);
    }

    protected function parseSummary(PlaybookJob $job): array
    {
        $recap = JobOutputLine::where('job_id', $job->id)
            ->where('type', 'recap')
            ->pluck('line')
            ->join("\n");

        $summary = [];
        // Parse: hostname : ok=3 changed=1 unreachable=0 failed=0 skipped=0
        if (preg_match_all('/ok=(\d+)\s+changed=(\d+)\s+unreachable=(\d+)\s+failed=(\d+)\s+skipped=(\d+)/', $recap, $m)) {
            $summary = [
                'hosts_ok'          => array_sum($m[1]),
                'hosts_changed'     => array_sum($m[2]),
                'hosts_unreachable' => array_sum($m[3]),
                'hosts_failed'      => array_sum($m[4]),
                'hosts_skipped'     => array_sum($m[5]),
            ];
        }

        return $summary;
    }
}
