@extends('layouts.app')
@section('title', 'Learning Hub: Variables & Templates')

@section('content')
<div class="page-header">
    <div class="flex items-center" style="padding-bottom:20px">
        <div>
            <h1 class="page-title">5. Variables & Templates</h1>
            <p class="page-subtitle">Dynamic configuration with Jinja2 and Ansible Vault.</p>
        </div>
        <div class="ml-auto flex gap-2">
            <a href="{{ route('learning.topic', 'roles') }}" class="btn btn-sm btn-secondary">← Back</a>
            <a href="{{ route('learning.index') }}" class="btn btn-sm btn-secondary">Hub</a>
        </div>
    </div>
</div>

<div class="page-body">
    @include('learning.topics._lesson_header', ['currentSlug' => 'vars-templates'])

    {{-- Variable Precedence & Types --}}
    <div class="grid-2 mb-6">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Variable Types & Precedence</span>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    Ansible resolves variable values based on a <strong>precedence chain</strong>. Higher precedence wins:
                </p>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    @php
                        $precedence = [
                            ['Extra vars (CLI)',     'Highest',  'var(--red)',         'ansible-playbook … -e "var=value"'],
                            ['Task vars',            'High',     'var(--orange)',       'vars: defined inside a task'],
                            ['Role vars/',           'Medium',   'var(--yellow)',       'roles/my_role/vars/main.yml'],
                            ['Host/group vars',      'Lower',    'var(--blue)',         'host_vars/ or group_vars/'],
                            ['Role defaults/',       'Lowest',   'var(--text-muted)',   'roles/my_role/defaults/main.yml'],
                        ];
                    @endphp
                    @foreach($precedence as $i => $p)
                        <div style="display:flex; align-items:center; gap:10px; background:var(--bg-surface); border:1px solid var(--border); border-left: 3px solid {{ $p[2] }}; padding:8px 12px; border-radius:var(--radius); font-size:12px;">
                            <div style="min-width:130px; font-weight:600; color:var(--text-primary);">{{ $p[0] }}</div>
                            <div style="min-width:60px; font-size:10px; color:{{ $p[2] }}; font-family:var(--font-mono);">{{ $p[1] }}</div>
                            <div style="color:var(--text-muted); font-family:var(--font-mono); font-size:11px;">{{ $p[3] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Defining & Using Variables</span>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:12px;">
                    Variables can be defined inline in a playbook or in a dedicated vars file. They are referenced using double curly braces: <code>@{{ variable_name }}</code>
                </p>

                <div style="margin-bottom:16px;">
                    <div style="font-size:11px; color:var(--text-muted); font-family:var(--font-mono); margin-bottom:4px;">In-playbook vars:</div>
                    <div style="position:relative;">
                        <button onclick="copyToClipboard('- name: Configure Nginx\n  hosts: media_servers\n  vars:\n    nginx_port: 8080\n    app_name: jellyfin\n  tasks:\n    - name: Print the port\n      debug:\n        msg: \"Listening on port @{{ nginx_port }}\"', 'Inline vars example')" style="position:absolute; top:6px; right:6px; background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 6px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                        <div style="font-family:var(--font-mono); font-size:11.5px; background:var(--bg-surface); padding:10px; border-radius:var(--radius); border:1px solid var(--border); line-height:1.6; overflow-x:auto;">
- name: Configure Nginx
  hosts: media_servers
  vars:
    nginx_port: 8080
    app_name: jellyfin
  tasks:
    - name: Print the port
      debug:
        msg: "Listening on port <span style="color:var(--accent);">@{{ nginx_port }}</span>"
                        </div>
                    </div>
                </div>

                <div>
                    <div style="font-size:11px; color:var(--text-muted); font-family:var(--font-mono); margin-bottom:4px;">group_vars/media_servers.yml:</div>
                    <div style="position:relative;">
                        <button onclick="copyToClipboard('nginx_port: 8080\napp_name: jellyfin\nmax_connections: 512', 'group_vars example')" style="position:absolute; top:6px; right:6px; background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 6px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                        <div style="font-family:var(--font-mono); font-size:11.5px; background:var(--bg-surface); padding:10px; border-radius:var(--radius); border:1px solid var(--border); line-height:1.6;">
<span style="color:var(--blue);">nginx_port</span>: 8080
<span style="color:var(--blue);">app_name</span>: jellyfin
<span style="color:var(--blue);">max_connections</span>: 512
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Jinja2 Templates --}}
    <div class="card mb-6">
        <div class="card-header">
            <span class="card-title">Jinja2 Templates (.j2 files)</span>
        </div>
        <div class="card-body" style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:24px;">
            <div>
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:12px;">
                    The <strong>template</strong> module renders a Jinja2 <code>.j2</code> file with variable substitution and deploys the resulting file to managed nodes. This is how you generate dynamic config files.
                </p>
                <p style="color:var(--text-secondary); font-size:13px; line-height:1.6;">
                    Supported Jinja2 features: variable substitution <code>@{{ }}</code>, conditionals <code>{% raw %}{% if %}{% endraw %}</code>, loops <code>{% raw %}{% for %}{% endraw %}</code>, and filters like <code>| upper</code>, <code>| default('value')</code>.
                </p>
            </div>
            <div>
                <div style="font-size:11px; color:var(--text-muted); font-family:var(--font-mono); margin-bottom:4px;">templates/nginx.conf.j2:</div>
                <div style="position:relative;">
                    <button onclick="copyToClipboard('server {\n    listen @{{ nginx_port }};\n    server_name @{{ ansible_hostname }};\n\n    {% if enable_ssl %}\n    ssl on;\n    ssl_certificate /etc/ssl/@{{ app_name }}.crt;\n    {% endif %}\n\n    location / {\n        proxy_pass http://localhost:@{{ app_port }};\n    }\n}', 'nginx.conf.j2 template')" style="position:absolute; top:6px; right:6px; background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 6px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                    <div style="font-family:var(--font-mono); font-size:11px; background:var(--bg-surface); padding:10px; border-radius:var(--radius); border:1px solid var(--border); line-height:1.6; overflow-x:auto;">
