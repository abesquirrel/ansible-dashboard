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
        $this->authorize('admin');

        $data = $request->validate([
            'ANSIBLE_SSH_HOST'     => 'required|string',
            'ANSIBLE_SSH_PORT'     => 'required|integer',
            'ANSIBLE_SSH_USER'     => 'required|string',
            'ANSIBLE_WORKING_DIR'  => 'required|string',
            'ANSIBLE_PLAYBOOKS_DIR'=> 'required|string',
            'ANSIBLE_INVENTORY_DEFAULT' => 'required|string',
        ]);

        // Write to .env (simple approach — for production use a proper config store)
        foreach ($data as $key => $value) {
            $this->setEnvValue($key, $value);
        }

        return back()->with('success', 'Settings updated. Restart queue workers to apply.');
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
