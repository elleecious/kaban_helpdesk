<?php include('includes/header.php'); ?>
<?php include('includes/session.php'); ?>
<?php include('includes/navbar.php'); ?>
<?php $page_title = "KabanDesk"; ?>
<section>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12 mt-5">
                <div class="card">
                    <div class="card-header p-3 white-text kaban-color">
                        Profile
                    </div>
                    <div class="card-body mt-3">
                        <div class="row">
                            <div class="col-md-12 mx-auto">
                                <h5><?php echo $name; ?></h5>
                                <h5><?php echo $role; ?></h5>
                                <h5><?php echo $department; ?></h5>
                            </div>
                            <a class="btn btn-primary btn-md" href="change_password.php">Change Password</a>
                        </div>
                    </div>
                </div>
            </div>    
        </div>
    </div>
</div>

</section>
<?php include('includes/footer.php'); ?>