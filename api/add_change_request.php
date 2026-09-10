<?php
    include('../config/connect.php');
    include("../includes/session.php");
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');
    
    $requestorId = $_SESSION['login_id'] ?? null;
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $changeType = $_POST['change_type'] ?? '';
    $changeTitle = trim($_POST['change_title'] ?? '');
    $locationSite = trim($_POST['location_site'] ?? '');
    $systemsAffected = trim($_POST['systems_affected'] ?? '');
    $requestedDate = $_POST['requested_implementation_date'] ?? null;
    $vendorInvolved = ($_POST['vendor_involved'] ?? '') === 'Yes' ? 1 : 0;
    $vendorName = trim($_POST['vendor_name'] ?? '') ?: null;
    $reasonForRequest = trim($_POST['reason_for_request'] ?? '');
    $impactIfNot = trim($_POST['impact_if_not_implemented'] ?? '');

    $get_username = retrieve("SELECT * FROM users WHERE id=?", array($requestorId));

    $year = date("Y");
    $crf_number = generateCrfNumber($year);

    $errors = [];
    if (!$requestorId) $errors[] = "You must be logged in.";
    if (!in_array($changeType, ['Standard','Normal','Major','Emergency'])) $errors[] = "Invalid change type.";
    if ($changeTitle === '')  $errors[] = "Change title is required.";
    if ($reasonForRequest === '')  $errors[] = "Reason for request is required.";

    if (!empty($errors)) {
        $response['message'] = implode(" | ", $errors);
        echo json_encode($response);
        exit;
    }

    $add_change_request_sql = manage("INSERT INTO change_requests (
            crf_number, requestor_id, contact_number, department,
            change_type, change_title, location_site, systems_affected,
            requested_implementation_date, vendor_involved, vendor_name,
            reason_for_request, impact_if_not_implemented, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            array($crf_number, $requestorId, $contactNumber, $department, $changeType, $changeTitle, $locationSite, 
            $systemsAffected, $requestedDate, $vendorInvolved, $vendorName, $reasonForRequest, $impactIfNot, 'Submitted', date("Y-m-d H:i:s")));

    if ($add_change_request_sql !== false) {

        manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
            VALUES (?,?,?,?,?,?,?)",
        array($get_username[0]['username'], gethostbyaddr($_SERVER['REMOTE_ADDR']), getLocalIP(), "Change Request", "CREATE",
            "<details>
                <p>Submitted Change Request <span class='font-weight-bold'>".$crf_number."</span></p>
                <p>
                    Title: <span class='font-weight-bold'>".$changeTitle."</span><br>
                    Type: <span class='font-weight-bold'>".$changeType."</span><br>
                    Department: <span class='font-weight-bold'>".$department."</span><br>
                    Systems Affected: <span class='font-weight-bold'>".$systemsAffected."</span><br>
                    Requested by: <span class='font-weight-bold'>".$get_username[0]['username']."</span><br>
                    Requested Implementation Date: <span class='font-weight-bold'>".$requestedDate."</span><br>
                    Status: <span class='font-weight-bold'>Submitted</span><br>
                </p>
            </details>", date('Y-m-d H:i:s')));

        $response['status']  = 'success';
        $response['message'] = 'Change request submitted successfully.';
        $response['crf_number'] = $crf_number;

    } else {
        $response['status']  = 'error';
        $response['message'] = 'Failed to submit change request. Please try again.';
    }


    echo json_encode($response);

?>