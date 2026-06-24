@extends('layouts.app')
@section('title', 'Inventory')

@section('content')
<div class="page-header">
    <div class="flex items-center justify-between" style="padding-bottom:20px; display:flex; justify-content:space-between; align-items:center; width:100%">
        <div>
            <h1 class="page-title">Inventory</h1>
            <p class="page-subtitle">Host topology, facts and ad-hoc commands</p>
        </div>
        <div>
            <form action="{{ route('inventory.index') }}" method="GET" style="display:flex;align-items:center;gap:8px">
                <input type="text" name="inventory" class="form-input" style="width:320px; font-family:var(--font-mono); font-size:12px" value="{{ $inventory }}" placeholder="Inventory file path...">
                <button type="submit" class="btn btn-secondary">Load</button>
                <a href="{{ route('inventory.index') }}?inventory={{ urlencode($inventory) }}&refresh=1" class="btn btn-secondary" title="Bust cache and refresh" style="display:inline-flex; align-items:center; gap:6px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                    </svg>
                    Refresh
                </a>
            </form>
        </div>
    </div>
    <div class="page-tabs">
        <a href="#" class="page-tab active" onclick="switchTab('graph', this)">Graph</a>
        <a href="#" class="page-tab" onclick="switchTab('hosts', this)">Hosts</a>
        <a href="#" class="page-tab" onclick="switchTab('adhoc', this)">Ad-hoc</a>
        <a href="#" class="page-tab" onclick="switchTab('editor', this)">File Editor</a>
    </div>
</div>

