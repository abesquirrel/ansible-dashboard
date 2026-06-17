<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AnsibleSSHService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class LearningTest extends TestCase
{
    use DatabaseMigrations;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the SQLite in-memory schema is built before we create any records.
        $this->artisan('migrate:fresh', ['--force' => true]);

        $this->user = User::create([
            'name' => 'Operator User',
            'email' => 'operator@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_active' => true,
            'role' => 'operator',
        ]);
    }

    public function test_learning_index_loads_successfully()
    {
        // Mock connection test to return offline state to ensure no external network dependencies
        $this->mock(AnsibleSSHService::class, function ($mock) {
            $mock->shouldReceive('testConnection')->andReturn([
                'connected' => false,
                'error' => 'Connection refused',
            ]);
        });

        $response = $this->actingAs($this->user)->get('/learning');

        $response->assertStatus(200);
        $response->assertSee('Learn Ansible');
        $response->assertSee('Active Lab Status:');
        $response->assertSee('Offline / Unconfigured');
    }

    public function test_learning_index_loads_connected_successfully()
    {
        // Mock connection test to return online state
        $this->mock(AnsibleSSHService::class, function ($mock) {
            $mock->shouldReceive('testConnection')->andReturn([
                'connected' => true,
                'host' => '127.0.0.1',
                'user' => 'ansible',
                'auth_method' => 'key',
                'latency_ms' => 12,
                'ansible_version' => 'ansible [core 2.15.0]',
            ]);
        });

        $response = $this->actingAs($this->user)->get('/learning');

        $response->assertStatus(200);
        $response->assertSee('Learn Ansible');
        $response->assertSee('Active Lab Status:');
        $response->assertSee('Online & Connected', false);
    }

    public function test_learning_topics_load_successfully()
    {
        // Mock connection to be offline
        $this->mock(AnsibleSSHService::class, function ($mock) {
            $mock->shouldReceive('testConnection')->andReturn([
                'connected' => false,
            ]);
        });

        $topics = ['basics', 'inventory-adhoc', 'playbooks', 'roles', 'vars-templates'];

        foreach ($topics as $slug) {
            $response = $this->actingAs($this->user)->get("/learning/{$slug}");
            $response->assertStatus(200, "Expected 200 for /learning/{$slug} but got {$response->status()}");
        }
    }

    public function test_learning_invalid_topic_returns_404()
    {
        $response = $this->actingAs($this->user)->get('/learning/invalid-topic-slug');
        $response->assertStatus(404);
    }
}
