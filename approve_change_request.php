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

$role = $_SESSION['role'] ?? null;
$canAct = getApprovalLevelForAction($cr['change_type'], $role, $id) !== null
          && in_array($cr['status'], ['Submitted', 'Under Review']);
?>

<div class="mt-5">
  <div class="row">
    <div class="col-md-12 mt-5">
        <div class="card rounded-0">
            <div class="card-header p-3 white-text kaban-color">
            Review Change Request — <?= htmlspecialchars($cr['crf_number']) ?>
            </div>
            <div class="card-body">
            <p><strong>Title:</strong> <?= htmlspecialchars($cr['change_title']) ?></p>
            <p><strong>Type:</strong> <?= htmlspecialchars($cr['change_type']) ?></p>
            <p><strong>Requested by:</strong> <?= htmlspecialchars($cr['requestor_name']) ?></p>
            <p><strong>Reason:</strong> <?= nl2br(htmlspecialchars($cr['reason_for_request'])) ?></p>
            <p><strong>Current status:</strong> <span id="statusLabel"><?= htmlspecialchars($cr['status']) ?></span></p>

            <?php if ($canAct): ?>
            <hr>
            <div class="md-form">
                <textarea id="comments" name="comments" class="form-control md-textarea" rows="5" style="resize:none;"></textarea>
                <label for="comments">Comments (optional)</label>
            </div>
            <button class="btn btn-success" id="btnApprove">Approve</button>
            <button class="btn btn-danger" id="btnReject">Reject</button>
            <?php else: ?>
            <hr>
            <p class="font-weight-bold text-warning">You are not authorized to act on this request at its current stage, or it has already been decided.</p>
            <?php endif; ?>
            </div>
        </div>
    </div>
  </div>
</div>

<?php include("includes/footer.php"); ?>
<script>
function submitDecision(decision) {
    $.ajax({
        url: "./api/process_change_approval.php",
        type: "POST",
        dataType: "JSON",
        data: {
            change_request_id: <?= (int)$cr['id'] ?>,
            decision: decision,
            comments: $('#comments').val()
        },
        success: function(response) {
            console.log(response);
            Swal.fire({
              title: response.status === 'success' ? 'Success!' : 'Error!',
              text: response.message,
              icon: response.status === 'success' ? 'success' : 'error',
              confirmButtonText: 'OK'
          }).then(() => { if (response.status === 'success') 
              location.reload(); 
          });
        },
        error: function(xhr, status, error) {
            console.log(xhr.responseText);
            console.log("Status: " + status);
            console.log("Error" + error);
            Swal.fire({ title: 'Error!', text: 'An error occurred: ' + error, icon: 'error' });
        }
    });
}
$(document).ready(function () {

  $('#btnApprove').on('click', () => {
    Swal.fire({
        title: 'Approve this change request?',
        text: 'This will record your approval and move the request forward.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, approve',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#1e6b3e',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            submitDecision('Approved');
        }
    });
});
  $('#btnReject').on('click', () => {
      Swal.fire({
          title: 'Reject this change request?',
          text: 'This will mark the request as Rejected and notify the requestor.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, reject it',
          cancelButtonText: 'Cancel',
          confirmButtonColor: '#a3282c',
          reverseButtons: true
      }).then((result) => {
          if (result.isConfirmed) {
              submitDecision('Rejected');
          }
      });
  });
});
</script>