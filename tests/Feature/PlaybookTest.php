<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PlaybookJob;
use App\Models\JobOutputLine;
use App\Services\AnsibleSSHService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PlaybookTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;

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

        // Mock AnsibleSSHService to prevent actual network calls
        $this->mock(AnsibleSSHService::class, function ($mock) {
            $mock->shouldReceive('exec')->andReturn([
                'output' => "/etc/ansible/playbooks/site.yml\n/etc/ansible/playbooks/web.yml",
                'exit_code' => 0,
                'duration_ms' => 10,
            ]);
            $mock->shouldReceive('testConnection')->andReturn([
                'connected' => true,
            ]);
            $mock->shouldReceive('execStreaming')->andReturn(0);
        });
    }

    public function test_can_list_playbooks()
    {
        $response = $this->actingAs($this->user)->get('/playbooks');

        $response->assertStatus(200);
        $response->assertSee('site.yml');
        $response->assertSee('web.yml');
    }

    public function test_can_run_playbook()
    {
        $response = $this->actingAs($this->user)->post('/playbooks/run', [
            'playbook' => '/etc/ansible/playbooks/site.yml',
            'inventory' => '/etc/ansible/hosts',
            'extra_vars' => ['foo' => 'bar'],
            'check_mode' => false,
            'verbose' => true,
        ]);

        $job = PlaybookJob::first();
        $this->assertNotNull($job);
        $this->assertEquals('/etc/ansible/playbooks/site.yml', $job->playbook);
        $this->assertEquals('success', $job->status);

        $response->assertRedirect("/jobs/{$job->id}");
    }

    public function test_can_get_job_output_lines()
    {
        $job = PlaybookJob::create([
            'user_id' => $this->user->id,
            'playbook' => '/etc/ansible/playbooks/site.yml',
            'inventory' => '/etc/ansible/hosts',
            'command' => 'ansible-playbook -i /etc/ansible/hosts /etc/ansible/playbooks/site.yml',
            'status' => 'running',
        ]);

        JobOutputLine::create([
            'job_id' => $job->id,
            'line' => 'GATHERING FACTS ****************************',
            'type' => 'output',
        ]);

        JobOutputLine::create([
            'job_id' => $job->id,
            'line' => 'ok: [localhost]',
            'type' => 'ok',
        ]);

        $response = $this->actingAs($this->user)->get("/jobs/{$job->id}/output");

        $response->assertStatus(200);
        $response->assertJsonPath('job_status', 'running');
        $response->assertJsonCount(2, 'lines');
        $response->assertJsonPath('lines.0.line', 'GATHERING FACTS ****************************');
        $response->assertJsonPath('lines.1.line', 'ok: [localhost]');
    }

    public function test_can_abort_running_job()
    {
        $job = PlaybookJob::create([
            'user_id' => $this->user->id,
            'playbook' => '/etc/ansible/playbooks/site.yml',
            'inventory' => '/etc/ansible/hosts',
            'command' => 'ansible-playbook -i /etc/ansible/hosts /etc/ansible/playbooks/site.yml',
            'status' => 'running',
        ]);

        $response = $this->actingAs($this->user)->post("/jobs/{$job->id}/abort");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'aborted');
        $this->assertEquals('aborted', $job->fresh()->status);
    }
}
