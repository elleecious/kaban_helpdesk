<?php
include("config/connect.php");
include("includes/session.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$rows = retrieve(
    "SELECT cr.*, u.name AS requestor_name
     FROM change_requests cr
     LEFT JOIN users u ON u.id = cr.requestor_id
     WHERE cr.id = ?",
    array($id)
);

if (empty($rows)) {
    die("Change request not found.");
}
$cr = $rows[0];

// Approval rows tied to this request (safe to leave empty if table not yet in use)
$approvals = retrieve(
    "SELECT ca.approval_level, ca.decision, ca.comments, ca.decided_at, u.name AS approver_name
     FROM change_approvals ca
     LEFT JOIN users u ON u.id = ca.approver_id
     WHERE ca.change_request_id = ?
     ORDER BY ca.id ASC",
    array($cr['id'])
);

function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }
function fmt_date($v) { return $v ? date("M j, Y", strtotime($v)) : "—"; }
function fmt_datetime($v) { return $v ? date("M j, Y g:i A", strtotime($v)) : "—"; }

$status = $cr['status'] ?? 'Submitted';
$statusClasses = [
    'Draft'=> 'st-draft',
    'Submitted' => 'st-pending',
    'Under Review' => 'st-pending',
    'Approved' => 'st-approved',
    'Rejected' => 'st-rejected',
    'Scheduled' => 'st-approved',
    'Implementing' => 'st-approved',
    'Implemented' => 'st-done',
    'Reviewed' => 'st-done',
    'Closed' => 'st-done',
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
      $allowed_roles = ['IT Manager', 'General Manager', 'IT Supervisor', 'IT Support Specialist'];
      if (in_array($role, $allowed_roles)): 
    ?>
      <a href="manage_change_request.php" class="back-link">&larr; Back to list</a>
    <?php else: ?>
      <a href="view_all_change_requests.php" class="back-link">&larr; Back to list</a>
    <?php endif; ?>

  </div>
</div>

<div class="sheet">
  
  <div class="form-title-bar">
    <img src="./assets/img/kaban-logo-horizontal.png" alt="Kaban Hotel and Casino Boracay" class="title-bar-logo">
    <div class="title-bar-label">Change Request Form</div>
    <div></div>
  </div>
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
          <div class="approval-role"> <?= h($a['decision'] ?: 'Pending') ?> by the <?= h($a['approval_level']) ?></div>
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

  
  <?php
      if (in_array($role, $allowed_roles)):
  ?>
  <div class="sheet-footer">
    <button type="button" class="btn btn-outline-kaban rounded-pill" onclick="window.print()">Print</button>
    <?php if (in_array($status, ['Submitted','Under Review'])): ?>
      <a href="approve_change_request.php?id=<?= (int)$cr['id'] ?>" class="btn btn-kaban rounded-pill text-white">Review &amp; approve</a>
    <?php endif; ?>
  </div>
    <?php else: ?>
    <?php endif; ?>
</div>

</body>
</html>