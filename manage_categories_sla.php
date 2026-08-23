<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("includes/modal.php") ;?>
<?php include("library/functions.php"); ?>
<?php $page_title = "KabanDesk"; ?>

<div class="mt-5">
    <div class="row mx-auto">
        <div class="col-md-12 mb-2">
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
                                        for($i=0; $i < COUNT($get_categories); $i++){
                                            echo "
                                            <tr>
                                                <td>".$get_categories[$i]['id']."</td>
                                                <td>".$get_categories[$i]['code']."</td>
                                                <td>".$get_categories[$i]['name']."</td>
                                                <td>
                                                    <span class='btn btn-primary btn-sm edit_category'
                                                        edit_category_id='".$get_categories[$i]['id']."'
                                                        edit_category_code='".$get_categories[$i]['code']."'
                                                        edit_category_name='".$get_categories[$i]['name']."'
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
                                            $thead_category = explode(",","No, Category Name, Priority, Resolution Hours, Response Hours, Actions");
                                            foreach($thead_category as $th_user){
                                                echo "<th>".$th_user."</th>";
                                            }
                                        ?>
                                    </tr>
                               </thead>
                               <tbody>
                                   <?php
                                        $get_sla = retrieve("SELECT cat.id AS cat_id, cat.name AS cat_name, sla.id AS sla_id, 
                                            sla.priority AS priority, sla.response_hours AS response_hours, sla.resolution_hours AS resolution_hours FROM 
                                            sla_rules AS sla 
                                            INNER JOIN categories AS cat ON sla.category_id=cat.id",array());
                                        for($i=0; $i < COUNT($get_sla); $i++){
                                            echo "
                                            <tr>
                                                <td>".$get_sla[$i]['sla_id']."</td>
                                                <td>".$get_sla[$i]['cat_name']."</td>
                                                <td>".$get_sla[$i]['priority']."</td>
                                                <td>".$get_sla[$i]['response_hours']."</td>
                                                <td>".$get_sla[$i]['resolution_hours']."</td>
                                                <td>
                                                    <span class='btn btn-primary btn-sm edit_sla'
                                                        edit_sla_id='".$get_sla[$i]['sla_id']."'
                                                        edit_sla_cat_id='".$get_sla[$i]['cat_id']."'
                                                        edit_sla_priority='".$get_sla[$i]['priority']."'
                                                        edit_sla_response_hours='".$get_sla[$i]['response_hours']."'
                                                        edit_sla_resolution_hours='".$get_sla[$i]['resolution_hours']."'
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
        "pageLength":20,
		"order": [],
	});

    $("#tblSLARules").DataTable({
		"scrollX": true,
		"info": true,
		"lengthChange": true,
		"paging": true,
		"searching": true,
        "pageLength":20,
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
        $("#edit_sla_response_hours").val($(this).attr("edit_sla_response_hours"));
        $("#edit_sla_resolution_hours").val($(this).attr("edit_sla_resolution_hours"));
        $("#edit_sla_modal").modal("show");
    });
});
</script>