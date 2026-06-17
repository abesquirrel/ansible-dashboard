@extends('layouts.app')
@section('title', 'Learning Hub: Playbooks & Modules')

@section('content')
<div class="page-header">
    <div class="flex items-center" style="padding-bottom:20px">
        <div>
            <h1 class="page-title">3. Playbooks & Modules</h1>
            <p class="page-subtitle">Infrastructure as Code using YAML.</p>
        </div>
        <div class="ml-auto flex gap-2">
            <a href="{{ route('learning.topic', 'inventory-adhoc') }}" class="btn btn-sm btn-secondary">← Back</a>
            <a href="{{ route('learning.index') }}" class="btn btn-sm btn-secondary">Hub</a>
            <a href="{{ route('learning.topic', 'roles') }}" class="btn btn-sm btn-primary">Next →</a>
        </div>
    </div>
</div>

<div class="page-body">
    @include('learning.topics._lesson_header', ['currentSlug' => 'playbooks'])

    {{-- Dynamic Playbooks List --}}
    <div class="grid-2 mb-6">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Available Playbooks (Live from Lab Node)</span>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); font-size:13px; line-height:1.6; margin-bottom:16px;">
                    Playbooks are written in YAML and declare lists of tasks. Here are the playbooks currently available on your active control node. You can run them directly:
                </p>

                @if(!empty($playbooks))
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        @foreach($playbooks as $playbookPath)
                            @php
                                $filename = basename($playbookPath);
                                $isTarget = in_array($filename, ['check_status.yml', 'identify_devices.yml']);
                            @endphp
                            <div style="background:var(--bg-surface); border:1px solid {{ $isTarget ? 'var(--blue)' : 'var(--border)' }}; padding:14px; border-radius:var(--radius); display:flex; align-items:center; justify-content:space-between; gap:16px;">
                                <div>
                                    <div style="font-weight:600; font-family:var(--font-mono); font-size:13px; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
                                        {{ $filename }}
                                        @if($isTarget)
                                            <span class="badge badge-info" style="font-size:9px; padding:1px 6px; font-family:var(--font-ui);">Lab Scenario</span>
                                        @endif
                                    </div>
                                    <div style="font-size:11px; color:var(--text-muted); font-family:var(--font-mono); margin-top:2px;">{{ $playbookPath }}</div>
                                </div>
                                <form action="{{ route('playbooks.run') }}" method="POST" style="margin:0;">
                                    @csrf
                                    <input type="hidden" name="playbook" value="{{ $playbookPath }}">
                                    <button type="submit" class="btn btn-sm {{ $isTarget ? 'btn-primary' : 'btn-secondary' }}" style="padding:4px 12px; font-size:11px; font-family:var(--font-mono);">Run →</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="padding:24px 0; text-align:center; border: 1px dashed var(--border); border-radius: var(--radius);">
                        <p style="color:var(--text-secondary); font-size:13px;">No active playbooks found in configuration directory.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Anatomy of a Health Check Playbook</span>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:12px;">
                    Here is how <code>check_status.yml</code> compiles visual host reports. Note the core YAML structure:
                </p>
                <div style="position:relative;">
                    <button onclick="copyToClipboard('- name: \"Health Check\"\n  hosts: homelab\n  gather_facts: yes\n  tasks:\n    - name: \"Ping verify\"\n      ping:\n    - name: \"Uptime state\"\n      command: uptime\n      register: uptime_result\n    - name: \"[ STATUS REPORT ]\"\n      debug:\n        msg: |\n          ║  SSH/Ping  : PONG\n          ║  Uptime    : @{{ uptime_result.stdout }}', 'Playbook YAML')" style="position:absolute; top:8px; right:8px; background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 8px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                    <div style="font-family:var(--font-mono); font-size:11.5px; background:var(--bg-surface); padding:12px 12px 12px 12px; border-radius:var(--radius); border:1px solid var(--border); overflow-x:auto; line-height:1.6;">
