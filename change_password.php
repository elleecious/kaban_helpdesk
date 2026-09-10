<?php include('includes/header.php'); ?>
<?php include('includes/session.php'); ?>
<?php include('includes/navbar.php'); ?>
<?php $page_title = "KabanDesk"; ?>
<section>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header p-3 white-text kaban-color">
                        Change Password
                    </div>
                    <div class="card-body">
                        <form method="post" id="frmChangePass">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="md-form">
                                        <i class="fa fa-lock prefix"></i>
                                        <input class="form-control" type="password" id="current_password" name="current_password">
                                        <label for="current_password">Current Password</label>
                                    </div>
                                    <div class="md-form">
                                        <i class="fa fa-lock prefix"></i>
                                        <input class="form-control" type="password" id="new_password" name="new_password">
                                        <label for="new_password">New Password</label>
                                    </div>
                                    <div class="md-form">
                                        <i class="fa fa-lock prefix"></i>
                                        <input class="form-control" type="password" id="confirm_password" name="confirm_password">
                                        <label class="confirm_password">Confirm Password</label>
                                    </div>
                                </div>
                            </div>
                             <div class="d-flex flex-row">
                                <button type="submit" class="btn btn-primary mt-3" id="save_password" name="save_password">Submit</button>
                                <button type="reset" class="btn btn-grey mt-3" id="cancel_change_pass">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>    
        </div>
    </div>
</div>
</section>
<?php include('includes/footer.php'); ?>