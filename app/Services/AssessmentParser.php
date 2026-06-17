<?php

namespace App\Services;

use App\Models\PlaybookJob;

class AssessmentParser
{
    /**
     * Parse a job's output lines into a structured assessment array.
     *
     * Strategy: reconstruct multi-line Ansible JSON blocks by scanning
     * through stored lines sequentially, then extract host data from
     * any JSON blocks that contain device/status reports.
     */
    public static function parse(PlaybookJob $job): array
    {
        $allLines = $job->outputLines->toArray();
        $hosts    = [];

        // ── Step 1: Reconstruct full Ansible output text ──────────────────
        // Lines are stored one-per-row in the DB, but Ansible JSON payloads
        // span multiple DB rows (ok: header line + output lines for the body).
        // IMPORTANT: Do NOT pre-decode escape sequences here — the JSON body
        // lines contain valid JSON strings (with \n, \" etc.) which must be
        // kept intact so json_decode() can parse them correctly.
        // json_decode will decode the msg string, giving us real newlines.
        $rawLines = array_column($allLines, 'line');
        $fullText = implode("\n", $rawLines);

        // ── Step 3: Extract per-host JSON result blocks ───────────────────
        // Pattern: "ok/fatal/failed: [hostname] (optional) => { ... }"
        // We use a non-backtracking approach: find each opening brace after
        // a host marker and scan for the matching closing brace.
        $pattern = '/^(ok|fatal|failed|changed):\s+\[([^\]]+)\](?:[^\[{=\n]*)=>\s*(\{)/m';

        if (preg_match_all($pattern, $fullText, $openers, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            foreach ($openers as $opener) {
                $prefix    = strtolower($opener[1][0]);
                $host      = trim($opener[2][0]);
                $bracePos  = $opener[3][1]; // position of the opening '{'

                // Walk forward to find the matching closing brace
                $json = self::extractBalancedJson($fullText, $bracePos);
                if ($json === null) continue;

                $decoded = json_decode($json, true);
                if (!$decoded) continue;

                // ── Fatal / Unreachable ───────────────────────────────────
                $isError   = ($prefix === 'fatal' || $prefix === 'failed');
                $errorType = '';
                if (preg_match('/\(UNREACHABLE\)/i', $opener[0][0])) {
                    $errorType = 'UNREACHABLE';
                } elseif (preg_match('/\(FAILED\)/i', $opener[0][0])) {
                    $errorType = 'FAILED';
                }

                if ($isError || $errorType) {
                    // Don't overwrite a successful full report with a minor error
                    if (!isset($hosts[$host]) || $hosts[$host]['status'] !== 'success') {
                        $errorMsg = $decoded['msg']
                            ?? $decoded['stderr']
                            ?? $decoded['module_stdout']
                            ?? json_encode($decoded);

                        $hosts[$host] = [
                            'name'   => $host,
                            'status' => $errorType === 'UNREACHABLE' ? 'unreachable' : 'failed',
                            'error'  => $errorMsg,
                            'type'   => 'error',
                            'data'   => [],
                        ];
                    }
                    continue;
                }

                // ── Successful result — look for report in msg ────────────
                if (!isset($decoded['msg'])) continue;

                $msg = $decoded['msg'];

                if (str_contains($msg, 'HOST STATUS SUMMARY')) {
                    $hosts[$host] = [
                        'name'       => $host,
                        'status'     => 'success',
                        'error'      => null,
                        'type'       => 'status_report',
                        'data'       => self::parseStatusReport($msg),
                        'raw_report' => $msg,
                    ];
                } elseif (str_contains($msg, 'DEVICE IDENTIFICATION REPORT') || str_contains($msg, 'DEVICE REPORT')) {
                    // Only store first occurrence per host (playbook can print twice)
                    if (!isset($hosts[$host]) || $hosts[$host]['type'] !== 'device_report') {
                        $hosts[$host] = [
                            'name'       => $host,
                            'status'     => 'success',
                            'error'      => null,
                            'type'       => 'device_report',
                            'data'       => self::parseDeviceReport($msg),
                            'raw_report' => $msg,
                        ];
                    }
                }
            }
        }

        // ── Step 4: Fill in unreachable/failed hosts from PLAY RECAP ──────
        foreach ($allLines as $row) {
            if ($row['type'] !== 'recap') continue;
            if (!preg_match('/^\s*(\S+)\s*:\s*ok=\d+\s+changed=\d+\s+unreachable=(\d+)\s+failed=(\d+)/i', $row['line'], $m)) continue;

            $host        = trim($m[1]);
            $unreachable = (int) $m[2];
            $failed      = (int) $m[3];

            if (isset($hosts[$host])) continue; // Already have a full report

            if ($unreachable > 0) {
                $hosts[$host] = [
                    'name'   => $host,
                    'status' => 'unreachable',
                    'error'  => 'Host was unreachable during execution.',
                    'type'   => 'error',
                    'data'   => [],
                ];
            } elseif ($failed > 0) {
                $hosts[$host] = [
                    'name'   => $host,
                    'status' => 'failed',
                    'error'  => 'Tasks failed on this host.',
                    'type'   => 'error',
                    'data'   => [],
                ];
            }
        }

        return [
            'hosts'          => $hosts,
            'has_assessments' => count($hosts) > 0,
        ];
    }

    /**
     * Walk forward from `startPos` (the '{' char) in `text` and return the
     * balanced JSON object string, or null on failure.
     */
    private static function extractBalancedJson(string $text, int $startPos): ?string
    {
        $len   = strlen($text);
        $depth = 0;
        $inStr = false;
        $esc   = false;

        for ($i = $startPos; $i < $len; $i++) {
            $c = $text[$i];

            if ($esc) {
                $esc = false;
                continue;
            }
            if ($c === '\\' && $inStr) {
                $esc = true;
                continue;
            }
            if ($c === '"') {
                $inStr = !$inStr;
                continue;
            }
            if ($inStr) continue;

            if ($c === '{') $depth++;
            elseif ($c === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($text, $startPos, $i - $startPos + 1);
                }
            }
        }

        return null; // Unbalanced
    }

