<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php include("library/stats.php"); ?>
<?php $page_title = "Reports - KabanDesk"; ?>
<?php

if (empty($_SESSION['login_id']) || !in_array($_SESSION['role'] ?? '', ['IT Supervisor', 'IT Manager'])) {
    header('Location: login.php');
    exit;
}

// ---- Filters ----
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$category = $_GET['category'] ?? 'ALL';
$status = $_GET['status'] ?? 'ALL';

$categories = retrieve("SELECT * FROM categories ORDER BY name",array());
$statuses   = ['New','Open','In Progress','Pending','Resolved','Closed'];

$where  = "WHERE t.created_at BETWEEN :date_from AND :date_to";
$params = [
    ':date_from' => $date_from . ' 00:00:00',
    ':date_to'   => $date_to . ' 23:59:59',
];

if ($category !== 'ALL') {
    $where .= " AND t.category_id = :category";
    $params[':category'] = $category;
}
if ($status !== 'ALL') {
    $where .= " AND t.status = :status";
    $params[':status'] = $status;
}

// ---- KPI: Totals ----
$sql = "SELECT
            COUNT(*) AS total_tickets,
            SUM(CASE WHEN t.status IN ('New','Open','In Progress','Pending') THEN 1 ELSE 0 END) AS open_tickets,
            SUM(CASE WHEN t.status IN ('Resolved','Closed') THEN 1 ELSE 0 END) AS resolved_tickets,
            SUM(CASE WHEN t.resolved_at IS NOT NULL AND t.resolved_at <= t.resolution_due_at THEN 1 ELSE 0 END) AS sla_met,
            SUM(CASE WHEN t.resolved_at IS NOT NULL THEN 1 ELSE 0 END) AS sla_eligible,
            AVG(CASE WHEN t.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, t.created_at, t.resolved_at) END) AS avg_resolution_minutes
        FROM tickets t
        $where";
$kpi = retrieve($sql, $params)[0] ?? array();

$total_tickets    = (int)($kpi['total_tickets'] ?? 0);
$open_tickets     = (int)($kpi['open_tickets'] ?? 0);
$resolved_tickets = (int)($kpi['resolved_tickets'] ?? 0);
$sla_eligible     = (int)($kpi['sla_eligible'] ?? 0);
$sla_met          = (int)($kpi['sla_met'] ?? 0);
$sla_compliance   = $sla_eligible > 0 ? round(($sla_met / $sla_eligible) * 100, 1) : null;
$avg_res_minutes  = $kpi['avg_resolution_minutes'] !== null ? round($kpi['avg_resolution_minutes']) : null;

function format_minutes($min) {
    if ($min === null) return 'N/A';
    $h = intdiv($min, 60);
    $m = $min % 60;
    return "{$h}h {$m}m";
}

    $sql = "SELECT c.name AS category_name, c.code AS category_code, COUNT(*) AS cnt
        FROM tickets t
        JOIN categories c ON c.id = t.category_id
        $where
        GROUP BY c.id, c.name, c.code
        ORDER BY cnt DESC";
    
    $by_category = retrieve($sql, $params);

