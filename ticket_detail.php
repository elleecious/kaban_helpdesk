<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php") ?>
<?php $page_title = "KabanDesk"; ?>
<?php
    if (!isset($_GET['id'])) {
        header("location: index.php");
    }
    $getTicket = retrieve("SELECT cat.name AS category_name, t.ticket_number AS ticket_number, t.subject AS subject, t.description AS description, t.priority AS priority, t.status AS status
FROM tickets AS t INNER JOIN categories AS cat ON t.category_id=cat.id WHERE t.id=?",array($_GET['id']));
?>


<div class="container my-4" style="max-width: 1080px;">

  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <p class="text-muted mb-1" style="font-size:15px;">Ticket &middot; <?php echo htmlspecialchars($getTicket[0]['ticket_number']); ?></p>
      <h4 class="mb-0 fw-bold"><?php echo $getTicket[0]['subject']; ?></h4>
    </div>
    <span class="badge" style="font-size: 25px;" id="statusTicket"><?php echo htmlspecialchars($getTicket[0]['status']); ?></span>
  </div>

  <div class="row g-3">

    <div class="col-lg-8">

      <div class="card mb-3">
        <div class="card-body">
          <p class="card-eyebrow mb-2">Description</p>
          <p class="mb-3"><?php echo htmlspecialchars($getTicket[0]['description']); ?></p>
          <a class="attachment-chip" href="#">
            <i class="fa-solid fa-paperclip"></i> printer_error.jpg
          </a>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <p class="card-eyebrow mb-3">Activity</p>

          <div class="d-flex gap-3 mb-3">
            <div class="avatar-circle avatar-blue"><span class="fa fa-user-circle fa-xl"></span>&nbsp;</div>
            <div>
              <p class="mb-0" style="font-size:0.85rem;">
                <span class="fw-semibold">Ellee</span>
                <span class="text-muted">created this ticket &middot; 11:19 AM</span>
              </p>
            </div>
          </div>

          <p class="system-note mb-3"><i class="fa-solid fa-circle"></i>Jomar (IT) was assigned &middot; 11:42 AM</p>

          <div class="d-flex gap-3 mb-4">
            <div class="avatar-circle avatar-green">JC</div>
            <div>
              <p class="mb-1" style="font-size:0.85rem;">
                <span class="fw-semibold">Jomar (IT)</span>
                <span class="text-muted">replied &middot; 12:05 PM</span>
              </p>
              <p class="mb-0">Checked the switch port on the floor, seems to be down. Sending a tech over now to check the cabling.</p>
            </div>
          </div>

          <div class="border-top pt-3">
            <div class="form-outline mb-2">
              <textarea class="form-control" id="replyInput" rows="3" placeholder="Write a reply"></textarea>
            </div>
            <p id="replyError" class="text-danger mb-2" style="display:none; font-size:0.8rem;">Write a reply before sending.</p>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-paperclip me-1"></i> Attach
              </button>
              <button type="button" class="btn btn-kaban btn-sm text-white" id="sendReplyBtn">Send reply</button>
            </div>
          </div>

        </div>
      </div>

    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">

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
            <span class="sla-val">Today, 7:19 PM</span>
          </div>
          <div class="sla-row">
            <span class="sla-key">Resolution due</span>
            <span class="sla-val">Aug 15, 11:19 AM</span>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <p class="card-eyebrow mb-2">Assigned to</p>
          <select class="form-select form-select-sm" id="assigneeSelect">
            <option>Unassigned</option>
            <option selected>Jomar (IT)</option>
            <option>Angge (IT)</option>
          </select>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <p class="card-eyebrow mb-2">Status</p>
          <select class="form-select form-select-sm" id="statusSelect">
            <option>Open</option>
            <option>In progress</option>
            <option>Resolved</option>
            <option>Closed</option>
          </select>
        </div>
      </div>

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