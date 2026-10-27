<?php $skip_activity = true;  ?>
<?php include("includes/header.php"); ?>
<link rel="stylesheet" href="/assets/css/server_monitor.css">
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php include("includes/modal.php") ;?>
<?php include("library/stats.php"); ?>
<?php $page_title = "KabanDesk"; ?>
<?php

// ---- Chart: Ticket Volume Trend (Weekly) ----
$sql = "SELECT YEARWEEK(t.created_at, 1) AS yw,
               MIN(DATE(t.created_at)) AS week_start,
               COUNT(*) AS cnt
        FROM tickets t
        WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
        GROUP BY yw
        ORDER BY yw";
$weekly_volume = retrieve($sql, []);

$js_weekly_volume_labels = json_encode(array_column($weekly_volume, 'week_start'));
$js_weekly_volume_data   = json_encode(array_map('intval', array_column($weekly_volume, 'cnt')));

?>
<div class="container mt-5">
    <div class="row mx-auto">
        <div class="col-md-12 mt-5">
           <div class="row">
                <h2 class="text-center">Hello, <?php echo $name; ?></h2>
            </div>
            <span><?php echo $role; ?></span>
            <hr>
            <section>
                <div class="row">
                    <div class="col-md-3">    
                        <div class="mt-3 border border-secondary">
                            <div class="p-2 text-center">
                                <span style="font-size: 30px;"><?= $count_staff; ?></span>
                                <br><span>Total Staff Accounts</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">    
                        <div class="mt-3 border border-secondary">
                            <div class="p-2 text-center">
                                <span style="font-size: 30px;"><?= $count_tickets; ?></span>
                                <br><span>Total Tickets (All Time)</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mt-3 border border-secondary">
                            <div class='p-2 text-center'>
                                <span class="font-weight-bold" style="font-size: 30px;"><?= $count_it_agents; ?></span>
                                <br><span class="font-weight-bold">Active IT Support</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">    
                        <div class="mt-3 border border-secondary">
                            <div class='p-2 text-center'>
                                <span style="font-size: 30px;">10</span>
                                <br><span>Categories / SLA Rules</span>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <?php if ($role == "IT Manager") { ?>
                <div class="row g-3 mt-3">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header p-3 white-text kaban-color">Unassigned Tickets</div>
                            <div class="card-body">
                                <div class="mt-3" id="overdueAlertContainer"></div>
                                <table class="table table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0">
                                    <thead>
                                        <?php
                                            $thead=explode(",","Ticket #, Subject, Priority, Waiting, Action");
                                            foreach ($thead as $th_value) {
                                                echo "<th>".$th_value."</th>";
                                            }
                                        ?>
                                    </thead>
                                    <tbody id="unassignedTicketsBody">
                                       <tr>
                                            <td colspan="5" class="text-center">Loading unassigned tickets...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <?php  } else { ?>
                <?php } ?>
                <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                    <div class="note note-info col-md-12 mb-0">
                        <strong>Ticket Volume Trend (Weekly)</strong>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div id="chart-container" style="width: 1000px; height: 500px;">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 mb-3 server-health-card is-loading" id="server-health-card">
                            <div class="card-header p-3 kaban-color text-white d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">Server Health</span>
                                <span id="server-status-badge"
                                    class="server-status-pill is-unreachable"
                                    role="status"
                                    aria-live="polite">
                                <span class="status-dot" aria-hidden="true"></span>
                                <span id="server-status-text">Checking…</span>
                                </span>
                            </div>
                            <div class="card-body p-3">
                                <div class="py-2 border-bottom" aria-labelledby="server-cpu-label">
                                <div class="d-flex justify-content-between align-items-baseline">
                                    <span id="server-cpu-label" class="metric-label">CPU Usage</span>
                                    <span id="server-cpu" class="metric-value fw-bold text-dark">–</span>
                                </div>
                                <div class="metric-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="server-cpu-bar-wrap">
                                    <div id="server-cpu-bar" class="metric-bar-fill level-ok"></div>
                                </div>
                                </div>

                                <div class="py-2 border-bottom" aria-labelledby="server-ram-label">
                                <div class="d-flex justify-content-between align-items-baseline">
                                    <span id="server-ram-label" class="metric-label">RAM Usage</span>
                                    <span id="server-ram" class="metric-value fw-bold text-dark">–</span>
                                </div>
                                <div class="metric-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="server-ram-bar-wrap">
                                    <div id="server-ram-bar" class="metric-bar-fill level-ok"></div>
                                </div>
                                </div>

                                <div class="py-2" aria-labelledby="server-disk-label">
                                <div class="d-flex justify-content-between align-items-baseline">
                                    <span id="server-disk-label" class="metric-label">Disk Usage</span>
                                    <span id="server-disk" class="metric-value fw-bold text-dark">–</span>
                                </div>
                                <div class="metric-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="server-disk-bar-wrap">
                                    <div id="server-disk-bar" class="metric-bar-fill level-ok"></div>
                                </div>
                                </div>

                                <div class="mt-3 pt-2 border-top">
                                <small id="server-updated" class="text-muted" style="font-size: 0.75rem;">
                                    Waiting for data…
                                </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 mb-3">
                            <div class="card-header p-3 kaban-color text-white d-flex justify-content-between align-items-center">
                                <span>Recent Activity Logs</span>
                                <span><a class="btn btn-outline-info btn-sm" href="logs.php">Logs</a></span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0 custom-activity-table">
                                        <thead>
                                            <tr>
                                                <th scope="col" class="ps-4">ACTION</th>
                                                <th scope="col">BY</th>
                                                <th scope="col" class="pe-4">WHEN</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recentActivityLogs">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
<?php include("includes/footer.php"); ?>
<script src="/assets/js/chart.umd.min.js"></script>
<script>
$(document).ready(function(){

    $("#all_tickets").click(function(e){
        window.location="all_tickets.php";
    });

    $("#manage_users").click(function(){
        window.location.href="manage_users.php";
    });
    $("#manage_cat_sla").click(function(){
        window.location="manage_categories_sla.php";
    });

    $("#full_reports").click(function(e){
        window.location="full_reports.php";
    });

    $("#knowledge_base").click(function(e){
        window.location="manage_kb_articles.php";
    });

    $("#change_request").click(function(e){
        window.location="manage_change_request.php";
    });

    $("#user_control").click(function(e){
        window.location="manage_user_control.php";
    });

    $("#logs").click(function(e){
        window.location="logs.php";
    });

    const weeklyVolumeChart = new Chart($("#categoryChart")[0], {
        type: 'bar',
        data: {
            labels: <?= $js_weekly_volume_labels ?>,
            datasets: [{
                label: 'Tickets Created',
                data: <?= $js_weekly_volume_data ?>,
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78,115,223,.15)',
                fill: true,
                tension: .3
            }]
        },
        options: {
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });
})
</script>