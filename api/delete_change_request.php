<?php

    include('../config/connect.php');
    include('../includes/session.php');
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');

    if (empty($login_id)) {
        $response['message'] = 'You must be logged in to delete a change request.';
        echo json_encode($response);
        exit;
    }

    $getEmployee = retrieve("SELECT * FROM users WHERE id=?", array($login_id));

    $change_request_id = $_POST['change_request_id'] ?? null;

    if (empty($change_request_id)) {
        $response['message'] = 'Missing change request ID.';
        echo json_encode($response);
        exit;
    }

    $change_request = retrieve("SELECT * FROM change_requests WHERE id = ?", array($change_request_id));
    if (empty($change_request)) {
        $response['message'] = 'Change request not found.';
        echo json_encode($response);
        exit;
    }
    $change_request = $change_request[0];

    if ($change_request['status'] === 'Closed') {
        $response['message'] = 'Change request is already closed.';
        echo json_encode($response);
        exit;
    }

    try {

        $deleted = manage(
            "UPDATE change_requests SET status = ?, deleted_by = ?, deleted_at = ? WHERE id = ?",
            array('Deleted', $login_id, date("Y-m-d H:i:s"), $change_request_id));

        if ($deleted === false) {
            $response['message'] = 'Failed to delete change request.';
            echo json_encode($response);
            exit;
        }

        manage("INSERT INTO logs (username, computer_name, ip_address, page, action, details, date)
            VALUES (?,?,?,?,?,?,?)",
        array($getEmployee[0]['username'], gethostbyaddr($_SERVER['REMOTE_ADDR']), getLocalIP(), "Delete Change Request", "UPDATE",
            "<details>
                <p>Delete Change Request</p>
                <p>
                    Change Request Number: <span class='font-weight-bold'>".$change_request['crf_number']."</span><br>
                    Deleted By: <span class='font-weight-bold'>".$getEmployee[0]['name']."</span><br>
                    Date Deleted: <span class='font-weight-bold'>".date("Y-m-d H:i:s")."</span><br>
                </p>
            </details>", date('Y-m-d H:i:s')));

        $response['status'] = 'success';
        $response['message'] = 'Change request deleted successfully.';
        $response['change_request_id'] = $change_request['id'];

    } catch (Exception $e) {
        error_log('Delete change request error: ' . $e->getMessage());
        $response['status'] = 'error';
        $response['message'] = 'Something went wrong please try again';
    }

    echo json_encode($response);
?>