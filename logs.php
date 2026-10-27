<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php $page_title = "KabanDesk"; ?>
<?php

$allowed_roles = ['IT Manager', 'Administrator'];
if (empty($_SESSION['login_id'])) {
    header('Location: index.php');
    exit;
}

// Logged in, but role not allowed
if (!in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Access Denied</title>
        <style>
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                font-family: system-ui, sans-serif;
                background: #f4f4f5;
                color: #333;
                text-align: center;
            }
            img { max-width: 700px; width: 80%; height: auto; }
            a { margin-top: 1rem; color: #2563eb; text-decoration: none; }
        </style>
    </head>
    <body>
        <img src="/assets/img/access_denied.jpg" alt="Access Denied">
        <h1 class="display-5 mt-3">You don't have permission to view this page.</h1>
    </body>
    </html>
    <?php
    exit;
}

?>
<div class="row mx-auto mt-5">
	<div class="col-md-12">
		<div class="row">
            <div class="col-md-12 mb-3 mt-5">
				<div class="card">
					<div class="card-header p-3 kaban-color text-white">
					    System Logs
					</div>
                    <div class="card-body">
						<div class="row">
                            <div class="col-md-12">
                                <table class="table table-striped table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0" id="tblLogs">
                                    <thead>
                                        <tr>
                                            <?php
                                                $stud_head=explode(",","Username,IP Address,Page,Action,Details,Date");
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