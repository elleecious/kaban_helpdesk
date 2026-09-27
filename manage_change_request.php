<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php $page_title = "KabanDesk"; ?>
<div class="mt-5">
    <div class="row mx-auto">
        <div class="col-md-12 mt-5">
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card rounded-0">
                        <div class="card-header p-3 white-text kaban-color">
                            Manage Change Requests
                        </div>
                        <div class="card-body">
                            <div class="row d-none">
                                <div class="col-md-12">
                                    <form class="row d-flex align-items-center" method="post">
                                        <div class="col-md-2">
                                            <select name="status" class="form-control form-control-sm">
                                                <option value="">Select Status</option>
                                                <?php 
                                                    $getStatus=array("Submitted","Rejected","Approved","Under Review");
                                                    foreach ($getStatus as $status): 
                                                ?>
                                                <option value="<?= $status ?>" <?= (($_GET['status'] ?? '') === $status) ? 'selected' : '' ?>><?= $status ?></option>
                                                <?php endforeach ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-primary btn-sm" name="filter">Filter</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <table class="table table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0" id="tblManageChangeRequest">
                                <thead>
                                    <tr>    
                                        <?php
                                            $thead_user = explode(",","CRF Number, Title, Department, Change Type, Status, Created By, Date Created, Actions");
                                            foreach($thead_user as $th_user){
                                                echo "<th>".$th_user."</th>";
                                            }
                                        ?>
                                    </tr>
                               </thead>
                               <tbody>
                                   <?php
                                        if ($role === "IT Supervisor") {
                                            $it_visor_get_change_request = retrieve("SELECT cr.id, cr.crf_number, cr.change_title, cr.department, cr.change_type, 
                                                    u.name, cr.status, cr.created_at
                                                FROM change_requests AS cr
                                                INNER JOIN users AS u ON cr.requestor_id = u.id
                                                WHERE cr.change_type IN (?, ?)
                                                ORDER BY cr.created_at DESC",
                                            array('Standard', 'Emergency'));
                                            foreach ($it_visor_get_change_request as $it_visor_cr) {
                                                echo "
                                                <tr>
                                                    <td>".$it_visor_cr['crf_number']."</td>
                                                    <td>".$it_visor_cr['change_title']."</td>
                                                    <td>".$it_visor_cr['department']."</td>
                                                    <td>".$it_visor_cr['change_type']."</td>
                                                    <td>".$it_visor_cr['status']."</td>
                                                    <td>".$it_visor_cr['name']."</td>
                                                    <td>".$it_visor_cr['created_at']."</td>
                                                    <td>
                                                        <a class='btn btn-primary btn-sm' href='view_change_request.php?id=".$it_visor_cr['id']."'>View</a>
                                                    </td>
                                                </tr>";
                                            }
                                        } else if ($role == "IT Manager") {
                                            $it_man_get_change_request = retrieve("SELECT cr.id, cr.crf_number, cr.change_title, cr.department, cr.change_type, 
                                                        u.name, cr.status, cr.created_at
                                                    FROM change_requests AS cr
                                                    INNER JOIN users AS u ON cr.requestor_id = u.id
                                                    WHERE cr.change_type IN (?, ?, ?)
                                                    ORDER BY cr.created_at DESC",
                                                array('Normal', 'Major', 'Emergency'));
                                            foreach ($it_man_get_change_request as $it_man_crf) {
                                                echo "
                                                <tr>
                                                    <td>".$it_man_crf['crf_number']."</td>
                                                    <td>".$it_man_crf['change_title']."</td>
                                                    <td>".$it_man_crf['department']."</td>
                                                    <td>".$it_man_crf['change_type']."</td>
                                                    <td>".$it_man_crf['name']."</td>
                                                    <td>".$it_man_crf['status']."</td>
                                                    <td>".$it_man_crf['created_at']."</td>
                                                    <td>
                                                        <a class='btn btn-primary' href='view_change_request.php?id=".$it_man_crf['id']."'>View</a>
                                                    </td>
                                                </tr>";
                                            }
                                        } else if ($role == "General Manager") {
                                            $gm_get_change_request = retrieve("SELECT cr.id, cr.crf_number, cr.change_title, cr.department, cr.change_type, 
                                                        u.name, cr.status, cr.created_at
                                                    FROM change_requests AS cr
                                                    INNER JOIN users AS u ON cr.requestor_id = u.id
                                                    WHERE cr.change_type = 'Major'
                                                    ORDER BY cr.created_at DESC",
                                                array());
                                            foreach ($gm_get_change_request as $gm_crf) {
                                                echo "
                                                <tr>
                                                    <td>".$gm_crf['crf_number']."</td>
                                                    <td>".$gm_crf['change_title']."</td>
                                                    <td>".$gm_crf['department']."</td>
                                                    <td>".$gm_crf['change_type']."</td>
                                                    <td>".$gm_crf['name']."</td>
                                                    <td>".$gm_crf['status']."</td>
                                                    <td>".$gm_crf['created_at']."</td>
                                                    <td>
                                                        <a class='btn btn-primary' href='view_change_request.php?id=".$gm_crf['id']."'>View</a>
                                                    </td>
                                                </tr>";
                                            }
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
<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
<script>
CKEDITOR.replace('kba_description');
$(document).ready(function () {
    $("#tblManageChangeRequest").DataTable({
		"scrollX": true,
		"info": true,
		"lengthChange": true,
		"paging": true,
		"searching": true,
        "pageLength":10,
		"order": [],
	});
});
</script>