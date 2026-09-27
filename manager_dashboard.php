<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
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
        <div class="col-md-12">
           <div class="row mt-5">
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
                <!-- Configuration Settings-->
                <hr>
                <div class="note border border-secondary col-md-3 mb-4">
                    <strong>Configuration Shortuts</strong>
                </div>
                <div class="row">
                    <?php foreach ($config_shorcuts as $shortcuts): ?>
                        <div class="col-md-4 mt-2 hvr-pulse">
                            <div class="mt-3 kaban-color" id="<?= $shortcuts['id'] ?>" style="cursor: pointer;">
                                <div class="p-4 text-white text-center">
                                    <span class="fa <?= $shortcuts['icon'] ?>" style="font-size: 1rem;"></span>
                                    <span><?= $shortcuts['title'] ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>

                <hr>
                <div class="note border border-secondary col-md-3 mb-2">
                    <strong>Ticket Volume Trend (Weekly)</strong>
                </div>
                <div class="row">
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-body">
                                <div id="chart-container" style="width: 600px; height: 300px;">
                                    <canvas id="categoryChart"></canvas>
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
<script src="./assets/js/addons-pro/chat.min.js"></script>
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