<div class="page-body">

    @if(!empty($sshError))
    <div class="card mb-4" style="border-color: var(--red);">
        <div class="card-header" style="background: rgba(255, 71, 87, 0.1);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--red)" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span class="card-title text-red" style="margin-left: 8px;">SSH Connection Failed</span>
        </div>
        <div class="card-body">
            <p style="font-size: 14px; margin-bottom: 16px;">
                The dashboard could not connect to the Ansible control node. This is a common issue when setting up this project for the first time.
            </p>
            <div class="code-block" style="margin-bottom: 16px; border-color: var(--red-dim); color: var(--red);">{{ $sshError }}</div>

            <h3 style="margin-top: 20px; font-size: 14px; color: var(--accent); margin-bottom: 8px;">Learning Goal: How Ansible Dashboard Works</h3>
            <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px; line-height: 1.6;">
                This dashboard runs inside a Docker container (<code>ansible-ctrl-app</code>) and connects to a separate <strong>Control Node</strong> or directly to a target machine via SSH. To fetch the inventory, it uses the <code>phpseclib3</code> library to SSH into the host specified in your <code>.env</code> file, execute Ansible commands, and parse the output.
            </p>
        </div>
    </div>
    @endif

    @if(!empty($inventoryError))
    <div class="card mb-4" style="border-color: var(--red);">
        <div class="card-header" style="background: rgba(255, 71, 87, 0.1);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--red)" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span class="card-title text-red" style="margin-left: 8px;">Inventory Load Failed</span>
        </div>
        <div class="card-body">
            <p style="font-size: 14px; margin-bottom: 16px;">
                Ansible was unable to parse the selected inventory file.
            </p>
            <div class="code-block" style="margin-bottom: 16px; border-color: var(--red-dim); color: var(--red);">{{ $inventoryError }}</div>
        </div>
    </div>
    @endif

    {{-- Graph Tab --}}
    <div id="tab-graph">
        <div class="card mb-4">
            <div class="card-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/>
                </svg>
                <span class="card-title">Host Topology</span>
                <div style="margin-left:auto;display:flex;gap:10px;align-items:center">
                    <input type="text" id="inv-search" class="form-input" style="width:200px;padding:5px 10px" placeholder="Filter hosts…" oninput="filterInventory(this.value)">
                    <button class="btn btn-sm btn-secondary" onclick="pingAll()">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12.55a11 11 0 0 1 14.08 0"/>
                            <path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                            <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                            <line x1="12" y1="20" x2="12.01" y2="20"/>
                        </svg>
                        Ping All
                    </button>
                </div>
            </div>

            {{-- Deterministic Swimlanes Container --}}
            <div id="inv-graph-swimlanes" style="display:flex;gap:20px;overflow-x:auto;padding:20px;background:var(--bg-base);min-height:300px;border-bottom:1px solid var(--border)">
                @php
                    $allGroups = $parsedInventory['groups'] ?? [];
                    $hostvars = $parsedInventory['hostvars'] ?? [];
                    // Find hosts with no groups
                    $ungroupedHosts = [];
                    foreach ($parsedInventory['hosts'] ?? [] as $h) {
                        if (empty($parsedInventory['hostGroups'][$h])) {
                            $ungroupedHosts[] = $h;
                        }
                    }
                @endphp

                @foreach($allGroups as $groupName => $groupData)
                @php $hosts = $groupData['hosts'] ?? []; @endphp
                @if(count($hosts) > 0)
                <div class="swimlane-column" data-group="{{ $groupName }}" style="flex:0 0 250px;display:flex;flex-direction:column;gap:12px;background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:16px">
                    <div style="display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border);padding-bottom:10px;margin-bottom:4px">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                        <span style="font-weight:600;font-size:13px;color:var(--text-primary)">{{ $groupName }}</span>
                        <span class="text-mono text-xs text-muted" style="margin-left:auto">{{ count($hosts) }}</span>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:8px;overflow-y:auto;max-height:350px">
                        @foreach($hosts as $host)
                        <div class="swimlane-host-node" data-host="{{ $host }}" onclick="showHostFacts('{{ $host }}')" style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bg-base);border:1px solid var(--border);border-radius:6px;cursor:pointer;transition:border-color .15s, background .15s"
                             onmouseenter="this.style.borderColor='var(--accent)';this.style.background='var(--bg-hover)'"
                             onmouseleave="this.style.borderColor='var(--border)';this.style.background='var(--bg-base)'">
                            <div class="conn-dot" id="ping-{{ $host }}" style="width:7px;height:7px;border-radius:50%;background:var(--text-muted)"></div>
                            <span class="text-mono text-sm" style="color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $host }}</span>
                            @if(isset($hostvars[$host]['ansible_host']))
                            <span class="text-mono text-xs text-muted" style="margin-left:auto;font-size:10px">{{ $hostvars[$host]['ansible_host'] }}</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @endforeach

                {{-- Ungrouped Lane --}}
                @if(count($ungroupedHosts) > 0)
                <div class="swimlane-column" data-group="ungrouped" style="flex:0 0 250px;display:flex;flex-direction:column;gap:12px;background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:16px">
                    <div style="display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border);padding-bottom:10px;margin-bottom:4px">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                        <span style="font-weight:600;font-size:13px;color:var(--text-primary)">ungrouped</span>
                        <span class="text-mono text-xs text-muted" style="margin-left:auto">{{ count($ungroupedHosts) }}</span>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:8px;overflow-y:auto;max-height:350px">
                        @foreach($ungroupedHosts as $host)
                        <div class="swimlane-host-node" data-host="{{ $host }}" onclick="showHostFacts('{{ $host }}')" style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bg-base);border:1px solid var(--border);border-radius:6px;cursor:pointer;transition:border-color .15s, background .15s"
                             onmouseenter="this.style.borderColor='var(--accent)';this.style.background='var(--bg-hover)'"
                             onmouseleave="this.style.borderColor='var(--border)';this.style.background='var(--bg-base)'">
                            <div class="conn-dot" id="ping-{{ $host }}" style="width:7px;height:7px;border-radius:50%;background:var(--text-muted)"></div>
                            <span class="text-mono text-sm" style="color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $host }}</span>
                            @if(isset($hostvars[$host]['ansible_host']))
                            <span class="text-mono text-xs text-muted" style="margin-left:auto;font-size:10px">{{ $hostvars[$host]['ansible_host'] }}</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Groups/Hosts grid --}}
        <div id="groups-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px">
            @foreach($allGroups as $groupName => $groupData)
            @php $hosts = $groupData['hosts'] ?? []; @endphp
            @if(count($hosts) > 0)
            <div class="card group-card" data-group="{{ $groupName }}">
                <div class="card-header" style="gap:8px">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                    <span class="card-title">{{ $groupName }}</span>
                    <span class="text-mono text-xs text-muted ml-auto">{{ count($hosts) }} host(s)</span>
                </div>
                <div class="card-body" style="padding:8px 0">
                    @foreach($hosts as $host)
                    <div class="host-row" data-host="{{ $host }}" style="padding:7px 16px;display:flex;align-items:center;gap:10px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .1s"
                        onclick="showHostFacts('{{ $host }}')"
                        onmouseenter="this.style.background='var(--bg-hover)'"
                        onmouseleave="this.style.background='transparent'">
                        <div class="conn-dot" id="ping-{{ $host }}" style="width:7px;height:7px;border-radius:50%;background:var(--text-muted)"></div>
                        <span class="text-mono text-sm" style="color:var(--text-primary)">{{ $host }}</span>
                        @if(isset($hostvars[$host]['ansible_host']))
                        <span class="text-mono text-xs text-muted" style="margin-left:auto">{{ $hostvars[$host]['ansible_host'] }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            @endforeach
        </div>

        {{-- Host facts modal --}}
        <div id="facts-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1000;overflow-y:auto" onclick="if(event.target===this)this.style.display='none'">
            <div style="max-width:700px;margin:60px auto;padding:20px">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title" id="facts-title">Host Facts</span>
                        <button onclick="document.getElementById('facts-modal').style.display='none'" class="btn btn-sm btn-secondary ml-auto">Close</button>
                    </div>
                    <div class="card-body" style="padding:0">
                        {{-- Modal Tabs --}}
                        <div style="display:flex; border-bottom: 1px solid var(--border); background: var(--bg-surface); padding: 0 16px">
                            <button id="btn-facts-summary" onclick="switchFactsTab('summary')" class="page-tab active" style="background:transparent; border:none; border-bottom: 2px solid var(--accent); cursor:pointer; font-family:var(--font-mono); font-size:12px; height: 38px">Summary</button>
                            <button id="btn-facts-raw" onclick="switchFactsTab('raw')" class="page-tab" style="background:transparent; border:none; border-bottom: 2px solid transparent; cursor:pointer; font-family:var(--font-mono); font-size:12px; height: 38px">Raw JSON</button>
                        </div>

                        {{-- Summary Tab --}}
                        <div id="facts-summary-view" style="padding:16px; display:block">
                            <table class="data-table" style="width:100%">
                                <tbody>
                                    <tr>
                                        <td style="font-family:var(--font-mono); font-size:11px; font-weight:600; width:180px; border-bottom: 1px solid var(--border)">OS Distribution</td>
                                        <td id="fact-os" style="color:var(--text-primary); border-bottom: 1px solid var(--border)">-</td>
                                    </tr>
                                    <tr>
                                        <td style="font-family:var(--font-mono); font-size:11px; font-weight:600; border-bottom: 1px solid var(--border)">Kernel Version</td>
                                        <td id="fact-kernel" style="border-bottom: 1px solid var(--border)">-</td>
                                    </tr>
                                    <tr>
                                        <td style="font-family:var(--font-mono); font-size:11px; font-weight:600; border-bottom: 1px solid var(--border)">IP Addresses</td>
                                        <td id="fact-ips" style="border-bottom: 1px solid var(--border)">-</td>
                                    </tr>
                                    <tr>
                                        <td style="font-family:var(--font-mono); font-size:11px; font-weight:600; border-bottom: 1px solid var(--border)">CPU / Architecture</td>
                                        <td id="fact-cpu" style="border-bottom: 1px solid var(--border)">-</td>
                                    </tr>
                                    <tr>
                                        <td style="font-family:var(--font-mono); font-size:11px; font-weight:600; border-bottom: 1px solid var(--border)">Total Memory</td>
                                        <td id="fact-memory" style="border-bottom: 1px solid var(--border)">-</td>
                                    </tr>
                                    <tr>
                                        <td style="font-family:var(--font-mono); font-size:11px; font-weight:600; border-bottom: 1px solid var(--border)">Uptime</td>
                                        <td id="fact-uptime" style="border-bottom: 1px solid var(--border)">-</td>
                                    </tr>
                                    <tr>
                                        <td style="font-family:var(--font-mono); font-size:11px; font-weight:600; border-bottom: none">Python Version</td>
                                        <td id="fact-python" style="border-bottom: none">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Raw Tab --}}
                        <div id="facts-raw-view" style="display:none">
                            <pre id="facts-content" style="font-family:var(--font-mono);font-size:11px;line-height:1.7;color:var(--text-code);background:var(--bg-base);padding:16px;max-height:500px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;margin:0"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Hosts Tab --}}
    <div id="tab-hosts" style="display:none">
        <div class="card">
            <div class="card-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                </svg>
                <span class="card-title">All Hosts</span>
                <span class="text-mono text-xs text-muted" style="margin-left:auto" id="hosts-count-badge">{{ count($parsedInventory['hosts'] ?? []) }} host(s)</span>
                <button class="btn btn-sm btn-secondary" onclick="pingAll()" style="margin-left:12px">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12.55a11 11 0 0 1 14.08 0"/>
                        <path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                        <line x1="12" y1="20" x2="12.01" y2="20"/>
                    </svg>
                    Ping All
                </button>
            </div>
            <table class="data-table" id="hosts-table">
                <thead>
                    <tr>
                        <th style="width:20px"></th>
                        <th>Hostname</th>
                        <th>IP Address</th>
                        <th>Groups</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="hosts-tbody">
                    @foreach($parsedInventory['hosts'] ?? [] as $host)
                    @php
                        $ip = $parsedInventory['hostvars'][$host]['ansible_host'] ?? '—';
                        $groupsList = implode(', ', $parsedInventory['hostGroups'][$host] ?? []);
                        if (empty($groupsList)) {
                            $groupsList = '—';
                        }
                    @endphp
                    <tr class="host-row-tr" data-host="{{ $host }}" style="cursor:pointer" onclick="showHostFacts('{{ $host }}')">
                        <td>
                            <div class="conn-dot" id="ping-{{ $host }}" style="width:7px;height:7px;border-radius:50%;background:var(--text-muted)"></div>
                        </td>
                        <td class="text-mono text-sm" style="color:var(--text-primary)">{{ $host }}</td>
                        <td class="text-mono text-sm">{{ $ip }}</td>
                        <td class="text-sm" style="color:var(--text-secondary)">{{ $groupsList }}</td>
                        <td id="status-{{ $host }}">
                            <span class="badge badge-secondary" style="background:var(--bg-card);color:var(--text-muted)">Unknown</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Ad-hoc Tab --}}
    <div id="tab-adhoc" style="display:none">
        <div class="card" style="max-width:640px" x-data="adhocRunner()">
            <div class="card-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                    <polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>
                </svg>
                <span class="card-title">Ad-hoc Command</span>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Inventory File</label>
                    <input class="form-input" x-model="form.inventory" placeholder="Path to inventory...">
                    <div class="form-hint">Default: {{ $inventory }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Host Pattern</label>
                    <input class="form-input" x-model="form.hosts" placeholder="all, webservers, 192.168.1.10">
                </div>
                <div class="form-group">
                    <label class="form-label">Module</label>
                    <select class="form-select" x-model="form.module">
                        <option value="ping">ping</option>
                        <option value="command">command</option>
                        <option value="shell">shell</option>
                        <option value="setup">setup (gather facts)</option>
                        <option value="copy">copy</option>
                        <option value="file">file</option>
                        <option value="yum">yum</option>
                        <option value="apt">apt</option>
                        <option value="service">service</option>
                        <option value="systemd">systemd</option>
                        <option value="user">user</option>
                    </select>
                </div>
                <div class="form-group" x-show="form.module !== 'ping' && form.module !== 'setup'">
                    <label class="form-label">Arguments</label>
                    <input class="form-input" x-model="form.args" placeholder="cmd=uptime / name=nginx state=started">
                </div>

                {{-- Quick Examples --}}
                <div class="form-group">
                    <label class="form-label" style="font-size: 11px; color: var(--text-secondary)">Quick Examples</label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:4px">
                        <button class="btn btn-sm btn-secondary" style="font-size:11px;padding:3px 8px" @click="quickFill('all', 'command', 'uptime')">Uptime (Command)</button>
                        <button class="btn btn-sm btn-secondary" style="font-size:11px;padding:3px 8px" @click="quickFill('all', 'shell', 'df -h')">Disk Space (Shell)</button>
                        <button class="btn btn-sm btn-secondary" style="font-size:11px;padding:3px 8px" @click="quickFill('webservers', 'service', 'name=nginx state=started')">Start Nginx (Service)</button>
                    </div>
                </div>

                {{-- Command preview --}}
                <div class="form-group">
                    <label class="form-label">Command Preview</label>
                    <div class="code-block" x-text="commandPreview()" style="font-size:11px;overflow-x:auto;white-space:pre-wrap;word-break:break-all"></div>
                </div>

                <button class="btn btn-primary" @click="run" :disabled="running">
                    <span x-show="running" class="spinner" style="width:13px;height:13px"></span>
                    <span x-text="running ? 'Running…' : 'Execute'"></span>
                </button>

                <div x-show="output || exitCode !== null" style="margin-top:16px">
                    <div class="flex items-center gap-3 mb-2" style="font-size: 12px; font-family: var(--font-mono)">
                        <template x-if="exitCode !== null">
                            <span :class="exitCode === 0 ? 'text-green' : 'text-red'" style="font-weight: 600">
                                Exit Code: <span x-text="exitCode"></span>
                            </span>
                        </template>
                        <template x-if="duration !== null">
                            <span class="text-muted">
                                Duration: <span x-text="duration"></span>ms
                            </span>
                        </template>
                    </div>
                    <div class="code-block" style="max-height:300px;overflow-y:auto;white-space:pre-wrap" x-text="output"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- File Editor Tab --}}
    <div id="tab-editor" style="display:none" x-data="fileEditor()">
        <div class="card">
            <div class="card-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                <span class="card-title">Remote File Editor</span>
                <div style="margin-left:auto; display:flex; gap:8px">
                    <button class="btn btn-primary btn-sm" @click="saveFile" :disabled="!filePath || saving">
                        <span x-show="saving" class="spinner" style="width:12px;height:12px"></span>
                        <span x-text="saving ? 'Saving…' : 'Save'"></span>
                    </button>
                </div>
            </div>
            <div class="card-body" style="padding:0; display:grid; grid-template-columns: 280px 1fr; min-height: 500px">
                <!-- File Tree Panel (Left) -->
                <div style="border-right: 1px solid var(--border); padding: 16px; background: var(--bg-card); overflow-y: auto; max-height: 600px">
                    <div style="font-weight: 600; font-size: 11px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 12px; letter-spacing: 0.5px">Remote Files</div>
                    <div style="font-family: var(--font-mono); font-size: 12px">
                        <div style="color: var(--accent); font-weight: bold; margin-bottom: 8px; font-size:11px">
                            Root: {{ config('ansible.working_dir') }}
                        </div>
                        <div id="file-tree-container">
                            <template x-for="item in treeData">
                                <div style="margin-left: 0px">
                                    <div style="padding: 4px 6px; display:flex; align-items:center; gap:6px; cursor:pointer; border-radius:4px"
                                         :style="filePath === item.path ? 'background: var(--bg-hover); color: var(--accent); font-weight: bold;' : 'color: var(--text-secondary)'"
                                         @click="handleTreeClick(item)"
                                         @mouseenter="$el.style.background='var(--bg-hover)'"
                                         @mouseleave="if(filePath !== item.path) $el.style.background='transparent'">
                                        
                                        <template x-if="item.type === 'dir'">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--yellow)">
                                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                                            </svg>
                                        </template>
                                        <template x-if="item.type === 'file'">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <polyline points="14 2 14 8 20 8"/>
                                            </svg>
                                        </template>
                                        <span x-text="item.name"></span>
                                        <template x-if="item.type === 'dir'">
                                            <span style="font-size: 8px; margin-left: auto; color: var(--text-muted)" x-text="item.expanded ? '▼' : '▶'"></span>
                                        </template>
                                    </div>

                                    <!-- Render Child Nodes -->
                                    <template x-if="item.type === 'dir' && item.expanded && item.children">
                                        <div style="margin-left: 14px; border-left: 1px dashed var(--border); padding-left: 8px">
                                            <template x-for="child in item.children">
                                                <div style="padding: 4px 6px; display:flex; align-items:center; gap:6px; cursor:pointer; border-radius:4px"
                                                     :style="filePath === child.path ? 'background: var(--bg-hover); color: var(--accent); font-weight: bold;' : 'color: var(--text-secondary)'"
                                                     @click.stop="handleTreeClick(child)"
                                                     @mouseenter="$el.style.background='var(--bg-hover)'"
                                                     @mouseleave="if(filePath !== child.path) $el.style.background='transparent'">
                                                    
                                                    <template x-if="child.type === 'dir'">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--yellow)">
                                                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                                                        </svg>
                                                    </template>
                                                    <template x-if="child.type === 'file'">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                            <polyline points="14 2 14 8 20 8"/>
                                                        </svg>
                                                    </template>
                                                    <span x-text="child.name"></span>
                                                    <template x-if="child.type === 'dir'">
                                                        <span style="font-size: 8px; margin-left: auto; color: var(--text-muted)" x-text="child.expanded ? '▼' : '▶'"></span>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Editor Panel (Right) -->
                <div style="display:flex; flex-direction:column; background:var(--bg-base)">
                    <div style="padding: 10px 16px; border-bottom: 1px solid var(--border); display:flex; align-items:center; gap:8px">
                        <span style="font-size: 11px; font-family: var(--font-mono); color: var(--text-secondary)">Active:</span>
                        <span style="font-size: 12px; font-family: var(--font-mono); color: var(--text-primary); font-weight:600" x-text="filePath || 'None'"></span>
                        <template x-if="hasChanges">
                            <span class="badge badge-warning" style="margin-left: 8px; font-size:10px">Unsaved Changes</span>
                        </template>
                    </div>

                    <!-- CodeMirror Mount -->
                    <div id="codemirror-editor-mount" style="flex:1; min-height: 450px; font-size: 13px"></div>

                    <!-- Alert message -->
                    <div x-show="message" class="alert" :class="error ? 'alert-error' : 'alert-success'" style="margin: 12px; margin-top: 0;" x-html="message"></div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/codemirror.js') }}"></script>
