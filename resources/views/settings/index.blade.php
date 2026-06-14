@extends('layouts.app')
@section('title', 'Settings')

@section('content')
<div class="page-header">
    <div style="padding-bottom:20px">
        <h1 class="page-title">Settings</h1>
        <p class="page-subtitle">SSH connection and Ansible configuration</p>
    </div>
</div>

<div class="page-body">
    <div class="grid-2 gap-4" style="gap:24px;align-items:start">

        {{-- Connection settings --}}
        <div class="card">
            <div class="card-header">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                    <path d="M5 12.55a11 11 0 0 1 14.08 0M1.42 9a16 16 0 0 1 21.16 0M8.53 16.11a6 6 0 0 1 6.95 0"/>
                    <line x1="12" y1="20" x2="12.01" y2="20"/>
                </svg>
                <span class="card-title">SSH Connection</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.env') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Control Node Host</label>
                        <input type="text" name="ANSIBLE_SSH_HOST" class="form-input" value="{{ $settings['ssh_host'] }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SSH Port</label>
                        <input type="number" name="ANSIBLE_SSH_PORT" class="form-input" value="{{ $settings['ssh_port'] }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SSH User</label>
                        <input type="text" name="ANSIBLE_SSH_USER" class="form-input" value="{{ $settings['ssh_user'] }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Auth Method</label>
                        <div class="code-block" style="font-size:12px">{{ $settings['ssh_auth_method'] === 'key' ? 'SSH Key (ANSIBLE_SSH_KEY_PATH)' : 'Password (ANSIBLE_SSH_PASSWORD)' }}</div>
                        <div class="form-hint">Edit ANSIBLE_SSH_KEY_PATH or ANSIBLE_SSH_PASSWORD in .env directly</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Working Directory</label>
                        <input type="text" name="ANSIBLE_WORKING_DIR" class="form-input" value="{{ $settings['working_dir'] }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Default Inventory Path</label>
                        <input type="text" name="ANSIBLE_INVENTORY_DEFAULT" class="form-input" value="{{ $settings['inventory'] }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Playbooks Directory</label>
                        <input type="text" name="ANSIBLE_PLAYBOOKS_DIR" class="form-input" value="{{ $settings['playbooks_dir'] }}">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>

        {{-- Test connection --}}
        <div>
            <div class="card mb-4" x-data="connTest()">
                <div class="card-header">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                        <polyline points="9 11 12 14 22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                    <span class="card-title">Connection Test</span>
                </div>
                <div class="card-body">
                    <button class="btn btn-secondary" @click="test" :disabled="testing">
                        <span x-show="testing" class="spinner" style="width:12px;height:12px"></span>
                        <span x-text="testing ? 'Testing…' : 'Test SSH Connection'"></span>
                    </button>

                    <div x-show="result" style="margin-top:16px">
                        <div class="code-block" style="font-size:11px;white-space:pre-wrap" x-text="JSON.stringify(result, null, 2)"></div>
                    </div>
                </div>
            </div>

            {{-- Device Sync & Backup --}}
            <div class="card mb-4" x-data="syncConfig()">
                <div class="card-header">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                    </svg>
                    <span class="card-title">Device Sync & Backup</span>
                </div>
                <div class="card-body">
                    <p class="text-muted text-xs mb-4">Export or import your environment config (.env) and SSH key as a password-encrypted ZIP archive to sync between development devices.</p>

                    <div class="flex gap-2 mb-4" style="border-bottom: 1px solid var(--border); padding-bottom: 12px">
                        <button class="btn btn-sm" :class="tab === 'export' ? 'btn-primary' : 'btn-secondary'" @click="tab = 'export'">Export Backup</button>
                        <button class="btn btn-sm" :class="tab === 'import' ? 'btn-primary' : 'btn-secondary'" @click="tab = 'import'">Import Backup</button>
                    </div>

                    {{-- Export Form --}}
                    <div x-show="tab === 'export'">
                        <form method="POST" action="{{ route('settings.export') }}">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">Encryption Password</label>
                                <input type="password" name="password" required class="form-input" placeholder="Enter password to lock backup">
                                <div class="form-hint">Required to decrypt the archive when importing on another laptop.</div>
                            </div>
                            <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>
                                </svg>
                                Generate & Download Backup
                            </button>
                        </form>
                    </div>

                    {{-- Import Form --}}
                    <div x-show="tab === 'import'">
                        <form method="POST" action="{{ route('settings.import') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">Select Backup File (.zip)</label>
                                <input type="file" name="backup_file" required class="form-input" accept=".zip" style="padding: 5px">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Decryption Password</label>
                                <input type="password" name="password" required class="form-input" placeholder="Enter backup password">
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm" style="width: 100%; justify-content: center" onclick="return confirm('Importing backup will overwrite your current keys and configuration settings. Are you sure you want to proceed?')">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                                </svg>
                                Decrypt & Apply Backup
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span class="card-title">System Info</span>
                </div>
                <div class="card-body">
                    <div style="display:grid;gap:10px">
                        <div class="flex gap-2">
                            <span class="form-label" style="margin:0;width:140px">Laravel</span>
                            <span class="text-mono text-sm">{{ app()->version() }}</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="form-label" style="margin:0;width:140px">PHP</span>
                            <span class="text-mono text-sm">{{ PHP_VERSION }}</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="form-label" style="margin:0;width:140px">Queue Driver</span>
                            <span class="text-mono text-sm">{{ config('queue.default') }}</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="form-label" style="margin:0;width:140px">Broadcast</span>
                            <span class="text-mono text-sm">{{ config('broadcasting.default') }}</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="form-label" style="margin:0;width:140px">Environment</span>
                            <span class="text-mono text-sm">{{ app()->environment() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function connTest() {
    return {
        testing: false,
        result: null,
        async test() {
            this.testing = true;
            this.result = null;
            this.result = await api('/settings/test', { method: 'POST' });
            this.testing = false;
        }
    };
}

function syncConfig() {
    return {
        tab: 'export'
    };
}
</script>
@endpush
