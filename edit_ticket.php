<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php") ?>
<?php $page_title = "KabanDesk"; ?>

<?php

    $get_ticket_id = $_GET['id'] ?? null;
    if (!$get_ticket_id) {
        header("Location: index.php");
        exit;
    }

    $ticket = retrieve("SELECT * FROM tickets WHERE id = ?", array($get_ticket_id));
    if (empty($ticket)) {
        header("Location: index.php");
        exit;
    }
    
    $ticket=$ticket[0];

?>

<div class="container mt-5">
    <div class="row mx-auto">
        <div class="col-md-12 mb-2">
            <div class="row mt-5">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header kaban-color p-3 white-text">
                            Edit Ticket
                        </div>
                        <div class="card-body mt-3">
                            <div class="row">
                                <div class="col-md-12">
                                    <form method="POST" id="frmEditTicket" enctype="multipart/form-data">
                                        <div class="row">
                                            <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']); ?>">
                                            <div class="col-md-12 mt-2">
                                                <label for="edit_subject">Subject <span class="text-danger font-weight-bold">*</span></label>
                                                <input type="text" class="form-control form-control-sm" name="edit_subject" id="edit_subject" value="<?= htmlspecialchars($ticket['subject']); ?>">
                                            </div>
                                            <div class="col-md-12 mt-2">
                                                <label for="edit_description">Description <span class="text-danger font-weight-bold">*</span></label>
                                                <textarea class="form-control" name="edit_description" id="edit_description" rows="10" style="resize:none"><?= htmlspecialchars($ticket['description']); ?></textarea>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>Category <span class="text-danger font-weight-bold">*</span></label>
                                                <select class="mdb-select" name="edit_category" id="edit_category" value="<?= htmlspecialchars($ticket['category']); ?>" selected>
                                                    <option value="" disabled selected>Select category (Network / POS / PMS / Hardware / Access)</option>
                                                    <?php
                                                        $get_category = retrieve("SELECT * FROM categories",array());
                                                        foreach($get_category as $category){
                                                            $selected = ($category['id'] == $ticket['category_id']) ? 'selected' : '';
                                                            echo "<option value='".$category['id']."' ".$selected.">".$category['name']."</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>Priority <span class="text-danger font-weight-bold">*</span></label>
                                                <select class="mdb-select" name="edit_priority" id="edit_priority" required>
                                                    <option value="" disabled selected>Select Priority Level</option>
                                                    <?php
                                                        $priority = array("Low","Medium","High","Critical");
                                                        foreach($priority as $level){
                                                            $selected = ($level == $ticket['priority']) ? 'selected' : '';
                                                            echo "<option value='".$level."' ".$selected.">".$level."</option>";
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
                                                        <input type="file" class="custom-file-input" id="edit_attachment" name="attachment"
                                                        aria-describedby="inputGroupFileAddon01">
                                                        <label class="custom-file-label" for="attachment">Attach File / Screenshot</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-row">
                                            <button type="submit" class="btn btn-primary mt-3" id="save_ticket" name="save_ticket">Update Ticket</button>
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