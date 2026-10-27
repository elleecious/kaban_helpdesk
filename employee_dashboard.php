<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php") ?>
<?php include("library/stats.php") ?>
<?php $page_title = "KabanDesk"; ?>

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
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                                    <span>Need help with something? <br>Submit a ticket</span>
                                    <a class="btn btn-outline-purple" href="create_ticket.php">Create Ticket</a>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                                    <span>Planning a change? <br>Raise a Change Request </span>
                                    <a class="btn btn-outline-purple" href="change_request_form.php">Create Change Request</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                 <div class="row">
                    <div class="col-md-3">    
                        <div class="mt-3 kaban-color">
                            <div class="p-3 text-white text-center">
                                <span class="large-text"><?= $count_user_tickets; ?></span>
                                <br><span>Total Tickets Sent</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">    
                        <div class="mt-3 kaban-color">
                            <div class="p-3 text-white text-center">
                                <span class="large-text"><?= $count_user_open_tickets; ?></span>
                                <br><span>Open</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mt-3 kaban-color">
                            <div class='p-3 text-white text-center'>
                                <span class="large-text"><?= $count_user_closed_tickets; ?></span>
                                <br><span>Closed</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">    
                        <div class="mt-3 kaban-color">
                            <div class='p-3 text-white text-center'>
                                <span class="large-text"><?= $count_user_awaiting; ?></span>
                                <br><span>Awaiting for Response</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                    <div class="note kaban-color white-text mb-0">
                        <strong>Recent Tickets</strong>
                    </div>
                    <a class="btn text-white" style="background-color: #2a0b3b;" href="view_all_tickets.php">View All</a>
                </div>
                <table class="table table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0" id="tblRecentTickets">
                    <thead class="thead">
                        <tr>
                            <th>Ticket #</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Status</th>	
                            <th>Date Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $getTickets = retrieve("SELECT t.id AS ticket_id, cat.name AS category_name, t.ticket_number AS ticket_number, 
                        t.subject AS subject, t.priority AS priority, t.status AS status, t.created_at AS created_at
                        FROM tickets AS t INNER JOIN categories AS cat ON t.category_id=cat.id
                        WHERE t.created_by=? ORDER BY t.created_at DESC LIMIT 5", array($login_id));

                            if (count($getTickets) > 0) {
                                foreach ($getTickets as $ticket) {
                                    // Show edit button only if status is NOT 'Resolved'
                                    $resolved_closed = (!in_array($ticket['status'], ['Resolved', 'Closed','Deleted','In Progress'])) 
                                        ? "<a class='btn btn-outline-primary btn-sm m-1' href='edit_ticket.php?id=" . htmlspecialchars($ticket['ticket_id'], ENT_QUOTES, 'UTF-8') . "'>
                                            Edit
                                        </a>
                                        <span class='btn btn-outline-danger btn-sm m-1 delete_ticket' delete_ticket_id='" . htmlspecialchars($ticket['ticket_id'], ENT_QUOTES, 'UTF-8') . "'>
                                            Delete
                                        </span>" 
                                        : "";

                                    echo "<tr>
                                            <td>" . htmlspecialchars($ticket['ticket_number'], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . $ticket['subject'] . "</td>
                                            <td>" . htmlspecialchars($ticket['category_name'], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . htmlspecialchars($ticket['status'], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . htmlspecialchars($ticket['created_at'], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>
                                                <a class='btn btn-outline-info btn-sm m-1' href='ticket_detail.php?id=" . htmlspecialchars($ticket['ticket_id'], ENT_QUOTES, 'UTF-8') . "'>
                                                    View
                                                </a>
                                                {$resolved_closed}
                                            </td>
                                        </tr>";
                                }
                            } else {
                                echo "<tr>
                                    <td colspan='6' class='text-center'>
                                        <h3 class='alert alert-warning'>
                                            <span class='fa fa-info-circle'></span> No tickets sent
                                        </h3>
                                    </td>
                                </tr>";
                            }
                        ?>
                    </tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                    <div class="note kaban-color white-text mb-0">
                        <strong>Recent Change Requests</strong>
                    </div>
                    <a class="btn text-white" style="background-color: #2a0b3b;" href="view_all_change_requests.php">View All</a>
                </div>
                <table class="table table-bordered table-sm text-center mb-3" width="100%" cellspacing="0" cellpadding="0" id="tblRecentChangeRequests">
                    <thead class="thead">
                        <tr>
                            <th>Change Request Number</th>
                            <th>Subject</th>
                            <th>Change Type</th>
                            <th>Status</th>	
                            <th>Date Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $getChangeRequest = retrieve("SELECT cr.*, u.name AS requestor_name
                                    FROM change_requests cr
                                    LEFT JOIN users u ON u.id = cr.requestor_id
                                    WHERE cr.requestor_id = ?", array($login_id));

                            if (count($getChangeRequest) > 0) {
                                foreach ($getChangeRequest as $change_request) {

                                    $resolved_closed_cr = ($change_request['status'] == 'Submitted'
                                        ? "<a class='btn btn-outline-primary btn-sm mr-1' href='edit_change_request.php?id=" . htmlspecialchars($change_request['id'], ENT_QUOTES, 'UTF-8') . "'>
                                            Edit
                                        </a>
                                        <span class='btn btn-outline-danger btn-sm mr-1 delete_change_request' delete_change_request_id='" . htmlspecialchars($change_request['id'], ENT_QUOTES, 'UTF-8') . "'>
                                            Delete
                                        </span>" 
                                        : "");

                                    echo "<tr>
                                        <td>".htmlspecialchars($change_request['crf_number'])."</td>
                                        <td>".$change_request['change_title']."</td>
                                        <td>".htmlspecialchars($change_request['change_type'])."</td>
                                        <td>".htmlspecialchars($change_request['status'])."</td>
                                        <td>".htmlspecialchars($change_request['created_at'])."</td>
                                        <td>
                                            <a class='btn btn-outline-info btn-sm mr-1' href='view_change_request.php?id=".htmlspecialchars($change_request['id'])."'>
                                                View
                                            </a>
                                            {$resolved_closed_cr}
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr>
                                    <td colspan='6' class='text-center'>
                                        <h3 class='alert alert-warning'>
                                            <span class='fa fa-info-circle'></span> No change request sent
                                        </h3>
                                    </td>
                                </tr>";
                            }
                        ?>
                    </tbody>
                </table>

            </section>
        </div>
    </div>
</div>
<?php include("includes/footer.php") ?>
<script>
$(document).ready(function () {
    $("#tblRecentTickets").DataTable({
		"scrollX": true,
		"info": true,
		"lengthChange": true,
		"paging": true,
		"searching": true,
        "pageLength":5,
		"order": [4,"desc"],
	});
    
    $("#tblRecentChangeRequests").DataTable({
		"scrollX": true,
		"info": true,
		"lengthChange": true,
		"paging": true,
		"searching": true,
        "pageLength":5,
		"order": [4,"desc"],
	});
});
</script>