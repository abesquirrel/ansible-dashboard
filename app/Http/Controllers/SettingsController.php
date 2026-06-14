<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AnsibleSSHService;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'ssh_host'        => config('ansible.ssh.host'),
            'ssh_port'        => config('ansible.ssh.port'),
            'ssh_user'        => config('ansible.ssh.user'),
            'ssh_auth_method' => config('ansible.ssh.key_path') ? 'key' : 'password',
            'working_dir'     => config('ansible.working_dir'),
            'inventory'       => config('ansible.inventory_default'),
            'playbooks_dir'   => config('ansible.playbooks_dir'),
        ];

        return view('settings.index', compact('settings'));
    }

    public function testConnection(AnsibleSSHService $ssh)
    {
        return response()->json($ssh->testConnection());
    }

    public function updateEnv(Request $request)
    {
        $data = $request->validate([
            'ANSIBLE_SSH_HOST'          => 'required|string',
            'ANSIBLE_SSH_PORT'          => 'required|integer',
            'ANSIBLE_SSH_USER'          => 'required|string',
            'ANSIBLE_WORKING_DIR'       => 'required|string',
            'ANSIBLE_PLAYBOOKS_DIR'     => 'required|string',
            'ANSIBLE_INVENTORY_DEFAULT' => 'required|string',
        ]);

        foreach ($data as $key => $value) {
            $this->setEnvValue($key, $value);
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        // Immediately refresh the in-process config so the redirect shows the new values
        $map = [
            'ANSIBLE_SSH_HOST'          => 'ansible.ssh.host',
            'ANSIBLE_SSH_PORT'          => 'ansible.ssh.port',
            'ANSIBLE_SSH_USER'          => 'ansible.ssh.user',
            'ANSIBLE_WORKING_DIR'       => 'ansible.working_dir',
            'ANSIBLE_PLAYBOOKS_DIR'     => 'ansible.playbooks_dir',
            'ANSIBLE_INVENTORY_DEFAULT' => 'ansible.inventory_default',
        ];
        foreach ($data as $key => $value) {
            if (isset($map[$key])) {
                config([$map[$key] => $value]);
            }
        }

        // Rebuild config cache so all future requests see the new values
        \Illuminate\Support\Facades\Artisan::call('config:cache');

        // Signal queue workers to gracefully restart after their current job
        \Illuminate\Support\Facades\Artisan::call('queue:restart');

        // Flush the SSH connection singleton so next request reconnects with new creds
        app()->forgetInstance(\App\Services\AnsibleSSHService::class);
        app()->forgetInstance(\App\Services\AnsibleService::class);

        return back()->with('success', 'Settings saved and applied — config refreshed, workers restarting.');
    }

    protected function setEnvValue(string $key, string $value): void
    {
        $envFile = base_path('.env');
        $content = file_get_contents($envFile);
        if (preg_match("/^{$key}=.*/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= "\n{$key}={$value}";
        }
        file_put_contents($envFile, $content);
    }
}
