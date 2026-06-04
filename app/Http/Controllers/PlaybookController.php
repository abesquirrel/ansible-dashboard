<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AnsibleService;
use App\Models\PlaybookJob;

class PlaybookController extends Controller
{
    public function __construct(protected AnsibleService $ansible) {}

    public function index()
    {
        try {
            $playbooks = $this->ansible->listPlaybooks();
        } catch (\Throwable $e) {
            $playbooks = [];
            session()->flash('warning', 'Playbook list unavailable: ' . $e->getMessage() . ' — configure SSH credentials in .env.');
        }
        $jobs = PlaybookJob::with('user')->latest()->paginate(20);
        return view('playbooks.index', compact('playbooks', 'jobs'));
    }

    public function show(PlaybookJob $job)
    {
        $job->load(['outputLines', 'user']);
        return view('playbooks.show', compact('job'));
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'playbook'   => 'required|string',
            'inventory'  => 'nullable|string',
            'extra_vars' => 'nullable|array',
            'tags'       => 'nullable|array',
            'limit'      => 'nullable|string',
            'check_mode' => 'nullable|boolean',
            'verbose'    => 'nullable|boolean',
        ]);

        $job = $this->ansible->runPlaybook(
            playbook:   $data['playbook'],
            inventory:  $data['inventory'] ?? '',
            extraVars:  $data['extra_vars'] ?? [],
            tags:       $data['tags'] ?? [],
            limit:      $data['limit'] ?? '',
            checkMode:  $data['check_mode'] ?? false,
            verbose:    $data['verbose'] ?? false,
            userId:     auth()->id(),
        );

        if ($request->expectsJson()) {
            return response()->json(['job_id' => $job->id, 'status' => 'queued']);
        }

        return redirect()->route('jobs.show', $job)
            ->with('success', "Playbook queued — Job #{$job->id}");
    }

    public function getContent(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        try {
            $content = $this->ansible->getPlaybookContent($request->path);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
        return response()->json(['content' => $content]);
    }

    public function abort(PlaybookJob $job)
    {
        if (!$job->isRunning()) {
            return response()->json(['error' => 'Job is not running'], 400);
        }
        // Kill via SSH process management — find the ansible-playbook PID and kill
        // In practice you'd track the PID; here we update status
        $job->update(['status' => 'aborted', 'finished_at' => now()]);
        return response()->json(['status' => 'aborted']);
    }

    public function outputLines(PlaybookJob $job, Request $request)
    {
        $lines = $job->outputLines()
            ->when($request->after, fn ($q) => $q->where('id', '>', $request->after))
            ->limit(500)
            ->get();

        return response()->json([
            'lines'       => $lines,
            'last_id'     => $lines->last()?->id,
            'job_status'  => $job->fresh()->status,
        ]);
    }
}
