<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("library/functions.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("includes/modal.php") ;?>
<?php include("library/stats.php"); ?>
<?php $page_title = "KabanDesk"; ?>
<div class="container">
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
                <div class="note border border-secondary col-md-3 mb-4">
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
                                <table class="table table-bordered">
                                    <thead>
                                        <?php
                                            $thead=explode(",","Ticket #, Subject, Priority, Waiting, Action");
                                            foreach ($thead as $th_value) {
                                                echo "<th>".$th_value."</th>";
                                            }
                                        ?>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $getUnAssignedTickets=retrieve("SELECT id, ticket_number, subject, priority, 
                                                    TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS waiting_minutes
                                            FROM tickets WHERE assigned_to IS NULL AND status = 'Open'
                                            ORDER BY FIELD(priority, 'Critical','High','Medium','Low'),
                                                created_at ASC",array());
                                            for ($i=0; $i < count($getUnAssignedTickets); $i++) { 
                                                echo "<tr>
                                                    <td>".$getUnAssignedTickets[$i]['ticket_number']."</td>
                                                    <td>".$getUnAssignedTickets[$i]['subject']."</td>
                                                    <td class='font-weight-bold ".(match($getUnAssignedTickets[$i]['priority']) {
                                                        'Low'=> 'text-low','Medium'=> 'text-medium',
                                                        'High'=> 'text-high','Critical'=> 'text-critical',
                                                    })."'>".$getUnAssignedTickets[$i]['priority']."</td>
                                                    <td>".format_waiting_time($getUnAssignedTickets[$i]['waiting_minutes'])."</td>
                                                    <td>
                                                        <a class='btn btn-primary btn-sm assign_ticket'
                                                            data-id='".$getUnAssignedTickets[$i]['id']."'
                                                            data-ticket-number='".$getUnAssignedTickets[$i]['ticket_number']."'
                                                            data-subject='".$getUnAssignedTickets[$i]['subject']."'
                                                            data-toggle='modal' 
                                                            data-target='#assignTicketModal'>
                                                            Assign
                                                        </a>
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
                <div class="row g-3">

                    <div class="col-lg-6 mt-3">
                        <div class="card">
                            <div class="card-header p-3 white-text kaban-color">IT Support Overload</div>
                            <div class="card-body">
                                <table class="table table-bordered">
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
                                            $escalations = retrieve("SELECT t.id, t.ticket_number, t.subject, u.name AS escalated_by, t.status
                                                FROM tickets t
                                                JOIN users u ON t.assigned_to = u.id
                                                WHERE t.status = 'Escalated'
                                                ORDER BY t.updated_at DESC",
                                                array());
                                            if (count($getUnAssignedTickets) > 0) {
                                                foreach ($escalations as $esc) {
                                                    echo "<tr>
                                                        <td>".$esc['ticket_number']."</td>
                                                        <td>".$esc['subject']."</td>
                                                        <td>".$esc['escalated_by']."</td>
                                                        <td class='badge badge-purple'>".$esc['status']."</td>
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
                                    <canvas id="myChart"></canvas>
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