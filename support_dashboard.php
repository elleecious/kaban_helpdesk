<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
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
                <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                    <div class="note note-info col-md-6 mb-0">
                        <strong>My Ticket Queue</strong>
                    </div>
                    <a class="text-primary" data-toggle="modal" data-target="#unassignedTicketsModal"><span class='fa fa-plus'></span> Pick up next assigned ticket</a>
                </div>

                <div id="overdueAlertContainer"></div>
            </section>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>