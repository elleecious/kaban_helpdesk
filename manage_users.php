<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php $page_title="KabanDesk"; ?>

<div class="mt-5">
    <div class="row mx-auto">
        <div class="col-md-12 mb-2">
            <div class="row">
                <div class="col-md-4">
                    <div class="card rounded-0">
                        <div class="card-header p-3 white-text kaban-color">
                            Add User
                        </div>
                        <div class="card-body">
                            <form id="frmAddUser" method="post">
                                <div class="row">
                                    <div class="col-md-12 mt-2">
                                        <small>Name</small>
                                        <input class="form-control form-control-sm" type="text" name="name" id="name">
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <small>Email Address</small>
                                        <input class="form-control form-control-sm" type="email" name="email" id="email">
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <small>Role</small>
                                        <input class="form-control form-control-sm" type="text" name="role" id="role">
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <div class="md-form">
                                            <select class="mdb-select md-form" name="department" id="department">
                                                <option value="">Select Department</option>
                                                <?php
                                                    $department = array("Information Technology","Human Resources","Housekeeping","Engineering",
                                                    "Slot and Electronic Table Games","Table Games","Sales & Marketing","Cage",
                                                    "Surveillance","Warehouse","Gaming Security","Finance & Accounting",
                                                    "Building Management","Front Office","Food and Beverage","Purchasing");
                                                    sort($department);
                                                    for ($i=0; $i < count($department); $i++) { 
                                                        echo "<option value='".$department[$i]."'>".$department[$i]."</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <small>Password</small>
                                        <input class="form-control form-control-sm" type="password" name="password" id="password">
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <small>Confirm Password</small>
                                        <input class="form-control form-control-sm" type="password" name="confirm_password" id="confirm_password">
                                    </div>
                                </div>
                                <div class="d-flex flex-row mt-2">
                                    <button type="submit" class="btn btn-primary mt-3" id="add_users" name="add_users">Add</button>
                                    <button type="reset" class="btn btn-grey mt-3" id="cancel_users">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card rounded-0">
                        <div class="card-header p-3 white-text kaban-color">
                            Manage User
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0" id="tblManageUsers">
                                <thead>
                                    <tr>    
                                        <?php
                                            $thead_user = explode(",","Name, Email Address, Role, Department, Username, Date Created, Actions");
                                            foreach($thead_user as $th_user){
                                                echo "<th>".$th_user."</th>";
                                            }
                                        ?>
                                    </tr>
                               </thead>
                               <tbody>
                                   <?php
                                        $get_users = retrieve("SELECT * FROM users",array());
                                        for($i=0; $i < COUNT($get_users); $i++){
                                            echo "
                                            <tr>
                                                <td>".$get_users[$i]['name']."</td>
                                                <td>".$get_users[$i]['email']."</td>
                                                <td>".$get_users[$i]['role']."</td>
                                                <td>".$get_users[$i]['department']."</td>
                                                <td>".$get_users[$i]['username']."</td>
                                                <td>".$get_users[$i]['created_at']."</td>
                                                <td>
                                                    <span class='btn btn-primary btn-sm'>Edit</span>
                                                </td>
                                            </tr>";
                                        }
                                   ?>
                               </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include("includes/footer.php"); ?>
<script>
$(document).ready(function () {
    $("#tblManageUsers").DataTable({
		"scrollX": true,
		"info": true,
		"lengthChange": true,
		"paging": true,
		"searching": true,
        "pageLength":20,
		"order": [],
	});
});
</script>