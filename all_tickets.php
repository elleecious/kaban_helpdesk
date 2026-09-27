<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php include("library/stats.php"); ?>
<?php $page_title = "KabanDesk"; ?>

<div class="mt-5">
    <div class="row mx-auto">
        <div class="col-md-12 mb-2">
            <div class="row mt-5">
                <div class="col-md-12 mb-2">
                    <div class="card">
                        <div class="card-header p-3 kaban-color text-white">
                            All Tickets
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <table class="table table-striped table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0" id="tblAllTickets">
                                        <thead>
                                            <tr>
                                                <?php
                                                    $stud_head=explode(",","Ticket Number,Subject,Category,Priority,Status,SLA, Requested By, Assigned To, Date Created,Action");
                                                    foreach($stud_head as $stud_val)
                                                    {
                                                        echo "<th>".$stud_val."</th>";
                                                    }
                                                ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                $all_tickets = retrieve("SELECT t.id,
                                                                t.ticket_number,
                                                                t.subject,cat.name AS category,
                                                                req.name AS employee, agent.name AS it_support,
                                                                t.status, t.priority, t.resolution_due_at,
                                                                CASE
                                                                    WHEN t.status IN ('Resolved', 'Closed') THEN 'Complete'
                                                                    WHEN t.resolution_due_at < NOW() THEN 'breached'
                                                                    WHEN t.resolution_due_at <= DATE_ADD(NOW(), INTERVAL 2 HOUR) THEN 'warning'
                                                                    ELSE 'on_track'
                                                                END AS sla_status,
                                                                t.created_at
                                                            FROM tickets t
                                                            LEFT JOIN categories cat ON t.category_id = cat.id
                                                            LEFT JOIN users req ON t.created_by = req.id
                                                            LEFT JOIN users agent ON t.assigned_to = agent.id
                                                            ORDER BY t.created_at DESC;",array());

                                                foreach ($all_tickets as $tickets) {
                                                    echo "<tr>
                                                        <td>".$tickets['ticket_number']."</td>
                                                        <td>".$tickets['subject']."</td>
                                                        <td>".$tickets['category']."</td>
                                                        <td>".$tickets['priority']."</td>
                                                        <td>".$tickets['status']."</td>
                                                        <td>".$tickets['sla_status']."</td>
                                                        <td>".$tickets['employee']."</td>
                                                        <td>".(($tickets['it_support'] == true) ? $tickets['it_support'] : 'Unknown')."</td>
                                                        <td>".$tickets['created_at']."</td>
                                                        <td>
                                                            <a href='ticket_detail.php?id=".$tickets['id']."'><span class='btn btn-info btn-sm'>View</span></a>
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
                </div>
            </div>
        </div>
    </div>
</div>
<?php include("includes/footer.php"); ?>
<script>
$(document).ready(function () {
    $("#tblAllTickets").DataTable({
		"scrollX": true,
		"info": true,
		"lengthChange": true,
		"paging": true,
		"searching": true,
        "pageLength":10,
		"order": [],
	});
});
</script>