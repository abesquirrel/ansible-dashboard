@extends('layouts.app')
@section('title', 'Learning Hub: Inventory & Ad-hoc')

@section('content')
<div class="page-header">
    <div class="flex items-center" style="padding-bottom:20px">
        <div>
            <h1 class="page-title">2. Inventory & Ad-Hoc Commands</h1>
            <p class="page-subtitle">Defining your fleet and running quick tasks.</p>
        </div>
        <div class="ml-auto flex gap-2">
            <a href="{{ route('learning.topic', 'basics') }}" class="btn btn-sm btn-secondary">← Back</a>
            <a href="{{ route('learning.index') }}" class="btn btn-sm btn-secondary">Hub</a>
            <a href="{{ route('learning.topic', 'playbooks') }}" class="btn btn-sm btn-primary">Next →</a>
        </div>
    </div>
</div>

<div class="page-body">
    @include('learning.topics._lesson_header', ['currentSlug' => 'inventory-adhoc'])

    {{-- Active Inventory Display --}}
    <div class="grid-2 mb-6">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Active Inventory (Live from Lab)</span>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); font-size:13px; line-height:1.6; margin-bottom:16px;">
                    Ansible matches nodes based on groups configured in the inventory. Here is the live parsed structure directly from your control node:
                </p>

                @if(!empty($inventory) && count($inventory) > 1)
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        @foreach($inventory as $groupName => $groupData)
                            @if($groupName !== '_meta' && $groupName !== 'all')
                                <div style="background:var(--bg-surface); padding:12px; border:1px solid var(--border); border-radius:var(--radius);">
                                    <div style="font-weight:600; color:var(--accent); font-family:var(--font-mono); font-size:13px; display:flex; align-items:center; gap:6px;">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                                        [{{ $groupName }}]
                                    </div>
                                    @if(isset($groupData['hosts']) && count($groupData['hosts']) > 0)
                                        <ul style="list-style:none; padding-left:14px; font-family:var(--font-mono); font-size:12px; margin-top:6px; display:flex; flex-direction:column; gap:4px;">
                                            @foreach($groupData['hosts'] as $host)
                                                <li style="color:var(--text-primary);">
                                                    <span style="color:var(--text-secondary);">•</span> {{ $host }}
                                                    @if(isset($inventory['_meta']['hostvars'][$host]['ansible_host']))
                                                        <span style="color:var(--text-muted); font-size:11px;">({{ $inventory['_meta']['hostvars'][$host]['ansible_host'] }})</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if(isset($groupData['children']) && count($groupData['children']) > 0)
                                        <div style="font-size:11px; color:var(--text-muted); font-family:var(--font-mono); padding-left:14px; margin-top:6px;">
                                            Subgroups: {{ implode(', ', $groupData['children']) }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div style="padding:24px 0; text-align:center; border: 1px dashed var(--border); border-radius: var(--radius);">
                        <p style="color:var(--text-secondary); font-size:13px;">No active inventory groups loaded. Ensure SSH setup is active.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Inventory Structure Theory</span>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    Ansible inventories define the hosts you target. They are typically structured in INI or YAML format.
                </p>
                <div style="font-family:var(--font-mono); font-size:12px; background:var(--bg-surface); padding:12px; border-radius:var(--radius); border:1px solid var(--border); overflow-x:auto; position:relative;">
                    <button onclick="copyToClipboard('[media_servers]\nwintersun.paulrojas.quest\n\n[internal_nodes]\nsummermoon.paulrojas.quest\nautumndusk.paulrojas.quest', 'Inventory example')" style="position:absolute; top:8px; right:8px; background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 8px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
<span style="color:var(--text-muted);"># INI Example</span>
[media_servers]
wintersun.paulrojas.quest

[internal_nodes]
summermoon.paulrojas.quest
autumndusk.paulrojas.quest
                </div>
                <p style="color:var(--text-secondary); font-size:13px; line-height:1.6; margin-top:12px;">
                    In your live lab, <code>wintersun</code> is grouped under <code>[media_servers]</code>, while <code>summermoon</code> and <code>autumndusk</code> are under <code>[internal_nodes]</code>. This allows running commands targeted per group.
                </p>
            </div>
        </div>
    </div>

    {{-- Ad-Hoc Commands --}}
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">Ad-Hoc Commands Reference</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                Ad-hoc commands are quick, one-off commands run via Ansible modules — designed for verification tasks without creating playbooks.
            </p>
            <table class="table" style="width:100%; text-align:left; border-collapse:collapse; font-size:13px; margin-bottom:16px;">
                <thead>
                    <tr style="border-bottom:1px solid var(--border);">
                        <th style="padding:10px 0; color:var(--text-primary); width:150px;">Command Name</th>
                        <th style="padding:10px 0; color:var(--text-primary);">Ansible Syntax</th>
                        <th style="padding:10px 0; color:var(--text-primary);">Explanation</th>
                        <th style="padding:10px 0; color:var(--text-primary); width:70px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $adhocCmds = [
                            ['Connectivity Ping', 'ansible all -m ping', 'Test basic Python execution and SSH connectivity on all nodes.'],
                            ['Uptime Triage', 'ansible internal_nodes -m command -a "uptime"', 'Query current uptime and load averages from internal_nodes group.'],
                            ['Disk Check', 'ansible media_servers -m command -a "df -h /"', 'Query remaining disk space on wintersun media server.'],
                            ['Service Status', 'ansible all -m shell -a "systemctl is-active nginx"', 'Check whether the nginx service is active across all nodes.'],
                        ];
                    @endphp
                    @foreach($adhocCmds as $cmd)
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:10px 0; font-weight:600; color:var(--blue);">{{ $cmd[0] }}</td>
                        <td style="padding:10px 0; font-family:var(--font-mono); color:var(--text-code); font-size:12px;">{{ $cmd[1] }}</td>
                        <td style="padding:10px 0; color:var(--text-secondary);">{{ $cmd[2] }}</td>
                        <td style="padding:10px 0;">
                            <button onclick="copyToClipboard('{{ $cmd[1] }}', '{{ $cmd[0] }} command')" style="background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 8px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui); white-space:nowrap;">Copy</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tasks, Exercises, Quiz --}}
    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px; margin-bottom:24px;">

        {{-- Task --}}
        <div class="card" style="border-top: 3px solid var(--blue);">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg class="icon-task" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    <span class="card-title" style="font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--blue);">Lab Task 2: Ping Managed Nodes</span>
                </div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    Test connectivity to all hosts in the inventory via standard Ansible ping modules.
                </p>
                <div style="background:var(--bg-surface); padding:12px; border-radius:var(--radius); border:1px solid var(--border); margin-bottom:12px;">
                    <div style="font-weight:600; font-size:12px; margin-bottom:6px; color:var(--text-primary);">Procedure:</div>
                    <ol style="margin-left:18px; font-size:12.5px; color:var(--text-secondary); line-height:1.6; margin-bottom:10px;">
                        <li>Go to the <a href="{{ route('inventory.index') }}" style="color:var(--blue); text-decoration:none; font-weight:600;">Inventory</a> page.</li>
                        <li>Verify that the <strong>Hosts Graph</strong> maps all your nodes.</li>
                        <li>Click the <strong>Ping All</strong> button to run an ad-hoc ping.</li>
                        <li>Verify every host reports a success status.</li>
                    </ol>
                    <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-base); padding:6px 12px; border-radius:var(--radius);">
                        <code style="font-size:11px; color:var(--text-code);">ansible all -m ping</code>
                        <button onclick="copyToClipboard('ansible all -m ping', 'Ping command')" style="background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 8px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Exercise --}}
        <div class="card" style="border-top: 3px solid var(--green);">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg class="icon-exercise" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span class="card-title" style="font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--green);">Exercise: Check Uptime via Ad-Hoc</span>
                </div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    Execute an ad-hoc command manually via the dashboard's command interface.
                </p>
                <div style="background:var(--bg-surface); padding:12px; border-radius:var(--radius); border:1px solid var(--border); margin-bottom:12px;">
                    <ol style="margin-left:18px; font-size:12.5px; color:var(--text-secondary); line-height:1.6; margin-bottom:10px;">
                        <li>Open the <a href="{{ route('inventory.index') }}#adhoc" style="color:var(--green); text-decoration:none; font-weight:600;">Ad-hoc Runner</a> tab on the Inventory page.</li>
                        <li>Set <strong>Hosts Pattern</strong> to: <code>internal_nodes</code>.</li>
                        <li>Set <strong>Module</strong> to: <code>command</code>.</li>
                        <li>Enter <strong>Arguments</strong>: <code>uptime</code>.</li>
                        <li>Click <strong>Run Ad-hoc Command</strong>.</li>
                        <li>Verify the stdout shows load averages for each host.</li>
                    </ol>
                    <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-base); padding:6px 12px; border-radius:var(--radius);">
                        <code style="font-size:11px; color:var(--text-code);">uptime</code>
                        <button onclick="copyToClipboard('uptime', 'Uptime command')" style="background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 8px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                    </div>
                </div>
                <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-primary" style="font-size:11px;">Go to Ad-hoc Runner →</a>
            </div>
        </div>

        {{-- Knowledge Check --}}
        <div class="card" style="border-top: 3px solid var(--yellow);">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg class="icon-quiz" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span class="card-title" style="font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--yellow);">Knowledge Check</span>
                </div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); font-size:13px; margin-bottom:16px;">Test your understanding:</p>
                <div style="display:flex; flex-direction:column; gap:10px;" id="quiz-inventory">
                    <div class="quiz-q" data-answer="b">
                        <p style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Q1. What flag is used with <code>ansible</code> to specify a module?</p>
                        <label class="quiz-opt"><input type="radio" name="qi1" value="a"> <code>-a</code></label>
                        <label class="quiz-opt"><input type="radio" name="qi1" value="b"> <code>-m</code></label>
                        <label class="quiz-opt"><input type="radio" name="qi1" value="c"> <code>-i</code></label>
                    </div>
                    <div class="quiz-q" data-answer="c">
                        <p style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Q2. In a static inventory INI file, hosts are organized into:</p>
                        <label class="quiz-opt"><input type="radio" name="qi2" value="a"> Roles</label>
                        <label class="quiz-opt"><input type="radio" name="qi2" value="b"> Tasks</label>
                        <label class="quiz-opt"><input type="radio" name="qi2" value="c"> Groups</label>
                    </div>
                    <button onclick="checkQuiz('quiz-inventory')" class="btn btn-sm btn-secondary" style="margin-top:6px; font-size:11px; align-self:flex-start;">Check Answers</button>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align:right;">
        <a href="{{ route('learning.topic', 'playbooks') }}" class="btn btn-primary">Next: Playbooks & Modules →</a>
    </div>
</div>

<style>
.quiz-opt { display:flex; align-items:center; gap:8px; font-size:12px; color:var(--text-secondary); cursor:pointer; padding:4px 0; }
.quiz-opt.correct { color:var(--green); }
.quiz-opt.wrong   { color:var(--red); }
</style>
<script>
function checkQuiz(containerId) {
    const container = document.getElementById(containerId);
    container.querySelectorAll('.quiz-q').forEach(function(q) {
        const answer = q.dataset.answer;
        const selected = q.querySelector('input[type=radio]:checked');
        q.querySelectorAll('.quiz-opt').forEach(o => o.classList.remove('correct','wrong'));
        if (!selected) return;
        const isCorrect = selected.value === answer;
        selected.closest('.quiz-opt').classList.add(isCorrect ? 'correct' : 'wrong');
        q.querySelector('.quiz-opt:has(input[value="'+answer+'"])').classList.add('correct');
    });
}
</script>
@endsection
