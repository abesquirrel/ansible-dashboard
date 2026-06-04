@extends('layouts.app')
@section('title', 'Terminal')

@section('content')
<div class="page-header">
    <div class="flex items-center" style="padding-bottom:16px">
        <div>
            <h1 class="page-title">Terminal</h1>
            <p class="page-subtitle">Direct SSH access to {{ config('ansible.ssh.user') }}@{{ config('ansible.ssh.host') }}</p>
        </div>
        <div class="ml-auto flex gap-2">
            <div id="mode-badge" class="badge" style="background:var(--green-dim);color:var(--green)">COMMAND</div>
            <button onclick="clearTerm()" class="btn btn-sm btn-secondary">Clear</button>
        </div>
    </div>
</div>

<div class="page-body" style="padding-top:0">
    <div class="term-wrap">
        <div class="term-bar">
            <div class="term-dot r"></div>
            <div class="term-dot y"></div>
            <div class="term-dot g"></div>
            <div class="term-title text-mono text-xs">
                {{ config('ansible.ssh.user') }}@{{ config('ansible.ssh.host') }} — ansible terminal
            </div>
            <div id="exec-indicator" style="display:none;font-family:var(--font-mono);font-size:10px;color:var(--yellow);display:flex;align-items:center;gap:6px">
                <div class="spinner" style="width:10px;height:10px;border-width:1.5px"></div>
                Running…
            </div>
        </div>

        {{-- xterm.js terminal --}}
        <div id="terminal" style="padding:8px;background:#0d0d0d;height:calc(100vh - 280px);min-height:400px"></div>
    </div>

    {{-- History --}}
    <div class="card" style="margin-top:16px">
        <div class="card-header">
            <span class="card-title">Command History</span>
            <button onclick="clearHistory()" class="btn btn-sm btn-secondary ml-auto">Clear</button>
        </div>
        <div id="history-list" style="padding:8px 0;max-height:200px;overflow-y:auto"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