// ---- Chart: Tickets by Status ----
    $sql = "SELECT t.status, COUNT(*) AS cnt
            FROM tickets t
            $where
            GROUP BY t.status";
    $by_status = retrieve($sql, $params);

    // ---- Chart: Monthly Volume (last 6 months, ignores filter dates for trend context) ----
    $sql = "SELECT DATE_FORMAT(t.created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
            FROM tickets t
            WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY ym
            ORDER BY ym";
    $monthly_volume = retrieve($sql, []);

    // ---- Chart: SLA Compliance Trend (by week, within filter range) ----
    $sql = "SELECT YEARWEEK(t.created_at, 1) AS yw,
                MIN(DATE(t.created_at)) AS week_start,
                SUM(CASE WHEN t.resolved_at IS NOT NULL AND t.resolved_at <= t.resolution_due_at THEN 1 ELSE 0 END) AS met,
                SUM(CASE WHEN t.resolved_at IS NOT NULL THEN 1 ELSE 0 END) AS eligible
            FROM tickets t
            $where
            GROUP BY yw
            ORDER BY yw";
    $sla_trend = retrieve($sql, $params);

    $sql = "SELECT u.name AS agent_name,
                COUNT(t.id) AS tickets_handled,
                SUM(CASE WHEN t.status IN ('Resolved','Closed') THEN 1 ELSE 0 END) AS resolved,
                AVG(CASE WHEN t.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, t.created_at, t.resolved_at) END) AS avg_minutes,
                SUM(CASE WHEN t.resolved_at IS NOT NULL AND t.resolved_at > t.resolution_due_at THEN 1 ELSE 0 END) AS sla_breaches
            FROM tickets t
            JOIN users u ON u.id = t.assigned_to
            $where
            GROUP BY u.id, u.name
            ORDER BY tickets_handled DESC";
    $agent_perf = retrieve($sql, $params);

    
    // ---- CSV Export ----
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="kaban_helpdesk_report_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Kaban Helpdesk Report', "$date_from to $date_to"]);
        fputcsv($out, []);
        fputcsv($out, ['Total Tickets', $total_tickets]);
        fputcsv($out, ['Open Tickets', $open_tickets]);
        fputcsv($out, ['Resolved Tickets', $resolved_tickets]);
        fputcsv($out, ['SLA Compliance %', $sla_compliance ?? 'N/A']);
        fputcsv($out, ['Avg Resolution Time', format_minutes($avg_res_minutes)]);
        fputcsv($out, []);
        fputcsv($out, ['Category', 'Code', 'Ticket Count']);
        foreach ($by_category as $row) fputcsv($out, [$row['category_name'], $row['category_code'], $row['cnt']]);
        fputcsv($out, []);
        fputcsv($out, ['Agent', 'Tickets Handled', 'Resolved', 'Avg Resolution', 'SLA Breaches']);
        foreach ($agent_perf as $row) {
            fputcsv($out, [$row['agent_name'], $row['tickets_handled'], $row['resolved'], format_minutes(round($row['avg_minutes'] ?? 0)), $row['sla_breaches']]);
        }
        fclose($out);
        exit;
    }

    // JSON-encode chart data for JS
    $js_category_labels = json_encode(array_column($by_category, 'category_code')); // codes on axis; names available via $by_category
    $js_category_data    = json_encode(array_map('intval', array_column($by_category, 'cnt')));
    $js_status_labels    = json_encode(array_column($by_status, 'status'));
    $js_status_data      = json_encode(array_map('intval', array_column($by_status, 'cnt')));
    $js_volume_labels    = json_encode(array_column($monthly_volume, 'ym'));
    $js_volume_data      = json_encode(array_map('intval', array_column($monthly_volume, 'cnt')));

    $sla_trend_labels = [];
    $sla_trend_data   = [];
    foreach ($sla_trend as $row) {
        $sla_trend_labels[] = $row['week_start'];
        $sla_trend_data[]   = $row['eligible'] > 0 ? round(($row['met'] / $row['eligible']) * 100, 1) : null;
    }
    $js_sla_trend_labels = json_encode($sla_trend_labels);
    $js_sla_trend_data   = json_encode($sla_trend_data);


