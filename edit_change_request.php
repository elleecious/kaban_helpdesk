<?php 
include("config/connect.php");
include("includes/session.php"); 
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Change Request — Kaban Hotel &amp; Casino</title>

<!-- MDBootstrap 5 (Material Design for Bootstrap) -->
<link rel="stylesheet" href="./assets/css/mdb-ui-kit-mdb.min.css">
<link rel="stylesheet" href="./assets/css/change_request_form.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Roboto:wght@400;500&display=swap">
</head>
<body>

<?php
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

    // Only the original requestor may edit, and only while nothing has been decided yet
    $editableStatuses = ['Draft', 'Submitted'];
    if ((int)$cr['requestor_id'] !== (int)$login_id || !in_array($cr['status'], $editableStatuses)) {
        die("This change request can no longer be edited.");
    }

    function val($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }
?>

<div class="brand-bar">
  <div class="container d-flex align-items-center" style="max-width:900px;">
    <img src="./assets/img/kaban-logo-horizontal.png" alt="Kaban Hotel and Casino Boracay" class="brand-logo">
  </div>
</div>

<form class="form-shell" id="editChangeRequestForm" name="editChangeRequestForm" novalidate>
  <input type="hidden" id="change_request_id" value="<?= (int)$cr['id'] ?>">

  <div class="form-heading">
    <div>
      <h1>Edit Change Request</h1>
      <p>Update the details below and save your changes.</p>
    </div>
    <div class="crn-box">
      <label for="crf_number">Change Request No.</label>
      <input type="text" id="crf_number" class="form-control form-control-sm" value="<?= val($cr['crf_number']) ?>" readonly>
    </div>
  </div>

  <div class="form-body">

    <!-- Requestor info -->
    <div class="row form-row gy-3">
      <div class="col-md-6">
        <div class="md-form">
          <input type="text" id="reqName" class="form-control" value="<?= val($cr['requestor_name']) ?>" readonly>
          <label class="form-label active" for="reqName">Requestor's name</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="md-form">
          <input type="text" id="dateSubmitted" class="form-control" value="<?= date("M j, Y", strtotime($cr['created_at'])) ?>" readonly>
          <label class="form-label active" for="dateSubmitted">Date submitted</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="md-form">
          <input type="tel" id="contact_number" name="contact_number" class="form-control" value="<?= val($cr['contact_number']) ?>" required>
          <label class="form-label active" for="contact_number">Contact number <span class="req-mark">*</span></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="md-form">
          <select id="department" name="department" class="form-select" required>
            <option value="" disabled>Choose department...</option>
            <?php
              $department = array("Information Technology","Human Resources","Housekeeping","Engineering",
              "Slot and Electronic Table Games","Table Games","Sales & Marketing","Cage",
              "Surveillance","Warehouse","Gaming Security","Finance & Accounting",
              "Building Management","Front Office","Food and Beverage","Purchasing");
              sort($department);
              foreach ($department as $dept) {
                  $selected = ($dept === $cr['department']) ? 'selected' : '';
                  echo "<option value='".val($dept)."' {$selected}>".val($dept)."</option>";
              }
            ?>
          </select>
        </div>
      </div>
    </div>

    <!-- Change type -->
    <div class="form-row">
      <div class="field-label mb-2">Change type <span class="req-mark">*</span></div>
      <div class="d-flex flex-wrap gap-2" role="radiogroup" aria-label="Change Type">
        <?php foreach (['Standard','Normal','Major','Emergency'] as $type):
            $checked = ($type === $cr['change_type']) ? 'checked' : '';
        ?>
        <label class="type-chip" for="change_type_<?= strtolower($type) ?>">
          <input type="radio" name="change_type" id="change_type_<?= strtolower($type) ?>" value="<?= $type ?>" <?= $checked ?>><?= $type ?>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="section-band">Change Request Details</div>

  <div class="form-body">
    <div class="row form-row gy-3">
      <div class="col-md-6">
        <div class="md-form">
            <label class="form-label active" for="change_title">Change title <span class="req-mark">*</span></label>
            <input type="text" id="change_title" name="change_title" class="form-control" value="<?= val($cr['change_title']) ?>" required>
        </div>
      </div>
      <div class="col-md-6">
        <div class="md-form">
            <label class="form-label active" for="location_site">Location / site affected</label>
            <input type="text" id="location_site" name="location_site" class="form-control" value="<?= val($cr['location_site']) ?>">
          
        </div>
      </div>
      <div class="col-md-6">
        <div class="md-form">
            <label class="form-label active" for="requested_implementation_date">Requested implementation date <span class="req-mark">*</span></label>
            <input type="date" id="requested_implementation_date" name="requested_implementation_date" class="form-control" value="<?= val($cr['requested_implementation_date']) ?>" required>
        </div>
      </div>
      <div class="col-md-6">
        <div class="md-form">
            <label class="form-label active" for="systems_affected">Systems / services affected</label>
            <input type="text" id="systems_affected" name="systems_affected" class="form-control" value="<?= val($cr['systems_affected']) ?>">
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="field-label mb-2">Vendor involved?</div>
      <div class="d-flex gap-2">
        <label class="yn-chip"><input type="radio" name="vendor_involved" value="Yes" id="vYes" <?= ($cr['vendor_involved'] == 1) ? 'checked' : '' ?>>Yes</label>
        <label class="yn-chip"><input type="radio" name="vendor_involved" value="No" id="vNo" <?= ($cr['vendor_involved'] == 0) ? 'checked' : '' ?>>No</label>
      </div>
      <div class="vendor-fade <?= ($cr['vendor_involved'] == 1) ? 'show' : '' ?>" id="vendorNameWrap">
        <div class="outline">
          <input type="text" id="vendor_name" name="vendor_name" class="form-control" value="<?= val($cr['vendor_name']) ?>">
          <label class="form-label <?= $cr['vendor_name'] ? 'active' : '' ?>" for="vendor_name">Vendor name</label>
        </div>
      </div>
    </div>

  </div>

  <div class="section-band">Business Justification</div>

  <div class="form-body">
    <div class="form-row">
      <div class="md-form">
        <label class="form-label active" for="reason_for_request">Reason for request <span class="req-mark">*</span></label>
        <textarea id="reason_for_request" name="reason_for_request" class="form-control md-textarea" rows="5" required style="resize:none;"><?= val($cr['reason_for_request']) ?></textarea>
      </div>
    </div>
    <div class="form-row">
      <div class="md-form">
        <label class="form-label active" for="impact_if_not_implemented">Impact if not implemented</label>
        <textarea id="impact_if_not_implemented" name="impact_if_not_implemented" class="form-control md-textarea" rows="5" style="resize:none;"><?= val($cr['impact_if_not_implemented']) ?></textarea>
      </div>
    </div>
  </div>

  <div class="footer-actions">
    <a href="view_change_request.php?id=<?= (int)$cr['id'] ?>" class="btn btn-outline-kaban rounded-pill">Cancel</a>
    <button type="submit" class="btn btn-kaban rounded-pill text-white" id="save_request">Save changes</button>
  </div>
