<?php $skip_activity = true;  ?>
<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php $page_title = "KabanDesk"; ?>
<div class="container-fluid mt-5">
    <div class="row mx-auto">
        <div class="col-md-12">
            <div class="row mt-5">
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
                                            $thead_user = explode(",", "CRF Number, Title, Department, Change Type, Status, Created By, Date Created, Actions");
                                            foreach ($thead_user as $th_user) {
                                                echo "<th>" . $th_user . "</th>";
                                            }
                                        ?>
                                    </tr>
                                </thead>
                                <tbody id="changeRequestsBody"></tbody>
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