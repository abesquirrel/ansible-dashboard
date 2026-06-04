<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\PlaybookJob;
use App\Models\JobOutputLine;
use App\Services\AnsibleService;
use App\Services\AnsibleSSHService;
use App\Jobs\RunPlaybookJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnsibleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_playbook_command()
    {
        $sshMock = $this->createMock(AnsibleSSHService::class);
        $service = new AnsibleService($sshMock);

        config(['ansible.vault_password_file' => '/etc/ansible/vault_pass']);

        $cmd = $service->buildPlaybookCommand(
            playbook: '/etc/ansible/playbooks/site.yml',
            inventory: '/etc/ansible/hosts',
            extraVars: ['env' => 'prod', 'version' => '1.2.3'],
            tags: ['nginx', 'php'],
            limit: 'webservers',
            checkMode: true,
            verbose: true
        );

        $this->assertStringContainsString('ansible-playbook -i /etc/ansible/hosts', $cmd);
        $this->assertStringContainsString('--check', $cmd);
        $this->assertStringContainsString('-v', $cmd);
        $this->assertStringContainsString("--limit 'webservers'", $cmd);
        $this->assertStringContainsString("--tags 'nginx,php'", $cmd);
        $this->assertStringContainsString("--extra-vars 'env=prod'", $cmd);
        $this->assertStringContainsString("--extra-vars 'version=1.2.3'", $cmd);
        $this->assertStringContainsString('--vault-password-file /etc/ansible/vault_pass', $cmd);
        $this->assertStringContainsString("'/etc/ansible/playbooks/site.yml'", $cmd);
    }

    public function test_recap_line_classification_and_summary_parsing()
    {
        $user = User::create([
            'name' => 'Operator User',
            'email' => 'operator@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_active' => true,
            'role' => 'operator',
        ]);

        $job = PlaybookJob::create([
            'user_id' => $user->id,
            'playbook' => '/etc/ansible/playbooks/site.yml',
            'inventory' => '/etc/ansible/hosts',
            'command' => 'ansible-playbook -i /etc/ansible/hosts /etc/ansible/playbooks/site.yml',
            'status' => 'queued',
        ]);

        // Access the protected storeLine method using Reflection, or instantiate Job class
        $jobInstance = new RunPlaybookJob($job->id);
        $reflector = new \ReflectionClass(RunPlaybookJob::class);
        
        $storeLineMethod = $reflector->getMethod('storeLine');
        $storeLineMethod->setAccessible(true);

        $parseSummaryMethod = $reflector->getMethod('parseSummary');
        $parseSummaryMethod->setAccessible(true);

        // Simulate outputs
        $storeLineMethod->invokeArgs($jobInstance, [$job->id, 'PLAY RECAP *********************************************************************']);
        // This is the target line that contains counts, which contains the word 'changed'
        $storeLineMethod->invokeArgs($jobInstance, [$job->id, 'localhost                  : ok=10   changed=3    unreachable=1    failed=2    skipped=0']);

        // Verify that the recap line with counts was classified as type 'recap' instead of 'changed'
        $lines = JobOutputLine::where('job_id', $job->id)->get();
        $this->assertCount(2, $lines);
        $this->assertEquals('recap', $lines[0]->type);
        $this->assertEquals('recap', $lines[1]->type);

        // Run summary parsing
        $summary = $parseSummaryMethod->invokeArgs($jobInstance, [$job]);

        $this->assertEquals(10, $summary['hosts_ok']);
        $this->assertEquals(3, $summary['hosts_changed']);
        $this->assertEquals(1, $summary['hosts_unreachable']);
        $this->assertEquals(2, $summary['hosts_failed']);
        $this->assertEquals(0, $summary['hosts_skipped']);
    }
}