server {
  listen <span style="color:var(--accent);">@{{ nginx_port }}</span>;
  server_name <span style="color:var(--accent);">@{{ ansible_hostname }}</span>;

  @{% if enable_ssl %}
  ssl on;
  ssl_certificate /etc/ssl/<span style="color:var(--accent);">@{{ app_name }}</span>.crt;
  @{% endif %}

  location / {
    proxy_pass http://localhost:<span style="color:var(--accent);">@{{ app_port }}</span>;
  }
}
                    </div>
                </div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--text-muted); font-family:var(--font-mono); margin-bottom:4px;">Task using the template:</div>
                <div style="position:relative;">
                    <button onclick="copyToClipboard('- name: Deploy Nginx config\n  template:\n    src: templates/nginx.conf.j2\n    dest: /etc/nginx/nginx.conf\n    owner: root\n    group: root\n    mode: \"0644\"\n  notify: Reload Nginx', 'Template task YAML')" style="position:absolute; top:6px; right:6px; background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 6px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                    <div style="font-family:var(--font-mono); font-size:11px; background:var(--bg-surface); padding:10px; border-radius:var(--radius); border:1px solid var(--border); line-height:1.6;">
- name: Deploy Nginx config
  <span style="color:var(--blue);">template</span>:
    src: templates/nginx.conf.j2
    dest: /etc/nginx/nginx.conf
    owner: root
    group: root
    mode: "0644"
  <span style="color:var(--yellow);">notify</span>: Reload Nginx
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ansible Vault --}}
    <div class="card mb-6">
        <div class="card-header">
            <div style="display:flex; align-items:center; gap:10px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span class="card-title">Ansible Vault — Securing Secrets</span>
            </div>
        </div>
        <div class="card-body" style="display:grid; grid-template-columns: 1fr 1fr; gap:24px;">
            <div>
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    <strong>Ansible Vault</strong> allows you to encrypt sensitive data (API keys, passwords, SSH keys) within YAML files. Encrypted files can be safely committed to version control.
                </p>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    @php
                        $vaultCmds = [
                            ['Create encrypted file',     'ansible-vault create secrets.yml'],
                            ['Encrypt existing file',     'ansible-vault encrypt vars/secrets.yml'],
                            ['Decrypt to view/edit',      'ansible-vault decrypt vars/secrets.yml'],
                            ['Edit encrypted in-place',   'ansible-vault edit vars/secrets.yml'],
                            ['Run playbook with vault',   'ansible-playbook site.yml --ask-vault-pass'],
                        ];
                    @endphp
                    @foreach($vaultCmds as $vc)
                        <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-surface); border:1px solid var(--border); padding:8px 12px; border-radius:var(--radius); gap:10px;">
                            <div>
                                <div style="font-size:11px; color:var(--text-secondary); margin-bottom:2px;">{{ $vc[0] }}</div>
                                <code style="font-family:var(--font-mono); font-size:11px; color:var(--text-code);">{{ $vc[1] }}</code>
                            </div>
                            <button onclick="copyToClipboard('{{ $vc[1] }}', '{{ $vc[0] }}')" style="flex-shrink:0; background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 8px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                        </div>
                    @endforeach
                </div>
            </div>
            <div>
                <div style="font-size:11px; color:var(--text-muted); font-family:var(--font-mono); margin-bottom:4px;">secrets.yml (after encryption):</div>
                <div style="font-family:var(--font-mono); font-size:11px; background:var(--bg-surface); padding:10px; border-radius:var(--radius); border:1px solid var(--border); line-height:1.6; margin-bottom:12px; color:var(--text-muted);">