<script>
// ── Tab switching with confirmation guard & hash updates ──
function switchTab(name, el) {
    if (el) event.preventDefault();

    // Confirmation guard for Editor
    const editorEl = document.getElementById('tab-editor');
    if (editorEl && editorEl.style.display === 'block') {
        const cmEditor = document.querySelector('[x-data^="fileEditor"]');
        if (cmEditor && window.Alpine) {
            const editorData = Alpine.$data(cmEditor);
            if (editorData && editorData.hasChanges) {
                if (!confirm('You have unsaved changes. Discard them and switch tabs?')) {
                    return;
                }
            }
        }
    }

    // Toggle tab display
    ['graph','hosts','adhoc','editor'].forEach(t => {
        const el2 = document.getElementById(`tab-${t}`);
        if (el2) el2.style.display = t === name ? 'block' : 'none';
    });

    // Toggle active link styling
    document.querySelectorAll('.page-tab').forEach(a => {
        if (a.getAttribute('onclick').includes(`'${name}'`)) {
            a.classList.add('active');
        } else {
            a.classList.remove('active');
        }
    });

    // Update URL hash
    window.location.hash = name;
}

window.addEventListener('hashchange', () => {
    const hash = window.location.hash.replace('#', '');
    if (['graph', 'hosts', 'adhoc', 'editor'].includes(hash)) {
        switchTab(hash);
    }
});

