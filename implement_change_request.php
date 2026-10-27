<?php $skip_activity = true;  ?>
<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php $page_title = "KabanDesk"; ?>
<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rows = retrieve(
    "SELECT cr.*, u.name AS requestor_name
     FROM change_requests cr LEFT JOIN users u ON u.id = cr.requestor_id
     WHERE cr.id = ?", array($id)
);
if (empty($rows)) { die("Change request not found."); }
$cr = $rows[0];

$allowedRoles = ['IT Support Specialist', 'IT Supervisor', 'IT Manager'];
$role = $_SESSION['role'] ?? null;

if (!in_array($role, $allowedRoles) || $cr['status'] === 'Rejected' || $cr['status'] === 'Closed') {
    die("This change request is not available for implementation.");
}
function val($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }
?>

<div class="mt-5">
    <div class="row mx-auto">
        <div class="col-md-12">
            <div class="row mt-5">
                <div class="col-md-12 mb-3">
                    <div class="card rounded-0">
                        <div class="card-header p-3 white-text kaban-color">
                            Implementation — <?= val($cr['crf_number']) ?>: <?= val($cr['change_title']) ?>
                        </div>
                        <div class="card-body">
                            <p><strong>Current status:</strong> <span id="statusLabel"><?= val($cr['status']) ?></span></p>

                            <!-- Step 1: Receive -->
                            <div class="form-group">
                                <label>Received by IT</label><br>
                                <?php if ($cr['received_by_it']): ?>
                                <span class="badge badge-success">Received</span>
                                <?php else: ?>
                                <button class="btn btn-sm btn-outline-primary" id="btnReceive">Mark as Received</button>
                                <?php endif; ?>
                            </div>

                            <hr>

                            <!-- Step 2: Implementation details -->
                            <div class="md-form">
                                <input type="text" id="expected_downtime" class="form-control" value="<?= val($cr['expected_downtime']) ?>" placeholder="e.g. 15 minutes, none expected">
                                <label for="expected_downtime">Expected downtime</label>
                            </div>
                            <div class="md-form">
                                <textarea id="rollback_plan" class="form-control md-textarea" rows="5" style="resize:none;"><?= val($cr['rollback_plan']) ?></textarea>
                                <label for="rollback_plan">Rollback plan</label>
                            </div>
                            <div class="md-form">
                                <textarea id="implementation_notes" class="form-control md-textarea" rows="5" style="resize:none;"  ><?= val($cr['implementation_notes']) ?></textarea>
                                <label for="implementation_notes">Implementation notes</label>
                            </div>
                            <button class="btn btn-sm btn-outline-primary" id="btnSaveImplementation">Save & Mark Implementing</button>

                            <hr>

                        <!-- Step 3: Validation -->
                            <div id="validationRresults">
                                <div class="md-form">
                                    <label for="validation_results">Test results / findings</label>
                                    <textarea id="validation_results" class="form-control md-textarea" rows="5" style="resize:none;"><?= val($cr['validation_results']) ?></textarea>
                                </div>
                                <small class="text-muted d-block mb-3">
                                    Describe what you tested and what you found. If the change passes, this is saved as the validation result. If it fails, it is recorded in the rollback history instead.
                                </small>

                                <?php if (!empty($cr['issues_encountered'])): ?>
                                    <div class="md-form">
                                        <label for="issues_encountered">Rollback / issue history</label>
                                        <?php
                                            if ($cr['status'] === "Implementing") {
                                                echo '<div class="alert alert-warning" style="white-space:pre-wrap;">'.val($cr['issues_encountered']).'</div>';
                                            } else {
                                                echo '<div class="alert alert-danger" style="white-space:pre-wrap;">'.val($cr['issues_encountered']).'</div>';
                                            }
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-success" id="btnValidate"><span class="fa fa-check"></span> Successful — Mark as Implemented</button>
                                    <button class="btn btn-sm btn-outline-warning" id="btnRollback"><span class="fa fa-close"></span> Failed — Rollback & Retry</button>
                                </div>
                            </div>

                            <hr>

                            <!-- Step 4: Close -->
                            <div class="md-form">
                                <label for="issues_encountered">Issues encountered (if any)</label>
                                <textarea id="issues_encountered" class="form-control md-textarea" rows="5" style="resize: none;"><?= val($cr['issues_encountered']) ?></textarea>
                            </div>
                            <button class="btn btn-sm btn-outline-info" id="btnClose">Close Change Request</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function postStep(step, extra) {
    $.ajax({
        url: "./api/process_implementation.php",
        type: "POST",
        dataType: "JSON",
        data: Object.assign({ change_request_id: <?= (int)$cr['id'] ?>, step: step }, extra),
        success: function(response) {
            console.log(response);
            if (response.status === 'success') {
                Swal.fire({ title: 'Saved', text: response.message, icon: 'success' })
                    .then(() => location.reload());
            } else {
                Swal.fire({ title: 'Error', text: response.message, icon: 'error' });
            }
        },
        error: function(xhr, status, error) {
            console.error("XHR: " + xhr.responseText);
            console.error("Status: " + status);
            console.error("Error: " + error);
            Swal.fire({ title: 'Error', text: 'An error occurred: ' + error, icon: 'error' });
        }
    });
}

