<?php
/*
 * sysstats.php - Real-Time System Metrics Endpoint for MitraNet Dashboard
 *
 * Dedicated lightweight JSON API endpoint for dashboard live metrics:
 * System Info (CPU, Memory, Uptime), Interfaces status, Conntrack state summary.
 * Authenticated via session; strict zero shell execution.
 */

require_once(__DIR__ . '/includes/api.inc');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Only allow authenticated users
if (!MitraNetApi::isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'status' => 401,
        'error' => 'Authentication required'
    ]);
    exit;
}

$now = microtime(true);

// 1. Fetch System Metrics
$sys = MitraNetApi::getSystem();

// 2. Fetch Interface Status
$ifaces = MitraNetApi::getInterfaces();

// 3. Fetch Conntrack State Summary
$conntrack = MitraNetApi::getConntrackStates();

// Extract compact and safe payload for dashboard
$interfaces_summary = [];
if (is_array($ifaces)) {
    foreach ($ifaces as $if) {
        $name = $if['name'] ?? '';
        if ($name === '') continue;
        $interfaces_summary[$name] = [
            'name' => $name,
            'is_up' => !empty($if['is_up']),
            'oper_state' => $if['oper_state'] ?? ($if['is_up'] ? 'UP' : 'DOWN'),
            'admin_state' => $if['admin_state'] ?? ''
        ];
    }
}

// Conntrack: validasi nilai numerik sah
$conntrack_total = null;
if (is_array($conntrack)) {
    if (isset($conntrack['total']) && is_numeric($conntrack['total']) && intval($conntrack['total']) >= 0) {
        $conntrack_total = intval($conntrack['total']);
    } elseif (isset($conntrack['states']) && is_array($conntrack['states'])) {
        $conntrack_total = count($conntrack['states']);
    }
}

// CPU Components & Total Calculation (Fase 4.4)
$cpu_data = null;
if (is_array($sys) && isset($sys['cpu']) && is_array($sys['cpu'])) {
    $u = $sys['cpu']['user'] ?? null;
    $s = $sys['cpu']['system'] ?? null;
    $i = $sys['cpu']['idle'] ?? null;
    $tot = $sys['cpu']['total'] ?? null;

    if (is_numeric($u) && is_numeric($s) && is_numeric($i)) {
        $u_int = intval($u);
        $s_int = intval($s);
        $i_int = intval($i);
        if ($u_int >= 0 && $s_int >= 0 && $i_int >= 0) {
            // Jika backend tidak menyediakan 'total', hitung dari sum(user, system, idle)
            $tot_int = (is_numeric($tot) && intval($tot) > 0) ? intval($tot) : ($u_int + $s_int + $i_int);
            if ($tot_int > 0) {
                $cpu_data = [
                    'user' => $u_int,
                    'system' => $s_int,
                    'idle' => $i_int,
                    'total' => $tot_int
                ];
            }
        }
    }
}

$response = [
    'status' => 200,
    'timestamp' => $now,
    'system' => [
        'uptime_seconds' => isset($sys['uptime_seconds']) && is_numeric($sys['uptime_seconds']) && floatval($sys['uptime_seconds']) >= 0 ? floatval($sys['uptime_seconds']) : null,
        'cpu' => $cpu_data,
        'memory' => [
            'total_kb' => isset($sys['memory']['total_kb']) && is_numeric($sys['memory']['total_kb']) ? intval($sys['memory']['total_kb']) : 0,
            'used_kb' => isset($sys['memory']['used_kb']) && is_numeric($sys['memory']['used_kb']) ? intval($sys['memory']['used_kb']) : 0,
            'available_kb' => isset($sys['memory']['available_kb']) && is_numeric($sys['memory']['available_kb']) ? intval($sys['memory']['available_kb']) : 0,
            'used_percent' => isset($sys['memory']['used_percent']) && is_numeric($sys['memory']['used_percent']) ? floatval($sys['memory']['used_percent']) : 0
        ]
    ],
    'interfaces' => $interfaces_summary,
    'conntrack' => [
        'total' => $conntrack_total
    ]
];

echo json_encode($response);
exit;
