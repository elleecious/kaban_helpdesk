<?php
include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'Invalid request');

$ticketId = $_POST['ticket_id'] ?? null;
$notes = $_POST['resolution_notes'] ?? '';
$agentId = $_SESSION['login_id'];

if (!$ticketId || empty(trim($notes))) {
    $response['message'] = 'Ticket ID and resolution notes are required.';
    echo json_encode($response);
    exit;
}

// Only the assigned Agent can resolve their own ticket, and only if it isn't already Resolved/Closed
$rowsAffected = manage(
    "UPDATE tickets 
     SET status = 'Resolved', resolved_at = NOW(), resolution_notes = ?, updated_at = ? 
     WHERE id = ? AND assigned_to = ? AND status NOT IN ('Resolved','Closed')",
    array($notes, date('Y-m-d H:i:s'), $ticketId, $agentId));

if ($rowsAffected > 0) {

    $logs_result = manage("INSERT INTO logs (computer_name,ip_address,page,action,details,date)
        VALUES (?,?,?,?,?,?)",
        array(
            $_SERVER['REMOTE_ADDR'],
            getLocalIP(),
            "Ticket Detail",
            "UPDATE",
            "<details>
                <p>Ticket Resolved</p>
                <p>
                    Ticket #: ".$ticketId."<br>
                    Status: In Progress => <span class='font-weight-bold'>Resolved</span><br>
                    Resolved By: <span class='font-weight-bold'>".$name."</span><br>
                    Notes: ".$notes."
                </p>
            </details>",
            date('Y-m-d H:i:s a')
        )
    );

    $response['status'] = 'success';

} else {
    $response['message'] = 'Unable to resolve — ticket may already be resolved, closed, or not assigned to you.';
}

echo json_encode($response);
exit;