<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AnsibleService;
use App\Services\AnsibleSSHService;
use Illuminate\Support\Facades\Log;

class LearningController extends Controller
{
    public function __construct(
        protected AnsibleService $ansible,
        protected AnsibleSSHService $ssh
    ) {}

    public function index()
    {
        $sshStatus = $this->ssh->testConnection();
        $hostCount = 0;
        $playbookCount = 0;

        if ($sshStatus['connected']) {
            try {
                $inventory = $this->ansible->getInventoryList();
                $hostCount = count($inventory['_meta']['hostvars'] ?? []);
                $playbookCount = count($this->ansible->listPlaybooks());
            } catch (\Throwable $e) {
                Log::warning('Error loading index counts from Ansible service: ' . $e->getMessage());
            }
        }

        return view('learning.index', compact('sshStatus', 'hostCount', 'playbookCount'));
    }

    public function topic($slug)
    {
        $sshStatus = $this->ssh->testConnection();
        $hostCount = 0;

        if ($sshStatus['connected']) {
            try {
                $inventory = $this->ansible->getInventoryList();
                $hostCount = count($inventory['_meta']['hostvars'] ?? []);
            } catch (\Throwable $e) {
                Log::warning('Error loading host counts for topic page: ' . $e->getMessage());
            }
        }

        $data = [
            'sshStatus' => $sshStatus,
            'hostCount' => $hostCount,
        ];

        switch ($slug) {
            case 'basics':
                break;

            case 'inventory-adhoc':
                $inventoryRaw = [];
                if ($sshStatus['connected']) {
                    try {
                        $inventoryRaw = $this->ansible->getInventoryList();
                    } catch (\Throwable $e) {
                        Log::warning('Error loading inventory list for topic: ' . $e->getMessage());
                    }
                }
                $data['inventory'] = $inventoryRaw;
                break;

            case 'playbooks':
                $playbooks = [];
                if ($sshStatus['connected']) {
                    try {
                        $playbooks = $this->ansible->listPlaybooks();
                    } catch (\Throwable $e) {
                        Log::warning('Error loading playbooks for topic: ' . $e->getMessage());
                    }
                }
                $data['playbooks'] = $playbooks;
                break;

            case 'roles':
                $roles = [];
                if ($sshStatus['connected']) {
                    try {
                        $roles = $this->ansible->listRoles();
                    } catch (\Throwable $e) {
                        Log::warning('Error loading roles for topic: ' . $e->getMessage());
                    }
                }
                $data['roles'] = $roles;
                break;

            case 'vars-templates':
                // Self-contained theory + exercises — no extra lab data required
                break;

            default:
                abort(404, 'Learning topic not found.');
        }

        $viewMap = [
            'basics'          => 'learning.topics.basics',
            'inventory-adhoc' => 'learning.topics.inventory-adhoc',
            'playbooks'       => 'learning.topics.playbooks',
            'roles'           => 'learning.topics.roles',
            'vars-templates'  => 'learning.topics.vars-templates',
        ];

        return view($viewMap[$slug], $data);
    }
}
