@extends('layouts.app')
@section('title', 'Learning Hub: Roles & Reusability')

@section('content')
<div class="page-header">
    <div class="flex items-center" style="padding-bottom:20px">
        <div>
            <h1 class="page-title">4. Roles & Reusability</h1>
            <p class="page-subtitle">Organizing playbooks for scale.</p>
        </div>
        <div class="ml-auto flex gap-2">
            <a href="{{ route('learning.topic', 'playbooks') }}" class="btn btn-sm btn-secondary">← Back</a>
            <a href="{{ route('learning.index') }}" class="btn btn-sm btn-secondary">Hub</a>
            <a href="{{ route('learning.topic', 'vars-templates') }}" class="btn btn-sm btn-primary">Next →</a>
        </div>
    </div>
</div>

<div class="page-body">
    @include('learning.topics._lesson_header', ['currentSlug' => 'roles'])

    {{-- What is a Role --}}
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">What is a Role?</span>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                As your infrastructure grows, a single monolithic playbook becomes hard to maintain. <strong>Roles</strong> allow you to automatically load related vars, files, tasks, and handlers based on a known file structure. They let you encapsulate logic so you can easily reuse it across multiple playbooks.
            </p>

            <h3 style="font-size:14px; color:var(--accent); margin-bottom:8px;">Standard Role Directory Structure:</h3>
            <div style="position:relative;">
                <button onclick="copyToClipboard('my_role/\n├── tasks/\n│   └── main.yml\n├── handlers/\n│   └── main.yml\n├── templates/\n├── files/\n├── vars/\n│   └── main.yml\n├── defaults/\n│   └── main.yml\n└── meta/\n    └── main.yml', 'Role structure')" style="position:absolute; top:8px; right:8px; background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 8px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                <div class="code-block" style="margin-bottom:16px; font-family:var(--font-mono); font-size:12px; padding-right:60px;">
