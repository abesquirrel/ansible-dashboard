@extends('layouts.app')
@section('title', 'Inventory')

@section('content')
<div class="page-header">
    <div class="flex items-center" style="padding-bottom:20px">
        <div>
            <h1 class="page-title">Inventory</h1>
            <p class="page-subtitle">Host topology, facts and ad-hoc commands</p>
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
                    <input type="text" id="inv-search" class="form-input" style="width:200px;padding:5px 10px" placeholder="Filter hosts…" oninput="filterGraph(this.value)">
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
            <div id="inv-graph-container" style="min-height:400px;background:var(--bg-base);border-bottom:1px solid var(--border);position:relative;overflow:hidden">
                <canvas id="inv-canvas" style="width:100%;height:400px"></canvas>
            </div>
        </div>

        {{-- Groups/Hosts grid --}}
        <div id="groups-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px">
            @php
                $allGroups = $list['_meta'] ?? [];
                $hostvars  = $list['_meta']['hostvars'] ?? [];
                $groups    = collect($list)->except(['_meta', 'all', 'ungrouped']);
            @endphp

            @foreach($groups as $groupName => $groupData)
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
                        <div id="facts-content" style="font-family:var(--font-mono);font-size:11px;line-height:1.7;color:var(--text-code);background:var(--bg-base);padding:16px;max-height:500px;overflow-y:auto"></div>
                    </div>
                </div>
            </div>
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

                <button class="btn btn-primary" @click="run" :disabled="running">
                    <span x-show="running" class="spinner" style="width:13px;height:13px"></span>
                    <span x-text="running ? 'Running…' : 'Execute'"></span>
                </button>

                <div x-show="output" style="margin-top:16px">
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
                <span class="card-title">Inventory File Editor</span>
            </div>
            <div class="card-body">
                <div class="flex gap-2 mb-4">
                    <input class="form-input" x-model="filePath" :placeholder="'{{ config('ansible.inventory_default') }}'" style="flex:1">
                    <button class="btn btn-secondary" @click="loadFile">Load</button>
                    <button class="btn btn-primary" @click="saveFile" :disabled="!content || saving">
                        <span x-show="saving" class="spinner" style="width:12px;height:12px"></span>
                        <span x-text="saving ? 'Saving…' : 'Save'"></span>
                    </button>
                </div>
                <textarea class="form-textarea" x-model="content" style="min-height:400px;font-family:var(--font-mono);font-size:12px;line-height:1.7;background:var(--bg-base);color:var(--text-code)" placeholder="Load a file to edit…"></textarea>
                <div x-show="message" class="alert" :class="error?'alert-error':'alert-success'" style="margin-top:12px;margin-bottom:0" x-text="message"></div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/d3.min.js') }}"></script>
<script>
// ── Tab switching ──
function switchTab(name, el) {
    event.preventDefault();
    ['graph','adhoc','editor'].forEach(t => {
        document.getElementById(`tab-${t}`).style.display = t === name ? 'block' : 'none';
    });
    document.querySelectorAll('.page-tab').forEach(a => a.classList.remove('active'));
    el.classList.add('active');
}