<span style="color:var(--text-muted);">- name: "Health Check"</span>
  hosts: homelab
  gather_facts: yes
  tasks:
    - name: "Ping verify"
      ping:
    - name: "Uptime state"
      command: uptime
      register: uptime_result
    - name: "[ STATUS REPORT ]"
      debug:
        msg: |
          ║  SSH/Ping  : PONG
          ║  Uptime    : @{{ uptime_result.stdout }}
                    </div>
                </div>

                <div style="margin-top:16px; display:flex; flex-direction:column; gap:6px;">
                    @php
                        $concepts = [
                            ['hosts',         'Which inventory group/host to target'],
                            ['gather_facts',  'Auto-collect system facts (OS, CPU, RAM, etc.)'],
                            ['tasks',         'Ordered list of actions to run'],
                            ['register',      'Capture task output into a variable'],
                            ['debug',         'Print a message or variable to the output log'],
                        ];
                    @endphp
                    @foreach($concepts as $c)
                        <div style="display:flex; gap:10px; align-items:baseline; font-size:12px;">
                            <code style="color:var(--accent); white-space:nowrap; min-width:100px;">{{ $c[0] }}</code>
                            <span style="color:var(--text-secondary);">{{ $c[1] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Idempotency --}}
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">Idempotency & Assessment Reports</span>
        </div>
        <div class="card-body" style="display:grid; grid-template-columns: 1fr 1fr; gap:24px;">
            <div>
                <p style="color:var(--text-secondary); line-height:1.6;">
                    Ansible is <strong>idempotent</strong>: executing a playbook multiple times always produces the same system state. If a package is already installed, Ansible reports <code style="color:var(--green);">ok</code> instead of <code style="color:var(--yellow);">changed</code>.
                </p>
            </div>
            <div style="display:flex; gap:16px; align-items:flex-start;">
                @php
                    $statuses = [
                        ['ok',      'var(--green)',  'Task succeeded, no change was made'],
                        ['changed', 'var(--yellow)', 'Task succeeded and made a change'],
                        ['failed',  'var(--red)',    'Task encountered an error'],
                        ['skipped', 'var(--text-muted)', 'Task was conditionally skipped'],
                    ];
                @endphp
                <div style="flex:1;">
                    @foreach($statuses as $s)
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                            <span style="font-family:var(--font-mono); font-size:11px; font-weight:600; color:{{ $s[1] }}; min-width:60px;">{{ $s[0] }}</span>
                            <span style="font-size:12px; color:var(--text-secondary);">{{ $s[2] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Tasks, Exercises, Quiz --}}
    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px; margin-bottom:24px;">

        {{-- Task --}}
        <div class="card" style="border-top: 3px solid var(--blue);">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg class="icon-task" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    <span class="card-title" style="font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--blue);">Lab Task 3: Execute Health Check</span>
                </div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    Run the health check playbook on all hosts to capture live status details.
                </p>
                <div style="background:var(--bg-surface); padding:12px; border-radius:var(--radius); border:1px solid var(--border); margin-bottom:16px;">
                    <div style="font-weight:600; font-size:12px; margin-bottom:6px; color:var(--text-primary);">Procedure:</div>
                    <ol style="margin-left:18px; font-size:12.5px; color:var(--text-secondary); line-height:1.6;">
                        <li>Locate <code>check_status.yml</code> in the list above.</li>
                        <li>Click the <strong>Run →</strong> button next to it.</li>
                        <li>You will be redirected to the <strong>Job Detail</strong> page.</li>
                        <li>Once finished, verify the <strong>Assessment Report</strong> shows uptime, disk use, and RAM for all hosts.</li>
                    </ol>
                </div>
            </div>
        </div>

        {{-- Exercise --}}
        <div class="card" style="border-top: 3px solid var(--green);">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg class="icon-exercise" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span class="card-title" style="font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--green);">Exercise: Analyze Host Hardware</span>
                </div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    Capture deep network profiles and hardware details using the device identification playbook.
                </p>
                <div style="background:var(--bg-surface); padding:12px; border-radius:var(--radius); border:1px solid var(--border); margin-bottom:16px;">
                    <ol style="margin-left:18px; font-size:12.5px; color:var(--text-secondary); line-height:1.6;">
                        <li>Click <strong>Run →</strong> next to <code>identify_devices.yml</code> above.</li>
                        <li>Let the job run to completion. It checks systemd services, ports, block devices, and Linux distros.</li>
                        <li>In the resulting <strong>Assessment Report</strong>, expand host cards for <code>wintersun</code>, <code>autumndusk</code>, and <code>summermoon</code>.</li>
                        <li>Examine the <strong>Network Profile</strong> table — verify interface names and MAC addresses.</li>
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
                <p style="color:var(--text-secondary); font-size:13px; margin-bottom:16px;">Test your understanding:</p>
                <div style="display:flex; flex-direction:column; gap:10px;" id="quiz-playbooks">
                    <div class="quiz-q" data-answer="c">
                        <p style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Q1. What does "idempotent" mean in Ansible context?</p>
                        <label class="quiz-opt"><input type="radio" name="qp1" value="a"> Running a playbook twice installs software twice</label>
                        <label class="quiz-opt"><input type="radio" name="qp1" value="b"> Playbooks can only run once</label>
                        <label class="quiz-opt"><input type="radio" name="qp1" value="c"> Running a playbook multiple times yields the same system state</label>
                    </div>
                    <div class="quiz-q" data-answer="b">
                        <p style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Q2. The <code>register</code> keyword saves task output to a:</p>
                        <label class="quiz-opt"><input type="radio" name="qp2" value="a"> File on disk</label>
                        <label class="quiz-opt"><input type="radio" name="qp2" value="b"> Variable</label>
                        <label class="quiz-opt"><input type="radio" name="qp2" value="c"> Database entry</label>
                    </div>
                    <button onclick="checkQuiz('quiz-playbooks')" class="btn btn-sm btn-secondary" style="margin-top:6px; font-size:11px; align-self:flex-start;">Check Answers</button>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align:right;">
        <a href="{{ route('learning.topic', 'roles') }}" class="btn btn-primary">Next: Roles & Reusability →</a>
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