window.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.replace('#', '');
    if (['graph', 'hosts', 'adhoc', 'editor'].includes(hash)) {
        switchTab(hash);
    }
});

// ── Unified Search Filter ──
function filterInventory(q) {
    const query = q.toLowerCase();

    // 1. Groups card list
    document.querySelectorAll('.host-row').forEach(el => {
        const host = el.dataset.host.toLowerCase();
        el.style.display = query && !host.includes(query) ? 'none' : 'flex';
    });
    document.querySelectorAll('.group-card').forEach(el => {
        const visibleHosts = Array.from(el.querySelectorAll('.host-row')).some(r => r.style.display !== 'none');
        el.style.display = visibleHosts ? '' : 'none';
    });

    // 2. Swimlane topology
    document.querySelectorAll('.swimlane-host-node').forEach(el => {
        const host = el.dataset.host.toLowerCase();
        el.style.display = query && !host.includes(query) ? 'none' : 'flex';
    });
    document.querySelectorAll('.swimlane-column').forEach(el => {
        const visibleHosts = Array.from(el.querySelectorAll('.swimlane-host-node')).some(r => r.style.display !== 'none');
        el.style.display = visibleHosts ? '' : 'none';
    });

    // 3. Hosts table rows
    document.querySelectorAll('.host-row-tr').forEach(el => {
        const host = el.dataset.host.toLowerCase();
        el.style.display = query && !host.includes(query) ? 'none' : '';
    });
}

