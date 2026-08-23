<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("library/functions.php"); ?>
<?php include("includes/navbar.php"); ?>
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
                                <br><span>Open Tickets (Team)</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">    
                        <div class="mt-3" style="background-color: #F77F00;">
                            <div class="p-3 text-white text-center">
                                <span class="large-text">0</span>
                                <br><span>Overdue (SLA)</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mt-3" style="background-color: #D62828;">
                            <div class='p-3 text-white text-center'>
                                <span class="large-text font-weight-bold">12</span>
                                <br><span class="font-weight-bold">SLA Compliance</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">    
                        <div class="mt-3" style="background-color: #07DD05;">
                            <div class='p-3 text-white text-center'>
                                <span class="large-text">0</span>
                                <br><span>Average Resolution Time</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3" id="attentionAlertContainer"></div>

                <div class="row g-3">

                    <div class="col-lg-7">
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

                    <div class="col-lg-5">
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