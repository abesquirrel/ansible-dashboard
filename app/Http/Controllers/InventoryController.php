<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AnsibleService;
use App\Services\AnsibleSSHService;
use App\Services\InventoryParser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function __construct(
        protected AnsibleService    $ansible,
        protected AnsibleSSHService $ssh
    ) {}

    public function index(Request $request)
    {
        $inventory = $request->get('inventory', config('ansible.inventory_default'));
        $inventoryError = null;

        if ($request->get('refresh')) {
            cache()->forget("inv_list_{$inventory}");
            cache()->forget("inv_graph_{$inventory}");
        }

        try {
            // Cache raw inventory list fetch for 1 hour
            $list = cache()->remember("inv_list_{$inventory}", 3600, function () use ($inventory) {
                return $this->ansible->getInventoryList($inventory);
            });

            if (isset($list['error'])) {
                $inventoryError = $list['error'];
                $parsedInventory = [
                    'groups' => [],
                    'hosts' => [],
                    'hostGroups' => [],
                    'hostvars' => [],
                    'error' => $inventoryError,
                ];
            } else {
                $parsedInventory = (new InventoryParser())->parseList($list);
            }
        } catch (\Throwable $e) {
            Log::warning('Inventory load failed', ['error' => $e->getMessage()]);
            $inventoryError = $e->getMessage();
            $parsedInventory = [
                'groups' => [],
                'hosts' => [],
                'hostGroups' => [],
                'hostvars' => [],
                'error' => $inventoryError,
            ];
        }

        $hostsToGroups = $parsedInventory['hostGroups'] ?? [];

        return view('inventory.index', compact('parsedInventory', 'hostsToGroups', 'inventory', 'inventoryError'));
    }

    public function ping(Request $request)
    {
        $data = $request->validate([
            'pattern'   => 'required|string|regex:/^[a-zA-Z0-9\.\-_:,\*\[\]]+$/',
            'inventory' => 'nullable|string',
        ]);

        try {
            $result = $this->ansible->pingHosts($data['pattern'], $data['inventory'] ?? '');
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
        return response()->json($result);
    }

    public function facts(Request $request)
    {
        $data = $request->validate([
            'host'      => 'required|string|regex:/^[a-zA-Z0-9\.\-_]+$/',
            'inventory' => 'nullable|string'
        ]);
        try {
            $facts = $this->ansible->getHostFacts($data['host'], $data['inventory'] ?? '');
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
        return response()->json($facts);
    }

    public function adHoc(Request $request)
    {
        $data = $request->validate([
            'hosts'     => 'required|string',
            'module'    => 'required|string',
            'args'      => 'nullable|string',
            'inventory' => 'nullable|string',
        ]);

        try {
            $result = $this->ansible->runAdHoc(
                $data['hosts'],
                $data['module'],
                $data['args'] ?? '',
                $data['inventory'] ?? '',
                Auth::id()
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }

        return response()->json($result);
    }

    public function getFile(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        if (!$this->isPathAllowed($request->path)) {
            return response()->json(['error' => 'Path not allowed'], 403);
        }

        try {
            $content = $this->ssh->readRemoteFile($request->path, Auth::id());
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
        return response()->json(['content' => $content, 'path' => $request->path]);
    }

    public function saveFile(Request $request)
    {
        $data = $request->validate([
            'path'    => 'required|string',
            'content' => 'required|string',
        ]);

        if (!$this->isPathAllowed($data['path'])) {
            return response()->json(['error' => 'Path not allowed'], 403);
        }

        $tmpPath = '/tmp/inv_edit_' . uniqid();
        file_put_contents($tmpPath, $data['content']);

        try {
            $this->ssh->uploadFile($tmpPath, $data['path'], Auth::id());

            // Cache invalidation for default inventory and current path
            $defaultInv = config('ansible.inventory_default');
            cache()->forget("inv_list_{$defaultInv}");
            cache()->forget("inv_graph_{$defaultInv}");
            cache()->forget("inv_list_{$data['path']}");
            cache()->forget("inv_graph_{$data['path']}");

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        } finally {
            @unlink($tmpPath);
        }
    }

    public function listFiles(Request $request)
    {
        $path = $request->get('path', config('ansible.working_dir'));

        if (!$this->isPathAllowed($path)) {
            return response()->json(['error' => 'Path not allowed'], 403);
        }

        try {
            $files = $this->ssh->listRemoteDir($path);
            return response()->json(['files' => $files]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function validateFile(Request $request)
    {
        $data = $request->validate([
            'path'    => 'required|string',
            'content' => 'required|string',
        ]);

        if (!$this->isPathAllowed($data['path'])) {
            return response()->json(['error' => 'Path not allowed'], 403);
        }

        $tmpLocal = tempnam(sys_get_temp_dir(), 'ansible_val_');
        file_put_contents($tmpLocal, $data['content']);

        $tmpRemote = '/tmp/ansible_val_' . uniqid();

        try {
            $this->ssh->uploadFile($tmpLocal, $tmpRemote, Auth::id());

            $ext = pathinfo($data['path'], PATHINFO_EXTENSION);
            $filename = basename($data['path']);

            $isValid = true;
            $errorMsg = '';

            // Run validation check using ansible-inventory
            if ($ext === 'ini' || $ext === 'yml' || $ext === 'yaml' || $filename === 'inventory' || $filename === 'hosts') {
                $cmd = "ansible-inventory -i " . escapeshellarg($tmpRemote) . " --list 2>&1";
                $res = $this->ssh->exec($cmd);
                if ($res['exit_code'] !== 0) {
                    $isValid = false;
                    $errorMsg = $res['output'];
                }
            } else {
                if ($ext === 'yml' || $ext === 'yaml') {
                    try {
                        \Symfony\Component\Yaml\Yaml::parse($data['content']);
                    } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                        $isValid = false;
                        $errorMsg = $e->getMessage();
                    }
                }
            }

            return response()->json([
                'valid' => $isValid,
                'error' => $isValid ? null : $errorMsg,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'valid' => false,
                'error' => 'Validation failed: ' . $e->getMessage(),
            ]);
        } finally {
            @unlink($tmpLocal);
            try {
                $this->ssh->exec("rm -f " . escapeshellarg($tmpRemote));
            } catch (\Throwable $e) {
                // Ignore
            }
        }
    }

    protected function isPathAllowed(string $path): bool
    {
        if (str_contains($path, '..')) {
            return false;
        }

        $allowedRoots = [
            config('ansible.working_dir'),
            config('ansible.playbooks_dir'),
            config('ansible.inventory_default'),
            dirname(config('ansible.inventory_default')),
        ];

        $cleanedPath = rtrim($path, '/');

        foreach ($allowedRoots as $root) {
            if (empty($root)) {
                continue;
            }
            $cleanedRoot = rtrim($root, '/');
            if ($cleanedPath === $cleanedRoot || str_starts_with($cleanedPath, $cleanedRoot . '/')) {
                return true;
            }
        }

        return false;
    }
}
