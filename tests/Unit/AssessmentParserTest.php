<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\PlaybookJob;
use App\Models\JobOutputLine;
use App\Services\AssessmentParser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AssessmentParserTest extends TestCase
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

    public function test_parse_status_report()
    {
        $job = PlaybookJob::create([
            'user_id' => $this->user->id,
            'playbook' => '/ansible/check_status.yml',
            'inventory' => '/ansible/hosts',
            'command' => 'ansible-playbook check_status.yml',
            'status' => 'success',
        ]);

        $lines = [
            ['type' => 'output', 'line' => 'PLAY [Check status] ************************************************************'],
            ['type' => 'ok', 'line' => 'ok: [host1.example.com] => {'],
            ['type' => 'output', 'line' => '    "changed": false,'],
            ['type' => 'output', 'line' => '    "msg": "╔══════════════════════════════════════════════════╗\n║  HOST STATUS SUMMARY\n║  Node      : host1.example.com\n╠══════════════════════════════════════════════════╣\n║  SSH/Ping  : PONG\n║  Uptime    :  20:47:03 up 4 days,  2:54,  1 user,  load average: 0.21, 0.07, 0.02\n║  Disk (/)  : 44%\n║  RAM       : 148 MB free / 3794 MB total\n╚══════════════════════════════════════════════════╝"'],
            ['type' => 'output', 'line' => '}'],
            ['type' => 'recap', 'line' => 'PLAY RECAP *********************************************************************'],
            ['type' => 'recap', 'line' => 'host1.example.com          : ok=2    changed=0    unreachable=0    failed=0    skipped=0'],
        ];

        foreach ($lines as $l) {
            JobOutputLine::create([
                'job_id' => $job->id,
                'line' => $l['line'],
                'type' => $l['type'],
            ]);
        }

        $assessment = AssessmentParser::parse($job);

        $this->assertTrue($assessment['has_assessments']);
        $this->assertArrayHasKey('host1.example.com', $assessment['hosts']);

        $host = $assessment['hosts']['host1.example.com'];
        $this->assertEquals('success', $host['status']);
        $this->assertEquals('status_report', $host['type']);
        $this->assertNull($host['error']);

        $data = $host['data'];
        $this->assertEquals('host1.example.com', $data['node']);
        $this->assertEquals('PONG', $data['ping']);
        $this->assertStringContainsString('4 days', $data['uptime']);
        $this->assertEquals('44%', $data['disk']);
        $this->assertStringContainsString('3794 MB total', $data['ram']);
    }

    public function test_parse_device_report()
    {
        $job = PlaybookJob::create([
            'user_id' => $this->user->id,
            'playbook' => '/ansible/identify_devices.yml',
            'inventory' => '/ansible/hosts',
            'command' => 'ansible-playbook identify_devices.yml',
            'status' => 'success',
        ]);

        $reportText = "╔══════════════════════════════════════════════════════════════════════╗\n"
            . "║  DEVICE IDENTIFICATION REPORT\n"
            . "║  Target    : host2.example.com\n"
            . "╠══════════════════════════════════════════════════════════════════════╣\n"
            . "║  NETWORK IDENTITY\n"
            . "╠══════════════════════════════════════════════════════════════════════╣\n"
            . "║  Hostname   : host2\n"
            . "║  FQDN       : host2.local\n"
            . "║  Domain     : local\n"
            . "║  Primary IP : 192.168.1.50\n"
            . "║  Primary IF : eth1\n"
            . "║  Primary MAC: aa:bb:cc:dd:ee:ff\n"
            . "║  Gateway    : 192.168.1.1\n"
            . "║\n"
            . "║  All addresses:\n"
            . "║  2: eth1    inet 192.168.1.50/24 brd 192.168.1.255 scope global eth1\n"
            . "3: docker0    inet 172.18.0.1/16 brd 172.18.255.255 scope global docker0\n"
            . "╠══════════════════════════════════════════════════════════════════════╣\n"
            . "║  HARDWARE\n"
            . "╠══════════════════════════════════════════════════════════════════════╣\n"
            . "║  CPU Model  : Intel Xeon\n"
            . "║  CPU Cores  : 8 vCPU(s)\n"
            . "║  Architecture: x86_64\n"
            . "║  Virtualised: kvm\n"
            . "║  Total RAM  : 16.00 GB\n"
            . "╠══════════════════════════════════════════════════════════════════════╣\n"
            . "║  OPERATING SYSTEM\n"
            . "╠══════════════════════════════════════════════════════════════════════╣\n"
            . "║  OS         : Linux\n"
            . "║  Distro     : Ubuntu 22.04\n"
            . "╠══════════════════════════════════════════════════════════════════════╣\n"
            . "║  RUNTIME\n"
            . "╠══════════════════════════════════════════════════════════════════════╣\n"
            . "║  Uptime     : up 12 days\n"
            . "╠══════════════════════════════════════════════════════════════════════╣\n"
            . "║  OPEN LISTENING PORTS\n"
            . "╠══════════════════════════════════════════════════════════════════════╣\n"
            . "║  tcp   LISTEN 0      128                       0.0.0.0:22         0.0.0.0:*\n"
            . "║  tcp   LISTEN 0      80                        0.0.0.0:80         0.0.0.0:*\n"
            . "╠══════════════════════════════════════════════════════════════════════╣\n"
            . "║  RUNNING SERVICES (systemd)\n"
            . "╠══════════════════════════════════════════════════════════════════════╣\n"
            . "║  nginx.service\n"
            . "ssh.service\n"
            . "╚══════════════════════════════════════════════════════════════════════╝";

        $jsonMsg = json_encode($reportText);

        $lines = [
            ['type' => 'ok', 'line' => 'ok: [host2.example.com] => {'],
            ['type' => 'output', 'line' => '    "changed": false,'],
            ['type' => 'output', 'line' => '    "msg": ' . $jsonMsg],
            ['type' => 'output', 'line' => '}'],
            ['type' => 'recap', 'line' => 'host2.example.com          : ok=5    changed=0    unreachable=0    failed=0    skipped=0'],
        ];

        foreach ($lines as $l) {
            JobOutputLine::create([
                'job_id' => $job->id,
                'line' => $l['line'],
                'type' => $l['type'],
            ]);
        }

        $assessment = AssessmentParser::parse($job);

        $this->assertTrue($assessment['has_assessments']);
        $this->assertArrayHasKey('host2.example.com', $assessment['hosts']);

        $host = $assessment['hosts']['host2.example.com'];
        $this->assertEquals('success', $host['status']);
        $this->assertEquals('device_report', $host['type']);

        $net = $host['data']['network'];
        $this->assertEquals('host2', $net['hostname']);
        $this->assertEquals('host2.local', $net['fqdn']);
        $this->assertEquals('192.168.1.50', $net['primary ip']);
        $this->assertEquals('eth1', $net['primary if']);
        $this->assertEquals('aa:bb:cc:dd:ee:ff', $net['primary mac']);
        $this->assertEquals('192.168.1.1', $net['gateway']);

        // Network interfaces should be extracted correctly without index numbers
        $this->assertCount(2, $net['all_addresses']);
        $this->assertEquals('eth1    inet 192.168.1.50/24 brd 192.168.1.255 scope global eth1', $net['all_addresses'][0]);
        $this->assertEquals('docker0    inet 172.18.0.1/16 brd 172.18.255.255 scope global docker0', $net['all_addresses'][1]);

        $hw = $host['data']['hardware'];
        $this->assertEquals('Intel Xeon', $hw['cpu model']);
        $this->assertEquals('8 vCPU(s)', $hw['cpu cores']);
        $this->assertEquals('16.00 GB', $hw['total ram']);

        $os = $host['data']['os'];
        $this->assertEquals('Linux', $os['os']);
        $this->assertEquals('Ubuntu 22.04', $os['distro']);

        $run = $host['data']['runtime'];
        $this->assertEquals('up 12 days', $run['uptime']);

        // Ports should be parsed correctly
        $this->assertCount(2, $host['data']['ports']);
        $this->assertEquals('TCP 0.0.0.0:22', $host['data']['ports'][0]);
        $this->assertEquals('TCP 0.0.0.0:80', $host['data']['ports'][1]);

        // Services should be parsed correctly
        $this->assertCount(2, $host['data']['services']);
        $this->assertEquals('nginx.service', $host['data']['services'][0]);
        $this->assertEquals('ssh.service', $host['data']['services'][1]);
    }

    public function test_parse_fatal_failed_host()
    {
        $job = PlaybookJob::create([
            'user_id' => $this->user->id,
            'playbook' => '/ansible/site.yml',
            'inventory' => '/ansible/hosts',
            'command' => 'ansible-playbook site.yml',
            'status' => 'failed',
        ]);

        $lines = [
            ['type' => 'error', 'line' => 'fatal: [error-host.example.com]: FAILED! => {'],
            ['type' => 'output', 'line' => '    "changed": false,'],
            ['type' => 'output', 'line' => '    "msg": "Connection timed out after 30 seconds"'],
            ['type' => 'output', 'line' => '}'],
            ['type' => 'recap', 'line' => 'error-host.example.com     : ok=0    changed=0    unreachable=0    failed=1    skipped=0'],
        ];

        foreach ($lines as $l) {
            JobOutputLine::create([
                'job_id' => $job->id,
                'line' => $l['line'],
                'type' => $l['type'],
            ]);
        }

        $assessment = AssessmentParser::parse($job);

        $this->assertTrue($assessment['has_assessments']);
        $this->assertArrayHasKey('error-host.example.com', $assessment['hosts']);

        $host = $assessment['hosts']['error-host.example.com'];
        $this->assertEquals('failed', $host['status']);
        $this->assertEquals('error', $host['type']);
        $this->assertEquals('Connection timed out after 30 seconds', $host['error']);
    }

    public function test_parse_unreachable_host_from_recap()
    {
        $job = PlaybookJob::create([
            'user_id' => $this->user->id,
            'playbook' => '/ansible/site.yml',
            'inventory' => '/ansible/hosts',
            'command' => 'ansible-playbook site.yml',
            'status' => 'failed',
        ]);

        $lines = [
            ['type' => 'output', 'line' => 'PLAY RECAP *********************************************************************'],
            ['type' => 'recap', 'line' => 'unreachable-host           : ok=0    changed=0    unreachable=1    failed=0    skipped=0'],
        ];

        foreach ($lines as $l) {
            JobOutputLine::create([
                'job_id' => $job->id,
                'line' => $l['line'],
                'type' => $l['type'],
            ]);
        }

        $assessment = AssessmentParser::parse($job);

        $this->assertTrue($assessment['has_assessments']);
        $this->assertArrayHasKey('unreachable-host', $assessment['hosts']);

        $host = $assessment['hosts']['unreachable-host'];
        $this->assertEquals('unreachable', $host['status']);
        $this->assertEquals('error', $host['type']);
        $this->assertEquals('Host was unreachable during execution.', $host['error']);
    }

    public function test_parse_non_string_msg_success()
    {
        $job = PlaybookJob::create([
            'user_id' => $this->user->id,
            'playbook' => '/ansible/site.yml',
            'inventory' => '/ansible/hosts',
            'command' => 'ansible-playbook site.yml',
            'status' => 'success',
        ]);

        $lines = [
            ['type' => 'ok', 'line' => 'ok: [host-arr.example.com] => {'],
            ['type' => 'output', 'line' => '    "changed": false,'],
            ['type' => 'output', 'line' => '    "msg": ['],
            ['type' => 'output', 'line' => '        "line one",'],
            ['type' => 'output', 'line' => '        "line two"'],
            ['type' => 'output', 'line' => '    ]'],
            ['type' => 'output', 'line' => '}'],
        ];

        foreach ($lines as $l) {
            JobOutputLine::create([
                'job_id' => $job->id,
                'line' => $l['line'],
                'type' => $l['type'],
            ]);
        }

        $assessment = AssessmentParser::parse($job);

        // It should parse correctly and not crash
        $this->assertFalse($assessment['has_assessments']);
    }

    public function test_parse_non_string_msg_error()
    {
        $job = PlaybookJob::create([
            'user_id' => $this->user->id,
            'playbook' => '/ansible/site.yml',
            'inventory' => '/ansible/hosts',
            'command' => 'ansible-playbook site.yml',
            'status' => 'failed',
        ]);

        $lines = [
            ['type' => 'error', 'line' => 'fatal: [host-arr.example.com]: FAILED! => {'],
            ['type' => 'output', 'line' => '    "changed": false,'],
            ['type' => 'output', 'line' => '    "msg": ['],
            ['type' => 'output', 'line' => '        "error line one",'],
            ['type' => 'output', 'line' => '        "error line two"'],
            ['type' => 'output', 'line' => '    ]'],
            ['type' => 'output', 'line' => '}'],
        ];

        foreach ($lines as $l) {
            JobOutputLine::create([
                'job_id' => $job->id,
                'line' => $l['line'],
                'type' => $l['type'],
            ]);
        }

        $assessment = AssessmentParser::parse($job);

        $this->assertTrue($assessment['has_assessments']);
        $this->assertArrayHasKey('host-arr.example.com', $assessment['hosts']);
        $host = $assessment['hosts']['host-arr.example.com'];
        $this->assertEquals('failed', $host['status']);
        $this->assertStringContainsString('error line one', $host['error']);
        $this->assertStringContainsString('error line two', $host['error']);
    }
}