.xterm-viewport::-webkit-scrollbar { width: 5px; }
.xterm-viewport::-webkit-scrollbar-thumb { background: #333; }
</style>
@endpush

@push('scripts')
<script>
(function() {
    // ── Initialize xterm.js ──
    const term = new Terminal({
        theme: {
            background:    '#0d0d0d',
            foreground:    '#c8d3e0',
            cursor:        '#39d98a',
            cursorAccent:  '#0d0d0d',
            selection:     'rgba(57,217,138,.2)',
            black:         '#1a1d23',
            red:           '#ff4757',
            green:         '#39d98a',
            yellow:        '#ffd32a',
            blue:          '#3dc6ff',
            magenta:       '#bd93f9',
            cyan:          '#39d98a',
            white:         '#e8eaf0',
            brightBlack:   '#4a5060',
            brightRed:     '#ff6b7a',
            brightGreen:   '#5af2ab',
            brightYellow:  '#ffe566',
            brightBlue:    '#6dd7ff',
            brightMagenta: '#d4b8ff',
            brightCyan:    '#5af2ab',
            brightWhite:   '#f0f2f8',
        },
        fontFamily: "'JetBrains Mono', 'Fira Code', monospace",
        fontSize: 13,
        lineHeight: 1.5,
        cursorBlink: true,
        cursorStyle: 'block',
        scrollback: 5000,
        allowTransparency: false,
    });

    const fitAddon = new FitAddon.FitAddon();
    term.loadAddon(fitAddon);

    term.open(document.getElementById('terminal'));
    fitAddon.fit();

    window.addEventListener('resize', () => fitAddon.fit());

    // ── State ──
    let currentLine   = '';
    let historyIndex  = -1;
    let commandHistory = JSON.parse(localStorage.getItem('termHistory') || '[]');
    let running       = false;
    let prompt        = '\x1b[32m❯\x1b[0m ';

    // ── Write helpers ──
    function writePrompt() {
        term.write('\r\n' + prompt);
        currentLine = '';
    }

    function writeOutput(text, cls) {
        // Convert ANSI from server
        const lines = text.replace(/\r\n/g, '\n').split('\n');
        lines.forEach((line, i) => {
            if (i > 0) term.write('\r\n');
            term.write(line);
        });
    }

    // ── Banner ──
    term.writeln('\x1b[32m ██████╗████████╗██████╗ ██╗   \x1b[0m');
    term.writeln('\x1b[32m██╔════╝╚══██╔══╝██╔══██╗██║   \x1b[0m');
    term.writeln('\x1b[32m██║        ██║   ██████╔╝██║   \x1b[0m');
    term.writeln('\x1b[32m██║        ██║   ██╔══██╗██║   \x1b[0m');
    term.writeln('\x1b[32m╚██████╗   ██║   ██║  ██║███████╗\x1b[0m');
    term.writeln('\x1b[32m ╚═════╝   ╚═╝   ╚═╝  ╚═╝╚══════╝\x1b[0m');
    term.writeln('\x1b[90m Ansible Control Dashboard — SSH Terminal\x1b[0m');
    term.writeln('\x1b[90m Connected to: \x1b[36m{{ config('ansible.ssh.user') }}@{{ config('ansible.ssh.host') }}\x1b[0m\r\n');
    term.write(prompt);

    // ── Key handling ──
    term.onData(async (data) => {
        if (running) return;

        const code = data.charCodeAt(0);

        if (code === 13) { // Enter
            if (!currentLine.trim()) { writePrompt(); return; }

            const cmd = currentLine.trim();

            // Add to history
            if (commandHistory[commandHistory.length-1] !== cmd) {
                commandHistory.push(cmd);
                if (commandHistory.length > 200) commandHistory.shift();
                localStorage.setItem('termHistory', JSON.stringify(commandHistory));
                renderHistory();
            }
            historyIndex = -1;

            term.write('\r\n');

            if (cmd === 'clear') { term.clear(); writePrompt(); return; }
            if (cmd === 'help')  { writeHelp(); writePrompt(); return; }

            await execCommand(cmd);

        } else if (code === 127) { // Backspace
            if (currentLine.length > 0) {
                currentLine = currentLine.slice(0, -1);
                term.write('\b \b');
            }

        } else if (data === '\x1b[A') { // Up arrow
            if (commandHistory.length === 0) return;
            if (historyIndex === -1) historyIndex = commandHistory.length - 1;
            else if (historyIndex > 0) historyIndex--;
            replaceCurrentLine(commandHistory[historyIndex]);

        } else if (data === '\x1b[B') { // Down arrow
            if (historyIndex === -1) return;
            historyIndex++;
            if (historyIndex >= commandHistory.length) { historyIndex = -1; replaceCurrentLine(''); }
            else replaceCurrentLine(commandHistory[historyIndex]);

        } else if (code === 3) { // Ctrl+C
            term.write('^C');
            writePrompt();

        } else if (code >= 32) { // Printable
            currentLine += data;
            term.write(data);
        }
    });

    function replaceCurrentLine(newLine) {
        term.write('\r' + prompt + newLine + '\x1b[K');
        currentLine = newLine;
    }

    async function execCommand(cmd) {
        running = true;
        document.getElementById('exec-indicator').style.display = 'flex';

        try {
            const r = await fetch('/terminal/exec', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.CSRF_TOKEN,
                },
                body: JSON.stringify({ command: cmd, session_id: 'ui-' + Date.now() })
            });

            const data = await r.json();

            if (data.error) {
                term.write('\x1b[31m' + data.error + '\x1b[0m');
            } else {
                writeOutput(data.output || '');

                const exitColor = data.exit_code === 0 ? '\x1b[90m' : '\x1b[31m';
                term.write(`\x1b[0m\r\n${exitColor}[exit: ${data.exit_code} · ${data.duration}ms]\x1b[0m`);
            }
        } catch (e) {
            term.write('\x1b[31mNetwork error: ' + e.message + '\x1b[0m');
        }

        document.getElementById('exec-indicator').style.display = 'none';
        running = false;
        writePrompt();
    }

    function writeHelp() {
        const cmds = [
            ['ansible --version',           'Show Ansible version'],
            ['ansible-playbook <file>',      'Run a playbook'],
            ['ansible all -m ping',          'Ping all hosts'],
            ['ansible-inventory --list',     'List inventory JSON'],
            ['ansible-galaxy role list',     'List installed roles'],
            ['clear',                        'Clear terminal'],
        ];
        term.write('\r\n\x1b[36mAvailable commands:\x1b[0m\r\n');
        cmds.forEach(([c, d]) => {
            term.write(`  \x1b[32m${c.padEnd(35)}\x1b[90m${d}\x1b[0m\r\n`);
        });
    }

    // ── History panel ──
    function renderHistory() {
        const list = document.getElementById('history-list');
        list.innerHTML = [...commandHistory].reverse().slice(0, 50).map(cmd =>
            `<div style="padding:5px 16px;font-family:var(--font-mono);font-size:11px;color:var(--text-secondary);cursor:pointer;border-bottom:1px solid var(--border)"
                onclick="injectCommand(${JSON.stringify(cmd)})"
                onmouseenter="this.style.background='var(--bg-hover)'"
                onmouseleave="this.style.background='transparent'"
            >${cmd}</div>`
        ).join('');
    }

    window.injectCommand = function(cmd) {
        term.focus();
        replaceCurrentLine(cmd);
    };

    window.clearTerm = function() { term.clear(); term.write(prompt); };

    window.clearHistory = function() {
        commandHistory = [];
        localStorage.setItem('termHistory', '[]');
        renderHistory();
    };

    renderHistory();
    term.focus();
})();
</script>
@endpush