$ANSIBLE_VAULT;1.1;AES256
35343965363430313934613562616437
63626162623661313761636235393765
30313265323462316639633365353337
...
                </div>
                <div style="background:var(--bg-surface); border:1px solid var(--border-bright); border-left:3px solid var(--orange); padding:12px; border-radius:var(--radius);">
                    <div style="font-size:12px; font-weight:600; color:var(--orange); margin-bottom:6px;">Best Practice</div>
                    <p style="font-size:12px; color:var(--text-secondary); line-height:1.6;">
                        Store the vault password in a <code>.vault_password_file</code> and reference it via <code>ANSIBLE_VAULT_PASSWORD_FILE</code> environment variable. Never commit the vault password to git.
                    </p>
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
                    <span class="card-title" style="font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--blue);">Lab Task 5: Create a vars File</span>
                </div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    Create a <code>group_vars/homelab.yml</code> on the control node and reference a variable inside a debug task.
                </p>
                <div style="background:var(--bg-surface); padding:12px; border-radius:var(--radius); border:1px solid var(--border); margin-bottom:12px;">
                    <div style="font-weight:600; font-size:12px; margin-bottom:6px; color:var(--text-primary);">Procedure:</div>
                    <ol style="margin-left:18px; font-size:12.5px; color:var(--text-secondary); line-height:1.6; margin-bottom:10px;">
                        <li>Open a <a href="{{ route('terminal.index') }}" style="color:var(--blue); text-decoration:none; font-weight:600;">Terminal</a> session.</li>
                        <li>Navigate to your Ansible directory: <code>cd ~/ansible</code></li>
                        <li>Create <code>group_vars/homelab.yml</code> containing: <code>greeting: "Hello from lab"</code></li>
                        <li>Create a playbook that uses <code>debug: msg="@{{ greeting }}"</code> and run it.</li>
                        <li>Verify the output shows your custom greeting.</li>
                    </ol>
                    <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-base); padding:6px 12px; border-radius:var(--radius);">
                        <code style="font-size:11px; color:var(--text-code);">mkdir -p group_vars && echo "greeting: Hello from lab" > group_vars/homelab.yml</code>
                        <button onclick="copyToClipboard('mkdir -p group_vars && echo \"greeting: Hello from lab\" > group_vars/homelab.yml', 'Create vars file')" style="flex-shrink:0; background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 8px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Exercise --}}
        <div class="card" style="border-top: 3px solid var(--green);">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:8px;">
                    <svg class="icon-exercise" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span class="card-title" style="font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--green);">Exercise: Deploy a Config Template</span>
                </div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:16px;">
                    Write a Jinja2 template for an <code>/etc/motd</code> message-of-the-day file and deploy it to all homelab nodes.
                </p>
                <div style="background:var(--bg-surface); padding:12px; border-radius:var(--radius); border:1px solid var(--border); margin-bottom:12px;">
                    <ol style="margin-left:18px; font-size:12.5px; color:var(--text-secondary); line-height:1.6; margin-bottom:10px;">
                        <li>In the <a href="{{ route('terminal.index') }}" style="color:var(--green); text-decoration:none; font-weight:600;">Terminal</a>, create <code>templates/motd.j2</code>:</li>
                    </ol>
                    <div style="font-family:var(--font-mono); font-size:11px; background:var(--bg-base); padding:8px 10px; border-radius:var(--radius); margin-bottom:8px; color:var(--text-primary); line-height:1.6;">
