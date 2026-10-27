<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php $page_title="KabanDesk"; ?>

<div class="container-fluid mt-5">
    <div class="row mx-auto">
        <div class="col-md-12 mt-5">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="card rounded-0">
                        <div class="card-header p-3 white-text kaban-color">
                            Manage User
                        </div>
                        <div class="card-body">
                            <span class="float-right btn btn-outline-info btn-md" data-toggle="modal" data-target="#add_users_modal">Add Users</span>
                            <table class="table table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0" id="tblManageUsers">
                                <thead>
                                    <tr>    
                                        <?php
                                            $thead_user = explode(",","Name, Email Address, Role, Department, Username, Status, Is Online?, Last Activity, Last Login, Date Created, Actions");
                                            foreach($thead_user as $th_user){
                                                echo "<th>".$th_user."</th>";
                                            }
                                        ?>
                                    </tr>
                               </thead>
                               <tbody id="usersTableBody">
                                   
                               </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="add_users_modal" tabindex="-1" role="dialog" aria-labelledby="AddUsersLabel"aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header kaban-color white-text">
        <h5 class="modal-title" id="AddUsersLabel">Add Users</h5>
        <button type="button" class="close white-text" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <form id="frmAddUser" method="post">
            <div class="row">
                <div class="col-md-12">
                    <div class="md-form">
                        <input class="form-control form-control-sm" type="text" name="name" id="name">
                        <label for="name">Name</label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="md-form">
                        <input class="form-control form-control-sm" type="email" name="email" id="email">
                        <label for="email">Email Address</label>
                    </div>
                </div>
              <div class="col-md-12 mt-2">
                    <div class="md-form">
                        <select class="mdb-select md-form" name="role" id="role" searchable="Search here..">
                            <option value="">Select Role</option>
                            <?php
                                // Fetch and parse JSON content
                                $json_data = file_get_contents('./data/roles.json');
                                $roles = json_decode($json_data, true);

                                if (is_array($roles)) {
                                    sort($roles);
                                    foreach ($roles as $role) {
                                        $role = htmlspecialchars(trim($role));
                                        echo "<option value='{$role}'>{$role}</option>";
                                    }
                                }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-12 mt-2">
                    <div class="md-form">
                        <select class="mdb-select md-form" name="department" id="department" searchable="Search here..">
                            <option value="">Select Department</option>
                            <?php
                                $department = array("Information Technology","Human Resources","Housekeeping","Engineering",
                                "Slot and Electronic Table Games","Table Games","Sales & Marketing","Cage",
                                "Surveillance","Warehouse","Gaming Security","Finance & Accounting",
                                "Building Management","Front Office","Food and Beverage","Purchasing", "Upper Management");
                                sort($department);
                                for ($i=0; $i < count($department); $i++) { 
                                    echo "<option value='".$department[$i]."'>".$department[$i]."</option>";
                                }
                            ?>
                        </select>
                        <label for="department">Department</label>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-row mt-2">
                <button type="submit" class="btn btn-primary mt-3" id="add_users" name="add_users">Add</button>
                <button type="button" class="btn btn-danger mt-3" data-dismiss="modal">Cancel</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Resetting password for: <strong id="resetPasswordName"></strong></p>

                <label>New Password</label>
                <div class="input-group">
                    <input type="text" class="form-control form-control-md" id="newPassword" placeholder="Enter or generate a password">
                    <div class="input-group-append" style="margin-top: -10px;">
                        <button class="btn btn-outline-secondary btn-md" type="button" id="btnGeneratePassword">Generate</button>
                    </div>
                </div>
                <small class="text-muted">Minimum 4 characters. Share this with the user securely — it won't be shown again.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnConfirmReset">Confirm Reset</button>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>
<script>
$(document).ready(function () {
    // $("#tblManageUsers").DataTable({
	// 	"scrollX": true,
	// 	"info": true,
	// 	"lengthChange": true,
	// 	"paging": true,
	// 	"searching": true,
    //     "pageLength":10,
	// 	"order": [],
	// });
});
</script>