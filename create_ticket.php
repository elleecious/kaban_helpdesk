<?php $skip_activity = true;  ?>
<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php") ?>
<?php $page_title = "KabanDesk"; ?>

<div class="container mt-5">
    <div class="row mx-auto">
        <div class="col-md-12 mb-2">
            <div class="row mt-5">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header kaban-color p-3 white-text">
                            Create Ticket
                        </div>
                        <div class="card-body mt-3">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="alert alert-light border p-3 text-muted">
                                        <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-info-circle mr-1"></i> How to Submit a Ticket</h6>
                                        <p class="mb-1 text-sm">Please fill out the form below so our support team can assist you as quickly as possible:</p>
                                        <ul class="pl-3 mb-0 text-sm">
                                            <li><strong>Subject *:</strong> Enter a short title summarizing your issue or request.(e.g. <i>"Reconnect Cage Shared Drive"</i>)</li>
                                            <li><strong>Description (optional):</strong> Provide extra details, error messages, or context.</li>
                                            <li><strong>Category & Priority *:</strong> Select the relevant category and urgency level.</li>
                                            <li><strong>Attachment (optional):</strong> Upload any supporting files or screenshots.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <form method="POST" id="frmCreateTicket" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-12 mt-2">
                                                <div class="md-form">
                                                    <i class="fas fa-tag prefix"></i>
                                                    <input type="text" class="form-control" name="subject" id="subject">
                                                    <label for="subject">Subject <span class="text-danger font-weight-bold">*</span></label>
                                                </div>
                                            </div>
                                            <div class="col-md-12 mt-2">
                                                <div class="md-form">
                                                    <i class="fas fa-message prefix"></i>
                                                    <textarea class="form-control md-textarea" name="description" id="description" rows="10" style="resize:none"></textarea>
                                                    <label for="subject">Description (optional)</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>Category <span class="text-danger font-weight-bold">*</span></label>
                                                <select class="mdb-select" name="category" id="category">
                                                    <option value="" disabled selected>Select category (Network / POS / PMS / Hardware / Access)</option>
                                                    <?php
                                                        $get_category = retrieve("SELECT * FROM categories",array());
                                                        for ($i=0; $i < count($get_category); $i++) { 
                                                            echo "<option value='".$get_category[$i]['id']."'>".$get_category[$i]['name']."</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>Priority <span class="text-danger font-weight-bold">*</span></label>
                                                <select class="mdb-select" name="priority" id="priority" required>
                                                    <option value="" disabled selected>Select Priority Level</option>
                                                    <?php
                                                        $priority = array("Low","Medium","High","Critical");
                                                        foreach($priority as $prio){
                                                            echo "<option value='".$prio."'>".$prio."</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-12 mt-2">
                                                <label>Attachment (optional)</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="inputGroupFileAddon01">Upload</span>
                                                    </div>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="attachment" name="attachment"
                                                        aria-describedby="inputGroupFileAddon01">
                                                        <label class="custom-file-label" for="attachment">Attach File / Screenshot</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-row">
                                            <button type="submit" class="btn btn-outline-primary mt-3" id="add_ticket" name="add_ticket">Submit Ticket</button>
                                            <button type="reset" class="btn btn-outline-danger mt-3" id="cancel_ticket">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include("includes/footer.php"); ?>