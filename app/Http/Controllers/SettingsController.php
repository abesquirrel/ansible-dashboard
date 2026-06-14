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

    public function exportConfig(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:4',
        ]);

        $password = $request->input('password');
        $envFile = base_path('.env');
        
        if (!file_exists($envFile)) {
            return back()->withErrors(['error' => 'No .env file found to export.']);
        }

        $envContent = file_get_contents($envFile);
        
        $keyPath = config('ansible.ssh.key_path');
        $keyContent = null;
        if ($keyPath && file_exists($keyPath)) {
            $keyContent = file_get_contents($keyPath);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'backup_') . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $zip->setPassword($password);
            
            $zip->addFromString('env_backup', $envContent);
            $zip->setEncryptionName('env_backup', \ZipArchive::EM_AES_256);

            if ($keyContent) {
                $zip->addFromString('ansible_rsa', $keyContent);
                $zip->setEncryptionName('ansible_rsa', \ZipArchive::EM_AES_256);
            }

            $zip->close();

            return response()->download($tempFile, 'ctrl_backup_' . date('Y-m-d_His') . '.zip')->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Failed to create backup zip file.');
    }

    public function importConfig(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file',
            'password'    => 'required|string',
        ]);

        $file = $request->file('backup_file');
        $password = $request->input('password');

        $zip = new \ZipArchive();
        if ($zip->open($file->getRealPath()) === true) {
            $zip->setPassword($password);

            $envContent = $zip->getFromName('env_backup');
            if ($envContent === false) {
                $zip->close();
                return back()->with('error', 'Failed to decrypt backup. Please verify your password.');
            }

            $keyContent = $zip->getFromName('ansible_rsa');
            $zip->close();

            file_put_contents(base_path('.env'), $envContent);

            if ($keyContent) {
                $localKeyPath = base_path('ansible_rsa');
                file_put_contents($localKeyPath, $keyContent);
                chmod($localKeyPath, 0600);

                $envContent = file_get_contents(base_path('.env'));
                
                if (preg_match('/^ANSIBLE_SSH_KEY_PATH=.*/m', $envContent)) {
                    $envContent = preg_replace('/^ANSIBLE_SSH_KEY_PATH=.*/m', 'ANSIBLE_SSH_KEY_PATH=/var/www/html/ansible_rsa', $envContent);
                } else {
                    $envContent .= "\nANSIBLE_SSH_KEY_PATH=/var/www/html/ansible_rsa";
                }
                file_put_contents(base_path('.env'), $envContent);
            }

            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('config:cache');
            \Illuminate\Support\Facades\Artisan::call('queue:restart');

            app()->forgetInstance(\App\Services\AnsibleSSHService::class);
            app()->forgetInstance(\App\Services\AnsibleService::class);

            return back()->with('success', 'Configuration and keys successfully imported! Application settings have been updated.');
        }

        return back()->with('error', 'Unable to open ZIP backup archive.');
    }
}
