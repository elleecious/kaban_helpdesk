<?php

    include('../config/connect.php');
    include('../includes/session.php');
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');

    if (empty($login_id)) {
        $response['message'] = 'You must be logged in to close a ticket.';
        echo json_encode($response);
        exit;
    }

    $getEmployee = retrieve("SELECT * FROM users WHERE id=?", array($login_id));

    $ticket_id = $_POST['ticket_id'] ?? null;

    if (empty($ticket_id)) {
        $response['message'] = 'Missing ticket ID.';
        echo json_encode($response);
        exit;
    }

    $ticket = retrieve("SELECT * FROM tickets WHERE id = ?", array($ticket_id));
    if (empty($ticket)) {
        $response['message'] = 'Ticket not found.';
        echo json_encode($response);
        exit;
    }
    $ticket = $ticket[0];

    if ($ticket['status'] === 'Closed') {
        $response['message'] = 'Ticket is already closed.';
        echo json_encode($response);
        exit;
    }

    try {

        $updated = manage(
            "UPDATE tickets SET status = ?, closed_by   =?, closed_at = ? WHERE id = ?",
            array('Closed', $login_id, date("Y-m-d H:i:s"), $ticket_id));

        if ($updated === false) {
            $response['message'] = 'Failed to close ticket.';
            echo json_encode($response);
            exit;
        }

        manage("INSERT INTO logs (username, computer_name, ip_address, page, action, details, date)
            VALUES (?,?,?,?,?,?,?)",
        array($getEmployee[0]['username'], gethostbyaddr($_SERVER['REMOTE_ADDR']), getLocalIP(), "Close Ticket", "UPDATE",
            "<details>
                <p>Close Ticket</p>
                <p>
                    Ticket Number: <span class='font-weight-bold'>".$ticket['ticket_number']."</span><br>
                    Closed By: <span class='font-weight-bold'>".$getEmployee[0]['name']."</span><br>
                    Date Closed: <span class='font-weight-bold'>".date("Y-m-d H:i:s")."</span><br>
                </p>
            </details>", date('Y-m-d H:i:s')));

        $response['status'] = 'success';
        $response['message'] = 'Ticket closed successfully.';
        $response['ticket_id'] = $ticket['id'];

    } catch (Exception $e) {
        error_log('Close ticket error: ' . $e->getMessage());
        $response['status'] = 'error';
        $response['message'] = 'Something went wrong please try again';
    }

    echo json_encode($response);
?>