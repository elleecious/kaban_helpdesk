<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php $page_title = "KabanDesk"; ?>
<div class="mt-5">
    <div class="row mx-auto">
        <div class="col-md-12">
            <div class="row mt-3">
                <div class="col-md-5">
                    <div class="card rounded-0">
                        <div class="card-header p-3 white-text kaban-color">
                            Add Knowledge Base Articles
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-12 mt-2">
                                        <label>Category</label>
                                        <select class="form-control" name="kba_category" id="kba_category">
                                        <option value="" disabled selected>Select category (Network / POS / PMS / Hardware / Access)</option>
                                        <?php
                                            $get_category = retrieve("SELECT * FROM categories",array());
                                            for ($i=0; $i < count($get_category); $i++) { 
                                                echo "<option value='".$get_category[$i]['id']."'>".$get_category[$i]['name']."</option>";
                                            }
                                        ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-12 mt-2">
                                        <label for="kba_title">Title</label>
                                        <input class="form-control" type="text" name="kba_title" id="kba_title">
                                    </div>
                                    
                                    <div class="col-md-12 mt-2">
                                        <label for="kba_description">Description</label>
                                        <textarea class="form-control md-textarea" name="kba_description" id="kba_description" rows="5"></textarea>
                                    </div>
                                </div>
                                 <div class="d-flex flex-row mt-2">
                                    <button type="submit" class="btn btn-primary mt-3" id="add_kba" name="add_kba">Add</button>
                                    <button type="reset" class="btn btn-grey mt-3" id="cancel_kba">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="card rounded-0">
                        <div class="card-header p-3 white-text kaban-color">
                            Manage Knowledge Base Articles
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-sm text-center" width="100%" cellspacing="0" cellpadding="0" id="tblManageKBA">
                                <thead>
                                    <tr>    
                                        <?php
                                            $thead_user = explode(",","Title, Category, Created By, Date Created, Actions");
                                            foreach($thead_user as $th_user){
                                                echo "<th>".$th_user."</th>";
                                            }
                                        ?>
                                    </tr>
                               </thead>
                               <tbody>
                                   <?php
                                        $get_kba = retrieve("SELECT users.name AS name, cat.name AS category, kba.title AS title, kba.body, kba.created_at AS kba_created FROM kb_articles AS kba 
                                        INNER JOIN categories AS cat ON kba.category_id=cat.id 
                                        INNER JOIN users AS users ON kba.created_by=users.id",array());
                                        for($i=0; $i < COUNT($get_kba); $i++){
                                            echo "
                                            <tr>
                                                <td>".$get_kba[$i]['title']."</td>
                                                <td>".$get_kba[$i]['category']."</td>
                                                <td>".$get_kba[$i]['name']."</td>
                                                <td>".$get_kba[$i]['kba_created']."</td>
                                                <td>
                                                    <span class='btn btn-primary btn-sm edit_kba'>Edit</span>
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