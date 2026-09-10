<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php include("includes/modal.php") ;?>
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
                                <span class="large-text">12</span>
                                <br><span>Assigned to Me</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">    
                        <div class="mt-3" style="background-color: #F77F00;">
                            <div class="p-3 text-white text-center">
                                <span class="large-text">0</span>
                                <br><span>In Progress</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mt-3" style="background-color: #D62828;">
                            <div class='p-3 text-white text-center'>
                                <span class="large-text font-weight-bold">12</span>
                                <br><span class="font-weight-bold">Overdue (SLA)</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">    
                        <div class="mt-3" style="background-color: #07DD05;">
                            <div class='p-3 text-white text-center'>
                                <span class="large-text">0</span>
                                <br><span>Resolved Today</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3" id="overdueAlertContainer"></div>
                <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                    <div class="note note-info col-md-9 mb-0">
                        <strong>My Ticket Queue</strong>
                    </div>
                    <a class="text-primary" data-toggle="modal" data-target="#unassignedTicketsModal"><span class='fa fa-plus'></span> Pick up next assigned ticket</a>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <?php
                                $thead_tickets = explode(",","Ticket #, Subject, Priority, SLA, Status, Requester, Action");
                                foreach($thead_tickets as $th_tickets){
                                    echo "<th>".$th_tickets."</th>";
                                }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $getTicketQueue = retrieve("SELECT t.id AS ticket_id, t.ticket_number, t.subject, t.priority, t.status, 
                            t.resolution_due_at,
                            TIMESTAMPDIFF(MINUTE, NOW(), t.resolution_due_at) AS minutes_left,
                            u.name AS requester_name
                            FROM tickets t
                            JOIN users u ON t.created_by = u.id
                            WHERE t.assigned_to = ? 
                            AND t.status NOT IN ('Resolved', 'Closed')
                            ORDER BY 
                                FIELD(t.priority, 'Critical','High','Medium','Low'),
                                t.resolution_due_at ASC",array($login_id));

                            for ($i=0; $i < count($getTicketQueue); $i++) { 

                                $minutesLeft = $getTicketQueue[$i]['minutes_left'];
                                if ($minutesLeft < 0) $slaDisplay = '<h5><span class="badge rounded-pill badge-danger">Overdue</span></h5>';
                                else $slaDisplay = format_waiting_time($minutesLeft) . ' left';

                                $status = $getTicketQueue[$i]['status'];
                                $statusBadge = match($status) {
                                    'In Progress' => 'badge-warning', 
                                    'Pending' => 'badge-info',
                                    'Resolved' => 'badge-success',
                                    'Closed' => 'badge-secondary',
                                    default      => 'badge-secondary'
                                };

                                echo "<tr>
                                    <td># ".$getTicketQueue[$i]['ticket_number']."</td>
                                    <td> ".$getTicketQueue[$i]['subject']."</td>
                                    <td> ".$getTicketQueue[$i]['priority']."</td>
                                    <td> ".$slaDisplay."</td>
                                    <td><h5><span class='badge ".$statusBadge."'>".$status."</span></h5></td>
                                    <td> ".$getTicketQueue[$i]['requester_name']."</td>
                                    <td><a href='ticket_detail.php?id=".$getTicketQueue[$i]['ticket_id']."'>View</a></td>
                                </tr>";
                            }
                        ?>
                    </tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                    <div class="note note-info col-md-12 mb-0">
                        <strong>Recently Resolved by Me</strong>
                    </div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <?php
                                $thead_tickets = explode(",","Ticket #, Subject, Resolved");
                                foreach($thead_tickets as $th_tickets){
                                    echo "<th>".$th_tickets."</th>";
                                }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $resolvedByMe = retrieve(
                                "SELECT id, ticket_number, subject, resolved_at,
                                        TIMESTAMPDIFF(MINUTE, resolved_at, NOW()) AS resolved_minutes_ago
                                FROM tickets
                                WHERE assigned_to = ? 
                                AND status = 'Resolved'
                                ORDER BY resolved_at DESC
                                LIMIT 5",
                                array($_SESSION['login_id'])
                            );

                            foreach ($resolvedByMe as $resolved) {
                                echo "<tr>
                                    <td>".$resolved['ticket_number']."</td>
                                    <td>".$resolved['subject']."</td>
                                    <td>".time_ago($resolved['resolved_minutes_ago'])."</td>
                                </tr>";
                            }
                        ?>
                    </tbody>
                </table>

            </section>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>