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

    {{-- Tabs selection if assessment is available --}}
    @if($job->hasAssessment())
    <div class="flex items-center gap-2 mb-6" style="border-bottom: 1px solid var(--border); padding-bottom: 2px;">
        <button onclick="switchTab('assessment')" id="assessment-tab-btn" class="tab-btn active">Assessment Report</button>
        <button onclick="switchTab('console')" id="console-tab-btn" class="tab-btn">Console Output</button>
    </div>
    @endif

    {{-- Assessment tab pane --}}
    @if($job->hasAssessment())
    <div id="assessment-tab-pane" class="tab-pane" style="display: block;">
        {{-- Stats Summary --}}
        <div class="stats-grid mb-6">
            <div class="stat-card blue">
                <div class="stat-label">Total Hosts Checked</div>
                <div class="stat-value">{{ count($job->assessment['hosts']) }}</div>
            </div>
            <div class="stat-card green">
                <div class="stat-label">Healthy Nodes</div>
                <div class="stat-value">{{ collect($job->assessment['hosts'])->where('status', 'success')->count() }}</div>
            </div>
            @php
                $failedCount = collect($job->assessment['hosts'])->whereIn('status', ['failed', 'unreachable'])->count();
            @endphp
            <div class="stat-card {{ $failedCount > 0 ? 'red' : 'blue' }}">
                <div class="stat-label">Issues / Offline</div>
                <div class="stat-value">{{ $failedCount }}</div>
            </div>
        </div>

        {{-- Failed/Unreachable Alerts --}}
        @foreach($job->assessment['hosts'] as $host)
            @if($host['status'] !== 'success')
            <div class="alert alert-error mb-4" style="padding:16px 20px; display:flex; align-items:flex-start; gap:16px">
                <div style="background:var(--red); color:var(--bg-base); border-radius:50%; width:24px; height:24px; display:grid; place-items:center; font-weight:bold; flex-shrink:0">!</div>
                <div>
                    <h4 style="font-family:var(--font-mono); font-size:14px; font-weight:600; margin-bottom:4px">{{ $host['name'] }} ({{ strtoupper($host['status']) }})</h4>
                    <p style="color:rgba(255,255,255,0.7); font-size:12px; font-family:var(--font-mono)">{{ $host['error'] }}</p>
                </div>
            </div>
            @endif
        @endforeach

        {{-- Status Reports (check_status.yml) --}}
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap:20px;" class="mb-6">
            @foreach($job->assessment['hosts'] as $host)
                @if($host['status'] === 'success' && $host['type'] === 'status_report')
                    @php
                        $vitals = $host['data'];
                        $diskPercent = (int)str_replace('%', '', $vitals['disk'] ?? '0');
                        
                        $ramFree = 0; $ramTotal = 1;
                        if (preg_match('/(\d+)\s*MB\s*free\s*\/\s*(\d+)\s*MB\s*total/i', $vitals['ram'] ?? '', $ramMatch)) {
                            $ramFree = (int)$ramMatch[1];
                            $ramTotal = (int)$ramMatch[2];
                        }
                        $ramUsedPercent = round((($ramTotal - $ramFree) / $ramTotal) * 100);
                    @endphp
                    <div class="card">
                        <div class="card-header" style="border-bottom:1px solid var(--border)">
                            <div class="conn-dot connected" style="margin-right:8px"></div>
                            <span class="card-title" style="font-size:13px">{{ $host['name'] }}</span>
                            <span class="badge badge-success ml-auto" style="font-size:9px">Active</span>
                        </div>
                        <div class="card-body">
                            <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-family:var(--font-mono); font-size:12px">
                                <span class="text-muted">SSH/Ping:</span>
                                <span class="text-green" style="font-weight:600">{{ $vitals['ping'] ?? 'PONG' }}</span>
                            </div>
                            
                            <div style="margin-bottom:16px; font-size:12px">
                                <span class="text-muted" style="display:block; font-family:var(--font-mono); margin-bottom:2px">Uptime & Load Average:</span>
                                <span style="font-family:var(--font-mono); color:var(--text-primary)">{{ $vitals['uptime'] ?? 'n/a' }}</span>
                            </div>

                            <div style="margin-bottom:16px">
                                <div style="display:flex; justify-content:space-between; font-size:11px; font-family:var(--font-mono); margin-bottom:4px">
                                    <span class="text-muted">Disk Usage (Root /)</span>
                                    <span class="{{ $diskPercent > 80 ? 'text-red' : ($diskPercent > 60 ? 'text-yellow' : 'text-green') }}">{{ $vitals['disk'] ?? '0%' }}</span>
                                </div>
                                <div style="background:var(--bg-surface); height:6px; border-radius:3px; overflow:hidden; border:1px solid var(--border)">
                                    <div style="background:{{ $diskPercent > 80 ? 'var(--red)' : ($diskPercent > 60 ? 'var(--yellow)' : 'var(--green)') }}; width:{{ $diskPercent }}%; height:100%"></div>
                                </div>
                            </div>

                            <div>
                                <div style="display:flex; justify-content:space-between; font-size:11px; font-family:var(--font-mono); margin-bottom:4px">
                                    <span class="text-muted">RAM Usage ({{ $ramTotal - $ramFree }} MB / {{ $ramTotal }} MB)</span>
                                    <span class="{{ $ramUsedPercent > 90 ? 'text-red' : ($ramUsedPercent > 70 ? 'text-yellow' : 'text-blue') }}">{{ $ramUsedPercent }}%</span>
                                </div>
                                <div style="background:var(--bg-surface); height:6px; border-radius:3px; overflow:hidden; border:1px solid var(--border)">
                                    <div style="background:{{ $ramUsedPercent > 90 ? 'var(--red)' : ($ramUsedPercent > 70 ? 'var(--yellow)' : 'var(--blue)') }}; width:{{ $ramUsedPercent }}%; height:100%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Detailed Device Reports (identify_devices.yml) --}}
        @foreach($job->assessment['hosts'] as $host)
            @if($host['status'] === 'success' && $host['type'] === 'device_report')
                @php
                    $device = $host['data'];
                    $net = $device['network'] ?? [];
                    $hw = $device['hardware'] ?? [];
                    $os = $device['os'] ?? [];
                    $run = $device['runtime'] ?? [];
                @endphp
                <div class="card mb-6">
                    <div class="card-header" style="cursor:pointer; display:flex; align-items:center; background:var(--bg-surface)" onclick="toggleDeviceReport('{{ $host['name'] }}')">
                        <div class="conn-dot connected" style="margin-right:10px"></div>
                        <span class="card-title" style="font-size:14px; font-family:var(--font-mono)">{{ $host['name'] }}</span>
                        <span class="badge badge-success" style="margin-left:12px">Identified</span>
                        <span style="font-size:11px; color:var(--text-muted); font-family:var(--font-mono); margin-left:16px">{{ $net['primary ip'] ?? '' }}</span>
                        
                        <button class="btn btn-secondary btn-sm ml-auto" style="padding:2px 8px; font-size:10px" id="toggle-btn-{{ $host['name'] }}">Collapse</button>
                    </div>
                    <div class="card-body" id="report-body-{{ $host['name'] }}" style="padding:24px">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px">
                            
                            {{-- Column 1: Network & OS --}}
                            <div style="display:flex; flex-direction:column; gap:20px">
                                {{-- Network --}}
                                <div>
                                    <h4 style="font-family:var(--font-mono); font-size:12px; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:8px; text-transform:uppercase; color:var(--blue)">Network Profile</h4>
                                    <table style="width:100%; font-size:12px; font-family:var(--font-mono)">
                                        <tr><td style="color:var(--text-muted); padding:3px 0; width:100px">Hostname:</td><td style="color:var(--text-primary)">{{ $net['hostname'] ?? 'n/a' }}</td></tr>
                                        <tr><td style="color:var(--text-muted); padding:3px 0">FQDN:</td><td style="color:var(--text-primary)">{{ $net['fqdn'] ?? 'n/a' }}</td></tr>
                                        <tr><td style="color:var(--text-muted); padding:3px 0">Primary IP:</td><td style="color:var(--text-primary)">{{ $net['primary ip'] ?? 'n/a' }} ({{ $net['primary if'] ?? 'n/a' }})</td></tr>
                                        <tr><td style="color:var(--text-muted); padding:3px 0">Primary MAC:</td><td style="color:var(--text-primary)">{{ $net['primary mac'] ?? 'n/a' }}</td></tr>
                                        <tr><td style="color:var(--text-muted); padding:3px 0">Gateway:</td><td style="color:var(--text-primary)">{{ $net['gateway'] ?? 'n/a' }}</td></tr>
                                        @if(isset($net['all_addresses']))
                                        <tr>
                                            <td style="color:var(--text-muted); padding:3px 0; vertical-align:top">IP Addrs:</td>
                                            <td style="color:var(--text-primary); font-size:11px">
                                                @foreach($net['all_addresses'] as $addr)
                                                    <div>{{ $addr }}</div>
                                                @endforeach
                                            </td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                                
                                {{-- Operating System --}}
                                <div>
                                    <h4 style="font-family:var(--font-mono); font-size:12px; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:8px; text-transform:uppercase; color:var(--yellow)">Operating System</h4>
                                    <table style="width:100%; font-size:12px; font-family:var(--font-mono)">
                                        <tr><td style="color:var(--text-muted); padding:3px 0; width:100px">OS:</td><td style="color:var(--text-primary)">{{ $os['os'] ?? 'n/a' }}</td></tr>
                                        <tr><td style="color:var(--text-muted); padding:3px 0">Distro:</td><td style="color:var(--text-primary)">{{ $os['distro'] ?? 'n/a' }}</td></tr>
                                        <tr><td style="color:var(--text-muted); padding:3px 0">Kernel:</td><td style="color:var(--text-primary)">{{ $os['kernel'] ?? 'n/a' }}</td></tr>
                                        <tr><td style="color:var(--text-muted); padding:3px 0">Architecture:</td><td style="color:var(--text-primary)">{{ $os['architecture'] ?? 'n/a' }}</td></tr>
                                        <tr><td style="color:var(--text-muted); padding:3px 0">Python:</td><td style="color:var(--text-primary)">{{ $os['python'] ?? 'n/a' }}</td></tr>
                                        <tr><td style="color:var(--text-muted); padding:3px 0">Packages:</td><td style="color:var(--text-primary)">{{ $os['packages'] ?? 'n/a' }}</td></tr>
                                    </table>
                                </div>
                            </div>

                            {{-- Column 2: Hardware & Services --}}
                            <div style="display:flex; flex-direction:column; gap:20px">
                                {{-- Hardware & Runtime --}}
                                <div>
                                    <h4 style="font-family:var(--font-mono); font-size:12px; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:8px; text-transform:uppercase; color:var(--green)">Hardware & Runtime</h4>
                                    <table style="width:100%; font-size:12px; font-family:var(--font-mono)">
                                        <tr><td style="color:var(--text-muted); padding:3px 0; width:100px">CPU Model:</td><td style="color:var(--text-primary); font-size:11px">{{ $hw['cpu model'] ?? 'n/a' }}</td></tr>
                                        <tr><td style="color:var(--text-muted); padding:3px 0">CPU Cores:</td><td style="color:var(--text-primary)">{{ $hw['cpu cores'] ?? 'n/a' }}</td></tr>
                                        <tr><td style="color:var(--text-muted); padding:3px 0">RAM Size:</td><td style="color:var(--text-primary)">{{ $hw['total ram'] ?? 'n/a' }}</td></tr>
                                        <tr><td style="color:var(--text-muted); padding:3px 0">Virtualised:</td><td style="color:var(--text-primary); text-transform:uppercase">{{ $hw['virtualised'] ?? 'n/a' }}</td></tr>
                                        <tr><td style="color:var(--text-muted); padding:3px 0">Uptime:</td><td style="color:var(--text-primary)">{{ $run['uptime'] ?? 'n/a' }}</td></tr>
                                        <tr><td style="color:var(--text-muted); padding:3px 0">Load Average:</td><td style="color:var(--text-primary)">{{ $run['load avg'] ?? 'n/a' }}</td></tr>
                                    </table>
                                </div>
                                
                                {{-- Open Ports & Services --}}
                                <div>
                                    <h4 style="font-family:var(--font-mono); font-size:12px; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:8px; text-transform:uppercase; color:var(--orange)">Open Ports & Services</h4>
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
                                        <div>
                                            <span style="font-size:10px; color:var(--text-muted); text-transform:uppercase; font-family:var(--font-mono); display:block; margin-bottom:4px">Listening Ports</span>
                                            <div style="background:var(--bg-base); border:1px solid var(--border); padding:8px; border-radius:4px; max-height:150px; overflow-y:auto; font-family:var(--font-mono); font-size:11px">
                                                @forelse($device['ports'] as $port)
                                                    <div style="color:var(--text-primary); border-bottom:1px solid rgba(255,255,255,0.05); padding:2px 0">{{ $port }}</div>
                                                @empty
                                                    <div style="color:var(--text-muted)">none</div>
                                                @endforelse
                                            </div>
                                        </div>
                                        <div>
                                            <span style="font-size:10px; color:var(--text-muted); text-transform:uppercase; font-family:var(--font-mono); display:block; margin-bottom:4px">Running Services</span>
                                            <div style="background:var(--bg-base); border:1px solid var(--border); padding:8px; border-radius:4px; max-height:150px; overflow-y:auto; font-family:var(--font-mono); font-size:11px">
                                                @forelse($device['services'] as $svc)
                                                    <div style="color:var(--text-primary); border-bottom:1px solid rgba(255,255,255,0.05); padding:2px 0">{{ $svc }}</div>
                                                @empty
                                                    <div style="color:var(--text-muted)">none</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
    @endif

    {{-- Console tab pane --}}
    {{-- Console tab pane --}}
    <div id="console-tab-pane" class="tab-pane" style="display: {{ $job->hasAssessment() ? 'none' : 'block' }}">
        <div class="term-wrap">
            <div class="term-bar">
                <div class="term-dot r"></div>
                <div class="term-dot y"></div>
                <div class="term-dot g"></div>
                <div class="term-title text-mono text-xs">ansible-playbook output — job #{{ $job->id }}</div>
                <button onclick="toggleConsoleMode()" id="console-mode-btn" class="btn btn-sm btn-secondary" style="padding:2px 8px;font-size:10px;margin-right:8px">Raw Mode</button>
                <button onclick="copyOutput()" class="btn btn-sm btn-secondary" style="padding:2px 8px;font-size:10px">Copy</button>
            </div>
            
            {{-- Visual Console Output --}}
            <div id="output-container-visual" style="
                background:#0c0f12;
                font-family:var(--font-ui);
                font-size:13px;
                line-height:1.6;
                padding:20px;
                min-height:400px;
                max-height:70vh;
                overflow-y:auto;
            ">
                {{-- Dynamic visual rows rendered via JS --}}
            </div>

            {{-- Raw Console Output (hidden by default) --}}
            <div id="output-container-raw" style="
                display:none;
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
                <div class="ol-{{ $line->type }}">{{ str_replace(['\n', '\t', '\"'], ["\n", "\t", '"'], $line->line) }}</div>
                @endforeach
            </div>
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

