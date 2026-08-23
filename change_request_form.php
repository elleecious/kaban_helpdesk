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

<div class="brand-bar">
  <div class="container d-flex align-items-center gap-2" style="max-width:900px;">
    <div class="logo-mark">K</div>
    <div>
      <div class="brand-name">KABAN BORACAY</div>
      <div class="brand-sub">Hotel &amp; Casino</div>
    </div>
  </div>
</div>

<form class="form-shell" id="crForm" novalidate>

  <div class="form-heading">
    <div>
      <h1>Change Request Form</h1>
      <p>Submit this form to request a change to any IT system, service, or infrastructure.</p>
    </div>
    <div class="crn-box">
      <label for="crn">Change Request No.</label>
      <input type="text" id="crn" class="form-control form-control-sm" placeholder="Auto-generated" readonly>
    </div>
  </div>

  <div class="form-body">

    <!-- Requestor info -->
    <div class="row form-row gy-3">
      <div class="col-md-6">
        <div class="form-outline">
          <input type="text" id="reqName" class="form-control" required>
          <label class="form-label" for="reqName">Requestor's name <span class="req-mark">*</span></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-outline">
          <input type="date" id="dateSubmitted" class="form-control" required>
          <label class="form-label" for="dateSubmitted">Date submitted <span class="req-mark">*</span></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-outline">
          <input type="tel" id="contactNo" class="form-control" required>
          <label class="form-label" for="contactNo">Contact number <span class="req-mark">*</span></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-outline">
          <select id="department" class="form-select" required>
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
      <div class="d-flex flex-wrap gap-2">
        <label class="type-chip"><input type="radio" name="changeType" value="Standard">Standard</label>
        <label class="type-chip"><input type="radio" name="changeType" value="Normal">Normal</label>
        <label class="type-chip"><input type="radio" name="changeType" value="Major">Major</label>
        <label class="type-chip"><input type="radio" name="changeType" value="Emergency">Emergency</label>
      </div>
    </div>
  </div>

  <div class="section-band">Change Request Details</div>

  <div class="form-body">
    <div class="row form-row gy-3">
      <div class="col-md-6">
        <div class="form-outline">
          <input type="text" id="changeTitle" name="changeTitle" class="form-control" required>
          <label class="form-label" for="changeTitle">Change title <span class="req-mark">*</span></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-outline">
          <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Casino Floor, Hotel Lobby">
          <label class="form-label" for="location">Location / site affected</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-outline">
          <input type="date" id="implDate" name="implDate" class="form-control" required>
          <label class="form-label" for="implDate">Requested implementation date <span class="req-mark">*</span></label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-outline">
          <input type="text" id="systemsAffected" name="systemsAffected" class="form-control" placeholder="e.g. NetSuite, Opera Cloud, MyACP">
          <label class="form-label" for="systemsAffected">Systems / services affected</label>
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="field-label mb-2">Vendor involved?</div>
      <div class="d-flex gap-2">
        <label class="yn-chip"><input type="radio" name="vendorInvolved" value="Yes" id="vYes">Yes</label>
        <label class="yn-chip"><input type="radio" name="vendorInvolved" value="No" id="vNo">No</label>
      </div>
      <div class="vendor-fade" id="vendorNameWrap">
        <div class="form-outline">
          <input type="text" id="vendorName" name="vendorName" class="form-control">
          <label class="form-label" for="vendorName">Vendor name</label>
        </div>
      </div>
    </div>
  </div>

  <div class="section-band">Business Justification</div>

  <div class="form-body">
    <div class="form-row">
      <div class="form-outline">
        <textarea id="reason" name="reason" class="form-control" rows="4" required></textarea>
        <label class="form-label" for="reason">Reason for request <span class="req-mark">*</span></label>
      </div>
    </div>
    <div class="form-row">
      <div class="form-outline">
        <textarea id="impact" name="impact" class="form-control" rows="3"></textarea>
        <label class="form-label" for="impact">Impact if not implemented</label>
      </div>
    </div>
  </div>

  <div class="section-band">Approvals</div>

  <div class="form-body">
    <div class="row sign-block gy-4">
      <div class="col-md-6">
        <div class="sign-line">signature</div>
        <div class="sign-caption">Requested by</div>
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
    <button type="submit" class="btn btn-kaban rounded-pill text-white">Submit request</button>
  </div>
</form>

<script type="text/javascript" src="./assets/js/mdb-ui-kit-mdb.min.js"></script>
<script>
  // Auto-generate a change request number
  document.getElementById('crn').value = 'CR-' + new Date().getFullYear() + '-' + String(Math.floor(Math.random()*9000)+1000);

  // Reveal vendor name field only when "Yes" is chosen
  const vendorWrap = document.getElementById('vendorNameWrap');
  document.querySelectorAll('input[name="vendorInvolved"]').forEach(el=>{
    el.addEventListener('change', ()=>{
      if(document.getElementById('vYes').checked){
        vendorWrap.classList.add('show');
      } else {
        vendorWrap.classList.remove('show');
        document.getElementById('vendorName').value = '';
      }
    });
  });

  const form = document.getElementById('crForm');
  form.addEventListener('submit', (e)=>{
    e.preventDefault();
    if(!form.checkValidity()){
      form.reportValidity();
      return;
    }
    const changeType = document.querySelector('input[name="changeType"]:checked');
    if(!changeType){
      alert('Please select a change type.');
      return;
    }
    alert('Change request ' + document.getElementById('crn').value + ' submitted for: ' + document.getElementById('changeTitle').value);
  });

  document.getElementById('resetBtn').addEventListener('click', ()=>{
    form.reset();
    vendorWrap.classList.remove('show');
  });
</script>
</body>
</html>
