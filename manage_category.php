<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php include("includes/modal.php") ;?>
<?php include("library/stats.php"); ?>
<?php $page_title = "KabanDesk"; ?>

<div class="container-fluid mt-5">
    <div class="row mx-auto">
        <div class="col-md-12 mt-5">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="card rounded-0">
                        <div class="card-header p-3 white-text kaban-color">
                            Manage Categories
                        </div>
                        <div class="card-body">
                            <span class="float-right btn btn-outline-info btn-md" data-toggle="modal" data-target="#add_category_modal">Add Category</span>
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
                                                    <span class='btn btn-outline-info btn-md edit_category'
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

    $(".edit_category").click(function(){
        $("#edit_category_id").val($(this).attr("edit_category_id"));
        $("#edit_category_code").val($(this).attr("edit_category_code"));
        $("#edit_category_name").val($(this).attr("edit_category_name"));
        $("#edit_category_modal").modal("show");
    });
});
</script>