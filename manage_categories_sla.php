<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php include("includes/modal.php") ;?>
<?php include("library/stats.php"); ?>
<?php $page_title = "KabanDesk"; ?>

<div class="mt-5">
    <div class="row mx-auto">
        <div class="col-md-12 mb-2 mt-5">
            <div class="row">
                <div class="col-md-6">
                    <div class="card rounded-0">
                        <div class="card-header p-3 white-text kaban-color">
                            Manage Categories
                        </div>
                        <div class="card-body">
                            <span class="float-right btn btn-primary btn-md" data-toggle="modal" data-target="#add_category_modal">Add Category</span>
                            <table class="table table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0" id="tblCategories">
                                <thead>
                                    <tr>    
                                        <?php
                                            $thead_category = explode(",","No, Code, Name, Actions");
                                            foreach($thead_category as $th_cat){
                                                echo "<th>".$th_cat."</th>";
                                            }
                                        ?>
                                    </tr>
                               </thead>
                               <tbody>
                                   <?php
                                        $get_categories = retrieve("SELECT * FROM categories",array());
                                        foreach ($get_categories as $cat_value) {
                                            echo "
                                            <tr>
                                                <td>".$cat_value['id']."</td>
                                                <td>".$cat_value['code']."</td>
                                                <td>".$cat_value['name']."</td>
                                                <td>
                                                    <span class='btn btn-primary btn-sm edit_category'
                                                        edit_category_id='".$cat_value['id']."'
                                                        edit_category_code='".$cat_value['code']."'
                                                        edit_category_name='".$cat_value['name']."'
                                                        data-toggle='modal' data-target='#edit_category_modal'
                                                        >Edit
                                                    </span>
                                                </td>
                                            </tr>";
                                        }
                                   ?>
                               </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card rounded-0">
                        <div class="card-header p-3 white-text kaban-color">
                            Manage SLA Rules
                        </div>
                        <div class="card-body">
                            <span class="float-right btn btn-primary btn-md" data-toggle="modal" data-target="#add_sla_modal">Add SLA Rules</span>
                            <table class="table table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0" id="tblSLARules">
                                <thead>
                                    <tr>    
                                        <?php
                                            $thead_category = explode(",","No, Category Name, Priority, Response Minutes, Resolution Hours, Actions");
                                            foreach($thead_category as $th_user){
                                                echo "<th>".$th_user."</th>";
                                            }
                                        ?>
                                    </tr>
                               </thead>
                               <tbody>
                                   <?php
                                        $get_sla = retrieve("SELECT cat.id AS cat_id, cat.name AS cat_name, sla.id AS sla_id, 
                                            sla.priority AS priority, sla.response_minutes AS response_minutes, sla.resolution_hours AS resolution_hours FROM 
                                            sla_rules AS sla 
                                            INNER JOIN categories AS cat ON sla.category_id=cat.id",array());
                                        foreach ($get_sla as $sla_value) {

                                            $colors = [
                                                'Critical' => 'bg-danger text-white',
                                                'High' => 'bg-warning text-dark',
                                                'Medium' => 'bg-success text-white',
                                                'Low' => 'bg-info text-white'
                                            ];

                                            echo "
                                                <tr>
                                                    <td>".$sla_value['sla_id']."</td>
                                                    <td>".$sla_value['cat_name']."</td>
                                                    <td class='".$colors[$sla_value['priority']]."'>".get_priority_code($sla_value['priority'])." - ".$sla_value['priority']."</td>
                                                    <td>".$sla_value['response_minutes']."</td>
                                                    <td>".$sla_value['resolution_hours']."</td>
                                                    <td>
                                                        <span class='btn btn-primary btn-sm edit_sla'
                                                            edit_sla_id='".$sla_value['sla_id']."'
                                                            edit_sla_cat_id='".$sla_value['cat_id']."'
                                                            edit_sla_priority='".$sla_value['priority']."'
                                                            edit_sla_response_minutes='".$sla_value['response_minutes']."'
                                                            edit_sla_resolution_hours='".$sla_value['resolution_hours']."'
                                                            data-toggle='modal' data-target='#edit_sla_modal'
                                                        >Edit</span>
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

    $("#category_code").on('input', function () {
        this.value = this.value.toUpperCase();
    });

    $("#tblCategories").DataTable({
		"scrollX": true,
		"info": true,
		"lengthChange": true,
		"paging": true,
		"searching": true,
        "pageLength":10,
		"order": [],
	});

    $("#tblSLARules").DataTable({
		"scrollX": true,
		"info": true,
		"lengthChange": true,
		"paging": true,
		"searching": true,
        "pageLength":10,
		"order": [],
	});

    $(".edit_category").click(function(){
        $("#edit_category_id").val($(this).attr("edit_category_id"));
        $("#edit_category_code").val($(this).attr("edit_category_code"));
        $("#edit_category_name").val($(this).attr("edit_category_name"));
        $("#edit_category_modal").modal("show");
    });

    $(".edit_sla").click(function(){
        $("#edit_sla_id").val($(this).attr("edit_sla_id"));
        $("#edit_sla_cat_id").val($(this).attr("edit_sla_cat_id"));
        $("#edit_sla_priority").val($(this).attr("edit_sla_priority"));
        $("#edit_sla_response_minutes").val($(this).attr("edit_sla_response_minutes"));
        $("#edit_sla_resolution_hours").val($(this).attr("edit_sla_resolution_hours"));
        $("#edit_sla_modal").modal("show");
    });
});
</script>