<div class="modal fade" id="add_category_modal" tabindex="-1" role="dialog" aria-labelledby="AddCategoryLabel"aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header kaban-color white-text">
        <h5 class="modal-title" id="AddCategoryLabel">Add Category</h5>
        <button type="button" class="close white-text" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <form method="POST" id="frmAddCategory">
              <div class="row">
                  <div class="col-md-12 mt-2">
                    <small>Name</small>
                    <input class="form-control form-control-sm" type="text" name="category_name" id="category_name">

                    <small>Code</small>
                    <input class="form-control form-control-sm" type="text" name="category_code" id="category_code" autocapitalize="on">
                  </div>
              </div>
              <button type="submit" class="btn btn-success float-right" name="add_category" id="add_category">Add</button>
          </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="edit_category_modal" tabindex="-1" role="dialog" aria-labelledby="editCategoryLabel" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header kaban-color white-text">
        <h5 class="modal-title" id="editCategoryLabel">Edit Category</h5>
        <button type="button" class="close white-text" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <form method="post" id="frmEditCategory">
              <div class="row">
                  <input type="text" name="edit_category_id" id="edit_category_id" hidden>
                  <?php
                    $edit_category_form = array(
                      "edit_category_code"=>"Code",
                      "edit_category_name"=>"Name",
                      );
                        foreach ($edit_category_form as $catkey => $catvalue) {
                            echo "
                            <div class='col-md-12 mt-2'>
                                <small>".$catvalue."</small>
                                <input class='form-control form-control-sm' type='text' name='".$catkey."' id='".$catkey."'>
                            </div>";
                        }
                  ?>
                  <button type="submit" class="btn btn-success ml-auto" name="save_category" id="save_category">Save</button>
              </div>
          </form>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="add_sla_modal" tabindex="-1" role="dialog" aria-labelledby="AddSLALabel"aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header kaban-color white-text">
        <h5 class="modal-title" id="AddSLALabel">Add SLA</h5>
        <button type="button" class="close white-text" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <form method="POST" id="frmAddSLA">
              <div class="row">
                  <div class="col-md-12 mt-2">
                    <small>Category <span class="text-danger font-weight-bold">*</span></small>
                    <select class="form-control form-control-sm" id="category" name="category" required>
                        <option value="" selected>Select Category</option>
                        <?php
                            $geCategories = retrieve("SELECT * FROM categories ORDER BY code ASC",array());
                            for ($i=0; $i < count($geCategories); $i++) { 
                              echo "<option value='".$geCategories[$i]['id']."'>".$geCategories[$i]['name']."</option>";
                            }
                        ?>
                    </select>

                    <small>Priority</small>
                    <select class="form-control form-control-sm" name="priority" id="priority">
                      <option value="" selected>Select Priority Level</option>
                      <?php
                          $priority = array("Low","Medium","High","Critical");
                          foreach($priority as $prio){
                              echo "<option value='".$prio."'>".$prio."</option>";
                          }
                      ?>
                      </select>
                  </div>
              </div>
              <div class="row">
                <div class="col-md-6 mt-2">
                    <small>Response Hours</small>
                    <input class="form-control form-control-sm" type="number" 
                          name="response_hours" id="response_hours" 
                          step="0.25" min="0" placeholder="e.g. 0.5">
                </div>
                <div class="col-md-6 mt-2">
                    <small>Resolution Hours</small>
                    <input class="form-control form-control-sm" type="number" 
                          name="resolution_hours" id="resolution_hours" 
                          step="0.25" min="0" placeholder="e.g. 4">
                </div>
              </div>
              <button type="submit" class="btn btn-success float-right" name="add_sla" id="add_sla">Add</button>
          </form>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="edit_sla_modal" tabindex="-1" role="dialog" aria-labelledby="editSLALabel" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header kaban-color white-text">
        <h5 class="modal-title" id="editSLALabel">Edit SLA Rules</h5>
        <button type="button" class="close white-text" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <form method="post" id="frmEditSLA">
              <div class="row">
                  <input type="text" name="edit_sla_id" id="edit_sla_id" hidden>
                  <div class="col-md-12 mt-2">
                      <small>Category <span class="text-danger font-weight-bold">*</span></small>
                      <select class="form-control form-control-sm" id="edit_sla_cat_id" name="edit_sla_cat_id">
                          <option value="" selected>Select Category</option>
                          <?php
                              $get_cat_name = retrieve("SELECT * FROM categories ORDER BY code ASC",array());
                              for ($i=0; $i < count($get_cat_name); $i++) { 
                                echo "<option value='".$get_cat_name[$i]['id']."'>".$get_cat_name[$i]['name']."</option>";
                              }
                          ?>
                      </select>
                      <small>Priority</small>
                      <select class="form-control form-control-sm" name="edit_sla_priority" id="edit_sla_priority">
                        <option value="" selected>Select Priority Level</option>
                        <?php
                            $priority = array("Low","Medium","High","Critical");
                            foreach($priority as $prio){
                                echo "<option value='".$prio."'>".$prio."</option>";
                            }
                        ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                  <div class="col-md-6 mt-2">
                      <small>Response Hours</small>
                      <input class="form-control form-control-sm" type="number" 
                            name="edit_sla_response_hours" id="edit_sla_response_hours" 
                            step="0.25" min="0" placeholder="e.g. 0.5">
                  </div>
                  <div class="col-md-6 mt-2">
                      <small>Resolution Hours</small>
                      <input class="form-control form-control-sm" type="number" 
                            name="edit_sla_resolution_hours" id="edit_sla_resolution_hours" 
                            step="0.25" min="0" placeholder="e.g. 4">
                  </div>
                </div>

                <button type="submit" class="btn btn-success ml-auto" name="save_sla" id="save_sla">Save</button>
              </div>
          </form>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="unassignedTicketsModal" tabindex="-1" role="dialog" aria-labelledby="unasssignedTicketLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header kaban-color white-text">
        <h5 class="modal-title" id="unasssignedTicketLabel">Unassigned Tickets</h5>
        <button type="button" class="close white-text" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <table class="table table-bordered">
            <thead>
              <tr>
              <?php
                  $table_array=array("Ticket #","Subject","Priority","Waiting","Action");
                  foreach ($table_array as $key => $value) {
                    echo "<th>".$value."</th>";
                  }
              ?>
              </tr>
            </thead>
            <tbody id="unassignedTicketsBody">
               
            </tbody>
          </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="assignTicketModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Ticket <span class="font-weight-bold" id="assignTicketNumber"></span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p><strong>Subject:</strong> <span class="font-weight-bold" id="assignTicketSubject"></span></p>

                <label>Assign to IT Support</label>
                <select class="form-control" id="assignAgentSelect" name="assignAgentSelect">
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnConfirmAssign">Confirm Assign</button>
            </div>
        </div>
    </div>
</div>