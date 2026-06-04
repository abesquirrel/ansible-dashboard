@extends('layouts.app')
@section('title', "Job #{{ $job->id }}")

@section('content')
<div class="page-header">
    <div style="padding-bottom:20px">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('playbooks.index') }}" style="color:var(--text-muted);font-family:var(--font-mono);font-size:12px;text-decoration:none">← Playbooks</a>
        </div>
        <div class="flex items-center gap-3">
            <h1 class="page-title">Job #{{ $job->id }}</h1>
            <span class="badge badge-{{ $job->status }}" id="job-status-badge">{{ $job->status }}</span>
            @if($job->check_mode)
                <span class="badge" style="background:var(--blue-dim);color:var(--blue)">CHECK MODE</span>
            @endif
        </div>
        <p class="page-subtitle" style="margin-top:6px;font-family:var(--font-mono)">{{ $job->playbook }}</p>
    </div>
</div>

<div class="page-body">

    {{-- Meta row --}}
    <div class="card mb-4">
        <div class="card-body" style="padding:16px 20px">
            <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:20px">
                <div>
                    <div class="form-label" style="margin-bottom:3px">Inventory</div>
                    <div class="text-mono text-sm" style="color:var(--text-primary)">{{ basename($job->inventory) }}</div>
                </div>
                <div>
                    <div class="form-label" style="margin-bottom:3px">User</div>
                    <div class="text-mono text-sm">{{ $job->user?->name ?? 'system' }}</div>
                </div>
                <div>
                    <div class="form-label" style="margin-bottom:3px">Started</div>
                    <div class="text-mono text-sm">{{ $job->started_at?->format('H:i:s') ?? '—' }}</div>
                </div>
                <div>
                    <div class="form-label" style="margin-bottom:3px">Duration</div>
                    <div class="text-mono text-sm" id="job-duration">{{ $job->duration ?? '—' }}</div>
                </div>
                <div>
                    <div class="form-label" style="margin-bottom:3px">Exit Code</div>
                    <div class="text-mono text-sm" id="job-exit" style="color:{{ $job->exit_code === 0 ? 'var(--green)' : ($job->exit_code === null ? 'var(--text-muted)' : 'var(--red)') }}">
                        {{ $job->exit_code ?? '—' }}
                    </div>
                </div>
                <div>
                    @if($job->isRunning())
                    <button id="abort-btn" class="btn btn-danger btn-sm"
                        onclick="abortJob({{ $job->id }})">Abort</button>
                    @endif
                </div>
            </div>

            @if($job->hosts_ok > 0 || $job->hosts_changed > 0 || $job->hosts_failed > 0)
            <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);display:flex;gap:20px" id="recap-row">
                <span class="text-mono text-xs"><span style="color:var(--text-muted)">ok=</span><span class="text-green">{{ $job->hosts_ok }}</span></span>
                <span class="text-mono text-xs"><span style="color:var(--text-muted)">changed=</span><span style="color:var(--yellow)">{{ $job->hosts_changed }}</span></span>
                <span class="text-mono text-xs"><span style="color:var(--text-muted)">unreachable=</span><span style="color:var(--red)">{{ $job->hosts_unreachable }}</span></span>
                <span class="text-mono text-xs"><span style="color:var(--text-muted)">failed=</span><span style="color:var(--red)">{{ $job->hosts_failed }}</span></span>
                <span class="text-mono text-xs"><span style="color:var(--text-muted)">skipped=</span><span class="text-muted">{{ $job->hosts_skipped }}</span></span>
            </div>
            @endif
        </div>
    </div>

    {{-- Output --}}
    <div class="term-wrap">
        <div class="term-bar">
            <div class="term-dot r"></div>
            <div class="term-dot y"></div>
            <div class="term-dot g"></div>
            <div class="term-title text-mono text-xs">ansible-playbook output — job #{{ $job->id }}</div>
            <button onclick="copyOutput()" class="btn btn-sm btn-secondary" style="padding:2px 8px;font-size:10px">Copy</button>
        </div>
        <div id="output-container" style="
            background:#0a0a0a;
            font-family:var(--font-mono);
            font-size:12px;
            line-height:1.6;
            padding:16px;
            min-height:400px;
            max-height:70vh;
            overflow-y:auto;
            white-space:pre-wrap;
            word-break:break-word;
        ">
            @foreach($job->outputLines as $line)
            <div class="ol-{{ $line->type }}">{{ $line->line }}</div>
            @endforeach
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
.ol-output      { color: #c8d3e0; }
.ol-ok          { color: #39d98a; }
.ol-changed     { color: #ffd32a; }
.ol-error       { color: #ff4757; }
.ol-recap       { color: #3dc6ff; font-weight:600; margin-top:4px; border-top:1px solid #1a2030; padding-top:4px; }
.ol-output:empty::after { content:'\00a0'; }
</style>
@endpush

@push('scripts')
<script>
(function() {
    const jobId     = {{ $job->id }};
    const isRunning = {{ $job->isRunning() ? 'true' : 'false' }};
    const container = document.getElementById('output-container');

    let lastId = {{ $job->outputLines->last()?->id ?? 0 }};
    let polling = isRunning;

    function scrollBottom() {
        container.scrollTop = container.scrollHeight;
    }

    function appendLines(lines) {
        lines.forEach(l => {
            const div = document.createElement('div');
            div.className = `ol-${l.type}`;
            div.textContent = l.line;
            container.appendChild(div);
        });
        scrollBottom();
    }

    function updateStatus(status) {
        const badge = document.getElementById('job-status-badge');
        badge.className = `badge badge-${status}`;
        badge.textContent = status;

        if (status === 'success' || status === 'failed' || status === 'error' || status === 'aborted') {
            polling = false;
            const btn = document.getElementById('abort-btn');
            if (btn) btn.remove();
            // reload page to get final summary
            setTimeout(() => location.reload(), 800);
        }
    }

    async function poll() {
        if (!polling) return;
        try {
            const r = await api(`/jobs/${jobId}/output?after=${lastId}`);
            if (r.lines && r.lines.length) {
                appendLines(r.lines);
                lastId = r.last_id;
            }
            updateStatus(r.job_status);
        } catch {}

        if (polling) setTimeout(poll, 1500);
    }

    scrollBottom();
    if (isRunning) poll();

    window.copyOutput = function() {
        const text = Array.from(container.querySelectorAll('div'))
            .map(d => d.textContent)
            .join('\n');
        navigator.clipboard.writeText(text);
    };

    window.abortJob = async function(id) {
        if (!confirm('Abort this job?')) return;
        await api(`/jobs/${id}/abort`, { method: 'POST' });
        location.reload();
    };
})();
</script>
@endpush
