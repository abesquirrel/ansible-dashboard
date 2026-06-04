<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CTRL — Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600&family=IBM+Plex+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base:    #0a0c0f;
            --bg-panel:   #0f1117;
            --bg-surface: #161920;
            --border:     #242830;
            --accent:     #39d98a;
            --text-primary: #e8eaf0;
            --text-secondary: #7c8496;
            --text-muted: #4a5060;
            --red: #ff4757;
            --font-mono: 'JetBrains Mono', monospace;
            --font-ui:   'IBM Plex Sans', sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            background: var(--bg-base);
            display: grid;
            place-items: center;
            font-family: var(--font-ui);
            color: var(--text-primary);
            position: relative;
            overflow: hidden;
        }
        /* Grid background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: .4;
            pointer-events: none;
        }
        /* Glow */
        body::after {
            content: '';
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -60%);
            width: 600px; height: 400px;
            background: radial-gradient(ellipse, rgba(57,217,138,.06) 0%, transparent 70%);
            pointer-events: none;
        }
        .login-box {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 380px;
            padding: 20px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 36px;
        }
        .logo-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px; height: 52px;
            border: 1.5px solid var(--accent);
            margin: 0 auto 14px;
            position: relative;
        }
        .logo-mark::before, .logo-mark::after {
            content: '';
            position: absolute;
            width: 6px; height: 6px;
            border: 1px solid var(--accent);
            opacity: .5;
        }
        .logo-mark::before { top: -4px; left: -4px; }
        .logo-mark::after  { bottom: -4px; right: -4px; }
        .logo-mark svg { color: var(--accent); }
        .logo-name {
            font-family: var(--font-mono);
            font-size: 22px;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--text-primary);
        }
        .logo-sub {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: .08em;
            margin-top: 4px;
        }
        .card {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 28px;
        }
        .form-group { margin-bottom: 18px; }
        .form-label {
            display: block;
            font-family: var(--font-mono);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 7px;
        }
        .form-input {
            width: 100%;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text-primary);
            font-family: var(--font-mono);
            font-size: 13px;
            padding: 10px 13px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(57,217,138,.1);
        }
        .form-input::placeholder { color: var(--text-muted); }
        .btn-submit {
            width: 100%;
            padding: 11px;
            background: var(--accent);
            color: var(--bg-base);
            border: none;
            border-radius: 4px;
            font-family: var(--font-mono);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .06em;
            cursor: pointer;
            transition: background .15s;
            margin-top: 4px;
        }
        .btn-submit:hover { background: #2fc87a; }
        .error-msg {
            background: rgba(255,71,87,.1);
            border: 1px solid var(--red);
            color: var(--red);
            font-family: var(--font-mono);
            font-size: 12px;
            padding: 10px 13px;
            border-radius: 4px;
            margin-bottom: 18px;
        }
        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--text-secondary);
            cursor: pointer;
        }
        .corner-tag {
            position: fixed;
            font-family: var(--font-mono);
            font-size: 10px;
            color: var(--text-muted);
            letter-spacing: .06em;
        }
        .corner-tag.tl { top: 20px; left: 20px; }
        .corner-tag.br { bottom: 20px; right: 20px; }
    </style>
</head>
<body>
    <div class="corner-tag tl">CTRL / ansible-dashboard / v1.0.0</div>
    <div class="corner-tag br">{{ config('ansible.ssh.host') }}</div>

    <div class="login-box">
        <div class="login-logo">
            <div class="logo-mark">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>
                </svg>
            </div>
            <div class="logo-name">CTRL</div>
            <div class="logo-sub">Ansible Control Dashboard</div>
        </div>

        <div class="card">
            @if($errors->any())
            <div class="error-msg">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input"
                        value="{{ old('email') }}" autofocus autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input"
                        autocomplete="current-password" required>
                </div>
                <label class="remember">
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <button type="submit" class="btn-submit">Sign In →</button>
            </form>
        </div>
    </div>
</body>
</html>