// ── Host facts modal tab switching ──
function switchFactsTab(tab) {
    const summaryBtn = document.getElementById('btn-facts-summary');
    const rawBtn = document.getElementById('btn-facts-raw');
    const summaryView = document.getElementById('facts-summary-view');
    const rawView = document.getElementById('facts-raw-view');

    if (tab === 'summary') {
        summaryBtn.classList.add('active');
        summaryBtn.style.borderBottomColor = 'var(--accent)';
        summaryBtn.style.color = 'var(--accent)';
        rawBtn.classList.remove('active');
        rawBtn.style.borderBottomColor = 'transparent';
        rawBtn.style.color = 'var(--text-secondary)';

        summaryView.style.display = 'block';
        rawView.style.display = 'none';
    } else {
        rawBtn.classList.add('active');
        rawBtn.style.borderBottomColor = 'var(--accent)';
        rawBtn.style.color = 'var(--accent)';
        summaryBtn.classList.remove('active');
        summaryBtn.style.borderBottomColor = 'transparent';
        summaryBtn.style.color = 'var(--text-secondary)';

        summaryView.style.display = 'none';
        rawView.style.display = 'block';
    }
}

// ── Host facts modal ──
async function showHostFacts(host) {
    document.getElementById('facts-title').textContent = `${host} — Facts`;
    
    // Reset view values
    document.getElementById('fact-os').textContent = 'Loading…';
    document.getElementById('fact-kernel').textContent = 'Loading…';
    document.getElementById('fact-ips').textContent = 'Loading…';
    document.getElementById('fact-cpu').textContent = 'Loading…';
    document.getElementById('fact-memory').textContent = 'Loading…';
    document.getElementById('fact-uptime').textContent = 'Loading…';
    document.getElementById('fact-python').textContent = 'Loading…';
    document.getElementById('facts-content').textContent = 'Loading…';
    
    // Reset tab to summary view
    switchFactsTab('summary');
    
    document.getElementById('facts-modal').style.display = 'block';

    try {
        const r = await api('/inventory/facts', {
            method: 'POST',
            body: JSON.stringify({ host, inventory: '{{ $inventory }}' })
        });
        
        const facts = r.ansible_facts || {};

        // OS
        const dist = facts.ansible_distribution || 'Unknown';
        const distVer = facts.ansible_distribution_version || '';
        document.getElementById('fact-os').textContent = `${dist} ${distVer}`.trim() || 'N/A';
        
        // Kernel
        document.getElementById('fact-kernel').textContent = facts.ansible_kernel || 'N/A';

        // IPs
        const defaultIp = facts.ansible_default_ipv4?.address;
        const allIps = facts.ansible_all_ipv4_addresses || [];
        let ipHtml = '';
        if (defaultIp) {
            ipHtml += `<div><strong>Default:</strong> <span class="text-green">${defaultIp}</span></div>`;
        }
        if (allIps.length > 0) {
            const filteredIps = allIps.filter(ip => ip !== defaultIp);
            if (filteredIps.length > 0) {
                ipHtml += `<div style="margin-top:4px; font-size:11px; color:var(--text-secondary)"><strong>Others:</strong> ${filteredIps.join(', ')}</div>`;
            }
        }
        document.getElementById('fact-ips').innerHTML = ipHtml || 'N/A';

        // CPU
        const cpuCount = facts.ansible_processor_vcpus || facts.ansible_processor_count || 'Unknown';
        const arch = facts.ansible_architecture || 'Unknown';
        document.getElementById('fact-cpu').textContent = `${cpuCount} vCPUs (${arch})`;

        // Memory
        const memTotal = facts.ansible_memtotal_mb || 0;
        if (memTotal > 0) {
            const gb = (memTotal / 1024).toFixed(2);
            document.getElementById('fact-memory').textContent = `${gb} GB (${memTotal} MB)`;
        } else {
            document.getElementById('fact-memory').textContent = 'N/A';
        }

        // Uptime
        const uptimeSecs = facts.ansible_uptime_seconds || 0;
        if (uptimeSecs > 0) {
            const days = Math.floor(uptimeSecs / 86400);
            const hours = Math.floor((uptimeSecs % 86400) / 3600);
            const mins = Math.floor((uptimeSecs % 3600) / 60);
            let uptimeStr = '';
            if (days > 0) uptimeStr += `${days}d `;
            if (hours > 0) uptimeStr += `${hours}h `;
            uptimeStr += `${mins}m`;
            document.getElementById('fact-uptime').textContent = uptimeStr;
        } else {
            document.getElementById('fact-uptime').textContent = 'N/A';
        }

        // Python
        const pyVer = facts.ansible_python_version || (facts.ansible_python?.version) || 'N/A';
        document.getElementById('fact-python').textContent = pyVer;

        // Raw
        document.getElementById('facts-content').textContent = JSON.stringify(r, null, 2);
    } catch(e) {
        document.getElementById('fact-os').textContent = 'Error';
        document.getElementById('fact-kernel').textContent = 'Error';
        document.getElementById('fact-ips').textContent = 'Error';
        document.getElementById('fact-cpu').textContent = 'Error';
        document.getElementById('fact-memory').textContent = 'Error';
        document.getElementById('fact-uptime').textContent = 'Error';
        document.getElementById('fact-python').textContent = 'Error';
        document.getElementById('facts-content').textContent = 'Failed to load host facts: ' + e.message;
    }
}