</form>

<script type="text/javascript" src="./assets/js/mdb-ui-kit-mdb.min.js"></script>
<script type="text/javascript" src="./assets/js/jquery-4.0.0.min.js"></script>
<script src="./assets/js/sweetalert2.all.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {

    $('input[name="vendor_involved"]').on('change', function() {
        if ($('#vYes').is(':checked')) {
            $('#vendorNameWrap').addClass('show');
        } else {
            $('#vendorNameWrap').removeClass('show');
            $('#vendor_name').val('');
        }
    });

    $("#editChangeRequestForm").on("submit", function(e){
        e.preventDefault();

        const changeType = $('input[name="change_type"]:checked').val();
        if (!changeType) {
            Swal.fire({
                title: "Warning",
                text: "Please select a change type.",
                icon: "warning",
                confirmButtonText: "OK"
            });
            return;
        }

        $.ajax({
            url: "./api/update_change_request.php",
            type: 'POST',
            data: {
                change_request_id: $('#change_request_id').val(),
                contact_number: $('#contact_number').val(),
                department: $('#department').val(),
                change_type: changeType,
                change_title: $('#change_title').val(),
                location_site: $('#location_site').val(),
                systems_affected: $('#systems_affected').val(),
                requested_implementation_date: $('#requested_implementation_date').val(),
                vendor_involved: $('input[name="vendor_involved"]:checked').val() || 'No',
                vendor_name: $('#vendor_name').val(),
                reason_for_request: $('#reason_for_request').val(),
                impact_if_not_implemented: $('#impact_if_not_implemented').val()
            },
            dataType: 'json',
            success: function(response){
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Saved!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'view_change_request.php?id=' + $('#change_request_id').val();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred: ' + error,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
});
</script>
</body>
</html>
