<?php
// cron/collect_server_metrics.php
// Run every minute by cron. CLI only.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

date_default_timezone_set('Asia/Manila');

require __DIR__ . '/../config/connect.php';               // must define $pdo
require __DIR__ . '/../library/server_metrics_lib.php';

const RETENTION_DAYS = 30;

try {
    $m = collect_server_metrics();

    $stmt = $pdo->prepare(
        'INSERT INTO server_metrics (checked_at, cpu_load, cpu_percent, ram_percent, disk_percent, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        date('Y-m-d H:i:s'),
        $m['cpu_load'],
        $m['cpu_percent'],
        $m['ram_percent'],
        $m['disk_percent'],
        $m['status'],
    ]);

    // Once an hour, delete rows older than the retention window
    if (date('i') === '00') {
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . RETENTION_DAYS . ' days'));
        $pdo->prepare('DELETE FROM server_metrics WHERE checked_at < ?')->execute([$cutoff]);
    }
} catch (Throwable $e) {
    error_log('[server_metrics] ' . $e->getMessage());
    exit(1);
}

?>