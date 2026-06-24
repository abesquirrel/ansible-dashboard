<?php

namespace App\Services;

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AnsibleSSHService
{
    protected ?SSH2 $connection = null;
    protected string $host;
    protected int $port;
    protected string $user;
    protected ?string $keyPath;
    protected ?string $password;
    protected ?string $workingDir;

    public function __construct()
    {
        $this->host    = config('ansible.ssh.host');
        $this->port    = (int) config('ansible.ssh.port', 22);
        $this->user    = config('ansible.ssh.user');
        $this->keyPath = config('ansible.ssh.key_path');
        $this->password = config('ansible.ssh.password');
        $this->workingDir = config('ansible.working_dir');
    }

    public function connect(): self
    {
        $this->connection = new SSH2($this->host, $this->port);
        $this->connection->setTimeout(30);

        if ($this->keyPath && file_exists($this->keyPath)) {
            $key = PublicKeyLoader::load(file_get_contents($this->keyPath));
            if (!$this->connection->login($this->user, $key)) {
                throw new \RuntimeException("SSH key auth failed for {$this->user}@{$this->host}");
            }
        } elseif ($this->password) {
            if (!$this->connection->login($this->user, $this->password)) {
                throw new \RuntimeException("SSH password auth failed for {$this->user}@{$this->host}");
            }
        } else {
            throw new \RuntimeException('No SSH credentials configured.');
        }

        return $this;
    }

    public function isConnected(): bool
    {
        return $this->connection !== null && $this->connection->isConnected();
    }

    /**
     * Prepare command by prepending directory navigation and environment configuration.
     *
     * Ansible requires a UTF-8 locale and the correct config path. When the dashboard
     * SSHes into the ansible_control container as user 'paul', the shell is non-interactive
     * and strips the container's environment variables. We always prepend them explicitly.
     */
    protected function prepareCommand(string $command): string
    {
        $prefix = 'export LC_ALL=C.UTF-8';

        if ($this->workingDir) {
            $configPath = rtrim($this->workingDir, '/') . '/ansible.cfg';
            $prefix .= ' && cd ' . escapeshellarg($this->workingDir)
                . ' && export ANSIBLE_CONFIG=' . escapeshellarg($configPath);
        }

        return $prefix . ' && ' . $command;
    }

    /**
     * Execute a command and return full output string.
     */
    public function exec(string $command, ?int $userId = null): array
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $preparedCommand = $this->prepareCommand($command);

        $start = microtime(true);
        $output = $this->connection->exec($preparedCommand);
        $exitCode = $this->connection->getExitStatus();
        $duration = round((microtime(true) - $start) * 1000);

        AuditLog::create([
            'user_id'   => $userId,
            'command'   => $command,
            'exit_code' => $exitCode,
            'duration_ms' => $duration,
            'source'    => 'exec',
        ]);

        return [
            'output'    => $output,
            'exit_code' => $exitCode,
            'duration_ms' => $duration,
        ];
    }

    /**
     * Execute with streaming callback — for live terminal output via WebSockets.
     */
    public function execStreaming(string $command, callable $onOutput, ?int $userId = null): int
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->connection->setTimeout(0);

        $preparedCommand = $this->prepareCommand($command);

        $this->connection->exec($preparedCommand, function ($chunk) use ($onOutput) {
            $onOutput($chunk);
        });

        $exitCode = $this->connection->getExitStatus() ?? 0;

        AuditLog::create([
            'user_id'   => $userId,
            'command'   => $command,
            'exit_code' => $exitCode,
            'source'    => 'stream',
        ]);

        return $exitCode;
    }

    /**
     * Test connectivity and return status array.
     */
    public function testConnection(): array
    {
        try {
            $this->connect();
            $result = $this->exec('ansible --version 2>&1 | head -1');
            $ping   = $this->exec('echo PONG');

            return [
                'connected'       => true,
                'ansible_version' => trim($result['output']),
                'latency_ms'      => $result['duration_ms'],
                'host'            => $this->host,
                'user'            => $this->user,
                'auth_method'     => $this->keyPath ? 'key' : 'password',
            ];
        } catch (\Throwable $e) {
            Log::error('SSH connection test failed', ['error' => $e->getMessage()]);
            return [
                'connected' => false,
                'error'     => $e->getMessage(),
                'host'      => $this->host,
            ];
        }
    }

    /**
     * Get SFTP connection.
     */
    protected function getSftp(): \phpseclib3\Net\SFTP
    {
        $sftp = new \phpseclib3\Net\SFTP($this->host, $this->port);
        $sftp->setTimeout(30);

        if ($this->keyPath && file_exists($this->keyPath)) {
            $key = PublicKeyLoader::load(file_get_contents($this->keyPath));
            if (!$sftp->login($this->user, $key)) {
                throw new \RuntimeException("SFTP key auth failed for {$this->user}@{$this->host}");
            }
        } elseif ($this->password) {
            if (!$sftp->login($this->user, $this->password)) {
                throw new \RuntimeException("SFTP password auth failed for {$this->user}@{$this->host}");
            }
        } else {
            throw new \RuntimeException('No SFTP credentials configured.');
        }

        return $sftp;
    }

    /**
     * List remote directory files and folders.
     */
    public function listRemoteDir(string $path): array
    {
        $sftp = $this->getSftp();
        $raw = $sftp->rawlist($path);
        if ($raw === false) {
            return [];
        }

        $results = [];
        foreach ($raw as $name => $info) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $type = 'file';
            if (isset($info['type'])) {
                // In phpseclib3 type 2 is directory
                if ($info['type'] === 2) {
                    $type = 'dir';
                }
            }
            $results[] = [
                'name' => $name,
                'path' => rtrim($path, '/') . '/' . $name,
                'type' => $type,
                'size' => $info['size'] ?? 0,
            ];
        }

        // Sort: directories first, then files alphabetically
        usort($results, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'dir' ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });

        return $results;
    }

    /**
     * Upload a file via SFTP.
     */
    public function uploadFile(string $localPath, string $remotePath, ?int $userId = null): bool
    {
        $start = microtime(true);
        $sftp = $this->getSftp();
        $ok = $sftp->put($remotePath, $localPath, \phpseclib3\Net\SFTP::SOURCE_LOCAL_FILE);
        $duration = round((microtime(true) - $start) * 1000);

        AuditLog::create([
            'user_id'   => $userId ?: Auth::id(),
            'command'   => "SFTP WRITE: {$remotePath}",
            'exit_code' => $ok ? 0 : 1,
            'duration_ms' => $duration,
            'source'    => 'sftp_write',
        ]);

        return $ok;
    }

    /**
     * Download a remote file content.
     */
    public function readRemoteFile(string $remotePath, ?int $userId = null): string
    {
        $start = microtime(true);
        $sftp = $this->getSftp();
        $content = $sftp->get($remotePath);
        $duration = round((microtime(true) - $start) * 1000);

        AuditLog::create([
            'user_id'   => $userId ?: Auth::id(),
            'command'   => "SFTP READ: {$remotePath}",
            'exit_code' => $content !== false ? 0 : 1,
            'duration_ms' => $duration,
            'source'    => 'sftp_read',
        ]);

        return $content !== false ? $content : '';
    }

    public function disconnect(): void
    {
        if ($this->connection) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