Welcome to <span style="color:var(--accent);">@{{ ansible_hostname }}</span>
Managed by Ansible · Lab: homelab
IP: <span style="color:var(--blue);">@{{ ansible_default_ipv4.address }}</span>
                    </div>
                    <ol start="2" style="margin-left:18px; font-size:12.5px; color:var(--text-secondary); line-height:1.6; margin-bottom:10px;">
                        <li>Write a playbook using the <code>template</code> module to copy it to <code>/etc/motd</code>.</li>
                        <li>Run it with <code>ansible-playbook deploy_motd.yml</code>.</li>
                        <li>SSH into <code>wintersun</code> and confirm the motd is visible on login.</li>
                    </ol>
                    <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-base); padding:6px 12px; border-radius:var(--radius);">
                        <code style="font-size:11px; color:var(--text-code);">ansible-playbook deploy_motd.yml</code>
                        <button onclick="copyToClipboard('ansible-playbook deploy_motd.yml', 'Run deploy_motd')" style="background:var(--bg-hover); border:1px solid var(--border); border-radius:var(--radius); padding:2px 8px; font-size:10px; color:var(--text-secondary); cursor:pointer; font-family:var(--font-ui);">Copy</button>
                    </div>
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
                <div style="display:flex; flex-direction:column; gap:10px;" id="quiz-vars">
                    <div class="quiz-q" data-answer="a">
                        <p style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Q1. Which has the <em>highest</em> variable precedence in Ansible?</p>
                        <label class="quiz-opt"><input type="radio" name="qv1" value="a"> Extra vars passed with <code>-e</code></label>
                        <label class="quiz-opt"><input type="radio" name="qv1" value="b"> Role <code>defaults/main.yml</code></label>
                        <label class="quiz-opt"><input type="radio" name="qv1" value="c"> <code>group_vars/all.yml</code></label>
                    </div>
                    <div class="quiz-q" data-answer="c">
                        <p style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Q2. What file extension do Ansible Jinja2 templates use?</p>
                        <label class="quiz-opt"><input type="radio" name="qv2" value="a"> <code>.tpl</code></label>
                        <label class="quiz-opt"><input type="radio" name="qv2" value="b"> <code>.tmpl</code></label>
                        <label class="quiz-opt"><input type="radio" name="qv2" value="c"> <code>.j2</code></label>
                    </div>
                    <div class="quiz-q" data-answer="b">
                        <p style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:8px;">Q3. Ansible Vault is used to:</p>
                        <label class="quiz-opt"><input type="radio" name="qv3" value="a"> Speed up playbook execution</label>
                        <label class="quiz-opt"><input type="radio" name="qv3" value="b"> Encrypt sensitive data like passwords and keys</label>
                        <label class="quiz-opt"><input type="radio" name="qv3" value="c"> Back up playbook history</label>
                    </div>
                    <button onclick="checkQuiz('quiz-vars')" class="btn btn-sm btn-secondary" style="margin-top:6px; font-size:11px; align-self:flex-start;">Check Answers</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Completion Banner --}}
    <div class="card mb-6" style="border: 1px solid var(--accent); background: linear-gradient(135deg, var(--bg-surface) 0%, var(--bg-hover) 100%);">
        <div class="card-body" style="text-align:center; padding:32px;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.5" style="margin-bottom:16px;">
                <circle cx="12" cy="12" r="10"/><polyline points="16 8 10 14 7 11" stroke-width="2"/>
            </svg>
            <h2 style="font-size:18px; font-weight:700; color:var(--text-primary); margin-bottom:8px;">Learning Path Complete</h2>
            <p style="color:var(--text-secondary); font-size:14px; line-height:1.6; max-width:500px; margin:0 auto 20px;">
                You've covered all five core Ansible concepts — from agentless architecture to Vault-secured templates. Put your skills to work with the full dashboard:
            </p>
            <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                <a href="{{ route('playbooks.index') }}" class="btn btn-primary">Run Playbooks →</a>
                <a href="{{ route('inventory.index') }}" class="btn btn-secondary">View Inventory</a>
                <a href="{{ route('learning.index') }}" class="btn btn-secondary">Back to Hub</a>
            </div>
        </div>
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
