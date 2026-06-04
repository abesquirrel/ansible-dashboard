<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\PlaybookJob;
use App\Models\InventoryHost;
use App\Events\PlaybookOutputChunk;
use App\Events\PlaybookFinished;

class AnsibleService
{
    public function __construct(protected AnsibleSSHService $ssh) {}

    // ─── Inventory ────────────────────────────────────────────────

    public function getInventoryGraph(string $inventory = ''): array
    {
        $inv = $inventory ?: config('ansible.inventory_default');
        $result = $this->ssh->exec("ansible-inventory -i {$inv} --graph --output-format json 2>&1");
        if ($result['exit_code'] !== 0) {
            return ['error' => $result['output']];
        }
        return json_decode($result['output'], true) ?? [];
    }

    public function getInventoryList(string $inventory = ''): array
    {
        $inv = $inventory ?: config('ansible.inventory_default');
        $result = $this->ssh->exec("ansible-inventory -i {$inv} --list 2>&1");
        if ($result['exit_code'] !== 0) {
            return ['error' => $result['output']];
        }
        return json_decode($result['output'], true) ?? [];
    }

    public function pingHosts(string $pattern = 'all', string $inventory = ''): array
    {
        $inv = $inventory ?: config('ansible.inventory_default');
        $result = $this->ssh->exec("ansible {$pattern} -i {$inv} -m ping 2>&1");
        return [
            'output'    => $result['output'],
            'exit_code' => $result['exit_code'],
            'parsed'    => $this->parsePingOutput($result['output']),
        ];
    }

    public function getHostFacts(string $host, string $inventory = ''): array
    {
        $inv = $inventory ?: config('ansible.inventory_default');
        $result = $this->ssh->exec(
            "ansible {$host} -i {$inv} -m setup --tree /tmp/ansible_facts_{$host} && cat /tmp/ansible_facts_{$host}/{$host} 2>&1"
        );
        if ($result['exit_code'] !== 0) {
            return ['error' => $result['output']];
        }
        return json_decode($result['output'], true) ?? [];
    }

    // ─── Playbooks ────────────────────────────────────────────────

    public function listPlaybooks(): array
    {
        $dir = config('ansible.playbooks_dir');
        $result = $this->ssh->exec("find {$dir} -name '*.yml' -o -name '*.yaml' 2>/dev/null | sort");
        if ($result['exit_code'] !== 0) {
            return [];
        }
        return array_filter(explode("\n", trim($result['output'])));
    }

    public function getPlaybookContent(string $path): string
    {
        return $this->ssh->readRemoteFile($path);
    }

    /**
     * Run a playbook asynchronously (non-streaming, stored in DB job).
     */
    public function runPlaybook(
        string $playbook,
        string $inventory = '',
        array  $extraVars = [],
        array  $tags = [],
        string $limit = '',
        bool   $checkMode = false,
        bool   $verbose = false,
        ?int   $userId = null
    ): PlaybookJob {
        $inv  = $inventory ?: config('ansible.inventory_default');
        $cmd  = $this->buildPlaybookCommand($playbook, $inv, $extraVars, $tags, $limit, $checkMode, $verbose);

        $job = PlaybookJob::create([
            'user_id'   => $userId,
            'playbook'  => $playbook,
            'inventory' => $inv,
            'command'   => $cmd,
            'extra_vars'=> json_encode($extraVars),
            'tags'      => implode(',', $tags),
            'limit'     => $limit,
            'check_mode'=> $checkMode,
            'status'    => 'queued',
        ]);

        \App\Jobs\RunPlaybookJob::dispatch($job->id);

        return $job;
    }

    /**
     * Build the ansible-playbook CLI command string.
     */
    public function buildPlaybookCommand(
        string $playbook,
        string $inventory,
        array  $extraVars = [],
        array  $tags = [],
        string $limit = '',
        bool   $checkMode = false,
        bool   $verbose = false
    ): string {
        $parts = ["ansible-playbook -i {$inventory}"];

        if ($checkMode) $parts[] = '--check';
        if ($verbose)   $parts[] = '-v';
        if ($limit)     $parts[] = "--limit " . escapeshellarg($limit);
        if ($tags)      $parts[] = "--tags " . escapeshellarg(implode(',', $tags));

        foreach ($extraVars as $key => $value) {
            $parts[] = "--extra-vars " . escapeshellarg("{$key}={$value}");
        }

        $vaultFile = config('ansible.vault_password_file');
        if ($vaultFile) $parts[] = "--vault-password-file {$vaultFile}";

        $parts[] = escapeshellarg($playbook);

        return implode(' ', $parts);
    }

    // ─── Ad-hoc commands ─────────────────────────────────────────

    public function runAdHoc(
        string $hosts,
        string $module,
        string $args = '',
        string $inventory = '',
        ?int   $userId = null
    ): array {
        $inv = $inventory ?: config('ansible.inventory_default');
        $cmd = "ansible {$hosts} -i {$inv} -m {$module}";
        if ($args) $cmd .= " -a " . escapeshellarg($args);

        return $this->ssh->exec($cmd, $userId);
    }

    // ─── Vault ───────────────────────────────────────────────────

    public function vaultEncrypt(string $value): array
    {
        $tmpFile = '/tmp/vault_input_' . uniqid();
        $this->ssh->exec("echo -n " . escapeshellarg($value) . " > {$tmpFile}");
        $vaultFile = config('ansible.vault_password_file');
        $result = $this->ssh->exec("ansible-vault encrypt_string --vault-password-file {$vaultFile} --stdin-name 'secret' < {$tmpFile} && rm -f {$tmpFile}");
        return $result;
    }

    // ─── Roles ───────────────────────────────────────────────────

    public function listRoles(): array
    {
        $result = $this->ssh->exec('ansible-galaxy role list 2>&1');
        $roles  = [];
        foreach (explode("\n", trim($result['output'])) as $line) {
            if (preg_match('/^- (.+), (.+)$/', $line, $m)) {
                $roles[] = ['name' => trim($m[1]), 'version' => trim($m[2])];
            }
        }
        return $roles;
    }

    public function installRole(string $role): array
    {
        return $this->ssh->exec("ansible-galaxy role install {$role} 2>&1");
    }

    // ─── Helpers ─────────────────────────────────────────────────

    protected function parsePingOutput(string $output): array
    {
        $results = [];
        foreach (explode("\n", $output) as $line) {
            if (preg_match('/^(\S+)\s+\|\s+(SUCCESS|FAILED|UNREACHABLE)/', $line, $m)) {
                $results[$m[1]] = strtolower($m[2]);
            }
        }
        return $results;
    }
}
