<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AnsibleService;
use App\Services\AnsibleSSHService;

class InventoryController extends Controller
{
    public function __construct(
        protected AnsibleService    $ansible,
        protected AnsibleSSHService $ssh
    ) {}

    public function index(Request $request)
    {
        $inventory = $request->get('inventory', config('ansible.inventory_default'));
        $graph     = cache()->remember("inv_graph_{$inventory}", 120, fn () => $this->ansible->getInventoryGraph($inventory));
        $list      = cache()->remember("inv_list_{$inventory}", 120, fn () => $this->ansible->getInventoryList($inventory));

        return view('inventory.index', compact('graph', 'list', 'inventory'));
    }

    public function ping(Request $request)
    {
        $data = $request->validate([
            'pattern'   => 'required|string',
            'inventory' => 'nullable|string',
        ]);

        $result = $this->ansible->pingHosts($data['pattern'], $data['inventory'] ?? '');
        return response()->json($result);
    }

    public function facts(Request $request)
    {
        $data = $request->validate(['host' => 'required|string']);
        $facts = $this->ansible->getHostFacts($data['host']);
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

        $result = $this->ansible->runAdHoc(
            $data['hosts'],
            $data['module'],
            $data['args'] ?? '',
            $data['inventory'] ?? '',
            auth()->id()
        );

        return response()->json($result);
    }

    public function getFile(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        $content = $this->ssh->readRemoteFile($request->path);
        return response()->json(['content' => $content, 'path' => $request->path]);
    }

    public function saveFile(Request $request)
    {
        $data = $request->validate([
            'path'    => 'required|string',
            'content' => 'required|string',
        ]);

        // Write to temp, upload via SFTP
        $tmpPath = '/tmp/inv_edit_' . uniqid();
        file_put_contents($tmpPath, $data['content']);

        try {
            $this->ssh->uploadFile($tmpPath, $data['path']);
            cache()->forget('inv_graph_' . $data['path']);
            cache()->forget('inv_list_' . $data['path']);
            return response()->json(['success' => true]);
        } finally {
            @unlink($tmpPath);
        }
    }
}
