<?php
const SM_MAX_LOAD_PER_CORE = 1.0;
const SM_MAX_RAM_PERCENT   = 90;
const SM_MAX_DISK_PERCENT  = 90;

function collect_server_metrics(): array
{
    $cores = 1;
    if (is_readable('/proc/cpuinfo')) {
        $cores = max(1, (int) preg_match_all('/^processor\s*:/m', file_get_contents('/proc/cpuinfo')));
    }

    $cpuLoad = null;
    if (function_exists('sys_getloadavg') && ($load = sys_getloadavg()) !== false) {
        $cpuLoad = round($load[1], 2);
    }

    $loadPerCore = ($cpuLoad !== null) ? ($cpuLoad / $cores) : null;
    $cpuPercent  = ($loadPerCore !== null)
        ? round(min(100, ($loadPerCore / SM_MAX_LOAD_PER_CORE) * 100), 1)
        : null;

    $ramPercent = null;
    if (is_readable('/proc/meminfo')) {
        $memInfo = file_get_contents('/proc/meminfo');
        if (preg_match('/MemTotal:\s+(\d+)/', $memInfo, $total) &&
            preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $available) &&
            (int) $total[1] > 0) {
            $ramPercent = round((((int) $total[1] - (int) $available[1]) / (int) $total[1]) * 100, 1);
        }
    }

    $diskPercent = null;
    $diskFree  = @disk_free_space('/');
    $diskTotal = @disk_total_space('/');
    if ($diskFree !== false && $diskTotal > 0) {
        $diskPercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 1);
    }

    $reasons = [];
    if ($loadPerCore !== null && $loadPerCore >= SM_MAX_LOAD_PER_CORE) $reasons[] = 'cpu';
    if ($ramPercent  !== null && $ramPercent  >= SM_MAX_RAM_PERCENT)   $reasons[] = 'ram';
    if ($diskPercent !== null && $diskPercent >= SM_MAX_DISK_PERCENT)  $reasons[] = 'disk';

    return [
        'cpu_load'     => $cpuLoad,
        'cpu_cores'    => $cores,
        'cpu_percent'  => $cpuPercent,
        'ram_percent'  => $ramPercent,
        'disk_percent' => $diskPercent,
        'reasons'      => $reasons,
        'status'       => $reasons ? 'degraded' : 'online',
    ];
}
?>