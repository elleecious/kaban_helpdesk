<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php $page_title = "KabanDesk"; ?>
<div class="container">
    <div class="row mx-auto">
        <div class="col-md-12">
           <div class="row mt-5">
                <h2 class="text-center">Hello, <?php echo $name; ?></h2>
            </div>
            <span><?php echo $role; ?> • Administrator</span>
            <hr>
            <section>
                <div class="row">
                    <div class="col-md-3">    
                        <div class="mt-3 border border-secondary">
                            <div class="p-2 text-center">
                                <span style="font-size: 30px;"><?php echo $count_staff; ?></span>
                                <br><span>Total Staff Accounts</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">    
                        <div class="mt-3 border border-secondary">
                            <div class="p-2 text-center">
                                <span style="font-size: 30px;">12</span>
                                <br><span>Total Tickets (All Time)</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mt-3 border border-secondary">
                            <div class='p-2 text-center'>
                                <span class="font-weight-bold" style="font-size: 30px;">12</span>
                                <br><span class="font-weight-bold">Active IT Support</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">    
                        <div class="mt-3 border border-secondary">
                            <div class='p-2 text-center'>
                                <span style="font-size: 30px;">10</span>
                                <br><span>Categories / SLA Rules</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Configuration Settings-->
                <hr>
                <div class="note border border-secondary col-md-3 mb-4">
                    <strong>Configuration Shortuts</strong>
                </div>
                <div class="row">
                    <div class="col-md-3 hvr-pulse">    
                        <div class="card text-center kaban-color" id="manage_users" style="cursor: pointer;">
                            <div class="card-body white-text">
                                <div class="p-1">
                                    <span class="fa fa-users" style="font-size: 4rem;"></span>    
                                </div>
                                 <h5 class='card-title mt-3'>
                                    <span>Manage Users</span>
                                </h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 hvr-pulse">    
                        <div class="card text-center" id="manage_cat_sla" style="cursor: pointer; background-color: #F77F00;">
                            <div class="card-body white-text">
                                <div class="p-0">
                                    <span class="fa fa-tags" style="font-size: 4rem;"></span>
                                </div>
                                <h5 class='card-title'>
                                    <span>Manage Category and SLA Rules</span>
                                </h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 hvr-pulse">    
                        <div class="card text-center" id="manage_reports" style="cursor: pointer; background-color: #07DD05;">
                            <div class="card-body white-text">
                                <div class="p-2">
                                    <span class="fa fa-line-chart" style="font-size: 4rem;"></span>
                                </div>
                                <h5 class='card-title'>
                                    <span>Full Reports</span>
                                </h5>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mt-2 hvr-pulse">    
                        <div class="card text-center" id="logs" style="cursor: pointer; background-color: #000000;">
                            <div class="card-body white-text">
                                <div class="p-2">
                                    <span class="fa fa-history" style="font-size: 4rem;"></span>
                                </div>
                                <h5 class='card-title'>
                                    <span>Logs</span>
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <div class="note border border-secondary mb-4">
                            <strong>Ticket Volume Trend (Weekly)</strong>
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
    $("#manage_users").click(function(){
        window.location.href="manage_users.php";
    });
    $("#manage_cat_sla").click(function(){
        window.location="manage_categories_sla.php";
    });

    $("#full_reports").click(function(e){
        window.location="full_reports.php";
    });

    $("#logs").click(function(e){
        window.location="logs.php";
    });
})
</script>