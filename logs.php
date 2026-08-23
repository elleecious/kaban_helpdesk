<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php") ?>
<?php $page_title = "KabanDesk"; ?>
<div class="row mx-auto mt-3">
	<div class="col-md-12 mb-2">
		<div class="row">
            <div class="col-md-12 mb-2">
				<div class="card">
					<div class="card-header p-2 kaban-color text-white">
					    System Logs
					</div>
                    <div class="card-body">
						<div class="row">
                            <div class="col-md-12">
                                <table class="table table-striped table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0" id="tblLogs">
                                    <thead>
                                        <tr>
                                            <?php
                                                $stud_head=explode(",","Username,Computer Name,IP Address,Page,Action,Details,Date");
                                                foreach($stud_head as $stud_val)
                                                {
                                                    echo "<th>".$stud_val."</th>";
                                                }
                                            ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $disp_logs = retrieve("SELECT * FROM logs ORDER BY date DESC",array());
                                            for ($i=0; $i < count($disp_logs); $i++) { 
                                            echo "<tr>
                                                    <td>".$disp_logs[$i]['username']."</td>
                                                    <td>".$disp_logs[$i]['computer_name']."</td>
                                                    <td>".$disp_logs[$i]['ip_address']."</td>
                                                    <td>".$disp_logs[$i]['page']."</td>
                                                    <td>".$disp_logs[$i]['action']."</td>
                                                    <td>".$disp_logs[$i]['details']."</td>
                                                    <td>".$disp_logs[$i]['date']."</td>
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
</div>
<?php include('includes/footer.php') ?>
<script>
$(document).ready(function () {
    $("#tblLogs").DataTable({
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