my_role/
├── tasks/
│   └── main.yml       <span style="color:var(--text-muted);"># Main list of tasks that the role executes</span>
├── handlers/
│   └── main.yml       <span style="color:var(--text-muted);"># Handlers used within or outside this role</span>
├── templates/         <span style="color:var(--text-muted);"># Jinja2 templates for config files (.j2)</span>
├── files/             <span style="color:var(--text-muted);"># Static files to be deployed</span>
├── vars/
│   └── main.yml       <span style="color:var(--text-muted);"># Variables for the role (high precedence)</span>
├── defaults/
│   └── main.yml       <span style="color:var(--text-muted);"># Default variables (low precedence, easily overridden)</span>
└── meta/
    └── main.yml       <span style="color:var(--text-muted);"># Role dependencies and author info</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Dynamic Roles List --}}
    <div class="grid-2 mb-6">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Installed Galaxy Roles (Live from Lab)</span>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); font-size:13px; line-height:1.6; margin-bottom:16px;">
                    These are the roles currently downloaded and available on your control node:
                </p>

                @if(!empty($roles) && count($roles) > 0)
                    <div style="display:grid; grid-template-columns:1fr; gap:8px;">
                        @foreach($roles as $role)
                            <div style="background:var(--bg-surface); border:1px solid var(--border); padding:10px 14px; border-radius:var(--radius); display:flex; align-items:center; justify-content:space-between;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                    <span style="font-weight:600; font-family:var(--font-mono); font-size:12.5px; color:var(--text-primary);">{{ $role['name'] }}</span>
                                </div>
                                <span class="badge" style="font-size:10px; background:var(--bg-hover); color:var(--text-secondary); font-family:var(--font-mono);">{{ $role['version'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="padding:24px 0; text-align:center; border: 1px dashed var(--border); border-radius: var(--radius);">
                        <p style="color:var(--text-secondary); font-size:13px; margin-bottom:12px;">No custom or Galaxy roles currently detected.</p>
                        <span style="font-size:11px; color:var(--text-muted);">Roles are stored in your configured ansible directory.</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Ansible Galaxy & Reusability</span>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    <strong>Ansible Galaxy</strong> is a public directory of pre-made roles written by the community. Instead of writing custom roles for standard software (like Docker, MySQL, or Nginx), you can pull down battle-tested configurations.
                </p>
                <div style="position:relative; margin-bottom:12px;">
                    <button onclick="copyToClipboard('ansible-galaxy role install geerlingguy.nginx', 'Galaxy install command')" style="position:absolute; top:8px; right:8px; background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 8px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                    <div style="background:var(--bg-surface); padding:12px; border-radius:var(--radius); border:1px solid var(--border); font-family:var(--font-mono); font-size:12px;">
<span style="color:var(--text-muted);"># Download Nginx Role from Galaxy</span>
ansible-galaxy role install geerlingguy.nginx
                    </div>
                </div>
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    Using a role in a playbook is as simple as adding a <code>roles:</code> section:
                </p>
                <div style="position:relative;">
                    <button onclick="copyToClipboard('- hosts: media_servers\n  roles:\n    - role: geerlingguy.nginx\n      vars:\n        nginx_listen_port: 80', 'Role usage YAML')" style="position:absolute; top:8px; right:8px; background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 8px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                    <div style="background:var(--bg-surface); padding:12px; border-radius:var(--radius); border:1px solid var(--border); font-family:var(--font-mono); font-size:12px; line-height:1.6;">
- hosts: media_servers
  roles:
    - role: geerlingguy.nginx
      vars:
        nginx_listen_port: 80
                    </div>
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
                    <span class="card-title" style="font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--blue);">Lab Task 4: Explore Installed Roles</span>
                </div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    Verify role installations directly using the Ansible CLI.
                </p>
                <div style="background:var(--bg-surface); padding:12px; border-radius:var(--radius); border:1px solid var(--border); margin-bottom:12px;">
                    <div style="font-weight:600; font-size:12px; margin-bottom:6px; color:var(--text-primary);">Procedure:</div>
                    <ol style="margin-left:18px; font-size:12.5px; color:var(--text-secondary); line-height:1.6; margin-bottom:10px;">
                        <li>Navigate to the <a href="{{ route('terminal.index') }}" style="color:var(--blue); text-decoration:none; font-weight:600;">Terminal</a> page.</li>
                        <li>Open an interactive terminal session to the control node.</li>
                        <li>Type <code>ansible-galaxy role list</code> and press Enter.</li>
                        <li>Review the role paths displayed.</li>
                    </ol>
                    <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-base); padding:6px 12px; border-radius:var(--radius);">
                        <code style="font-size:11px; color:var(--text-code);">ansible-galaxy role list</code>
                        <button onclick="copyToClipboard('ansible-galaxy role list', 'Role list command')" style="background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 8px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Exercise --}}
        <div class="card" style="border-top: 3px solid var(--green);">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg class="icon-exercise" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span class="card-title" style="font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--green);">Exercise: Install a Galaxy Role</span>
                </div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    Install a new community role on the control node and verify it appears in the live list.
                </p>
                <div style="background:var(--bg-surface); padding:12px; border-radius:var(--radius); border:1px solid var(--border); margin-bottom:12px;">
                    <ol style="margin-left:18px; font-size:12.5px; color:var(--text-secondary); line-height:1.6; margin-bottom:10px;">
                        <li>Go to the <a href="{{ route('terminal.index') }}" style="color:var(--green); text-decoration:none; font-weight:600;">Terminal</a> page.</li>
                        <li>Run: <code>ansible-galaxy role install geerlingguy.nginx</code>.</li>
                        <li>Wait for the success confirmation in the terminal.</li>
                        <li>Return to this page and check the <strong>Installed Galaxy Roles</strong> list — <code>geerlingguy.nginx</code> should now appear.</li>
                    </ol>
                    <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-base); padding:6px 12px; border-radius:var(--radius);">
                        <code style="font-size:11px; color:var(--text-code);">ansible-galaxy role install geerlingguy.nginx</code>
                        <button onclick="copyToClipboard('ansible-galaxy role install geerlingguy.nginx', 'Galaxy install command')" style="background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 8px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                    </div>
                </div>
                <a href="{{ route('terminal.index') }}" class="btn btn-sm btn-primary" style="font-size:11px;">Open Terminal →</a>
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
                <div style="display:flex; flex-direction:column; gap:10px;" id="quiz-roles">
                    <div class="quiz-q" data-answer="b">
                        <p style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Q1. Which directory in a role contains the primary list of tasks?</p>
                        <label class="quiz-opt"><input type="radio" name="qr1" value="a"> <code>defaults/</code></label>
                        <label class="quiz-opt"><input type="radio" name="qr1" value="b"> <code>tasks/</code></label>
                        <label class="quiz-opt"><input type="radio" name="qr1" value="c"> <code>meta/</code></label>
                    </div>
                    <div class="quiz-q" data-answer="a">
                        <p style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Q2. Variables in <code>defaults/main.yml</code> have which precedence?</p>
                        <label class="quiz-opt"><input type="radio" name="qr2" value="a"> Low — easily overridden by the playbook</label>
                        <label class="quiz-opt"><input type="radio" name="qr2" value="b"> High — cannot be overridden</label>
                        <label class="quiz-opt"><input type="radio" name="qr2" value="c"> They are environment variables</label>
                    </div>
                    <button onclick="checkQuiz('quiz-roles')" class="btn btn-sm btn-secondary" style="margin-top:6px; font-size:11px; align-self:flex-start;">Check Answers</button>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align:right;">
        <a href="{{ route('learning.topic', 'vars-templates') }}" class="btn btn-primary">Next: Variables & Templates →</a>
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
