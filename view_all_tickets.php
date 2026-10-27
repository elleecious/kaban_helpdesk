<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>

<?php $page_title = "KabanDesk"; ?>
<div class="container mt-5">
    <div class="row mx-auto">
        <div class="col-md-12">
            <div class="row mt-5">
                <h3 class="text-center">All of your Tickets</h3>
            </div>
            <hr>
            <section>
                <?php
                    $getAllTickets = retrieve("SELECT t.id AS ticked_id, t.created_by AS emp_name, cat.name AS category_name, t.ticket_number AS ticket_number, 
                        t.subject AS subject, t.description AS description, t.priority AS priority, t.status AS status, t.created_at AS created_at
                        FROM tickets AS t INNER JOIN categories AS cat ON t.category_id=cat.id WHERE t.created_by=?", array($login_id));

                    if ($getAllTickets) {
                ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover table-sm text-center" width="100%" cellspacing="0" cellpadding="0" id="tblViewTickets">
                            <thead>
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Subject</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($getAllTickets as $ticket): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($ticket['ticket_number']) ?></strong></td>
                                        <td><?= $ticket['subject'] ?></td>
                                        <td><?= htmlspecialchars($ticket['category_name']) ?></td>
                                        <td><?= htmlspecialchars($ticket['priority']) ?></td>
                                        <td><?= htmlspecialchars($ticket['status']) ?></td>
                                        <td><?= htmlspecialchars($ticket['created_at']) ?></td>
                                        <td class="text-center">
                                            <a class="btn btn-sm btn-primary btn-md" href="ticket_detail.php?id=<?= $ticket['ticked_id'] ?>">View Ticket</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php 
                    } else {
                        echo "<div class='alert alert-info text-center' role='alert'>
                                <h4 class='alert-heading'>No Tickets Found</h4>
                                <p class='mb-0'>You haven't submitted any tickets yet.</p>
                              </div>";
                    }
                ?>
            </section>
        </div>
    </div>
</div>
<?php include("includes/footer.php"); ?>
<script>
$(document).ready(function () {
    $("#tblViewTickets").DataTable({
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