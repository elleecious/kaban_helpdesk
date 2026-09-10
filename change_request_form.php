<?php 
include("config/connect.php");
include("includes/session.php"); 
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Change Request Form — Kaban Hotel &amp; Casino</title>

<!-- MDBootstrap 5 (Material Design for Bootstrap) -->
<link rel="stylesheet" href="./assets/css/mdb-ui-kit-mdb.min.css">
<link rel="stylesheet" href="./assets/css/change_request_form.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Roboto:wght@400;500&display=swap">
</head>
<body>

<?php
    $requested_by = retrieve("SELECT name FROM users WHERE id=?",array($login_id));
?>

<div class="brand-bar">
  <div class="container d-flex align-items-center" style="max-width:900px;">
    <img src="./assets/img/kaban-logo-horizontal.png" alt="Kaban Hotel and Casino Boracay" class="brand-logo">
  </div>
</div>

<form class="form-shell" id="changeRequestForm" name="changeRequestForm" novalidate>

  <div class="form-heading">
    <div>
      <h1>Change Request Form</h1>
      <p>Submit this form to request a change to any IT system, service, or infrastructure.</p>
    </div>
    <div class="crn-box">
      <label for="crf_number">Change Request No.</label>
      <input type="text" id="crf_number" name="crf_number" class="form-control form-control-sm" placeholder="Auto-generated" readonly>
    </div>
  </div>

  <div class="form-body">

    <!-- Requestor info -->
    <div class="row form-row gy-3">
      <div class="col-md-6">
        <div class="form-outline">
          <input type="text" id="reqName" name="reqName"  class="form-control" value="<?php echo $requested_by[0]['name']; ?>">
          <label class="form-label" for="reqName">Requestor's name <span class="req-mark">*</span></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-outline">
          <input type="date" id="dateSubmitted" name="dateSubmitted" class="form-control" required>
          <label class="form-label" for="dateSubmitted">Date submitted <span class="req-mark">*</span></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-outline">
          <input type="tel" id="contact_number" name="contact_number" class="form-control" required>
          <label class="form-label" for="contact_number">Contact number <span class="req-mark">*</span></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-outline">
          <select id="department" name="department" class="form-select" required>
            <option value="" selected disabled>Choose department...</option>
            <?php
              $department = array("Information Technology","Human Resources","Housekeeping","Engineering",
              "Slot and Electronic Table Games","Table Games","Sales & Marketing","Cage",
              "Surveillance","Warehouse","Gaming Security","Finance & Accounting",
              "Building Management","Front Office","Food and Beverage","Purchasing");
              sort($department);
              for ($i=0; $i < count($department); $i++) { 
                  echo "<option value='".$department[$i]."'>".$department[$i]."</option>";
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
          <label class="type-chip" for="change_type_standard"><input type="radio" name="change_type" id="change_type_standard" value="Standard">Standard</label>
          <label class="type-chip" for="change_type_normal"><input type="radio" name="change_type" id="change_type_normal" value="Normal">Normal</label>
          <label class="type-chip" for="change_type_major"><input type="radio" name="change_type" id="change_type_major" value="Major">Major</label>
          <label class="type-chip" for="change_type_emergency"><input type="radio" name="change_type" id="change_type_emergency" value="Emergency">Emergency</label>
        </div>
      </div>
  </div>

  <div class="section-band">Change Request Details</div>

  <div class="form-body">
    <div class="row form-row gy-3">
      <div class="col-md-6">
        <div class="form-outline">
          <input type="text" id="change_title" name="change_title" class="form-control" required>
          <label class="form-label" for="change_title">Change title <span class="req-mark">*</span></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-outline">
          <input type="text" id="location_site" name="location_site" class="form-control" placeholder="e.g. Casino Floor, Hotel Lobby">
          <label class="form-label" for="location_site">Location / site affected</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-outline">
          <input type="date" id="requested_implementation_date" name="requested_implementation_date" class="form-control" required>
          <label class="form-label" for="requested_implementation_date">Requested implementation date <span class="req-mark">*</span></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-outline">
          <input type="text" id="systems_affected" name="systems_affected" class="form-control" placeholder="e.g. NetSuite, Opera Cloud, MyACP">
          <label class="form-label" for="systems_affected">Systems / services affected</label>
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="field-label mb-2">Vendor involved?</div>
      <div class="d-flex gap-2">
        <label class="yn-chip"><input type="radio" name="vendor_involved" value="Yes" id="vYes">Yes</label>
        <label class="yn-chip"><input type="radio" name="vendor_involved" value="No" id="vNo">No</label>
      </div>
      <div class="vendor-fade" id="vendorNameWrap">
        <div class="form-outline">
          <input type="text" id="vendor_name" name="vendor_name" class="form-control">
          <label class="form-label" for="vendor_name">Vendor name</label>
        </div>
      </div>
    </div>

  </div>

  <div class="section-band">Business Justification</div>

  <div class="form-body">
    <div class="form-row">
      <div class="form-outline">
        <textarea id="reason_for_request" name="reason_for_request" class="form-control" rows="4" required></textarea>
        <label class="form-label" for="reason_for_request">Reason for request <span class="req-mark">*</span></label>
      </div>
    </div>
    <div class="form-row">
      <div class="form-outline">
        <textarea id="impact_if_not_implemented" name="impact_if_not_implemented" class="form-control" rows="3"></textarea>
        <label class="form-label" for="impact_if_not_implemented">Impact if not implemented</label>
      </div>
    </div>
  </div>

  <div class="section-band">Approvals</div>

  <div class="form-body">
    <div class="row sign-block gy-4">
      <div class="col-md-6">
        <div class="sign-line">signature</div>
        <div class="sign-caption">Requested by <span><br><?php echo $requested_by[0]['name']; ?></span></div>
      </div>
      <div class="col-md-6">
        <div class="sign-line">signature</div>
        <div class="sign-caption">Noted by — IT Manager / Project Manager</div>
      </div>
      <div class="col-md-6">
        <div class="sign-line">signature</div>
        <div class="sign-caption">Received by I.T.</div>
      </div>
      <div class="col-md-6">
        <div class="sign-line">signature</div>
        <div class="sign-caption">Implemented by</div>
      </div>
    </div>
  </div>

  <div class="footer-actions">
    <button type="button" class="btn btn-outline-kaban rounded-pill" id="resetBtn">Clear form</button>
    <button type="submit" class="btn btn-kaban rounded-pill text-white" id="submit_request" name="submit_request">Submit request</button>
  </div>
</form>

<script type="text/javascript" src="./assets/js/mdb-ui-kit-mdb.min.js"></script>
<script type="text/javascript" src="./assets/js/jquery-4.0.0.min.js"></script>
<script src="./assets/js/sweetalert2.all.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {

    $.ajax({
        url: "./api/get_crf_number.php",
        type: "GET",
        dataType: "JSON",
        success: function(response) {
            if (response.crf_number) {
                console.log("CRF Number: " + response.crf_number);
                $('#crf_number').val(response.crf_number);
            }
        },
        error: function(xhr, status, error) {
            console.log('Error fetching CRF number: ', error);
        }
    });

    $('input[name="vendor_involved"]').on('change', function() {
          if ($('#vYes').is(':checked')) {
              console.log("Checked Vendor");
              $('#vendorNameWrap').addClass('show');
          } else {
              console.log("Not Checked Vendor");
              $('#vendorNameWrap').removeClass('show');
              $('#vendor_name').val('');
          }
        });


    $("#submit_request").on("click", function(e){
        e.preventDefault();

        const changeType = $('input[name="change_type"]:checked').val();
        console.log("Change Type: " + changeType);
        if (changeType.length === 0) {
            Swal.fire({
                title: "Warning",
                text: "Please select a change type.",
                icon: "warning",
                confirmButtonText: "OK"
            });
            return;
        }

        $.ajax({
            url: "./api/add_change_request.php",
            type: 'POST',
            data: {
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
                impact_if_not_implemented: $('#impact_if_not_implemented').val(),
            },
            dataType: 'json',
            success: function(response){
                console.log(response);
                if (response.status === 'success') {
                    $('#crf_number').val(response.crf_number);
                    Swal.fire({
                        title: response.status === 'success' ? 'Success! ' : 'Error!',
                        text: response.message,
                        icon: response.status === 'success' ? 'success' : 'error',
                        confirmButtonText: 'OK',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: response.status,
                        text: 'Error: ' + response.message,
                        icon: response.status,
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log("Error ooccured: " + error);
                console.log("Ajax Error: " + xhr.responseText);
                console.log("Ajax Status: " + status);
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
