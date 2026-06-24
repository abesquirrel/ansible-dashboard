<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AnsibleService;
use App\Services\AnsibleSSHService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected \Mockery\MockInterface $mockAnsible;
    protected \Mockery\MockInterface $mockSsh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', ['--force' => true]);

        $this->user = User::create([
            'name' => 'Operator User',
            'email' => 'operator@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true, // Make admin so they can access setting configs if needed
            'is_active' => true,
            'role' => 'admin',
        ]);

        // Setup mock services
        $this->mockAnsible = $this->mock(AnsibleService::class);
        $this->mockSsh = $this->mock(AnsibleSSHService::class);
    }

    public function test_inventory_index_renders_with_hosts()
    {
        $mockList = [
            '_meta' => [
                'hostvars' => [
                    'web-01' => ['ansible_host' => '192.168.10.11'],
                    'db-01'  => ['ansible_host' => '192.168.10.12'],
                ]
            ],
            'all' => [
                'children' => ['webservers', 'databases']
            ],
            'webservers' => [
                'hosts' => ['web-01'],
                'children' => []
            ],
            'databases' => [
                'hosts' => ['db-01'],
                'children' => []
            ]
        ];

        $this->mockAnsible->shouldReceive('getInventoryList')
            ->once()
            ->andReturn($mockList);

        $response = $this->actingAs($this->user)->get('/inventory');

        $response->assertStatus(200);
        $response->assertSee('web-01');
        $response->assertSee('db-01');
        $response->assertSee('192.168.10.11');
        $response->assertSee('192.168.10.12');
        $response->assertSee('webservers');
        $response->assertSee('databases');
    }

    public function test_inventory_cache_invalidation_on_save()
    {
        $allowedPath = config('ansible.inventory_default');
        
        // Put values in cache
        Cache::forever("inv_list_{$allowedPath}", ['dummy' => 'list']);
        Cache::forever("inv_graph_{$allowedPath}", ['dummy' => 'graph']);

        $this->mockSsh->shouldReceive('uploadFile')
            ->once()
            ->andReturn(true);

        $response = $this->actingAs($this->user)->post('/inventory/file', [
            'path' => $allowedPath,
            'content' => 'dummy content',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Assert caches are cleared
        $this->assertFalse(Cache::has("inv_list_{$allowedPath}"));
        $this->assertFalse(Cache::has("inv_graph_{$allowedPath}"));
    }

    public function test_path_allowlist_enforcement()
    {
        // 1. Prohibited path: directory traversal
        $response = $this->actingAs($this->user)->get('/inventory/file?path=' . urlencode('/etc/ansible/../../etc/passwd'));
        $response->assertStatus(403);
        $response->assertJsonPath('error', 'Path not allowed');

        // 2. Prohibited path: arbitrary system folder
        $response = $this->actingAs($this->user)->get('/inventory/file?path=' . urlencode('/var/log/syslog'));
        $response->assertStatus(403);
        $response->assertJsonPath('error', 'Path not allowed');

        // 3. Allowed path
        $allowedPath = config('ansible.inventory_default');
        $this->mockSsh->shouldReceive('readRemoteFile')
            ->once()
            ->andReturn('hosts content');

        $response = $this->actingAs($this->user)->get('/inventory/file?path=' . urlencode($allowedPath));
        $response->assertStatus(200);
        $response->assertJsonPath('path', $allowedPath);
    }

    public function test_validate_endpoint_valid_yaml()
    {
        $allowedPath = config('ansible.inventory_default') . '.yml'; // Allowed folder parent directory

        $this->mockSsh->shouldReceive('uploadFile')
            ->once()
            ->andReturn(true);

        // Mock ansible-inventory command
        $this->mockSsh->shouldReceive('exec')
            ->with(\Mockery::pattern('/ansible-inventory/'))
            ->once()
            ->andReturn([
                'exit_code' => 0,
                'output' => '{}',
                'duration_ms' => 5
            ]);

        // Mock clean up command exec
        $this->mockSsh->shouldReceive('exec')
            ->with(\Mockery::pattern('/rm -f/'))
            ->once()
            ->andReturn(['exit_code' => 0]);

        $response = $this->actingAs($this->user)->post('/inventory/file/validate', [
            'path' => $allowedPath,
            'content' => "foo: bar\nlist:\n  - item1\n  - item2",
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('valid', true);
    }

    public function test_validate_endpoint_invalid_yaml()
    {
        $allowedPath = config('ansible.inventory_default') . '.yml';

        $response = $this->actingAs($this->user)->post('/inventory/file/validate', [
            'path' => $allowedPath,
            'content' => "foo: : bar (invalid yaml structure)",
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('valid', false);
        $this->assertNotNull($response->json('error'));
    }
}
