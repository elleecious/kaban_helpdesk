<?php
    include('../config/connect.php');
    include('../includes/session.php');
    include('../library/functions.php');
    include("../library/notify.php");

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');

    $changeRequestId = isset($_POST['change_request_id']) ? (int)$_POST['change_request_id'] : 0;
    $decision = $_POST['decision'] ?? '';
    $comments = trim($_POST['comments'] ?? '');
    $approverId = $_SESSION['login_id'] ?? null;
    $role = $_SESSION['role'] ?? null;

    if (!$changeRequestId) {
        $response['status'] = 'error';
        $response['message'] = 'Change Request ID is required.';
        echo json_encode($response);
        exit;
    }

    if (!$approverId) {
        $response['message'] = 'You must be logged in.';
        echo json_encode($response); 
        exit;
    }
    if (!in_array($decision, ['Approved', 'Rejected'])) {
        $response['message'] = 'Invalid decision.';
        echo json_encode($response); 
        exit;
    }

    $changeRequest = retrieve("SELECT id, change_title, requestor_id, crf_number FROM change_requests WHERE id = ?", array($changeRequestId));
    $changeRequest = $changeRequest[0];
    $crf_number = $changeRequest['crf_number'];

    $approver = retrieve("SELECT name FROM users WHERE id = ?", array($approverId));
    $approverName = $approver[0]['name'] ?? 'Unknown';



    $crRows = retrieve("SELECT id, change_type, status FROM change_requests WHERE id = ?", array($changeRequestId));
    if (empty($crRows)) {
        $response['message'] = 'Change request not found.';
        echo json_encode($response); 
        exit;
    }
    $cr = $crRows[0];

    $approvalLevel = getApprovalLevelForAction($cr['change_type'], $role, $changeRequestId);
    if (!$approvalLevel) {
        $response['message'] = 'You are not authorized to approve this request at this stage.';
        echo json_encode($response); 
        exit;
    }

    // Prevent double-voting at the same level
    $existing = retrieve(
        "SELECT id FROM change_approvals WHERE change_request_id = ? AND approval_level = ?",
        array($changeRequestId, $approvalLevel)
    );
    if (!empty($existing)) {
        $response['message'] = 'This request has already been actioned at this approval level.';
        echo json_encode($response); 
        exit;
    }

    $insert = manage(
        "INSERT INTO change_approvals (change_request_id, approver_id, approval_level, decision, comments, decided_at)
         VALUES (?, ?, ?, ?, ?, ?)",
        array($changeRequestId, $approverId, $approvalLevel, $decision, $comments, date("Y-m-d H:i:s"))
    );

    if ($insert === false) {
        $response['message'] = 'Failed to record decision. Please try again.';
        echo json_encode($response); 
        exit;
    }

    $newStatus = recalculateChangeStatus($changeRequestId, $cr['change_type']);
    manage("UPDATE change_requests SET status = ? WHERE id = ?", array($newStatus, $changeRequestId));

    $link = "view_change_request.php?id=$changeRequestId";


    if ($decision === 'Approved') {
        create_notification(
            $changeRequest['requestor_id'],
            'approved_request',
            "Your Change Request #{$crf_number} has been Approved by <br> {$approverName}",
            $link,
            null,
            $changeRequestId
        );
    } else {
        create_notification(
            $changeRequest['requestor_id'],
            'reject_request',
            "Your Change Request #{$crf_number} has been Rejected by <br> {$approverName}",
            $link,
            null,
            $changeRequestId
        );
    }

    $allCapsDecision = strtoupper($decision);

    $get_username = retrieve("SELECT username FROM users WHERE id=?", array($approverId));
    manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
            VALUES (?,?,?,?,?,?,?)",
        array($get_username[0]['username'], gethostbyaddr($_SERVER['REMOTE_ADDR']), getLocalIP(), "Change Request", $allCapsDecision,
            "<details>
                <p>{$decision} Change Request as <span class='font-weight-bold'>{$approvalLevel}</span></p>
                <p>Comments: ".($comments ?: 'None')."</p>
                <p>New status: <span class='font-weight-bold'>{$newStatus}</span></p>
            </details>", date('Y-m-d H:i:s')));

    $response['status'] = 'success';
    $response['message'] = "Change request {$decision}.";
    $response['new_status'] = $newStatus;
    echo json_encode($response);
?>