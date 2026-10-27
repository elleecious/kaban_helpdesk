<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php $page_title = "KabanDesk"; ?>
<?php

$allowed_roles = ['IT Supervisor','IT Manager', 'Administrator'];
if (empty($_SESSION['login_id']) || !in_array($_SESSION['role'] ?? '', ['IT Supervisor', 'IT Manager', 'Administrator'])) {
    header('Location: index.php');
    exit;
}

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

?>
<style>
    .kpi-card { border-radius: 8px; padding: 18px 14px; text-align: center; border: 1.5px solid transparent; height: 100%; }
    .kpi-num  { font-size: 28px; font-weight: 700; line-height: 1.1; }
    .kpi-lbl  { font-size: 12px; color: #666; margin-top: 6px; text-transform: uppercase; letter-spacing: .4px; }
    .kpi-hint { font-size: 11px; color: #999; margin-top: 4px; }
    .kpi-neutral { background: #f4f2f7; border-color: #e3dcec; } .kpi-neutral .kpi-num { color: #431765; }
    .kpi-good    { background: #e6f7ec; border-color: #b9e4c8; } .kpi-good .kpi-num    { color: #1e8a4c; }
    .kpi-warn    { background: #fdf1e0; border-color: #f3d9a8; } .kpi-warn .kpi-num    { color: #c07d00; }
    .kpi-bad     { background: #fdecea; border-color: #f2bcb6; } .kpi-bad .kpi-num     { color: #c0392b; }
    .chart-wrap  { position: relative; height: 280px; }
</style>

<div class="container-fluid py-4 px-4 mt-5">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-2">Key Performance Indicators</h4>
        <div class="btn-group" id="kpiRange">
            <?php
            // Define the buttons data in an array
            // Format: 'days' => is_active (true/false)
            $daysButtons = [
                7  => false, 
                14 => false, 
                30 => true, 
                60 => false, 
                90 => false,
            ];
            ?>

            <div class="btn-group" role="group">
                <?php foreach ($daysButtons as $days => $isActive): ?>
                    <?php 
                        $activeClass = $isActive ? ' active' : ''; 
                    ?>
                    <button type="button" class="btn btn-sm btn-outline-purple btn-days<?= $activeClass ?>" data-days="<?= $days ?>">
                        Last <?= $days ?> days
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div id="kpiError" class="alert alert-danger d-none"></div>

    <!-- KPI cards -->
    <div class="row" id="kpiCards">
        <div class="col-12 text-center text-muted py-4">Loading KPIs...</div>
    </div>

    <!-- Charts -->
    <div class="row mt-2">
        <div class="col-lg-8 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="font-weight-bold">Tickets Created vs Resolved</h6>
                    <div class="chart-wrap"><canvas id="trendChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="font-weight-bold">By Category</h6>
                    <div class="chart-wrap"><canvas id="categoryChart"></canvas></div>
                    <div class="mt-3" id="priorityChips"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Per-agent table -->
    <div class="card mb-4"><div class="card-body">
        <h6 class="font-weight-bold">IT Support Performance</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <?php
                            $thead = explode(",","IT Support Specialist, Assigned, Resolved, Avg Resolution, Resolved On Time");
                            foreach ($thead as $th) {
                                echo "<th>{$th}</th>";
                            }
                        ?>
                    </tr>
                </thead>
                <tbody id="agentRows">
                    <tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        <small class="text-muted">
            On-time % counts tickets resolved before their deadline, plus tickets already past their deadline that are still unresolved (counted as misses).
            Tickets are credited to their current assignee.
        </small>
    </div></div>

</div>

<?php include("includes/footer.php"); ?>
<script src="/assets/js/chart.umd.min.js"></script>
<script>
$(document).ready(function () {
    var trendChart = null;
    var categoryChart = null;

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    // minutes -> "2d 4h 10m" / "3h 5m" / "12m"
    function fmtMin(m) {
        if (m === null || m === undefined) return '—';
        m = Math.round(m);
        if (m < 60) return m + 'm';
        if (m < 1440) return Math.floor(m / 60) + 'h ' + (m % 60) + 'm';
        var d = Math.floor(m / 1440), r = m % 1440;
        return d + 'd ' + Math.floor(r / 60) + 'h ' + (r % 60) + 'm';
    }

    function pctText(v) { return (v === null || v === undefined) ? '—' : v + '%'; }

    // Starter thresholds, tune to what your IT Manager wants
    function tone(v, good, warn, higherIsBetter) {
        if (v === null || v === undefined) return 'kpi-neutral';
        if (higherIsBetter) return v >= good ? 'kpi-good' : (v >= warn ? 'kpi-warn' : 'kpi-bad');
        return v <= good ? 'kpi-good' : (v <= warn ? 'kpi-warn' : 'kpi-bad');
    }

    function card(label, value, cls, hint) {
        return '<div class="col-6 col-lg-3 mb-3"><div class="kpi-card ' + cls + '">' +
               '<div class="kpi-num">' + value + '</div>' +
               '<div class="kpi-lbl">' + label + '</div>' +
               (hint ? '<div class="kpi-hint">' + hint + '</div>' : '') +
               '</div></div>';
    }

    function renderCards(k) {
        var html = '';
        html += card('Response SLA',   pctText(k.response_sla_pct),   tone(k.response_sla_pct, 90, 75, true),   'Picked up within target');
        html += card('Resolution SLA', pctText(k.resolution_sla_pct), tone(k.resolution_sla_pct, 90, 75, true), 'Resolved before deadline');
        html += card('Avg First Response', fmtMin(k.avg_response_min),   'kpi-neutral', 'Created → In Progress');
        html += card('Avg Resolution',     fmtMin(k.avg_resolution_min), 'kpi-neutral', 'Created → Resolved');
        html += card('Tickets Created',  k.created,  'kpi-neutral', '');
        html += card('Tickets Resolved and Closed', k.resolved, 'kpi-neutral', '');
        html += card('Tickets Cancelled or Deleted', k.cancelled, 'kpi-neutral', '');
        html += card('Escalation Rate', pctText(k.escalation_rate), tone(k.escalation_rate, 10, 25, false), 'Of tickets created');
        html += card('Reopen Rate',     pctText(k.reopen_rate),     tone(k.reopen_rate, 5, 10, false),     'Of resolved tickets');
        $('#kpiCards').html(html);
    }

    function renderTrend(t) {
        if (trendChart) trendChart.destroy();
        trendChart = new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: t.labels,
                datasets: [
                    { label: 'Created',  data: t.created,  borderColor: '#431765', backgroundColor: 'rgba(67,23,101,.1)', tension: .3, fill: true },
                    { label: 'Resolved', data: t.resolved, borderColor: '#1e8a4c', backgroundColor: 'rgba(30,138,76,.1)', tension: .3, fill: true }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { maxTicksLimit: 10 } } }
            }
        });
    }

    function renderCategory(list) {
        if (categoryChart) categoryChart.destroy();
        categoryChart = new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: {
                labels: list.map(function (r) { return r.name; }),
                datasets: [{ data: list.map(function (r) { return r.count; }), backgroundColor: '#431765' }]
            },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    function renderPriority(list) {
        var colors = { Critical: '#c0392b', High: '#e67e22', Medium: '#27ae60', Low: '#2980b9' };
        var html = '';
        list.forEach(function (p) {
            html += '<span class="badge mr-1 mb-1" style="background:' + (colors[p.priority] || '#777') + ';color:#fff;">' +
                    esc(p.priority) + ': ' + p.count + '</span>';
        });
        $('#priorityChips').html(html);
    }

    function renderAgents(list) {
        if (!list.length) {
            $('#agentRows').html('<tr><td colspan="5" class="text-center text-muted">No IT Support Specialists found.</td></tr>');
            return;
        }
        var rows = '';
        list.forEach(function (a) {
            rows += '<tr>' +
                '<td>' + esc(a.name) + '</td>' +
                '<td>' + a.assigned + '</td>' +
                '<td>' + a.resolved + '</td>' +
                '<td>' + fmtMin(a.avg_resolution_min) + '</td>' +
                '<td>' + pctText(a.on_time_pct) + '</td>' +
                '</tr>';
        });
        $('#agentRows').html(rows);
    }

    function loadKpis(days) {
        $('#kpiError').addClass('d-none');
        $.ajax({
            url: './api/get_kpis.php',
            type: 'GET',
            data: { days: days },
            dataType: 'json',
            success: function (res) {
                if (res.status !== 'success') {
                    $('#kpiError').text(res.message || 'Failed to load KPIs.').removeClass('d-none');
                    return;
                }
                renderCards(res.kpis);
                renderTrend(res.trend);
                renderCategory(res.by_category);
                renderPriority(res.by_priority);
                renderAgents(res.agents);
            },
            error: function (xhr, status, error) {
                 console.log("Error: " + error);
                console.log("Ajax Error: " + xhr.responseText);
                console.log("Ajax Status: " + status);
                $('#kpiError').text('Failed to load KPIs.').removeClass('d-none');
            }
        });
    }

    $('#kpiRange').on('click', '.btn-days', function () {
        $('#kpiRange .btn-days').removeClass('active');
        $(this).addClass('active');
        loadKpis($(this).data('days'));
    });

    loadKpis(30);
});
</script>