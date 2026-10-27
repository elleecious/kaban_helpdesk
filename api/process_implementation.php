<?php
    include('../config/connect.php');
    include('../includes/session.php');
    include('../library/functions.php');
    include('../library/notify.php');

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');

    $changeRequestId = isset($_POST['change_request_id']) ? (int)$_POST['change_request_id'] : 0;
    $step = $_POST['step'] ?? '';
    $role = $_SESSION['role'] ?? null;
    $userId = $_SESSION['login_id'] ?? null;

    $allowedRoles = ['IT Support Specialist', 'System Administrator', 'IT Supervisor', 'IT Manager'];
    if (!$userId || !in_array($role, $allowedRoles)) {
        $response['message'] = 'Not authorized.';
        echo json_encode($response); exit;
    }

    $crRows = retrieve("SELECT * FROM change_requests WHERE id = ?", array($changeRequestId));
    if (empty($crRows)) {
        $response['message'] = 'Change request not found.';
        echo json_encode($response); exit;
    }
    $cr = $crRows[0];

    $get_actor = retrieve("SELECT name, username FROM users WHERE id=?", array($userId));
    $actorName = $get_actor[0]['name'] ?? 'IT Staff';
    $crfNumber = $cr['crf_number'];
    $link = "view_change_request.php?id={$changeRequestId}";

    switch ($step) {

        case 'receive':
            manage("UPDATE change_requests SET received_by_it = ?, status = ? WHERE id = ?",
                array($userId, 'Scheduled', $changeRequestId));

            create_notification(
                $cr['requestor_id'],
                'change_received',
                "Your Change Request #{$crfNumber} has been received by IT and is now scheduled by {$actorName}",
                $link,
                null,
                $changeRequestId
            );

            $response['status'] = 'success';
            $response['message'] = 'Marked as received.';
            break;

        case 'implement':
            $expectedDowntime = trim($_POST['expected_downtime'] ?? '');
            $rollbackPlan = trim($_POST['rollback_plan'] ?? '');
            $implementationNotes = trim($_POST['implementation_notes'] ?? '');

            manage("UPDATE change_requests SET
                        implemented_by = ?, expected_downtime = ?, rollback_plan = ?,
                        implementation_notes = ?, status = ?
                    WHERE id = ?",
                array($userId, $expectedDowntime, $rollbackPlan, $implementationNotes, 'Implementing', $changeRequestId));

            create_notification(
                $cr['requestor_id'],
                'change_implementing',
                "Your Change Request #{$crfNumber} is now being implemented by {$actorName}",
                $link,
                null,
                $changeRequestId
            );

            $response['status'] = 'success';
            $response['message'] = 'Implementation details saved.';
            break;

        case 'validate':
            $validationResults = trim($_POST['validation_results'] ?? '');

            if ($validationResults === '') {
                $response['message'] = 'Validation results are required.';
                echo json_encode($response); exit;
            }

            manage("UPDATE change_requests SET validation_results = ?, status = ? WHERE id = ?",
                array($validationResults, 'Implemented', $changeRequestId));

            create_notification(
                $cr['requestor_id'],
                'change_implemented',
                "Your Change Request #{$crfNumber} has been successfully validated and implemented  by {$actorName}",
                $link,
                null,
                $changeRequestId
            );

            $response['status'] = 'success';
            $response['message'] = 'Marked as implemented and validated.';
            break;

        case 'rollback':
            $rollbackNote = trim($_POST['rollback_note'] ?? '');
            $attemptNumber = (int)(retrieve(
                "SELECT COUNT(*) AS cnt FROM logs WHERE page = 'Change Request' AND action = 'ROLLBACK' AND details LIKE ?",
                array("%".$crfNumber."%")
            )[0]['cnt']) + 1;

            $timestamp = date("M j, Y g:i A");
            $newIssueLog = trim(($cr['issues_encountered'] ?? '') . "\n\n[Attempt {$attemptNumber} — rolled back {$timestamp} by {$actorName}]\n{$rollbackNote}");

            manage("UPDATE change_requests SET issues_encountered = ?, validation_results = NULL, status = ? WHERE id = ?",
                array($newIssueLog, 'Implementing', $changeRequestId));

            create_notification(
                $cr['requestor_id'],
                'change_rolled_back',
                "Your Change Request #{$crfNumber} failed validation and was rolled back by {$actorName}. It will be re-attempted.",
                $link,
                null,
                $changeRequestId
            );

            $response['status'] = 'success';
            $response['message'] = 'Rolled back. The change has been reset to Implementing for another attempt.';
            break;

        case 'close':
            $issuesEncountered = trim($_POST['issues_encountered'] ?? '');
            manage("UPDATE change_requests SET issues_encountered = ?, status = ? WHERE id = ?",
                array($issuesEncountered, 'Closed', $changeRequestId));

            create_notification(
                $cr['requestor_id'],
                'change_closed',
                "Your Change Request #{$crfNumber} has been closed by {$actorName}",
                $link,
                null,
                $changeRequestId
            );

            $response['status'] = 'success';
            $response['message'] = 'Change request closed.';
            break;

        default:
            $response['message'] = 'Unknown step.';
            echo json_encode($response); exit;
    }

    manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
            VALUES (?,?,?,?,?,?,?)",
        array($get_actor[0]['username'], gethostbyaddr($_SERVER['REMOTE_ADDR']), getLocalIP(), "Change Request", strtoupper($step),
            "<details><p>{$response['message']} for <span class='font-weight-bold'>{$crfNumber}</span></p></details>",
            date('Y-m-d H:i:s')));

    echo json_encode($response);
?>