?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="fa-solid fa-chart-line me-2"></i>&nbsp;Reports</h3>
        <a class="btn btn-outline-primary btn-sm" href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>">
            <i class="fa-solid fa-file-csv me-1"></i>Export CSV
        </a>
    </div>

    <!-- Filters -->
    <form method="get" class="filter-bar p-3 mb-4 row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($date_from) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($date_to) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Category</label>
            <select name="category" class="form-control form-control-sm">
                <option value="ALL" <?= $category === 'ALL' ? 'selected' : '' ?>>All</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (string)$category === (string)$c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-control form-control-sm">
                <option value="ALL" <?= $status === 'ALL' ? 'selected' : '' ?>>All</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-filter me-1"></i>Apply</button>
        </div>
        <div class="col-6 col-md-2">
            <a href="reports.php" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2-4 col-lg">
            <div class="card kpi-card p-3">
                <div class="kpi-label">Total Tickets</div>
                <div class="kpi-value text-primary"><?= number_format($total_tickets) ?></div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="card kpi-card p-3">
                <div class="kpi-label">Open</div>
                <div class="kpi-value text-warning"><?= number_format($open_tickets) ?></div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="card kpi-card p-3">
                <div class="kpi-label">Resolved</div>
                <div class="kpi-value text-success"><?= number_format($resolved_tickets) ?></div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="card kpi-card p-3">
                <div class="kpi-label">SLA Compliance</div>
                <div class="kpi-value <?= ($sla_compliance !== null && $sla_compliance >= 90) ? 'sla-good' : 'sla-bad' ?>">
                    <?= $sla_compliance !== null ? $sla_compliance . '%' : 'N/A' ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="card kpi-card p-3">
                <div class="kpi-label">Avg Resolution Time</div>
                <div class="kpi-value" style="font-size:1.4rem;"><?= format_minutes($avg_res_minutes) ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card chart-card p-3">
                <h6 class="mb-3">Tickets by Category</h6>
                <canvas id="categoryChart" height="220"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card chart-card p-3">
                <h6 class="mb-3">Tickets by Status</h6>
                <canvas id="statusChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card chart-card p-3">
                <h6 class="mb-3">Ticket Volume — Last 6 Months</h6>
                <canvas id="volumeChart" height="220"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card chart-card p-3">
                <h6 class="mb-3">Weekly SLA Compliance Trend</h6>
                <canvas id="slaChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <div class="card chart-card p-3 mb-4">
        <h6 class="mb-3">Agent Performance</h6>
        <div class="table-responsive">
            <table class="table table-hover agent-table align-middle">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Tickets Handled</th>
                        <th>Resolved</th>
                        <th>Avg Resolution Time</th>
                        <th>SLA Breaches</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($agent_perf)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No data for the selected filters.</td></tr>
                    <?php else: foreach ($agent_perf as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['agent_name']) ?></td>
                            <td><?= (int)$row['tickets_handled'] ?></td>
                            <td><?= (int)$row['resolved'] ?></td>
                            <td><?= format_minutes($row['avg_minutes'] !== null ? round($row['avg_minutes']) : null) ?></td>
                            <td class="<?= (int)$row['sla_breaches'] > 0 ? 'sla-bad' : 'sla-good' ?>"><?= (int)$row['sla_breaches'] ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<?php include("includes/footer.php"); ?>
<script src="./assets/js/addons-pro/chat.min.js"></script>
<script>
const categoryChart = new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: {
        labels: <?= $js_category_labels ?>,
        datasets: [{ label: 'Tickets', data: <?= $js_category_data ?>, backgroundColor: '#4e73df', borderRadius: 4 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});

const statusChart = new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= $js_status_labels ?>,
        datasets: [{ data: <?= $js_status_data ?>, backgroundColor: ['#4e73df','#f6c23e','#e74a3b','#36b9cc','#1cc88a','#858796'] }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});

const volumeChart = new Chart(document.getElementById('volumeChart'), {
    type: 'line',
    data: {
        labels: <?= $js_volume_labels ?>,
        datasets: [{ label: 'Tickets Created', data: <?= $js_volume_data ?>, borderColor: '#1cc88a', backgroundColor: 'rgba(28,200,138,.15)', fill: true, tension: .3 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});

const slaChart = new Chart(document.getElementById('slaChart'), {
    type: 'line',
    data: {
        labels: <?= $js_sla_trend_labels ?>,
        datasets: [{ label: 'SLA Compliance %', data: <?= $js_sla_trend_data ?>, borderColor: '#e74a3b', backgroundColor: 'rgba(231,74,59,.15)', fill: true, tension: .3 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100 } } }
});
</script>