// ── Ping all ──
async function pingAll() {
    const btns = document.querySelectorAll('[onclick="pingAll()"]');
    const origHtmls = Array.from(btns).map(b => b.innerHTML);
    btns.forEach(btn => {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" style="width:11px;height:11px"></span> Pinging…';
    });

    try {
        const r = await api('/inventory/ping', {
            method: 'POST',
            body: JSON.stringify({ pattern: 'all', inventory: '{{ $inventory }}' })
        });

        if (r.error) {
            showToast('Ping failed: ' + r.error, 'error');
            return;
        }

        if (r.parsed) {
            Object.entries(r.parsed).forEach(([host, status]) => {
                document.querySelectorAll(`[id="ping-${host}"]`).forEach(dot => {
                    dot.style.background = status === 'success' ? 'var(--green)' : 'var(--red)';
                    dot.style.boxShadow  = status === 'success' ? '0 0 4px var(--green)' : '0 0 4px var(--red)';
                });
                const statusCell = document.getElementById(`status-${host}`);
                if (statusCell) {
                    statusCell.innerHTML = status === 'success'
                        ? '<span class="badge badge-success">Online</span>'
                        : '<span class="badge badge-failed">Offline</span>';
                }
            });
            showToast(`Pinged ${Object.keys(r.parsed).length} host(s)`, 'success');
        }
    } catch(e) {
        showToast('Ping request failed', 'error');
    } finally {
        btns.forEach((btn, idx) => {
            btn.disabled = false;
            btn.innerHTML = origHtmls[idx];
        });
    }
}