/**
 * Helper to DRY up SweetAlert2 confirmation dialogs
 */
function confirmAndPost(config, callback) {
    Swal.fire(Object.assign({
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        reverseButtons: true
    }, config)).then((result) => {
        if (result.isConfirmed) {
            callback(result.value);
        }
    });
}

$(document).ready(function () {
    // Note: Double-check if your HTML ID is "#validationRresults" or "#validationResults"
    const $validationResults = $("#validationRresults");
    
    $validationResults.hide();
    if ($('#statusLabel').text() === 'Implementing') {
        $validationResults.show();
    }

    // 1. Receive
    $("#btnReceive").on('click', () => {
        confirmAndPost({
            title: 'Mark as Received?',
            text: 'This will mark the change request as received by IT and allow implementation to proceed.',
            icon: 'question',
            confirmButtonText: 'Yes, mark as received!'
        }, () => postStep('receive', {}));
    });

    // 2. Save Implementation
    $("#btnSaveImplementation").on('click', () => {
        confirmAndPost({
            title: 'Mark as Implementing?',
            text: 'This will save your implementation details and mark the change request as Implementing.',
            icon: 'question',
            confirmButtonText: 'Yes, mark as Implementing!'
        }, () => postStep('implement', {
            expected_downtime: $('#expected_downtime').val(),
            rollback_plan: $('#rollback_plan').val(),
            implementation_notes: $('#implementation_notes').val()
        }));
    });

    // 3. Validate / Implemented
    $("#btnValidate").on('click', () => {
        confirmAndPost({
            title: 'Mark as Implemented?',
            text: 'This will mark the change request as successfully implemented.',
            icon: 'question',
            confirmButtonText: 'Yes, mark as implemented!'
        }, () => postStep('validate', {
            validation_results: $('#validation_results').val()
        }));
    });

    // 4. Rollback
    $('#btnRollback').on('click', () => {
        confirmAndPost({
            title: 'Rollback this change?',
            text: 'This logs the failure and moves the request back to Implementing for another attempt.',
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'What went wrong? (recorded in the rollback history)',
            inputPlaceholder: 'e.g. Config change caused portal timeout; reverted to previous settings.',
            inputValue: $('#validation_results').val(),
            confirmButtonColor: '#b8860b',
            confirmButtonText: 'Yes, rollback!',
            inputValidator: (value) => {
                if (!value || !value.trim()) return 'Please describe what went wrong before rolling back.';
            }
        }, (rollbackNote) => {
            postStep('rollback', { rollback_note: rollbackNote });
        });
    });

    // 5. Close
    $('#btnClose').on('click', () => {
        confirmAndPost({
            title: 'Close this change request?',
            text: 'This will mark the change request as closed and no further actions can be taken.',
            icon: 'info',
            confirmButtonColor: '#17a2b8',
            confirmButtonText: 'Yes, close it!'
        }, () => {
            postStep('close', { issues_encountered: $('#issues_encountered').val() });
        }, () => {
            window.location.href = 'view_change_request.php?id=<?= (int)$cr['id'] ?>';
        });
    });
});
</script>