.tab-btn {
    background: transparent;
    border: none;
    padding: 8px 16px;
    font-family: var(--font-mono);
    font-size: 12px;
    color: var(--text-secondary);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all .15s;
    outline: none;
}
.tab-btn:hover {
    color: var(--text-primary);
}
.tab-btn.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
    font-weight: 500;
}

/* Visual Console Styling */
.visual-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 18px;
    margin-bottom: 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid var(--border);
}
.visual-header:first-child {
    margin-top: 0;
}
.visual-header-badge {
    font-family: var(--font-mono);
    font-size: 9px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 3px;
    text-transform: uppercase;
}
.play-badge {
    background: var(--blue-dim);
    color: var(--blue);
}
.task-badge {
    background: var(--bg-surface);
    color: var(--text-secondary);
    border: 1px solid var(--border);
}
.recap-badge {
    background: var(--green-dim);
    color: var(--green);
}
.visual-header-title {
    font-family: var(--font-mono);
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
}

.visual-host-line {
    background: var(--bg-panel);
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 10px 14px;
    margin-bottom: 8px;
    font-family: var(--font-mono);
    font-size: 12px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.visual-host-line-header {
    display: flex;
    align-items: center;
    gap: 12px;
}
.visual-host-line .badge {
    align-self: flex-start;
}
.visual-host-line .host-name {
    color: var(--text-primary);
}

.visual-json-details {
    margin-top: 4px;
    border-top: 1px dashed var(--border);
    padding-top: 6px;
}
.visual-json-details summary {
    cursor: pointer;
    font-size: 11px;
    color: var(--text-secondary);
    outline: none;
    user-select: none;
}
.visual-json-details summary:hover {
    color: var(--text-primary);
}
.visual-json-pre {
    background: var(--bg-base);
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 10px;
    margin-top: 6px;
    font-size: 11px;
    overflow-x: auto;
    color: var(--text-code);
    white-space: pre-wrap;
    word-break: break-all;
}

/* Msg Panel — rendered from Ansible box-drawing output */
.msg-panel {
    margin-top: 8px;
    border: 1px solid var(--border);
    border-radius: 6px;
    overflow: hidden;
    font-family: var(--font-mono);
    font-size: 12px;
}
.msg-panel-title {
    background: var(--bg-surface);
    color: var(--text-secondary);
    font-size: 10px;
    font-weight: 600;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: 6px 12px;
    border-bottom: 1px solid var(--border);
}
.msg-rows {
    padding: 4px 0;
}
.msg-row {
    display: flex;
    align-items: baseline;
    gap: 8px;
    padding: 4px 12px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.msg-row:last-child { border-bottom: none; }
.msg-key {
    flex-shrink: 0;
    width: 130px;
    color: var(--text-muted);
    font-size: 11px;
}
.msg-val {
    color: var(--text-primary);
    font-size: 11px;
    word-break: break-word;
}
.msg-section {
    padding: 6px 12px 2px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--accent);
    border-top: 1px solid var(--border);
    margin-top: 4px;
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    const jobId     = {{ $job->id }};
    const isRunning = {{ $job->isRunning() ? 'true' : 'false' }};
    const containerRaw = document.getElementById('output-container-raw');
    const containerVisual = document.getElementById('output-container-visual');

    let lastId = {{ $job->outputLines->last()?->id ?? 0 }};
    let polling = isRunning;
    let isRawMode = false;
    let currentJsonBlock = null;

    function scrollBottom() {
        containerRaw.scrollTop = containerRaw.scrollHeight;
        containerVisual.scrollTop = containerVisual.scrollHeight;
    }

    function escapeHtml(string) {
        return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function renderHostBlock(host, statusLabel, badgeClass, jsonContent, itemName = '') {
        let html = `<div class="visual-host-line">
            <div class="visual-host-line-header">
                <span class="badge ${badgeClass}">${statusLabel}</span>
                <strong class="host-name">${escapeHtml(host)}${itemName ? ` <span class="text-muted" style="font-size:11px; font-weight:normal; margin-left:4px;">(item=${escapeHtml(itemName)})</span>` : ''}</strong>
            </div>`;

        if (jsonContent) {
            try {
                let decoded = JSON.parse(jsonContent);
                let msgStr = '';
                if (decoded.msg) {
                    if (typeof decoded.msg === 'string') {
                        msgStr = decoded.msg;
                    } else if (Array.isArray(decoded.msg)) {
                        msgStr = decoded.msg.map(item => {
                            if (Array.isArray(item)) {
                                return item.join('\n');
                            }
                            return String(item);
                        }).join('\n');
                    } else {
                        msgStr = JSON.stringify(decoded.msg, null, 2);
                    }
                }

                if (msgStr) {
                    html += renderMsgPanel(msgStr);
                } else {
                    let pretty = JSON.stringify(decoded, null, 2);
                    html += `
                    <details class="visual-json-details">
                        <summary>View Response JSON</summary>
                        <pre class="visual-json-pre">${escapeHtml(pretty)}</pre>
                    </details>`;
                }
            } catch {
                html += `
                <details class="visual-json-details">
                    <summary>View Response Data</summary>
                    <pre class="visual-json-pre">${escapeHtml(jsonContent)}</pre>
                </details>`;
            }
        }
        html += `</div>`;
        return html;
    }

    function formatLineToVisual(lineText, lineType) {
        let cleanText = lineText.replace(/\\n/g, '\n').replace(/\\t/g, '\t').replace(/\\"/g, '"');
        
        if (currentJsonBlock) {
            currentJsonBlock.lines.push(cleanText);
            
            let openBraces = (cleanText.match(/\{/g) || []).length;
            let closeBraces = (cleanText.match(/\}/g) || []).length;
            currentJsonBlock.braceCount += openBraces - closeBraces;
            
            if (currentJsonBlock.braceCount <= 0) {
                let completeJson = currentJsonBlock.lines.join('\n');
                let host = currentJsonBlock.host;
                let statusLabel = currentJsonBlock.statusLabel;
                let badgeClass = currentJsonBlock.badgeClass;
                let itemName = currentJsonBlock.itemName;
                
                currentJsonBlock = null;
                return renderHostBlock(host, statusLabel, badgeClass, completeJson, itemName);
            }
            return null;
        }

        // Match PLAY header
        if (cleanText.includes('PLAY [')) {
            let match = cleanText.match(/PLAY\s+\[(.*)\]\s*[\*\s]*$/i)
                     || cleanText.match(/PLAY\s+\[(.*)\]/i);
            let title = match ? match[1].trim() : 'Playbook Start';
            return `<div class="visual-header play-header">
                <span class="visual-header-badge play-badge">PLAY</span>
                <span class="visual-header-title">${escapeHtml(title)}</span>
            </div>`;
        }
        
        // Match TASK header
        if (cleanText.includes('TASK [')) {
            let match = cleanText.match(/TASK\s+\[(.*)\]\s*[\*\s]*$/i)
                     || cleanText.match(/TASK\s+\[(.*)\]/i);
            let title = match ? match[1].trim() : 'Task Execution';
            return `<div class="visual-header task-header">
                <span class="visual-header-badge task-badge">TASK</span>
                <span class="visual-header-title">${escapeHtml(title)}</span>
            </div>`;
        }

        // Match PLAY RECAP header
        if (cleanText.includes('PLAY RECAP')) {
            return `<div class="visual-header recap-header">
                <span class="visual-header-badge recap-badge">RECAP</span>
                <span class="visual-header-title">Play Recap</span>
            </div>`;
        }

        // Match host status block (ok: [host] => { ... })
        if (cleanText.match(/^(ok|changed|fatal|failed|skipping):\s+\[([^\]]+)\]/i)) {
            let match = cleanText.match(/^(ok|changed|fatal|failed|skipping):\s+\[([^\]]+)\](?:\s*:\s*(UNREACHABLE|FAILED)!)?(?:\s*=>\s*\(item=(.*?)\))?\s*(=>\s*(\{[\s\S]*))?$/i);
            if (match) {
                let status = match[1].toLowerCase();
                let host = match[2];
                let errorType = match[3] ?? '';
                let itemName = match[4] ?? '';
                let hasJson = !!match[5];
                let jsonStart = match[5] ? match[5].substring(match[5].indexOf('{')) : '';

                let badgeClass = 'badge-success';
                if (status === 'changed') badgeClass = 'badge-running';
                if (status === 'fatal' || status === 'failed') badgeClass = 'badge-failed';
                if (status === 'skipping') badgeClass = 'badge-queued';

                let statusLabel = status.toUpperCase();
                if (errorType) statusLabel += ` : ${errorType}`;

                if (hasJson) {
                    let openBraces = (jsonStart.match(/\{/g) || []).length;
                    let closeBraces = (jsonStart.match(/\}/g) || []).length;
                    let braceCount = openBraces - closeBraces;

                    if (braceCount > 0) {
                        currentJsonBlock = {
                            host: host,
                            status: status,
                            errorType: errorType,
                            itemName: itemName,
                            badgeClass: badgeClass,
                            statusLabel: statusLabel,
                            lines: [jsonStart],
                            braceCount: braceCount
                        };
                        return null; // Suppress line, wait for completion
                    } else {
                        return renderHostBlock(host, statusLabel, badgeClass, jsonStart, itemName);
                    }
                } else {
                    return renderHostBlock(host, statusLabel, badgeClass, null, itemName);
                }
            }
        }

        // Match simple recap status reports (hostname : ok=N changed=N ...)
        if (cleanText.match(/^\s*\S+\s*:\s*ok=\d+\s+changed=\d+\s+/i)) {
            return `<div style="font-family:var(--font-mono); font-size:12px; color:var(--text-secondary); padding:4px 8px; background:var(--bg-surface); border-radius:3px; margin-bottom:4px; border-left:2px solid var(--accent)">${escapeHtml(cleanText)}</div>`;
        }

        // --- Suppress JSON scaffolding lines that wrap Ansible msg output ---
        const trimmed = cleanText.trim();
        if (trimmed === '{' || trimmed === '}' || trimmed === '},' ) return null;
        if (/^"changed"\s*:\s*(true|false),?$/.test(trimmed)) return null;
        if (/^"failed"\s*:\s*(true|false),?$/.test(trimmed)) return null;
        if (/^"invocation"\s*:/.test(trimmed)) return null;

        // Lines starting with "msg": — strip the key and render the value content
        if (/^"msg"\s*:/.test(trimmed)) {
            let inner = trimmed.replace(/^"msg"\s*:\s*"?/, '').replace(/",$/, '').replace(/"$/, '');
            inner = inner.replace(/\\n/g, '\n').replace(/\\"/g, '"');
            return renderBoxLines(inner);
        }

        // Lines that are pure box-drawing report lines (║ prefix or ╔ ╚ ╠)
        if (/^[║╔╠╚═╗╝]/.test(trimmed)) {
            return renderBoxLines(cleanText);
        }

        // Default: regular line styling
        let typeClass = `ol-${lineType}`;
        return `<div class="${typeClass}" style="font-family:var(--font-mono); font-size:11px; padding: 2px 0;">${escapeHtml(cleanText)}</div>`;
    }

    /**
     * Render a full msg string that may contain multi-line box-drawing output.
     */
    function renderMsgPanel(msg) {
        const lines = msg.split('\n').map(l => l.trim()).filter(l => l.length > 0);
        let rows = '';
        let title = '';

        lines.forEach(line => {
            if (/^[╔╚╠╗╝═]+$/.test(line)) return;

            let titleMatch = line.match(/^║\s{2}([A-Z][A-Z ]+[A-Z])\s*$/);
            if (titleMatch) {
                if (!title) title = titleMatch[1].trim();
                return;
            }

            let sectionMatch = line.match(/^║\s+([A-Z][A-Z ]+)$/);
            if (sectionMatch && !line.includes(':')) {
                rows += `<div class="msg-section">${escapeHtml(sectionMatch[1].trim())}</div>`;
                return;
            }

            let kvMatch = line.match(/^║\s{2}(.+?)\s{2,}:\s(.+)$/) || line.match(/^║\s+(.+?)\s*:\s+(.+)$/);
            if (kvMatch) {
                let key = kvMatch[1].trim();
                let val = kvMatch[2].trim().replace(/^"+|"+$/g, '');
                rows += `<div class="msg-row"><span class="msg-key">${escapeHtml(key)}</span><span class="msg-val">${escapeHtml(val)}</span></div>`;
                return;
            }
        });

        if (!rows) {
            return `<pre class="visual-json-pre" style="margin-top:6px">${escapeHtml(msg)}</pre>`;
        }

        return `<div class="msg-panel">
            ${title ? `<div class="msg-panel-title">${escapeHtml(title)}</div>` : ''}
            <div class="msg-rows">${rows}</div>
        </div>`;
    }

    /**
     * Render a chunk of box-drawing lines (║ characters) as a clean panel.
     */
    function renderBoxLines(text) {
        return renderMsgPanel(text);
    }

    function initVisualConsole() {
        containerVisual.innerHTML = '';
        currentJsonBlock = null;
        const rawDivs = containerRaw.querySelectorAll('div');
        rawDivs.forEach(div => {
            const text = div.textContent;
            const type = div.className.replace('ol-', '');
            const html = formatLineToVisual(text, type);
            if (!html) return; // suppressed line
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            if (wrapper.firstElementChild) containerVisual.appendChild(wrapper.firstElementChild);
        });
        scrollBottom();
    }

    function appendLines(lines) {
        lines.forEach(l => {
            // Raw Append
            const divRaw = document.createElement('div');
            divRaw.className = `ol-${l.type}`;
            divRaw.textContent = l.line.replace(/\\n/g, '\n').replace(/\\t/g, '\t').replace(/\\"/g, '"');
            containerRaw.appendChild(divRaw);

            // Visual Append
            const html = formatLineToVisual(l.line, l.type);
            if (!html) return; // suppressed line
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            if (wrapper.firstElementChild) containerVisual.appendChild(wrapper.firstElementChild);
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

    // Initialize visual console on load
    initVisualConsole();
    scrollBottom();
    if (isRunning) poll();

    window.toggleConsoleMode = function() {
        const btn = document.getElementById('console-mode-btn');
        if (isRawMode) {
            containerRaw.style.display = 'none';
            containerVisual.style.display = 'block';
            btn.textContent = 'Raw Mode';
            isRawMode = false;
        } else {
            containerRaw.style.display = 'block';
            containerVisual.style.display = 'none';
            btn.textContent = 'Visual Mode';
            isRawMode = true;
        }
        scrollBottom();
    };

    window.copyOutput = function() {
        const text = Array.from(containerRaw.querySelectorAll('div'))
            .map(d => d.textContent)
            .join('\n');
        navigator.clipboard.writeText(text);
    };

    window.abortJob = async function(id) {
        if (!confirm('Abort this job?')) return;
        await api(`/jobs/${id}/abort`, { method: 'POST' });
        location.reload();
    };

    window.switchTab = function(tabName) {
        document.querySelectorAll('.tab-pane').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        
        document.getElementById(tabName + '-tab-pane').style.display = 'block';
        document.getElementById(tabName + '-tab-btn').classList.add('active');
    };

    window.toggleDeviceReport = function(host) {
        const el = document.getElementById('report-body-' + host);
        const btn = document.getElementById('toggle-btn-' + host);
        if (el.style.display === 'none') {
            el.style.display = 'block';
            btn.textContent = 'Collapse';
        } else {
            el.style.display = 'none';
            btn.textContent = 'Expand';
        }
    };
})();
</script>
@endpush
