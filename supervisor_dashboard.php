<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("library/functions.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("includes/modal.php") ;?>
<?php include("library/stats.php"); ?>
<?php $page_title = "KabanDesk"; ?>
<?php

// ---- Chart: Tickets by Category (30 Days) ----
$sql = "SELECT c.name AS category_name, c.code AS category_code, COUNT(*) AS cnt
        FROM tickets t
        JOIN categories c ON c.id = t.category_id
        WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY c.id, c.name, c.code
        ORDER BY cnt DESC";
$by_category = retrieve($sql, []);

$category_palette = [
    '#4e73df', '#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc',
    '#fd7e14', '#6f42c1', '#20c997', '#e83e8c', '#858796',
    '#17a2b8', '#d63384', '#198754', '#7a07e6',
];

$js_category_labels = json_encode(array_column($by_category, 'category_code'));
$js_category_data    = json_encode(array_map('intval', array_column($by_category, 'cnt')));
$js_category_colors  = json_encode(array_map(
    fn($i) => $category_palette[$i % count($category_palette)],
    array_keys($by_category)
));

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
                        <div class="mt-3 kaban-color">
                            <div class="p-3 text-white text-center">
                                <span class="large-text"><?= $stats['open_tickets'] ?></span>
                                <br><span>Open Tickets (Team)</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">    
                        <div class="mt-3" style="background-color: #F77F00;">
                            <div class="p-3 text-white text-center">
                                <span class="large-text"><?= $stats['overdue_count'] ?></span>
                                <br><span>Overdue (SLA)</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mt-3" style="background-color: #D62828;">
                            <div class='p-3 text-white text-center'>
                                <span class="large-text font-weight-bold"><?= $complianceRate ?>%</span>
                                <br><span class="font-weight-bold">SLA Compliance</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">    
                        <div class="mt-3" style="background-color: #07DD05;">
                            <div class='p-3 text-white text-center'>
                                <span class="large-text"><?= $avgHoursDisplay ?></span>
                                <br><span>Average Resolution Time</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3" id="attentionAlertContainer"></div>
                 <hr>
                <div class="note border border-secondary col-md-3 mb-2">
                    <strong>Configuration Shortuts</strong>
                </div>
                <div class="row">
                    <div class="col-md-3 mt-2 hvr-pulse">
                        <div class="mt-3 secondary-color" id="all_tickets" style="cursor: pointer;">
                            <div class="p-4 text-white text-center">
                                <span class="fa fa-ticket" style="font-size: 1rem;"></span>
                                <span>All Tickets</span>
                            </div>
                        </div>
                    </div>

                     <div class="col-md-3 mt-2 hvr-pulse">
                        <div class="mt-3 secondary-color" id="knowledge_base" style="cursor: pointer;">
                            <div class="p-4 text-white text-center">
                                <span class="fa fa-book-open" style="font-size: 1rem;"></span>
                                <span>Knowledge Base</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mt-2 hvr-pulse">
                        <div class="mt-3 secondary-color" id="change_request" style="cursor: pointer;">
                            <div class="p-4 text-white text-center">
                                <span class="fa fa-refresh" style="font-size: 1rem;"></span>
                                <span>Change Request</span>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="row g-3 mt-3">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header p-3 white-text kaban-color">Unassigned Tickets</div>
                            <div class="card-body">
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
                <div class="row g-3">

                    <div class="col-lg-6 mt-3">
                        <div class="card">
                            <div class="card-header p-3 white-text kaban-color">IT Support Overload</div>
                            <div class="card-body">
                                <table class="table table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0">
                                    <thead>
                                        <?php
                                            $thead=explode(",","IT Officer, Open, Overdue");
                                            foreach ($thead as $th_value) {
                                                echo "<th>".$th_value."</th>";
                                            }
                                        ?>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $it_work_overload = retrieve(
                                                "SELECT u.id, u.name,
                                                        SUM(CASE WHEN t.status NOT IN ('Resolved','Closed') THEN 1 ELSE 0 END) AS open_count,
                                                        SUM(CASE WHEN t.resolution_due_at < NOW() AND t.status NOT IN ('Resolved','Closed') THEN 1 ELSE 0 END) AS overdue_count
                                                FROM users u
                                                LEFT JOIN tickets t ON t.assigned_to = u.id
                                                WHERE u.role = 'IT Support Specialist'
                                                GROUP BY u.id, u.name
                                                ORDER BY open_count DESC",
                                                array());

                                            foreach ($it_work_overload as $it_support) {
                                                $rowClass = $it_support['overdue_count'] > 0 ? 'table-danger' : ($it_support['open_count'] >= 8 ? 'table-warning' : '');
                                                echo "
                                                    <tr class='".$rowClass."'>
                                                        <td>".$it_support['name']."</td>
                                                        <td>".$it_support['open_count']."</td>
                                                        <td>".$it_support['overdue_count']."</td>
                                                    </tr>
                                                ";
                                            }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mt-3">
                        <div class="card">
                            <div class="card-header p-3 white-text kaban-color">Escalations</div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <thead>
                                        <?php
                                            $thead=explode(",","Ticket #, Subject, From, Status");
                                            foreach ($thead as $th_value) {
                                                echo "<th>".$th_value."</th>";
                                            }
                                        ?>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $escalations = retrieve(
                                                "SELECT t.id, t.ticket_number, t.subject, u.name AS escalated_by_name, t.escalation_reason, t.status
                                                FROM tickets t
                                                INNER JOIN users u ON t.escalated_by = u.id
                                                WHERE t.status = 'Escalated'
                                                ORDER BY t.updated_at DESC",
                                                array());
                                            if (count($escalations) > 0) {
                                                foreach ($escalations as $esc) {
                                                    echo "<tr style='cursor:pointer;' onclick=\"window.location='ticket_detail.php?id=".$esc['id']."'\">
                                                        <td>".$esc['ticket_number']."</td>
                                                        <td>".$esc['subject']."</td>
                                                        <td>".$esc['escalated_by_name']."</td>
                                                        <td class='text-secondary'>".$esc['status']."</td>
                                                    </tr>";
                                                }
                                            } else {
                                                echo "<tr>
                                                        <td colspan='4' class='text-center'>
                                                            <h5 class='alert alert-info'>
                                                                <span class='fa fa-info-circle'></span> No escalated tickets
                                                            </h5>
                                                        </td>
                                                    </tr>";
                                            }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                
                <div class="row mt-3">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header p-3 white-text kaban-color">Tickets by Category</div>
                            <div class="card-body">
                                <div id="chart-container" style="width: 1000px; height: 500px;">
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
<script>
$(document).ready(function(){

    const categoryChart = new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: <?= $js_category_labels ?>,
            datasets: [{
                label: 'Tickets',
                data: <?= $js_category_data ?>,
                backgroundColor: <?= $js_category_colors ?>,
                borderRadius: 4
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    $("#knowledge_base").click(function(e){
        window.location="manage_kb_articles.php";
    });

    $("#change_request").click(function(e){
        window.location="manage_change_request.php";
    });

    $("#all_tickets").click(function(e){
        window.location="all_tickets.php";
    });

})
</script>