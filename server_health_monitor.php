<?php
include("includes/header.php");
include('includes/session.php');
include("includes/navbar.php");
$page_title = "KabanDesk";
?>
<link rel="stylesheet" href="/assets/css/server_monitor.css">
<?php

// Roles allowed to access this page
$allowed_roles = ['IT Manager', 'Administrator'];
if (empty($_SESSION['login_id'])) {
    header('Location: index.php');
    exit;
}

// Logged in, but role not allowed
if (!in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Access Denied</title>
        <style>
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                font-family: system-ui, sans-serif;
                background: #f4f4f5;
                color: #333;
                text-align: center;
            }
            img { max-width: 700px; width: 80%; height: auto; }
            a { margin-top: 1rem; color: #2563eb; text-decoration: none; }
        </style>
    </head>
    <body>
        <img src="/assets/img/access_denied.jpg" alt="Access Denied">
        <h1 class="display-5 mt-3">You don't have permission to view this page.</h1>
    </body>
    </html>
    <?php
    exit;
}

$pollSeconds = 300;   // live check interval
$historyRows = 30;   // rows kept in the "Recent checks" table
?>
<main class="py-4 mt-5"
      id="server-monitor-page"
      data-poll-interval="<?= (int) $pollSeconds * 1000 ?>"
      data-history-max="<?= (int) $historyRows ?>">

    <!-- Title + actions -->
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="mb-0 fw-semibold">Server Health</h4>
            <small class="text-muted">Live status of the KabanDesk server <?= $_SERVER['SERVER_NAME']; ?>, refreshed every <?= (int) ($pollSeconds / 60) ?> minutes.</small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn sm-btn btn-outline-secondary" id="btn-monitor-pause">
                <i class="fas fa-pause me-1"></i><span>Pause</span>
            </button>
            <button type="button" class="btn sm-btn kaban-color text-white" id="btn-monitor-refresh">
                <i class="fas fa-rotate me-1"></i>Refresh now
            </button>
        </div>
    </div>

    <div id="server-degraded-wrap" class="alert alert-warning d-none mb-3" role="alert"></div>

    <!-- Row 1: live status (left) + stored history chart (right) -->
    <div class="row mb-3">
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm border-0 server-health-card server-health-card--detail is-loading h-100" id="server-health-card">
                <div class="card-header p-3 sm-header text-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Current status</span>
                    <span id="server-status-badge" class="server-status-pill is-unreachable" role="status" aria-live="polite">
                        <span class="status-dot" aria-hidden="true"></span>
                        <span id="server-status-text">Checking…</span>
                    </span>
                </div>
                <div class="card-body p-3">
                    <div class="py-2 border-bottom">
                        <div class="d-flex justify-content-between align-items-baseline">
                            <span id="server-cpu-label" class="metric-label">CPU Usage</span>
                            <span id="server-cpu" class="metric-value fw-bold text-dark">–</span>
                        </div>
                        <div class="metric-bar" role="progressbar" aria-labelledby="server-cpu-label" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="server-cpu-bar-wrap">
                            <div id="server-cpu-bar" class="metric-bar-fill level-ok"></div>
                        </div>
                    </div>

                    <div class="py-2 border-bottom">
                        <div class="d-flex justify-content-between align-items-baseline">
                            <span id="server-ram-label" class="metric-label">RAM Usage</span>
                            <span id="server-ram" class="metric-value fw-bold text-dark">–</span>
                        </div>
                        <div class="metric-bar" role="progressbar" aria-labelledby="server-ram-label" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="server-ram-bar-wrap">
                            <div id="server-ram-bar" class="metric-bar-fill level-ok"></div>
                        </div>
                    </div>

                    <div class="py-2">
                        <div class="d-flex justify-content-between align-items-baseline">
                            <span id="server-disk-label" class="metric-label">Disk Usage</span>
                            <span id="server-disk" class="metric-value fw-bold text-dark">–</span>
                        </div>
                        <div class="metric-bar" role="progressbar" aria-labelledby="server-disk-label" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="server-disk-bar-wrap">
                            <div id="server-disk-bar" class="metric-bar-fill level-ok"></div>
                        </div>
                    </div>

                    <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <small id="server-updated" class="text-muted" style="font-size: 0.75rem;">Waiting for data…</small>
                    </div>

                    <!-- Compact replacement for the old "Alert thresholds" card -->
                    <div class="sm-legend mt-2 pt-2 border-top">
                        <span class="me-2"><span class="sm-dot" style="background:#22c55e"></span>Under 70%</span>
                        <span class="me-2"><span class="sm-dot" style="background:#f59e0b"></span>70–89%</span>
                        <span><span class="sm-dot" style="background:#ef4444"></span>90%+</span>
                        <div class="mt-1">Status changes to Degraded when RAM or disk reaches 90%, or CPU load reaches 1.0 per core.</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow-sm border-0 server-health-card h-100" id="server-history-card">
                <div class="card-header p-3 sm-header text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-semibold">History</span>
                    <div class="btn-group btn-group-sm" id="server-history-ranges" role="group" aria-label="History range">
                        <?php
                        $timeRanges = [
                            '1h'  => '1 hour',
                            '6h'  => '6 hours',
                            '15h' => '15 hours',
                            '24h' => '24 hours',
                            '7d'  => '7 days',
                        ];
                        $currentActive = '6h';
                        ?>

                        <div class="btn-group" role="group">
                            <?php foreach ($timeRanges as $range => $label): ?>
                                <?php 
                                    $isActive = ($range === $currentActive);
                                    $activeClass = $isActive ? ' active' : ''; 
                                ?>
                                <button type="button" class="btn btn-outline-light<?= $activeClass ?>" data-range="<?= $range ?>">
                                    <?= $label ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div style="position: relative; height: 300px;">
                        <canvas id="server-history-chart"></canvas>
                    </div>
                    <small id="server-history-summary" class="text-muted d-block mt-2">Loading history…</small>
                </div>
            </div>
        </div>
        <div class="col-xl-12 col-lg-6">
            <div class="card shadow-sm border-0 server-health-card mt-3">
                <div class="card-header p-3 sm-header text-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Recent checks</span>
                    <small class="text-white-50">Last <?= (int) $historyRows ?> checks from this session</small>
                </div>
                <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0 sm-table">
                        <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th>Time</th>
                                <th>Status</th>
                                <th>CPU</th>
                                <th>RAM</th>
                                <th>Disk</th>
                                <th class="pe-3">Note</th>
                            </tr>
                        </thead>
                        <tbody id="server-monitor-history">
                            <tr id="server-monitor-history-empty">
                                <td colspan="6" class="text-center text-muted py-4">Waiting for the first check…</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include('includes/footer.php'); ?>
<script src="/assets/js/chart.umd.min.js"></script>
<script src="/assets/js/server_history.js"></script>
<script>
if (typeof monitorHistoryMax !== 'undefined' && !monitorHistoryMax) {
    monitorHistoryMax = <?= (int) $historyRows ?>;
}
// Pause / resume and manual refresh
(function () {
    let paused = false;
    const $pause = $('#btn-monitor-pause');

    $('#btn-monitor-refresh').on('click', function () {
        pollServerMonitor();
    });

    $pause.on('click', function () {
        paused = !paused;
        if (paused) {
            clearTimeout(monitorTimer);
            $pause.find('span').text('Resume');
            $pause.find('i').attr('class', 'fas fa-play me-1');
        } else {
            $pause.find('span').text('Pause');
            $pause.find('i').attr('class', 'fas fa-pause me-1');
            pollServerMonitor();
        }
    });

    // Don't reschedule while paused
    $(document).ajaxComplete(function () {
        if (paused) clearTimeout(monitorTimer);
    });

    // Resume polling when the tab becomes visible again
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && !paused) pollServerMonitor();
    });
})();
</script>