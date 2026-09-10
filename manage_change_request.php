<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php $page_title = "KabanDesk"; ?>
<div class="mt-5">
    <div class="row mx-auto">
        <div class="col-md-12">
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card rounded-0">
                        <div class="card-header p-3 white-text kaban-color">
                            Manage Change Requests
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0" id="tblManageKBA">
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
                                                    AND cr.status = ?
                                                    ORDER BY cr.created_at DESC",
                                                array('Standard', 'Emergency', 'Submitted'));
                                            for($i=0; $i < COUNT($it_visor_get_change_request); $i++){
                                                echo "
                                                <tr>
                                                    <td>".$it_visor_get_change_request[$i]['crf_number']."</td>
                                                    <td>".$it_visor_get_change_request[$i]['change_title']."</td>
                                                    <td>".$it_visor_get_change_request[$i]['department']."</td>
                                                    <td>".$it_visor_get_change_request[$i]['change_type']."</td>
                                                    <td>".$it_visor_get_change_request[$i]['status']."</td>
                                                    <td>".$it_visor_get_change_request[$i]['name']."</td>
                                                    <td>".$it_visor_get_change_request[$i]['created_at']."</td>
                                                    <td>
                                                        <a class='btn btn-primary' href='view_change_request.php?id=".$it_visor_get_change_request[$i]['id']."'>View</a>
                                                    </td>
                                                </tr>";
                                            }
                                        } else if ($role === "IT Manager") {
                                            $it_man_get_change_request = retrieve("SELECT cr.id, cr.crf_number, cr.change_title, cr.department, cr.change_type, 
                                                        u.name, cr.status, cr.created_at
                                                    FROM change_requests AS cr
                                                    INNER JOIN users AS u ON cr.requestor_id = u.id
                                                    WHERE cr.change_type IN (?, ?, ?)
                                                    AND cr.status = ?
                                                    ORDER BY cr.created_at DESC",
                                                array('Normal', 'Major', 'Emergency', 'Submitted'));
                                            for($i=0; $i < COUNT($it_man_get_change_request); $i++){
                                                echo "
                                                <tr>
                                                    <td>".$it_man_get_change_request[$i]['crf_number']."</td>
                                                    <td>".$it_man_get_change_request[$i]['change_title']."</td>
                                                    <td>".$it_man_get_change_request[$i]['department']."</td>
                                                    <td>".$it_man_get_change_request[$i]['change_type']."</td>
                                                    <td>".$it_man_get_change_request[$i]['name']."</td>
                                                    <td>".$it_man_get_change_request[$i]['status']."</td>
                                                    <td>".$it_man_get_change_request[$i]['created_at']."</td>
                                                    <td>
                                                        <a class='btn btn-primary' href='view_change_request.php?id=".$it_man_get_change_request[$i]['id']."'>View</a>
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
    $("#tblManageKBA").DataTable({
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