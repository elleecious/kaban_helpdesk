<?php
include("config/connect.php");
include("includes/session.php");
include("library/functions.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$rows = retrieve(
    "SELECT cr.*, 
            u.name AS requestor_name,
            ru.name AS received_by_name,
            iu.name AS implemented_by_name
     FROM change_requests cr
     LEFT JOIN users u  ON u.id = cr.requestor_id
     LEFT JOIN users ru ON ru.id = cr.received_by_it
     LEFT JOIN users iu ON iu.id = cr.implemented_by
     WHERE cr.id = ?",
    array($id)
);

if (empty($rows)) {
    die("Change request not found.");
}
$cr = $rows[0];

$approvals = retrieve(
    "SELECT ca.approval_level, ca.decision, ca.comments, ca.decided_at, u.name AS approver_name
     FROM change_approvals ca
     LEFT JOIN users u ON u.id = ca.approver_id
     WHERE ca.change_request_id = ?
     ORDER BY ca.id ASC",
    array($cr['id'])
);

$role = $_SESSION['role'] ?? null;
$approvalLevel = getApprovalLevelForAction($cr['change_type'], $role, $cr['id']);
$canAct = ($approvalLevel !== null) && in_array($cr['status'], ['Submitted', 'Under Review']);

function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }
function fmt_date($v) { return $v ? date("M j, Y", strtotime($v)) : "—"; }
function fmt_datetime($v) { return $v ? date("M j, Y g:i A", strtotime($v)) : "—"; }

$status = $cr['status'] ?? 'Submitted';
$statusClasses = [
    'Draft'         => 'st-draft',
    'Submitted'     => 'st-pending',
    'Under Review'  => 'st-pending',
    'Approved'      => 'st-approved',
    'Rejected'      => 'st-rejected',
    'Scheduled'     => 'st-approved',
    'Implementing'  => 'st-approved',
    'Implemented'   => 'st-done',
    'Reviewed'      => 'st-done',
    'Closed'        => 'st-done',
];
$statusClass = $statusClasses[$status] ?? 'st-pending';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($cr['crf_number']) ?> — Change Request | Kaban Hotel &amp; Casino</title>
<link rel="stylesheet" href="./assets/css/mdb-ui-kit-mdb.min.css">
<link rel="stylesheet" href="./assets/css/view_change_request.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Roboto:wght@400;500&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
@media print {
    @page {
        size: auto;   /* auto is the initial value */
        margin: 0;  /* this affects the margin in the printer settings */
    }
    .topbar, .sheet-footer, .back-link { display: none; }

    /* Adjust the sheet to take full width for printing */
    .sheet { width: 100%; margin: 0; }
}
</style>
</head>
<body>

<div class="topbar">
  <div class="wrap">
    <img src="./assets/img/kaban-logo-horizontal.png" alt="Kaban Hotel and Casino Boracay" class="brand-logo">
    <?php
      $allowed_roles = ['IT Support Specialist', 'System Administrator', 'IT Supervisor', 'IT Manager'];
      $user_role = $_SESSION['role'] ?? null;

      if (in_array($user_role, $allowed_roles, true)): 
    ?>
      <a href="manage_change_request.php" class="back-link">&larr; Back to Manage Change Request</a>  
    <?php else: ?>
      <a href="employee_dashboard.php" class="back-link">&larr; Back to dashboard</a>
    <?php endif; ?>
  </div>
</div>

