<?php

$skip_activity = true;

include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');

header('Content-Type: application/json');

// Adjust to the exact role values stored in users.role
$allowedRoles = ['IT Supervisor', 'IT Manager', 'Administrator'];
if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    echo json_encode(['status' => 'error', 'message' => 'You do not have permission to view KPIs.']);
    exit;
}

$days = (int)($_GET['days'] ?? 30);
if (!in_array($days, [7, 14, 30, 60, 90])) {
    $days = 30;
}
$since = date('Y-m-d', strtotime("-{$days} days")); // midnight, Manila time

$pct = function ($a, $b) {
    return ((int)$b > 0) ? (int)round(((int)$a / (int)$b) * 100) : null;
};
$mins = function ($v) {
    return $v === null ? null : (int)round((float)$v);
};

try {
    
    $k = retrieve(
        "SELECT
            COUNT(*) AS created_count,
            SUM(resolved_at IS NOT NULL) AS resolved_count,
            SUM(deleted_at IS NOT NUll) AS deleted_count,
            SUM(started_at IS NOT NULL OR response_due_at < NOW()) AS response_eligible,
            SUM(started_at IS NOT NULL AND started_at <= response_due_at) AS response_on_time,
            AVG(CASE WHEN started_at IS NOT NULL
                     THEN TIMESTAMPDIFF(MINUTE, created_at, started_at) END) AS avg_response_min,
            SUM(resolved_at IS NOT NULL OR resolution_due_at < NOW()) AS resolution_eligible,
            SUM(resolved_at IS NOT NULL AND resolved_at <= resolution_due_at) AS resolution_on_time,
            AVG(CASE WHEN resolved_at IS NOT NULL
                     THEN TIMESTAMPDIFF(MINUTE, created_at, resolved_at) END) AS avg_resolution_min,
            SUM(escalated_by IS NOT NULL) AS escalated_count,
            SUM(resolved_at IS NOT NULL OR reopen_count > 0) AS ever_resolved,
            SUM(reopen_count > 0) AS reopened_count
         FROM tickets
         WHERE created_at >= ?",
        array($since)
    )[0];

    // ---------- Per-agent ----------
    $agentRows = retrieve(
        "SELECT u.id, u.name,
                COUNT(t.id) AS assigned,
                SUM(t.resolved_at IS NOT NULL) AS resolved,
                AVG(CASE WHEN t.resolved_at IS NOT NULL
                         THEN TIMESTAMPDIFF(MINUTE, t.created_at, t.resolved_at) END) AS avg_resolution_min,
                SUM(t.resolved_at IS NOT NULL OR t.resolution_due_at < NOW()) AS eligible,
                SUM(t.resolved_at IS NOT NULL AND t.resolved_at <= t.resolution_due_at) AS on_time
         FROM users u
         LEFT JOIN tickets t ON t.assigned_to = u.id AND t.created_at >= ? AND t.deleted_at IS NULL
         WHERE u.role = 'IT Support Specialist'
         GROUP BY u.id, u.name
         ORDER BY resolved DESC, u.name ASC",
        array($since)
    );

    $agents = [];
    foreach ($agentRows as $a) {
        $agents[] = [
            'name'               => $a['name'],
            'assigned'           => (int)$a['assigned'],
            'resolved'           => (int)$a['resolved'],
            'avg_resolution_min' => $mins($a['avg_resolution_min']),
            'on_time_pct'        => $pct($a['on_time'], $a['eligible']),
        ];
    }

    // ---------- Created vs resolved per day ----------
    $createdRows = retrieve(
        "SELECT DATE(created_at) AS d, COUNT(*) AS c
         FROM tickets WHERE created_at >= ? GROUP BY DATE(created_at)",
        array($since)
    );
    $resolvedRows = retrieve(
        "SELECT DATE(resolved_at) AS d, COUNT(*) AS c
         FROM tickets WHERE resolved_at >= ? GROUP BY DATE(resolved_at)",
        array($since)
    );

    $createdMap = [];
    foreach ($createdRows as $r)  { $createdMap[$r['d']]  = (int)$r['c']; }
    $resolvedMap = [];
    foreach ($resolvedRows as $r) { $resolvedMap[$r['d']] = (int)$r['c']; }

    $labels = []; $createdSeries = []; $resolvedSeries = [];
    for ($i = $days; $i >= 0; $i--) {
        $ts = strtotime("-{$i} days");
        $d  = date('Y-m-d', $ts);
        $labels[]         = date('M j', $ts);
        $createdSeries[]  = $createdMap[$d]  ?? 0;
        $resolvedSeries[] = $resolvedMap[$d] ?? 0;
    }

    // ---------- Breakdowns ----------
    $byCategory = retrieve(
        "SELECT COALESCE(c.name, 'Uncategorized') AS name, COUNT(*) AS cnt
         FROM tickets t
         LEFT JOIN categories c ON t.category_id = c.id
         WHERE t.created_at >= ?
         GROUP BY c.id, c.name
         ORDER BY cnt DESC
         LIMIT 8",
        array($since)
    );

    $byPriority = retrieve(
        "SELECT priority, COUNT(*) AS cnt
         FROM tickets
         WHERE created_at >= ?
         GROUP BY priority
         ORDER BY FIELD(priority, 'Critical', 'High', 'Medium', 'Low')",
        array($since)
    );

    echo json_encode([
        'status' => 'success',
        'days'   => $days,
        'kpis'   => [
            'created' => (int)$k['created_count'],
            'resolved' => (int)$k['resolved_count'],
            'cancelled' => (int)$k['deleted_count'],
            'response_sla_pct' => $pct($k['response_on_time'], $k['response_eligible']),
            'resolution_sla_pct' => $pct($k['resolution_on_time'], $k['resolution_eligible']),
            'avg_response_min' => $mins($k['avg_response_min']),
            'avg_resolution_min' => $mins($k['avg_resolution_min']),
            'escalation_rate' => $pct($k['escalated_count'], $k['created_count']),
            'reopen_rate' => $pct($k['reopened_count'], $k['ever_resolved']),
        ],
        'agents'      => $agents,
        'trend'       => ['labels' => $labels, 'created' => $createdSeries, 'resolved' => $resolvedSeries],
        'by_category' => array_map(fn($r) => ['name' => $r['name'], 'count' => (int)$r['cnt']], $byCategory),
        'by_priority' => array_map(fn($r) => ['priority' => $r['priority'], 'count' => (int)$r['cnt']], $byPriority),
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to load KPIs.']);
}
exit;

?>