function showToast(msg, type) {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:9999;padding:10px 18px;border-radius:6px;font-family:var(--font-mono);font-size:12px;color:#fff;background:${type==='error'?'var(--red)':'var(--green)'};box-shadow:0 4px 20px rgba(0,0,0,.4);transition:opacity .4s`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 3000);
}

// ── Ad-hoc runner component ──
function adhocRunner() {
    return {
        form: {
            hosts: 'all',
            module: 'ping',
            args: '',
            inventory: '{{ $inventory }}'
        },
        running: false,
        output: '',
        exitCode: null,
        duration: null,

        commandPreview() {
            let parts = ['ansible', this.form.hosts || 'all'];
            parts.push('-i', this.form.inventory || '{{ config('ansible.inventory_default') }}');
            parts.push('-m', this.form.module);
            if (this.form.module !== 'ping' && this.form.module !== 'setup' && this.form.args) {
                parts.push('-a', `"${this.form.args}"`);
            }
            return parts.join(' ');
        },

        quickFill(hosts, module, args) {
            this.form.hosts = hosts;
            this.form.module = module;
            this.form.args = args;
        },

        async run() {
            this.running = true;
            this.output = '';
            this.exitCode = null;
            this.duration = null;

            try {
                const r = await api('/inventory/adhoc', {
                    method: 'POST',
                    body: JSON.stringify(this.form)
                });

                this.output = r.output || (r.error ? 'Error: ' + r.error : JSON.stringify(r, null, 2));
                this.exitCode = r.exit_code !== undefined ? r.exit_code : (r.error ? 1 : 0);
                this.duration = r.duration_ms || null;
            } catch (e) {
                this.output = 'Request failed: ' + e.message;
                this.exitCode = 1;
            } finally {
                this.running = false;
            }
        }
    };
}

