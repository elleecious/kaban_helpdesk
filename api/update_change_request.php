<?php
    include('../config/connect.php');
    include("../includes/session.php");
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');

    $changeRequestId = isset($_POST['change_request_id']) ? (int)$_POST['change_request_id'] : 0;
    $contactNumber   = trim($_POST['contact_number'] ?? '');
    $department      = trim($_POST['department'] ?? '');
    $changeType      = $_POST['change_type'] ?? '';
    $changeTitle     = trim($_POST['change_title'] ?? '');
    $locationSite    = trim($_POST['location_site'] ?? '');
    $systemsAffected = trim($_POST['systems_affected'] ?? '');
    $requestedDate   = $_POST['requested_implementation_date'] ?? null;
    $vendorInvolved  = ($_POST['vendor_involved'] ?? '') === 'Yes' ? 1 : 0;
    $vendorName      = trim($_POST['vendor_name'] ?? '') ?: null;
    $reasonForRequest = trim($_POST['reason_for_request'] ?? '');
    $impactIfNot     = trim($_POST['impact_if_not_implemented'] ?? '');

    // --- Confirm the request exists, belongs to this user, and is still editable ---
    $crRows = retrieve("SELECT * FROM change_requests WHERE id = ?", array($changeRequestId));
    if (empty($crRows)) {
        $response['message'] = 'Change request not found.';
        echo json_encode($response); exit;
    }
    $cr = $crRows[0];

    if ((int)$cr['requestor_id'] !== (int)($_SESSION['login_id'] ?? 0)) {
        $response['message'] = 'You are not authorized to edit this change request.';
        echo json_encode($response); exit;
    }

    if (!in_array($cr['status'], ['Draft', 'Submitted'])) {
        $response['message'] = 'This change request can no longer be edited.';
        echo json_encode($response); exit;
    }

    // --- Validation ---
    $errors = [];
    if (!in_array($changeType, ['Standard','Normal','Major','Emergency'])) $errors[] = "Invalid change type.";
    if ($changeTitle === '')          $errors[] = "Change title is required.";
    if ($reasonForRequest === '')     $errors[] = "Reason for request is required.";
    if (empty($requestedDate))        $errors[] = "Requested implementation date is required.";

    if (!empty($errors)) {
        $response['message'] = implode(" | ", $errors);
        echo json_encode($response); exit;
    }

    // --- Update ---
    $update = manage(
        "UPDATE change_requests SET
            contact_number = ?, department = ?, change_type = ?, change_title = ?,
            location_site = ?, systems_affected = ?, requested_implementation_date = ?,
            vendor_involved = ?, vendor_name = ?, reason_for_request = ?, impact_if_not_implemented = ?
         WHERE id = ?",
        array($contactNumber, $department, $changeType, $changeTitle,
              $locationSite, $systemsAffected, $requestedDate,
              $vendorInvolved, $vendorName, $reasonForRequest, $impactIfNot,
              $changeRequestId)
    );

    if ($update !== false) {
        $get_username = retrieve("SELECT username FROM users WHERE id=?", array($_SESSION['login_id']));

        manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
                VALUES (?,?,?,?,?,?,?)",
            array($get_username[0]['username'], gethostbyaddr($_SERVER['REMOTE_ADDR']), getLocalIP(), "Change Request", "UPDATE",
                "<details>
                    <p>Updated Change Request <span class='font-weight-bold'>".$cr['crf_number']."</span></p>
                    <p>
                        Title: <span class='font-weight-bold'>".$changeTitle."</span><br>
                        Type: <span class='font-weight-bold'>".$changeType."</span><br>
                        Department: <span class='font-weight-bold'>".$department."</span><br>
                    </p>
                </details>", date('Y-m-d H:i:s')));

        $response['status']  = 'success';
        $response['message'] = 'Change request updated successfully.';
    } else {
        $response['message'] = 'Failed to update change request. Please try again.';
    }

    echo json_encode($response);
?>