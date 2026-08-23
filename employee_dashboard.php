<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php") ?>
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
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                                    <span>Need help with something? <br>Submit a new ticket</span>
                                    <a class="btn kaban-color white-text" href="create_ticket.php">New Ticket</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                 <div class="row">
                    <div class="col-md-3">    
                        <div class="mt-3 kaban-color">
                            <div class="p-3 text-white text-center">
                                <span class="large-text">12</span>
                                <br><span>Total Tickets Sent</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">    
                        <div class="mt-3 kaban-color">
                            <div class="p-3 text-white text-center">
                                <span class="large-text">0</span>
                                <br><span>Open</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mt-3 kaban-color">
                            <div class='p-3 text-white text-center'>
                                <span class="large-text">12</span>
                                <br><span>Resolved</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">    
                        <div class="mt-3 kaban-color">
                            <div class='p-3 text-white text-center'>
                                <span class="large-text">0</span>
                                <br><span>Awaiting for Response</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                    <div class="note kaban-color white-text mb-0">
                        <strong>Recent Tickets</strong>
                    </div>
                    <a class="btn btn-grey" href="view_all_tickets.php">View All</a>
                </div>
                <table class="table table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0">
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
                        WHERE t.created_by=? LIMIT 5", array($login_id));

                            if (count($getTickets) > 0) {
                                for ($i=0; $i < count($getTickets); $i++) { 
                                    echo "<tr>
                                        <td>".htmlspecialchars($getTickets[$i]['ticket_number'])."</td>
                                        <td>".htmlspecialchars($getTickets[$i]['subject'])."</td>
                                        <td>".htmlspecialchars($getTickets[$i]['category_name'])."</td>
                                        <td>".htmlspecialchars($getTickets[$i]['status'])."</td>
                                        <td>".htmlspecialchars($getTickets[$i]['created_at'])."</td>
                                        <td><a class='btn btn-primary btn-sm' href='ticket_detail.php?id=".htmlspecialchars($getTickets[$i]['ticket_id'])."'>View</a></td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr>
                                    <td colspan='5' class='text-center'>
                                        <h3 class='alert alert-warning'>
                                            <span class='fa fa-info-circle'></span> No tickets sent
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