// ── File editor component with CodeMirror 6 and lazy-loaded tree ──
function fileEditor() {
    return {
        filePath: '',
        loadedContent: '',
        treeData: [],
        saving: false,
        message: '',
        error: false,
        editorView: null,
        languageConf: null,

        get hasChanges() {
            if (!this.editorView) return false;
            return this.editorView.state.doc.toString() !== this.loadedContent;
        },

        init() {
            // Load initial remote directory listing
            this.loadDirectory('{{ config('ansible.working_dir') }}');

            // Setup CodeMirror
            this.languageConf = new CodeMirror.Compartment();
            const state = CodeMirror.EditorState.create({
                doc: '',
                extensions: [
                    CodeMirror.basicSetup,
                    CodeMirror.oneDark,
                    this.languageConf.of([]),
                    CodeMirror.EditorView.updateListener.of((v) => {
                        if (v.docChanged) {
                            // Trigger Alpine updates for changes state
                        }
                    })
                ]
            });

            this.editorView = new CodeMirror.EditorView({
                state,
                parent: document.getElementById('codemirror-editor-mount')
            });

            // Prevent closing window if unsaved
            window.addEventListener('beforeunload', (e) => {
                if (this.hasChanges) {
                    e.preventDefault();
                    e.returnValue = 'You have unsaved changes in the file editor.';
                }
            });
        },

        async loadDirectory(path, parentNode = null) {
            try {
                const r = await api('/inventory/files?path=' + encodeURIComponent(path));
                if (r.files) {
                    if (parentNode) {
                        parentNode.children = r.files.map(f => ({ ...f, expanded: false, children: null }));
                    } else {
                        this.treeData = r.files.map(f => ({ ...f, expanded: false, children: null }));
                    }
                } else if (r.error) {
                    this.showBanner(r.error, true);
                }
            } catch (e) {
                this.showBanner(e.message, true);
            }
        },

        async handleTreeClick(item) {
            if (item.type === 'dir') {
                item.expanded = !item.expanded;
                if (item.expanded && !item.children) {
                    await this.loadDirectory(item.path, item);
                }
            } else {
                if (this.hasChanges) {
                    if (!confirm('You have unsaved changes. Discard changes and open this file?')) {
                        return;
                    }
                }
                this.filePath = item.path;
                await this.loadFile();
            }
        },

        async loadFile() {
            this.message = '';
            try {
                const r = await api('/inventory/file?path=' + encodeURIComponent(this.filePath));
                if (r.error) {
                    this.showBanner(r.error, true);
                    return;
                }
                this.loadedContent = r.content || '';
                
                // Configure language highlighting based on extension
                const ext = this.filePath.split('.').pop().toLowerCase();
                let langExt = [];
                if (ext === 'yaml' || ext === 'yml') {
                    langExt = CodeMirror.languages.yaml();
                } else if (ext === 'json') {
                    langExt = CodeMirror.languages.json();
                }

                this.editorView.dispatch({
                    changes: { from: 0, to: this.editorView.state.doc.length, insert: this.loadedContent },
                    effects: this.languageConf.reconfigure(langExt)
                });
            } catch (e) {
                this.showBanner('Failed to load file: ' + e.message, true);
            }
        },

        async saveFile() {
            if (!this.filePath) return;
            this.saving = true;
            this.message = '';
            this.error = false;

            const editorContent = this.editorView.state.doc.toString();

            try {
                // 1. Validation before save
                const valRes = await api('/inventory/file/validate', {
                    method: 'POST',
                    body: JSON.stringify({ path: this.filePath, content: editorContent })
                });

                if (!valRes.valid) {
                    this.showBanner('<strong>Validation Failed:</strong><pre style="margin-top:8px;font-size:11px;white-space:pre-wrap;word-break:break-all">' + valRes.error + '</pre>', true);
                    this.saving = false;
                    return;
                }

                // 2. Perform Save
                const saveRes = await api('/inventory/file', {
                    method: 'POST',
                    body: JSON.stringify({ path: this.filePath, content: editorContent })
                });

                if (saveRes.success) {
                    this.loadedContent = editorContent;
                    this.showBanner('File saved and validated successfully.', false);
                    showToast('File saved successfully', 'success');
                } else {
                    this.showBanner(saveRes.error || 'Error saving file', true);
                }
            } catch (e) {
                this.showBanner('Save failed: ' + e.message, true);
            } finally {
                this.saving = false;
            }
        },

        showBanner(msg, isError) {
            this.message = msg;
            this.error = isError;
        }
    };
}
</script>
@endpush
