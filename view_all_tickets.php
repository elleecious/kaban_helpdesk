<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php") ?>
<?php $page_title = "KabanDesk"; ?>

<div class="container mt-5">
    <div class="row mx-auto">
        <div class="col-md-12">
            <div class="row">
                <h3 class="text-center">All of your Tickets</h3>
            </div>
            <hr>
            <section>
                <div class="row">
                    <?php
                        $getAllTickets = retrieve("SELECT t.id AS ticked_id, t.created_by AS emp_name, cat.name AS category_name, t.ticket_number AS ticket_number, 
                            t.subject AS subject, t.description AS description, t.priority AS priority, t.status AS status, t.created_at AS created_at
                            FROM tickets AS t INNER JOIN categories AS cat ON t.category_id=cat.id WHERE emp_name=?",array($login_id));

                        if ($getAllTickets) {

                            for ($i=0; $i < count($getAllTickets); $i++) { 
                                echo "
                                    <div class='col-md-12'>
                                        <div class='card'>
                                            <div class='card-body'>
                                                <h5 class='card-title'>".$getAllTickets[$i]['subject']."</h5>
                                                <p>".$getAllTickets[$i]['description']."</p>
                                                <a class='text-primary' href='ticket_detail.php?id=".$getAllTickets[$i]['ticked_id']."'>View Ticket</a>
                                            </div>
                                        </div>
                                    </div>
                                ";
                            }
                        } else {
                             echo "<div class='col-md-12'>
                                        <div class='card'>
                                            <div class='card-body text-center'>
                                                <h3 class='card-title'>No Tickets Found</h3>
                                                <p class='text-muted'>You haven't submitted any tickets yet.</p>
                                            </div>
                                        </div>
                                    </div>";
                        }
                    ?>
                </div>
            </section>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>