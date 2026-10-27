<?php
// api/server_metrics_history.php?range=1h|6h|24h|7d
include('../config/connect.php');   // must define $pdo
session_start();
if (empty($_SESSION['login_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'unauthorized']);
    exit;
}
session_write_close();

date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// range => [seconds back, bucket size in seconds, label format]
$ranges = [
    '1h'  => [3600,   60,   'H:i'],
    '6h'  => [21600,  300,  'H:i'],
    '15h' => [54000,  900,  'H:i'],
    '24h' => [86400,  900,  'H:i'],
    '7d'  => [604800, 3600, 'D H:i'],
];

$range = $_GET['range'] ?? '6h';
if (!isset($ranges[$range])) {
    $range = '6h';
}
[$span, $bucket, $labelFormat] = $ranges[$range];   // whitelisted ints, safe to inline

$since = date('Y-m-d H:i:s', time() - $span);

try {
    // Peak value in each bucket so short spikes stay visible
    $stmt = $pdo->prepare(
        "SELECT FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(checked_at) / $bucket) * $bucket) AS bucket_time,
                MAX(cpu_percent)  AS cpu,
                MAX(ram_percent)  AS ram,
                MAX(disk_percent) AS disk
         FROM server_metrics
         WHERE checked_at >= ?
         GROUP BY bucket_time
         ORDER BY bucket_time"
    );
    $stmt->execute([$since]);

    $points = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $points[] = [
            'label' => date($labelFormat, strtotime($row['bucket_time'])),
            'cpu'   => $row['cpu']  !== null ? (float) $row['cpu']  : null,
            'ram'   => $row['ram']  !== null ? (float) $row['ram']  : null,
            'disk'  => $row['disk'] !== null ? (float) $row['disk'] : null,
        ];
    }

    $sum = $pdo->prepare(
        "SELECT COUNT(*) AS checks, COALESCE(SUM(status = 'degraded'), 0) AS degraded
         FROM server_metrics WHERE checked_at >= ?"
    );
    $sum->execute([$since]);
    $totals = $sum->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status'   => 'ok',
        'range'    => $range,
        'points'   => $points,
        'checks'   => (int) $totals['checks'],
        'degraded' => (int) $totals['degraded'],
    ]);
} catch (Throwable $e) {
    error_log('[server_metrics_history] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error']);
}

?>