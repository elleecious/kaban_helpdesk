<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php") ?>
<?php $page_title = "KabanDesk"; ?>

<div class="container">
    <div class="row mx-auto">
        <div class="col-md-12 mb-2">
            <div class="row mt-5">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header kaban-color p-3 white-text">
                            Create Ticket
                        </div>
                        <div class="card-body mt-3">
                            <div class="row">
                                <div class="col-md-12">
                                    <form method="POST" id="frmCreateTicket">
                                        <div class="row">
                                            <div class="col-md-12 mt-2">
                                                <label for="subject">Subject <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" class="form-control form-control-sm" name="subject" id="subject">
                                            </div>
                                            <div class="col-md-12 mt-2">
                                                <label for="subject">Description <span class="text-danger font-weight-bold">*</span></label>
                                                <textarea class="form-control" name="description" id="description" rows="4" style="resize:none"></textarea>
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
                                            <button type="submit" class="btn btn-primary mt-3" id="add_ticket" name="add_ticket">Submit Ticket</button>
                                            <button type="reset" class="btn btn-grey mt-3" id="cancel_ticket">Cancel</button>
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
<script>
$(function () {
    // var currentPage = window.location.pathname.split("/").pop();

    // $(".nav-link").each(function () {
    //     var href = $(this).attr("href");

    //     if (href === currentPage) {
    //         $(this).addClass("active");
    //     }
    // });
});
</script>