// ── Inventory Graph (D3 force-directed) ──
(function drawGraph() {
    const list = @json($list);
    const nodes = [];
    const links = [];
    const groups = Object.entries(list).filter(([k]) => k !== '_meta');
    const hostSet = new Set();

    // Add group nodes
    groups.forEach(([name, data]) => {
        if (name === 'all' || name === 'ungrouped') return;
        nodes.push({ id: name, type: 'group', r: 22 });
        (data.hosts || []).forEach(host => {
            if (!hostSet.has(host)) {
                nodes.push({ id: host, type: 'host', r: 14 });
                hostSet.add(host);
            }
            links.push({ source: name, target: host });
        });
    });

    if (nodes.length === 0) return;

    const container = document.getElementById('inv-graph-container');
    const W = container.offsetWidth || 800;
    const H = 400;

    const svg = d3.select('#inv-canvas')
        .attr('width', W).attr('height', H)
        .style('width', '100%').style('height', H + 'px');

    const sim = d3.forceSimulation(nodes)
        .force('link', d3.forceLink(links).id(d => d.id).distance(90))
        .force('charge', d3.forceManyBody().strength(-200))
        .force('center', d3.forceCenter(W/2, H/2))
        .force('collision', d3.forceCollide(30));

    const link = svg.append('g')
        .selectAll('line')
        .data(links).join('line')
        .attr('stroke', '#242830').attr('stroke-width', 1.5);

    const node = svg.append('g')
        .selectAll('g')
        .data(nodes).join('g')
        .call(d3.drag()
            .on('start', (e,d) => { if (!e.active) sim.alphaTarget(.3).restart(); d.fx = d.x; d.fy = d.y; })
            .on('drag',  (e,d) => { d.fx = e.x; d.fy = e.y; })
            .on('end',   (e,d) => { if (!e.active) sim.alphaTarget(0); d.fx = null; d.fy = null; }));

    node.append('circle')
        .attr('r', d => d.r)
        .attr('fill', d => d.type === 'group' ? 'rgba(61,198,255,.15)' : 'rgba(57,217,138,.08)')
        .attr('stroke', d => d.type === 'group' ? '#3dc6ff' : '#39d98a')
        .attr('stroke-width', 1.5)
        .style('cursor', d => d.type === 'host' ? 'pointer' : 'default')
        .on('click', (e, d) => { if (d.type === 'host') showHostFacts(d.id); });

    node.append('text')
        .text(d => d.id)
        .attr('text-anchor', 'middle')
        .attr('dy', d => d.r + 14)
        .attr('font-family', 'JetBrains Mono')
        .attr('font-size', 10)
        .attr('fill', '#7c8496');

    sim.on('tick', () => {
        link.attr('x1', d => d.source.x).attr('y1', d => d.source.y)
            .attr('x2', d => d.target.x).attr('y2', d => d.target.y);
        node.attr('transform', d => `translate(${d.x},${d.y})`);
    });
})();

// ── Host facts ──
async function showHostFacts(host) {
    document.getElementById('facts-title').textContent = `${host} — Facts`;
    document.getElementById('facts-content').textContent = 'Loading…';
    document.getElementById('facts-modal').style.display = 'block';

    const r = await api('/inventory/facts', {
        method: 'POST',
        body: JSON.stringify({ host })
    });

    document.getElementById('facts-content').textContent = JSON.stringify(r, null, 2);
}

// ── Ping all ──
async function pingAll() {
    const r = await api('/inventory/ping', {
        method: 'POST',
        body: JSON.stringify({ pattern: 'all' })
    });

    if (r.parsed) {
        Object.entries(r.parsed).forEach(([host, status]) => {
            const dot = document.getElementById(`ping-${host}`);
            if (dot) {
                dot.style.background = status === 'success' ? 'var(--green)' : 'var(--red)';
                if (status === 'success') dot.style.boxShadow = '0 0 4px var(--green)';
            }
        });
    }
}

function filterGraph(q) {
    document.querySelectorAll('.host-row').forEach(el => {
        const host = el.dataset.host.toLowerCase();
        el.style.display = q && !host.includes(q.toLowerCase()) ? 'none' : 'flex';
    });
    document.querySelectorAll('.group-card').forEach(el => {
        const vis = Array.from(el.querySelectorAll('.host-row')).some(r => r.style.display !== 'none');
        el.style.display = vis ? '' : 'none';
    });
}

// ── Ad-hoc runner ──
function adhocRunner() {
    return {
        form: { hosts: 'all', module: 'ping', args: '' },
        running: false,
        output: '',
        async run() {
            this.running = true;
            this.output = '';
            const r = await api('/inventory/adhoc', {
                method: 'POST',
                body: JSON.stringify(this.form)
            });
            this.output = r.output || JSON.stringify(r, null, 2);
            this.running = false;
        }
    };
}

// ── File editor ──
function fileEditor() {
    return {
        filePath: '{{ config('ansible.inventory_default') }}',
        content: '',
        saving: false,
        message: '',
        error: false,
        async loadFile() {
            const r = await api('/inventory/file?path=' + encodeURIComponent(this.filePath));
            this.content = r.content;
        },
        async saveFile() {
            this.saving = true;
            try {
                const r = await api('/inventory/file', {
                    method: 'POST',
                    body: JSON.stringify({ path: this.filePath, content: this.content })
                });
                this.message = r.success ? 'File saved successfully' : 'Error saving file';
                this.error = !r.success;
            } finally {
                this.saving = false;
            }
        }
    };
}
</script>
@endpush
