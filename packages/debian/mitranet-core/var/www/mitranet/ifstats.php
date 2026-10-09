<?php
/*
 * ifstats.php - Interface traffic and statistics endpoint for MitraNet
 * Compatible with traffic-graphs.js and D3/NVD3 graphing widgets.
 * Output format: JSON map of interface name => [ [time, rx_bytes], [time, tx_bytes], name ]
 * Or action=all / default json providing detailed rate & totals.
 */

require_once(__DIR__ . '/includes/api.inc');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$now = microtime(true);

// Read /proc/net/dev directly for ultra-low latency & zero overhead
$proc_stats = [];
if (file_exists('/proc/net/dev') && is_readable('/proc/net/dev')) {
    $lines = file('/proc/net/dev');
    foreach ($lines as $line) {
        if (strpos($line, ':') === false) continue;
        list($dev, $data) = explode(':', $line, 2);
        $dev = trim($dev);
        $fields = preg_split('/\s+/', trim($data));
        if (count($fields) >= 16) {
            $proc_stats[$dev] = [
                'rx_bytes'   => (float)$fields[0],
                'rx_packets' => (float)$fields[1],
                'rx_errs'    => (float)$fields[2],
                'rx_drop'    => (float)$fields[3],
                'tx_bytes'   => (float)$fields[8],
                'tx_packets' => (float)$fields[9],
                'tx_errs'    => (float)$fields[10],
                'tx_drop'    => (float)$fields[11],
            ];
        }
    }
}

// Fallback to API if /proc/net/dev is not directly readable
if (empty($proc_stats)) {
    $ifaces = MitraNetApi::getInterfaces();
    foreach ($ifaces as $i) {
        $name = $i['name'] ?? '';
        if (!$name) continue;
        $t = $i['traffic'] ?? [];
        $proc_stats[$name] = [
            'rx_bytes'   => (float)($t['rx_bytes'] ?? 0),
            'rx_packets' => (float)($t['rx_packets'] ?? 0),
            'tx_bytes'   => (float)($t['tx_bytes'] ?? 0),
            'tx_packets' => (float)($t['tx_packets'] ?? 0),
        ];
    }
}

// If requested via traffic-graphs.js (POST with if=...&realif=...)
$req_if = $_POST['if'] ?? $_GET['if'] ?? '';
$req_realif = $_POST['realif'] ?? $_GET['realif'] ?? '';

if (!empty($req_if)) {
    // Format expected by traffic-graphs.js:
    // {
    //   "wan": [
    //      { "key": "wan (in)",  "values": [ timestamp_sec, rx_bytes ] },
    //      { "key": "wan (out)", "values": [ timestamp_sec, tx_bytes ] },
    //      "name": "WAN (eth0)"
    //   ]
    // }
    $ifs = explode('|', $req_if);
    $realifs = !empty($req_realif) ? explode('|', $req_realif) : $ifs;
    
    $response = [];
    foreach ($ifs as $idx => $key) {
        $real = $realifs[$idx] ?? $key;
        $st = $proc_stats[$real] ?? $proc_stats[$key] ?? [
            'rx_bytes' => 0,
            'tx_bytes' => 0,
            'rx_packets' => 0,
            'tx_packets' => 0
        ];
        
        $response[$key] = [
            0 => [
                'key' => $key . ' (in)',
                'values' => [$now, $st['rx_bytes']]
            ],
            1 => [
                'key' => $key . ' (out)',
                'values' => [$now, $st['tx_bytes']]
            ],
            'name' => strtoupper($key) . ' (' . $real . ')'
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Standard response for AJAX calls querying all interface stats
echo json_encode([
    'timestamp' => $now,
    'interfaces' => $proc_stats
]);
exit;
