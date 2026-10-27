<?php

date_default_timezone_set('Asia/Manila');
session_start();
if (empty($_SESSION['login_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'unauthorized']);
    exit;
}
session_write_close();

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const MAX_LOAD_PER_CORE = 1.0;
const MAX_RAM_PERCENT   = 90;
const MAX_DISK_PERCENT  = 90;

$cores = 1;
if (is_readable('/proc/cpuinfo')) {
    $cores = max(1, preg_match_all('/^processor\s*:/m', file_get_contents('/proc/cpuinfo')));
}

$cpuLoad = null;
if (function_exists('sys_getloadavg') && ($load = sys_getloadavg()) !== false) {
    $cpuLoad = round($load[1], 2);
}

$loadPerCore = ($cpuLoad !== null && $cores > 0) ? ($cpuLoad / $cores) : null;
$cpuPercent  = ($loadPerCore !== null)
    ? round(min(100, ($loadPerCore / MAX_LOAD_PER_CORE) * 100), 1)
    : null;

$memUsedPercent = null;
if (is_readable('/proc/meminfo')) {
    $memInfo = file_get_contents('/proc/meminfo');
    if (preg_match('/MemTotal:\s+(\d+)/', $memInfo, $total) &&
        preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $available) &&
        (int) $total[1] > 0) {
        $memUsedPercent = round(
            (((int) $total[1] - (int) $available[1]) / (int) $total[1]) * 100,
            1
        );
    }
}

$diskUsedPercent = null;
$diskFree  = @disk_free_space('/');
$diskTotal = @disk_total_space('/');
if ($diskFree !== false && $diskTotal > 0) {
    $diskUsedPercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 1);
}

$reasons = [];
if ($loadPerCore !== null && $loadPerCore >= MAX_LOAD_PER_CORE) {
    $reasons[] = 'cpu';
}
if ($memUsedPercent !== null && $memUsedPercent >= MAX_RAM_PERCENT) {
    $reasons[] = 'ram';
}
if ($diskUsedPercent !== null && $diskUsedPercent >= MAX_DISK_PERCENT) {
    $reasons[] = 'disk';
}

$degraded = count($reasons) > 0;

echo json_encode([
    'status' => $degraded ? 'degraded' : 'online',
    'cpu_load'=> $cpuLoad,
    'cpu_cores' => $cores,
    'cpu_percent' => $cpuPercent,
    'ram_percent' => $memUsedPercent,
    'disk_percent'  => $diskUsedPercent,
    'degraded_reasons' => $reasons,
    'checked_at' => date('Y-m-d H:i:s'),
]);