    // ── Sub-parsers ────────────────────────────────────────────────────────

    private static function parseStatusReport(string $msg): array
    {
        $data  = [];
        $lines = explode("\n", $msg);

        foreach ($lines as $line) {
            $line = trim(str_replace(['║', '╠', '╚', '═', '╔', '╝'], '', $line));
            if (empty($line)) continue;

            if (preg_match('/Node\s*:\s*(.*)/i', $line, $m))         $data['node']   = trim($m[1]);
            elseif (preg_match('/SSH\/Ping\s*:\s*(.*)/i', $line, $m)) $data['ping']   = trim($m[1]);
            elseif (preg_match('/Uptime\s*:\s*(.*)/i', $line, $m))    $data['uptime'] = trim($m[1]);
            elseif (preg_match('/Disk\s*\(\/\)\s*:\s*(.*)/i', $line, $m)) $data['disk'] = trim($m[1]);
            elseif (preg_match('/RAM\s*:\s*(.*)/i', $line, $m))       $data['ram']    = trim($m[1]);
        }

        return $data;
    }

    private static function parseDeviceReport(string $msg): array
    {
        $data = [
            'network'    => [],
            'hardware'   => [],
            'os'         => [],
            'runtime'    => [],
            'ports'      => [],
            'services'   => [],
            'connection' => [],
        ];

        $currentSection  = '';
        $portsBuffer     = [];
        $servicesBuffer  = [];
        $addressesBuffer = [];

        $lines = explode("\n", $msg);

        foreach ($lines as $line) {
            // ── Detect section headers ──────────────────────────────────────
            if (str_contains($line, 'NETWORK IDENTITY')) {
                $currentSection = 'network';    continue;
            } elseif (str_contains($line, 'HARDWARE')) {
                $currentSection = 'hardware';   continue;
            } elseif (str_contains($line, 'OPERATING SYSTEM')) {
                $currentSection = 'os';         continue;
            } elseif (str_contains($line, 'RUNTIME')) {
                $currentSection = 'runtime';    continue;
            } elseif (str_contains($line, 'OPEN LISTENING PORTS')) {
                $currentSection = 'ports';      continue;
            } elseif (str_contains($line, 'RUNNING SERVICES')) {
                $currentSection = 'services';   continue;
            } elseif (str_contains($line, 'CONNECTION')) {
                $currentSection = 'connection'; continue;
            }

            // Strip box-drawing chars and leading/trailing whitespace
            $stripped = trim(str_replace(['║', '╠', '╚', '═', '╔', '╝', '╣', '├', '└', '─'], '', $line));
            if (empty($stripped)) continue;

            // ── Ports: ss output (tcp/udp LISTEN/UNCONN …) — NOT key:value ──
            if ($currentSection === 'ports') {
                if (preg_match('/^(tcp|udp)\s+\S+\s+\d+\s+\d+\s+(\S+)/i', $stripped, $pm)) {
                    $proto = strtoupper($pm[1]);
                    $addr  = $pm[2];
                    $portsBuffer[] = "{$proto} {$addr}";
                }
                continue;
            }

            // ── Services: one service name per line ─────────────────────────
            if ($currentSection === 'services') {
                if (preg_match('/\w[\w\-@]*\.(service|socket|target|timer)/i', $stripped, $sm)) {
                    $servicesBuffer[] = trim($sm[0]);
                }
                continue;
            }

            // ── Key:value pairs for all other sections ──────────────────────
            if (preg_match('/^([^:]+?)\s*:\s*(.*)$/', $stripped, $m)) {
                $key   = strtolower(trim($m[1]));
                $value = trim($m[2]);

                switch ($currentSection) {
                    case 'network':
                        // Lines like "2: eth0    inet 172.x ..." are IP address rows, not kv pairs
                        if (preg_match('/^\d+\s*$/', $key)) {
                            $addressesBuffer[] = $value;
                        } else {
                            $data['network'][$key] = $value;
                        }
                        break;
                    case 'hardware':   $data['hardware'][$key]   = $value; break;
                    case 'os':         $data['os'][$key]         = $value; break;
                    case 'runtime':    $data['runtime'][$key]    = $value; break;
                    case 'connection': $data['connection'][$key] = $value; break;
                }
            } else {
                // Non-key:value lines (continuation / raw output)
                if ($currentSection === 'network') {
                    $addressesBuffer[] = $stripped;
                }
            }
        }

        $data['ports']    = array_values(array_unique(array_filter(array_map('trim', $portsBuffer))));
        $data['services'] = array_values(array_unique(array_filter(array_map('trim', $servicesBuffer))));

        if (!empty($addressesBuffer)) {
            $data['network']['all_addresses'] = array_values(array_filter(array_map('trim', $addressesBuffer)));
        }

        return $data;
    }
}
