@extends('layouts.app')
@section('title', 'Learning Hub: Core Concepts')

@section('content')
<div class="page-header">
    <div class="flex items-center" style="padding-bottom:20px">
        <div>
            <h1 class="page-title">1. Ansible Core Concepts</h1>
            <p class="page-subtitle">The foundation of agentless configuration management.</p>
        </div>
        <div class="ml-auto flex gap-2">
            <a href="{{ route('learning.index') }}" class="btn btn-sm btn-secondary">Hub</a>
            <a href="{{ route('learning.topic', 'inventory-adhoc') }}" class="btn btn-sm btn-primary">Next →</a>
        </div>
    </div>
</div>

<div class="page-body">
    @include('learning.topics._lesson_header', ['currentSlug' => 'basics'])

    {{-- Core Concepts --}}
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">What is Ansible?</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                Ansible is an open-source IT automation engine that automates provisioning, configuration management, application deployment, and orchestration. Unlike other tools (like Chef or Puppet), Ansible is <strong>agentless</strong>. It doesn't require any daemon or client software to be running on the servers you are managing.
            </p>
            <p style="color:var(--text-secondary); line-height:1.6;">
                Instead, Ansible connects from the <strong>Control Node</strong> to <strong>Managed Nodes</strong> over standard SSH (or WinRM for Windows), executes temporary Ansible Modules, and cleans up after itself.
            </p>
        </div>
    </div>

    {{-- Connection diagram & Live Stats --}}
    <div class="grid-2 mb-6">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Push Architecture</span>
            </div>
            <div class="card-body">
                <div style="padding:24px; background:var(--bg-base); border:1px solid var(--border); border-radius:var(--radius); margin-bottom:20px; text-align:center;">
                    <div style="display:inline-block; border:1px solid var(--blue); padding:6px 12px; border-radius:var(--radius); background:var(--bg-surface);">
                        <code style="color:var(--blue); font-weight:600;">Control Node</code>
                    </div>
                    <div style="margin: 8px 0; color:var(--text-secondary); font-size:12px; font-family:var(--font-mono);">
                        SSH PUSH (Port 22)
                        <br>▼
                    </div>
                    <div style="display:flex; justify-content:center; gap:8px; flex-wrap:wrap;">
                        <div style="border:1px solid var(--green); padding:4px 8px; border-radius:var(--radius); background:var(--bg-surface); font-size:11px;">
                            <code style="color:var(--green);">wintersun</code>
                        </div>
                        <div style="border:1px solid var(--green); padding:4px 8px; border-radius:var(--radius); background:var(--bg-surface); font-size:11px;">
                            <code style="color:var(--green);">autumndusk</code>
                        </div>
                        <div style="border:1px solid var(--green); padding:4px 8px; border-radius:var(--radius); background:var(--bg-surface); font-size:11px;">
                            <code style="color:var(--green);">summermoon</code>
                        </div>
                    </div>
                </div>
                <p style="color:var(--text-secondary); font-size:13px; line-height:1.5;">
                    The dashboard's web container acts as the client pushing commands to the control node via SSH. The control node then runs <code>ansible</code> commands targeting the remote hosts.
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Active Lab Identity</span>
            </div>
            <div class="card-body">
                @if($sshStatus['connected'])
                    <table style="width:100%; font-size:13px; font-family:var(--font-mono); line-height:2.0;">
                        <tr><td style="color:var(--text-muted);">Status:</td><td><span class="badge badge-success">Online</span></td></tr>
                        <tr><td style="color:var(--text-muted);">Control Host:</td><td style="color:var(--text-primary);">{{ $sshStatus['host'] }}</td></tr>
                        <tr><td style="color:var(--text-muted);">SSH User:</td><td style="color:var(--text-primary);">{{ $sshStatus['user'] }}</td></tr>
                        <tr><td style="color:var(--text-muted);">Auth Method:</td><td style="color:var(--text-primary);">{{ strtoupper($sshStatus['auth_method']) }} key</td></tr>
                        <tr><td style="color:var(--text-muted);">Latency:</td><td style="color:var(--green);">{{ $sshStatus['latency_ms'] }} ms</td></tr>
                        <tr><td style="color:var(--text-muted);">Ansible Version:</td><td style="color:var(--blue); font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:200px;">{{ $sshStatus['ansible_version'] }}</td></tr>
                    </table>
                @else
                    <div style="text-align:center; padding:32px 0;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--text-secondary)" stroke-width="2" style="margin-bottom:12px;">
                            <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <p style="color:var(--text-secondary); font-size:13px; margin-bottom:16px;">Active connection to control node is unavailable.</p>
                        <a href="{{ route('settings.index') }}" class="btn btn-sm btn-primary">Configure Settings</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Lab Node Inventory --}}
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">Real-World Case Scenario: Homelab Devices</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                This lab is connected to three live homelab nodes mapped in the Ansible configuration. Each represents a unique role:
            </p>
            <table class="table" style="width:100%; text-align:left; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="border-bottom:1px solid var(--border);">
                        <th style="padding:10px 0; color:var(--text-primary);">Hostname</th>
                        <th style="padding:10px 0; color:var(--text-primary);">IP Address</th>
                        <th style="padding:10px 0; color:var(--text-primary);">Role Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:10px 0; font-family:var(--font-mono); color:var(--blue);">wintersun.paulrojas.quest</td>
                        <td style="padding:10px 0; font-family:var(--font-mono);">172.16.12.20</td>
                        <td style="padding:10px 0; color:var(--text-secondary);">Media server (running Plex, Jellyfin, Nginx, and Docker workloads)</td>
                    </tr>
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:10px 0; font-family:var(--font-mono); color:var(--blue);">autumndusk.paulrojas.quest</td>
                        <td style="padding:10px 0; font-family:var(--font-mono);">172.16.12.40</td>
                        <td style="padding:10px 0; color:var(--text-secondary);">Internal helper node (running system utilities, databases, backups)</td>
                    </tr>
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:10px 0; font-family:var(--font-mono); color:var(--blue);">summermoon.paulrojas.quest</td>
                        <td style="padding:10px 0; font-family:var(--font-mono);">172.16.12.60</td>
                        <td style="padding:10px 0; color:var(--text-secondary);">DNS and Ad-blocking server (configured as a local Pi-hole)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tasks & Exercises & Quiz --}}
    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px; margin-bottom:24px;">

        {{-- Task --}}
        <div class="card" style="border-top: 3px solid var(--blue);">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg class="icon-task" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    <span class="card-title" style="font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--blue);">Lab Task 1: Check Control Node</span>
                </div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    Before Ansible can control any remote node, it must confirm its connection to the control environment.
                </p>
                <div style="background:var(--bg-surface); padding:12px; border-radius:var(--radius); border:1px solid var(--border); margin-bottom:16px;">
                    <div style="font-weight:600; font-size:12px; margin-bottom:6px; color:var(--text-primary);">Step-by-Step:</div>
                    <ol style="margin-left:18px; font-size:12.5px; color:var(--text-secondary); line-height:1.6;">
                        <li>Verify the <strong>Active Lab Identity</strong> card above shows <span style="color:var(--green); font-weight:600;">Online</span>.</li>
                        <li>Confirm the <strong>Latency</strong> field is below 100 ms — this is the round-trip SSH time.</li>
                        <li>Check <strong>Ansible Version</strong> confirms <code>ansible-core</code> is installed.</li>
                    </ol>
                </div>
                <a href="{{ route('settings.index') }}" class="btn btn-sm btn-primary" style="font-size:11px;">Open Settings →</a>
            </div>
        </div>

        {{-- Exercise --}}
        <div class="card" style="border-top: 3px solid var(--green);">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg class="icon-exercise" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 10c-.83 0-1.5-.67-1.5-1.5v-5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5v5c0 .83-.67 1.5-1.5 1.5z"/><path d="M20.5 10H19V8.5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/><path d="M9.5 14c.83 0 1.5.67 1.5 1.5v5c0 .83-.67 1.5-1.5 1.5S8 21.33 8 20.5v-5c0-.83.67-1.5 1.5-1.5z"/><path d="M3.5 14H5v1.5c0 .83-.67 1.5-1.5 1.5S2 16.33 2 15.5 2.67 14 3.5 14z"/><path d="M14 14.5c0-.83.67-1.5 1.5-1.5h5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-5c-.83 0-1.5-.67-1.5-1.5z"/><path d="M15.5 19H14v1.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5-.67-1.5-1.5-1.5z"/><path d="M10 9.5C10 8.67 9.33 8 8.5 8h-5C2.67 8 2 8.67 2 9.5S2.67 11 3.5 11h5c.83 0 1.5-.67 1.5-1.5z"/><path d="M8.5 5H10V3.5C10 2.67 9.33 2 8.5 2S7 2.67 7 3.5 7.67 5 8.5 5z"/></svg>
                    <span class="card-title" style="font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--green);">Exercise: Test Connection</span>
                </div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    Use the dashboard's built-in connection testing engine to verify SSH to the control node.
                </p>
                <div style="background:var(--bg-surface); padding:12px; border-radius:var(--radius); border:1px solid var(--border); margin-bottom:16px;">
                    <ol style="margin-left:18px; font-size:12.5px; color:var(--text-secondary); line-height:1.6;">
                        <li>Navigate to the <a href="{{ route('settings.index') }}" style="color:var(--green); text-decoration:none; font-weight:600;">Settings</a> panel.</li>
                        <li>Find the <strong>SSH Control Node</strong> settings form.</li>
                        <li>Click the <strong>Test Connection</strong> button.</li>
                        <li>Look for the green alert with latency details.</li>
                    </ol>
                </div>
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
                <p style="color:var(--text-secondary); font-size:13px; margin-bottom:16px;">Test your understanding of the core concepts:</p>
                <div style="display:flex; flex-direction:column; gap:10px;" id="quiz-basics">
                    <div class="quiz-q" data-answer="b">
                        <p style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Q1. Ansible connects to managed nodes using which protocol?</p>
                        <label class="quiz-opt"><input type="radio" name="q1" value="a"> WinRM only</label>
                        <label class="quiz-opt"><input type="radio" name="q1" value="b"> SSH (or WinRM for Windows)</label>
                        <label class="quiz-opt"><input type="radio" name="q1" value="c"> A proprietary daemon</label>
                    </div>
                    <div class="quiz-q" data-answer="a">
                        <p style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Q2. Where are Ansible playbooks and inventory files stored?</p>
                        <label class="quiz-opt"><input type="radio" name="q2" value="a"> On the Control Node</label>
                        <label class="quiz-opt"><input type="radio" name="q2" value="b"> On each Managed Node</label>
                        <label class="quiz-opt"><input type="radio" name="q2" value="c"> In a central database</label>
                    </div>
                    <button onclick="checkQuiz('quiz-basics')" class="btn btn-sm btn-secondary" style="margin-top:6px; font-size:11px; align-self:flex-start;">Check Answers</button>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align:right;">
        <a href="{{ route('learning.topic', 'inventory-adhoc') }}" class="btn btn-primary">Next: Inventory & Ad-hoc →</a>
    </div>
</div>

<style>
.quiz-opt {
    display:flex; align-items:center; gap:8px;
    font-size:12px; color:var(--text-secondary);
    cursor:pointer; padding:4px 0;
}
.quiz-opt.correct { color:var(--green); }
.quiz-opt.wrong   { color:var(--red); }
</style>
<script>
function checkQuiz(containerId) {
    const container = document.getElementById(containerId);
    let allCorrect = true;
    container.querySelectorAll('.quiz-q').forEach(function(q) {
        const answer = q.dataset.answer;
        const selected = q.querySelector('input[type=radio]:checked');
        q.querySelectorAll('.quiz-opt').forEach(function(opt) {
            opt.classList.remove('correct', 'wrong');
        });
        if (!selected) { allCorrect = false; return; }
        const isCorrect = selected.value === answer;
        if (!isCorrect) allCorrect = false;
        const selectedOpt = selected.closest('.quiz-opt');
        selectedOpt.classList.add(isCorrect ? 'correct' : 'wrong');
        q.querySelector('.quiz-opt:has(input[value="'+answer+'"])').classList.add('correct');
    });
}
</script>
@endsection