<div class="sheet">

  <div class="sheet-head">
    <div>
      <h1><?= h($cr['change_title']) ?></h1>
      <div class="subtitle">Requested by <?= h($cr['requestor_name'] ?? 'Unknown') ?> · <?= fmt_date($cr['created_at']) ?></div>
    </div>
    <div class="crn-tag"><?= h($cr['crf_number']) ?></div>
  </div>

  <div class="status-row">
    <span class="status-pill <?= $statusClass ?>"><?= h($status) ?></span>
    <span class="meta">Change type: <strong><?= h($cr['change_type']) ?></strong></span>
    <?php if (!empty($cr['linked_ticket_id'])): ?>
      <span class="meta">· Linked to ticket #<?= (int)$cr['linked_ticket_id'] ?></span>
    <?php endif; ?>
  </div>

  <div class="section-label">Requestor Information</div>
  <div class="field-grid">
    <div class="field">
      <div class="field-label">Requestor's name</div>
      <div class="field-value"><?= h($cr['requestor_name'] ?? '—') ?></div>
    </div>
    <div class="field">
      <div class="field-label">Date submitted</div>
      <div class="field-value"><?= fmt_date($cr['created_at']) ?></div>
    </div>
    <div class="field">
      <div class="field-label">Contact number</div>
      <div class="field-value"><?= h($cr['contact_number'] ?: '—') ?></div>
    </div>
    <div class="field">
      <div class="field-label">Department</div>
      <div class="field-value"><?= h($cr['department'] ?: '—') ?></div>
    </div>
  </div>

  <div class="section-label">Change Request Details</div>
  <div class="field-grid">
    <div class="field">
      <div class="field-label">Change type</div>
      <div class="field-value"><span class="type-chip"><?= h($cr['change_type']) ?></span></div>
    </div>
    <div class="field">
      <div class="field-label">Location / site affected</div>
      <div class="field-value <?= empty($cr['location_site']) ? 'empty' : '' ?>">
        <?= $cr['location_site'] ? h($cr['location_site']) : 'Not specified' ?>
      </div>
    </div>
    <div class="field">
      <div class="field-label">Requested implementation date</div>
      <div class="field-value"><?= fmt_date($cr['requested_implementation_date']) ?></div>
    </div>
    <div class="field">
      <div class="field-label">Systems / services affected</div>
      <div class="field-value <?= empty($cr['systems_affected']) ? 'empty' : '' ?>">
        <?= $cr['systems_affected'] ? h($cr['systems_affected']) : 'Not specified' ?>
      </div>
    </div>
    <div class="field">
      <div class="field-label">Vendor involved?</div>
      <div class="field-value"><?= (int)$cr['vendor_involved'] === 1 ? 'Yes' : 'No' ?></div>
    </div>
    <div class="field">
      <div class="field-label">Vendor name</div>
      <div class="field-value <?= empty($cr['vendor_name']) ? 'empty' : '' ?>">
        <?= $cr['vendor_name'] ? h($cr['vendor_name']) : '—' ?>
      </div>
    </div>
  </div>

  <div class="section-label">Business Justification</div>
  <div class="prose-block">
    <div class="field-label">Reason for request</div>
    <div class="field-value"><?= h($cr['reason_for_request']) ?></div>
  </div>
  <div class="prose-block">
    <div class="field-label">Impact if not implemented</div>
    <div class="field-value <?= empty($cr['impact_if_not_implemented']) ? 'empty' : '' ?>">
      <?= $cr['impact_if_not_implemented'] ? h($cr['impact_if_not_implemented']) : 'Not specified' ?>
    </div>
  </div>

  <div class="section-label">Approvals</div>
  <?php if (!empty($approvals)): ?>
    <div class="approval-list">
      <?php foreach ($approvals as $a):
        $dClass = strtolower($a['decision']) === 'approved' ? 'approved' : (strtolower($a['decision']) === 'rejected' ? 'rejected' : 'pending');
        $dSymbol = $dClass === 'approved' ? '✓' : ($dClass === 'rejected' ? '✕' : '···');
      ?>
      <div class="approval-item">
        <div class="approval-dot <?= $dClass ?>"><?= $dSymbol ?></div>
        <div>
          <div class="approval-role"><?= h($a['approval_level']) ?> — <?= h($a['decision'] ?: 'Pending') ?></div>
          <div class="approval-sub">
            <?= $a['approver_name'] ? h($a['approver_name']) : 'Awaiting assignment' ?>
            <?php if ($a['decided_at']): ?> · <?= fmt_datetime($a['decided_at']) ?><?php endif; ?>
          </div>
          <?php if (!empty($a['comments'])): ?>
            <div class="approval-comment"><?= h($a['comments']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="no-approvals">No approval activity recorded yet for this request.</div>
  <?php endif; ?>

  <div class="section-label">Implementation</div>
<?php
    $hasImplementationActivity = $cr['received_by_it'] || $cr['implemented_by'] || !empty($cr['issues_encountered']);
?>
<?php if ($hasImplementationActivity): ?>
  <div class="approval-list">

      <?php if ($cr['received_by_it']): ?>
      <div class="approval-item">
        <div class="approval-dot approved"><span class="fa fa-check"></span></div>
        <div>
          <div class="approval-role">Received by IT</div>
          <div class="approval-sub"><?= h($cr['received_by_name'] ?: 'Unknown') ?></div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($cr['implemented_by']): ?>
      <div class="approval-item">
        <div class="approval-dot approved"><span class="fa fa-check"></span></div>
        <div>
          <div class="approval-role">Implemented by <?= h($cr['implemented_by_name'] ?: 'Unknown') ?></div>
          <?php if ($cr['expected_downtime']): ?>
            <div class="approval-sub">Expected downtime: <?= h($cr['expected_downtime']) ?></div>
          <?php endif; ?>
          <?php if (!empty($cr['implementation_notes'])): ?>
            <div class="approval-comment"><?= nl2br(h($cr['implementation_notes'])) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      
      <?php
        $log = str_replace("\r\n", "\n", $cr['issues_encountered'] ?? '');
        if (trim($log) !== ''):
            $found = preg_match_all(
                '/\[Attempt (\d+)[^\]]*?rolled back (.*?) by (.*?)\]\s*(.*?)(?=\s*\[(?:Attempt \d+|Closing note)|\z)/s',
                $log, $matches, PREG_SET_ORDER
            );

            if ($found):
                foreach ($matches as $m):
          ?>
            <div class="approval-item">
              <div class="approval-dot rejected"><span class="fas fa-xmark"></span></div>
              <div>
                <div class="approval-role">Rollback — Attempt <?= h($m[1]) ?></div>
                <div class="approval-sub"><?= h($m[3]) ?> · <?= h($m[2]) ?></div>
                <?php if (trim($m[4]) !== ''): ?>
                  <div class="approval-comment"><?= nl2br(h(trim($m[4]))) ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php
                  endforeach;
              else:
          ?>
            <div class="approval-item">
              <div class="approval-dot bg-primary text-white"><span class="fa fa-history"></span></div>
              <div>
                <div class="approval-role">Rollback history</div>
                <div class="approval-comment"><?= nl2br(h($log)) ?></div>
              </div>
            </div>
          <?php
              endif;
              endif;
          ?>

      <?php if (!empty($cr['validation_results'])): ?>
      <div class="approval-item">
        <div class="approval-dot approved"><span class="fa fa-check"></span></div>
        <div>
          <div class="approval-role">Validated &amp; Tested</div>
          <div class="approval-comment"><?= nl2br(h($cr['validation_results'])) ?></div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($status === 'Closed'):
        $closingNote = '';
          if (preg_match('/\[Closing note — (.*?) by (.*?)\]\n?(.*)\z/s', $cr['issues_encountered'] ?? '', $cm)) {
            $closingNote = trim($cm[3]);
        }
      ?>
      <div class="approval-item">
        <div class="approval-dot approved"><span class="fa fa-check"></span></div>
        <div>
          <div class="approval-role">Closed</div>
          <?php if ($closingNote): ?>
            <div class="approval-comment"><?= nl2br(h($closingNote)) ?></div>
          <?php else: ?>
            <div class="approval-sub">No issues reported.</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>

  <?php else: ?>
    <div class="no-approvals">No implementation activity recorded yet.</div>
  <?php endif; ?>

  <?php if ($canAct): ?>
  <div class="action-panel">
    <div class="field-label">Your decision — acting as <?= h($approvalLevel) ?></div>
      <div class="form-row">
        <div class="form-outline">
          <textarea id="comments" name="comments" class="form-control" rows="5" style="resize:none;"></textarea>
          <label class="form-label" for="comments">Comments (optional)</label>
        </div>
      </div>
    <div class="action-btns">
      <button type="button" class="btn btn-success btn-lg rounded-pill" id="btnApprove">Approve</button>
      <button type="button" class="btn btn-danger btn-lg rounded-pill" id="btnReject">Reject</button>
    </div>
  </div>
  <?php endif; ?>
  <div class="sheet-footer">
    <button type="button" class="btn btn-outline-kaban rounded-pill" onclick="window.print()">Print</button>
    <?php if (in_array($status, ['Approved', 'Scheduled', 'Implementing']) 
              && in_array($role, ['IT Support Specialist', 'IT Supervisor', 'IT Manager'])): ?>
      <a href="implement_change_request.php?id=<?= (int)$cr['id'] ?>" class="btn btn-outline-info rounded-pill">
        Go to Implementation
      </a>
    <?php endif; ?>
  </div>

</div>

<script type="text/javascript" src="./assets/js/mdb-ui-kit-mdb.min.js"></script>
<script type="text/javascript" src="./assets/js/jquery-4.0.0.min.js"></script>
<script src="./assets/js/sweetalert2.all.min.js"></script>
<script type="text/javascript">
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
            Swal.fire({
                title: response.status === 'success' ? 'Success!' : 'Error!',
                text: response.message,
                icon: response.status === 'success' ? 'success' : 'error',
                confirmButtonText: 'OK'
            }).then(() => { if (response.status === 'success') location.reload(); });
        },
        error: function(xhr, status, error) {
            Swal.fire({ title: 'Error!', text: 'An error occurred: ' + error, icon: 'error' });
        }
    });
}

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
        if (result.isConfirmed) submitDecision('Approved');
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
        if (result.isConfirmed) submitDecision('Rejected');
    });
});
</script>
</body>
</html>