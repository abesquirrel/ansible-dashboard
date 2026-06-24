<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use DatabaseMigrations;

    protected User $admin;
    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', ['--force' => true]);

        // Create an Admin user
        $this->admin = User::create([
            'name'      => 'Admin User',
            'email'     => 'admin@example.com',
            'password'  => bcrypt('password'),
            'is_admin'  => true,
            'is_active' => true,
            'role'      => 'admin',
        ]);

        // Create an Operator user
        $this->operator = User::create([
            'name'      => 'Operator User',
            'email'     => 'operator@example.com',
            'password'  => bcrypt('password'),
            'is_admin'  => false,
            'is_active' => true,
            'role'      => 'operator',
        ]);
    }

    public function test_non_admin_cannot_access_user_management()
    {
        // Guests redirected to login
        $response = $this->get('/admin/users');
        $response->assertRedirect('/login');

        // Operators get 403 Forbidden
        $response = $this->actingAs($this->operator)->get('/admin/users');
        $response->assertStatus(403);

        $response = $this->actingAs($this->operator)->post('/admin/users', [
            'name'     => 'New User',
            'email'    => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role'     => 'viewer',
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_view_users_list()
    {
        $response = $this->actingAs($this->admin)->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee($this->admin->email);
        $response->assertSee($this->operator->email);
    }

    public function test_admin_can_create_user()
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name'      => 'New Operator',
            'email'     => 'new_op@example.com',
            'password'  => 'secret123',
            'password_confirmation' => 'secret123',
            'role'      => 'operator',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name'      => 'New Operator',
            'email'     => 'new_op@example.com',
            'role'      => 'operator',
            'is_active' => true,
        ]);

        $newUser = User::where('email', 'new_op@example.com')->first();
        $this->assertTrue(Hash::check('secret123', $newUser->password));
    }

    public function test_admin_can_edit_user()
    {
        $response = $this->actingAs($this->admin)->put("/admin/users/{$this->operator->id}", [
            'name'      => 'Updated Name',
            'email'     => 'operator_updated@example.com',
            'role'      => 'viewer',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id'    => $this->operator->id,
            'name'  => 'Updated Name',
            'email' => 'operator_updated@example.com',
            'role'  => 'viewer',
        ]);
    }

    public function test_admin_can_change_user_password_optionally()
    {
        $response = $this->actingAs($this->admin)->put("/admin/users/{$this->operator->id}", [
            'name'      => $this->operator->name,
            'email'     => $this->operator->email,
            'role'      => $this->operator->role,
            'password'  => 'new_password123',
            'password_confirmation' => 'new_password123',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/users');
        
        $this->operator->refresh();
        $this->assertTrue(Hash::check('new_password123', $this->operator->password));
    }

    public function test_admin_cannot_deactivate_themselves()
    {
        // 1. Through normal update form submit
        $response = $this->actingAs($this->admin)->put("/admin/users/{$this->admin->id}", [
            'name'  => 'Admin Updated',
            'email' => 'admin_updated@example.com',
            'role'  => 'admin',
            // is_active checkbox absent (i.e. deactivation attempt)
        ]);

        $response->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->is_active);

        // 2. Through toggle-status endpoint
        $response = $this->actingAs($this->admin)->post("/admin/users/{$this->admin->id}/toggle-status");
        $response->assertStatus(400);
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_admin_cannot_demote_themselves()
    {
        $response = $this->actingAs($this->admin)->put("/admin/users/{$this->admin->id}", [
            'name'      => 'Admin Updated',
            'email'     => 'admin_updated@example.com',
            'role'      => 'operator', // Attempt to change role away from admin
            'is_active' => '1',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('admin', $this->admin->fresh()->role);
    }

    public function test_admin_cannot_delete_themselves()
    {
        $response = $this->actingAs($this->admin)->delete("/admin/users/{$this->admin->id}");
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_admin_can_toggle_other_user_status()
    {
        $this->assertTrue($this->operator->is_active);

        $response = $this->actingAs($this->admin)->post("/admin/users/{$this->operator->id}/toggle-status");
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('is_active', false);

        $this->assertFalse($this->operator->fresh()->is_active);
    }

    public function test_admin_can_delete_other_users()
    {
        $response = $this->actingAs($this->admin)->delete("/admin/users/{$this->operator->id}");
        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $this->operator->id]);
    }

    public function test_admin_creation_synchronizes_is_admin_flag()
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name'      => 'New Admin',
            'email'     => 'new_admin@example.com',
            'password'  => 'secret123',
            'password_confirmation' => 'secret123',
            'role'      => 'admin',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/users');
        
        $newUser = User::where('email', 'new_admin@example.com')->first();
        $this->assertTrue($newUser->is_admin);
    }

    public function test_admin_update_synchronizes_is_admin_flag()
    {
        // 1. Demote admin to operator
        $tempAdmin = User::create([
            'name'      => 'Temp Admin',
            'email'     => 'temp_admin@example.com',
            'password'  => bcrypt('password'),
            'is_admin'  => true,
            'is_active' => true,
            'role'      => 'admin',
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/users/{$tempAdmin->id}", [
            'name'      => 'Temp Admin',
            'email'     => 'temp_admin@example.com',
            'role'      => 'operator',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertFalse($tempAdmin->fresh()->is_admin);

        // 2. Promote operator to admin
        $response = $this->actingAs($this->admin)->put("/admin/users/{$this->operator->id}", [
            'name'      => $this->operator->name,
            'email'     => $this->operator->email,
            'role'      => 'admin',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertTrue($this->operator->fresh()->is_admin);
    }
}
