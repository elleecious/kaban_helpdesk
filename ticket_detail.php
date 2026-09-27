<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php"); ?>
<?php include("library/functions.php"); ?>
<?php include("includes/modal.php"); ?>
<?php $page_title = "KabanDesk"; ?>
<?php
    if (!isset($_GET['id'])) {
        header("location: index.php");
    }
    $getTicket = retrieve("SELECT t.id AS ticket_id, t.assigned_to, emp.name AS created_by_name,
          it.name AS assigned_to_name,cat.name AS category_name,t.ticket_number,
          t.subject, t.description, t.escalated_by, t.escalation_reason,
          t.priority,t.status, t.created_at AS ticket_date,
          t.response_due_at, t.resolution_due_at, t.resolution_notes, t.resolved_at,
          com.body AS comments, com.created_at AS comment_date, att.file_url AS attachment_url, att.file_name AS attachment_name
          FROM tickets AS t
            LEFT JOIN categories AS cat 
              ON t.category_id = cat.id
            LEFT JOIN users AS emp 
              ON t.created_by = emp.id
            LEFT JOIN users AS it 
              ON t.assigned_to = it.id
            LEFT JOIN comments AS com
              ON t.id = com.ticket_id
            LEFT JOIN attachments AS att
              ON t.id = att.ticket_id
            WHERE t.id=?",array($_GET['id']));

      $escalatedByName = '';
        if ($getTicket[0]['status'] === 'Escalated' && $getTicket[0]['escalated_by']) {
            $get_escalator = retrieve("SELECT name FROM users WHERE id = ?", array($getTicket[0]['escalated_by']));
            $escalatedByName = $get_escalator[0]['name'] ?? 'Unknown';
        }

?>


<div class="container mt-5" style="max-width: 1080px;">
  
  <div class="d-flex justify-content-between align-items-start">
    <div>
      <h4 class="text-muted mt-5 display-5"><?= htmlspecialchars($getTicket[0]['ticket_number']); ?></h4>
    </div>
    <span class="badge mt-5" style="font-size: 25px;" id="statusTicket"><?= htmlspecialchars($getTicket[0]['status']); ?></span>
  </div>

  <div class="row g-3">

    <div class="col-lg-8 mt-3">

      <div class="card mb-3">
        <div class="card-body">
          <h4 class="mb-3 fw-bold">Subject: <?= $getTicket[0]['subject']; ?></h4>
          <p class="card-eyebrow mb-2">Description</p>
          <p class="mb-3"><?= $getTicket[0]['description']; ?></p>
            <a class="attachment-chip" href="./<?= htmlspecialchars($getTicket[0]['attachment_url'] ?? ""); ?>" target="_blank">
              <i class="fa-solid fa-paperclip"></i><span> <?= htmlspecialchars($getTicket[0]['attachment_name'] ?? "No attachment"); ?></span>
            </a>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <p class="card-eyebrow mb-3">Activity</p>

          <div class="d-flex gap-3 mb-3 mr-3">
            <div class="avatar-circle avatar-green"><span class="fa fa-user-circle fa-xl"></span>&nbsp;</div>
            <div>
              <p class="mb-0 ml-1" style="font-size:0.85rem;">
                <span class="fw-semibold"><?= $getTicket[0]['created_by_name']; ?></span>
                <span class="text-muted">created this ticket &middot; <?= date('g:i a', strtotime($getTicket[0]['ticket_date'])); ?></span>
              </p>
            </div>
          </div>

          <p class="system-note mb-3"><?= $getTicket[0]['assigned_to_name']; ?> was assigned</p>
          <?php
              $comments = retrieve(
                  "SELECT c.id, c.body, c.created_at, u.name AS author_name, u.role AS author_role
                  FROM comments AS c
                  LEFT JOIN users AS u ON c.author_id = u.id
                  WHERE c.ticket_id = ?
                  ORDER BY c.id ASC",
                  array($_GET['id'])
              );
              ?>
              <?php foreach ($comments as $comment): ?>
                <div class="d-flex gap-3 mb-4">
                  <div class="avatar-circle <?= $comment['author_role'] === 'IT Support Specialist' ? 'avatar-blue' : 'avatar-green'; ?>">
                    <span class="fa-solid fa-user-circle fa-xl"></span>
                  </div>
                  <div>
                    <p class="mb-1 ml-1" style="font-size:0.85rem;">
                      <?= $comment['author_role'] === 'IT Support Specialist' || 'IT Supervisor'  ? 
                        '<span class="fw-semibold text-primary">' . htmlspecialchars($comment['author_name']) . '</span>' : 
                        '<span class="fw-semibold">' . htmlspecialchars($comment['author_name']) . '</span>' ?>
                      <span class="text-muted">commented &middot; <?= date('g:i a', strtotime($comment['created_at'])); ?></span>
                    </p>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($comment['body'])); ?></p>
                  </div>
                </div>
                <?php endforeach; ?>

              <?php if (empty($comments)): ?>
              <p class="text-muted">No comments yet.</p>
              <?php endif; ?>

          <div class="border-top pt-3">
            <div class="md-form mb-2">
              <i class="fa fa-comment prefix"></i>
              <textarea class="form-control md-textarea" name="comment" id="comment" rows="3" style="resize:none;"></textarea>
              <label>Write a comment</label>
            </div>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-kaban btn-sm text-white" id="btnComment" data-ticket-id="<?= $getTicket[0]['ticket_id'] ?>">Send Comment</button>
            </div>
          </div>
        </div>
      </div>

      <div class="card mt-3">
        <div class="card-body">
          <p class="card-eyebrow mb-2">Resolution Notes: <span class="font-weight-bold"><?= $getTicket[0]['resolution_notes'] ?? ''; ?></span> </p>
          <span>Date Resolved: <?= ($getTicket[0]['resolved_at'] == '' ? '' : date("M d, Y g:i A", strtotime($getTicket[0]['resolved_at']))); ?> </span>
        </div>
      </div>

    </div>

    <!-- Sidebar -->
    <div class="col-lg-4 mt-3">

      <div class="card mb-3">
        <div class="card-body">
          <div class="mb-3">
            <p class="field-label mb-1">Priority</p>
            <span class="badge-priority badge-priority-low">Low</span>
          </div>
          <div>
            <p class="field-label mb-1">Category</p>
            <p class="field-value mb-0">Point of sale</p>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <p class="card-eyebrow mb-2">SLA</p>
          <div class="sla-row">
            <span class="sla-key">Response due</span>
            <span class="sla-val"><?= date('M d, Y g:i A', strtotime($getTicket[0]['response_due_at'])); ?></span>
          </div>
          <div class="sla-row">
            <span class="sla-key">Resolution due</span>
            <span class="sla-val"><?= date('M d, Y g:i A', strtotime($getTicket[0]['resolution_due_at'])); ?></span>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <p class="card-eyebrow mb-2">Assigned to</p>
          <span class="font-weight-bold"><?= $getTicket[0]['assigned_to_name']; ?></span>
        </div>
      </div>

      <?php if ($getTicket[0]['status'] === 'Escalated' && in_array($_SESSION['role'], ['IT Supervisor', 'IT Manager'])): ?>
      <div class="alert alert-warning">
          <strong><span class="fa fa-warning"></span> 
          Escalated by <?= $escalatedByName ?>:</strong>
          Ticket Number: <?= htmlspecialchars($getTicket[0]['ticket_number']); ?><br>
          Subject: <?= htmlspecialchars($getTicket[0]['escalation_reason']) ?>
      </div>
      <div class="btn-group">
          <button class="btn btn-primary btn-md ml-1" 
              data-toggle="modal" 
              data-target="#assignTicketModal"
              data-id="<?= $getTicket[0]['ticket_id']; ?>"
              data-ticket-number="<?= $getTicket[0]['ticket_number']; ?>"
              data-subject="<?= htmlspecialchars($getTicket[0]['subject']); ?>">
              Reassign to Another IT
          </button>
          <button class="btn btn-success btn-md ml-1" id="btnTakeOver" data-ticket-id="<?= $getTicket[0]['ticket_id'] ?>">Take This Myself</button>
      </div>
      <?php endif; ?>

      <?php if ($getTicket[0]['status'] === 'In Progress' && $getTicket[0]['assigned_to'] === $login_id): ?>
      <div class="card mt-3">
          <div class="card-body">
              <label>Reason for Escalation</label>
              <textarea class="form-control" id="escalationReason" rows="3" placeholder="Why does this need to go up? (e.g. needs vendor support, needs elevated access)" style="resize:none;"></textarea>
              <button class="btn btn-danger btn-md mt-2" id="btnEscalate" data-ticket-id="<?= $getTicket[0]['ticket_id'] ?>">
                  Escalate to Supervisor
              </button>
          </div>
      </div>
      <?php endif; ?>

        <?php if (!in_array($getTicket[0]['status'], ['Resolved', 'Closed']) && $getTicket[0]['assigned_to'] == $login_id): ?>
        <div class="card mt-3">
            <div class="card-body">
                <label>Resolution Notes</label>
                <textarea class="form-control" id="resolutionNotes" rows="3" placeholder="What was done to fix this?" style="resize:none;"></textarea>
                <button class="btn btn-success mt-2" id="btnMarkResolved" data-ticket-id="<?= $getTicket[0]['ticket_id'] ?>">
                    Mark as Resolved
                </button>
            </div>
        </div>
        <?php endif; ?>

    </div>

  </div>
</div>
<?php include("includes/footer.php"); ?>
<script>
$(document).ready(function () {
        
    var statusBadgeMap = {
        'Open':        'badge-warning',
        'In Progress': 'badge-primary',
        'Resolved':    'badge-success',
        'Closed':      'badge-secondary'
    };

    var currentStatus = $('#statusTicket').text().trim();
    var badgeClass = statusBadgeMap[currentStatus] || 'badge-secondary';

    $('#statusTicket').addClass(badgeClass);
});
</script>