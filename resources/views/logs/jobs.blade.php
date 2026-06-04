@extends('layouts.app')
@section('title', 'Job History')

@section('content')
<div class="page-header">
    <div style="padding-bottom:20px">
        <h1 class="page-title">Job History</h1>
        <p class="page-subtitle">All playbook executions</p>
    </div>
</div>

<div class="page-body">
    <div class="card mb-4">
        <div class="card-body" style="padding:14px 18px">
            <form method="GET" class="flex gap-2 items-center flex-wrap">
                <input type="text" name="playbook" value="{{ request('playbook') }}" class="form-input" style="width:220px" placeholder="Filter by playbook…">
                <select name="status" class="form-select" style="width:140px">
                    <option value="">All statuses</option>
                    @foreach(['queued','running','success','failed','error','aborted'] as $s)
                    <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-secondary">Filter</button>
                <a href="{{ route('logs.jobs') }}" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Playbook</th>
                        <th>Status</th>
                        <th>User</th>
                        <th>Inventory</th>
                        <th>Summary</th>
                        <th>Duration</th>
                        <th>Started</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                    <tr>
                        <td class="text-mono text-xs text-muted">{{ $job->id }}</td>
                        <td>
                            <span class="text-mono text-sm" style="color:var(--text-primary)">{{ basename($job->playbook) }}</span>
                            @if($job->check_mode)
                                <span class="badge" style="background:var(--blue-dim);color:var(--blue);margin-left:6px">CHECK</span>
                            @endif
                            @if($job->limit)
                                <div class="text-xs text-muted text-mono" style="margin-top:2px">limit: {{ $job->limit }}</div>
                            @endif
                        </td>
                        <td><span class="badge badge-{{ $job->status }}">{{ $job->status }}</span></td>
                        <td class="text-sm">{{ $job->user?->name ?? '—' }}</td>
                        <td class="text-mono text-xs text-muted">{{ basename($job->inventory) }}</td>
                        <td class="text-mono text-xs">
                            @if($job->hosts_ok + $job->hosts_changed + $job->hosts_failed > 0)
                                <span class="text-green">ok={{ $job->hosts_ok }}</span>
                                <span style="color:var(--yellow)"> chg={{ $job->hosts_changed }}</span>
                                @if($job->hosts_failed > 0)
                                    <span class="text-red"> fail={{ $job->hosts_failed }}</span>
                                @endif
                                @if($job->hosts_unreachable > 0)
                                    <span class="text-red"> unr={{ $job->hosts_unreachable }}</span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-mono text-xs text-muted">{{ $job->duration ?? '—' }}</td>
                        <td class="text-xs text-muted" style="white-space:nowrap">
                            {{ $job->started_at?->format('m-d H:i:s') ?? $job->created_at->format('m-d H:i:s') }}
                        </td>
                        <td><a href="{{ route('jobs.show', $job) }}" class="btn btn-sm btn-secondary">View</a></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:32px;color:var(--text-muted);font-family:var(--font-mono)">No jobs found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($jobs->hasPages())
        <div style="padding:14px 18px;border-top:1px solid var(--border)">
            {{ $jobs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
