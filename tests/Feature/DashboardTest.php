<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AnsibleSSHService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the SQLite in-memory schema is built before we create any records.
        $this->artisan('migrate:fresh', ['--force' => true]);

        // Mock AnsibleSSHService to avoid real network/SSH connections during testing
        $this->mock(AnsibleSSHService::class, function ($mock) {
            $mock->shouldReceive('testConnection')->andReturn([
                'connected' => true,
                'ansible_version' => 'ansible [core 2.16.3]',
                'latency_ms' => 5,
                'host' => '127.0.0.1',
                'user' => 'ansible',
                'auth_method' => 'key',
            ]);
        });
    }

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_active' => true,
            'role' => 'operator',
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('ansible [core 2.16.3]');
    }

    public function test_operator_cannot_access_settings()
    {
        $user = User::create([
            'name' => 'Operator User',
            'email' => 'operator@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_active' => true,
            'role' => 'operator',
        ]);

        $response = $this->actingAs($user)->get('/settings');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_settings()
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
            'is_active' => true,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/settings');
        $response->assertStatus(200);
        $response->assertSee('Settings');
    }
}
