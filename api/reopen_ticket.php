<?php
include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');
include('../library/notify.php');

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'Invalid request');

$ticketId = $_POST['ticket_id'] ?? null;
$reason   = trim($_POST['reason'] ?? '');

if (!$ticketId) {
    $response['message'] = 'No ticket specified.';
    echo json_encode($response);
    exit;
}

// Only the requester can reopen, and only while the ticket is Resolved
$getTicket = retrieve(
    "SELECT ticket_number, assigned_to
     FROM tickets
     WHERE id = ? AND created_by = ? AND status = 'Resolved'",
    array($ticketId, $login_id)
);

if (empty($getTicket)) {
    $response['message'] = 'Only the requester can reopen a Resolved ticket.';
    echo json_encode($response);
    exit;
}
$ticket = $getTicket[0];

$getUser = retrieve("SELECT name FROM users WHERE id = ?", array($login_id));
$userName = $getUser[0]['name'] ?? 'Unknown';

$rowsAffected = manage(
    "UPDATE tickets
     SET status = 'In Progress', resolved_at = NULL,
         reopen_count = reopen_count + 1, updated_at = NOW()
     WHERE id = ? AND created_by = ? AND status = 'Resolved'",
    array($ticketId, $login_id)
);

if ($rowsAffected > 0) {

    if (!empty($ticket['assigned_to'])) {
        create_notification(
            $ticket['assigned_to'],
            'ticket_reopened',
            "Ticket #{$ticket['ticket_number']} was reopened by {$userName}: still not fixed",
            "ticket_detail.php?id={$ticketId}",
            $ticketId
        );
    }

    $logs_result = manage(
        "INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
         VALUES (?,?,?,?,?,?,?)",
        array(
            $userName,
            $_SERVER['REMOTE_ADDR'],
            getLocalIP(),
            "Ticket Detail",
            "UPDATE",
            "<details>
                <p>Ticket Reopened</p>
                <p>
                    Ticket #: {$ticket['ticket_number']}<br>
                    Status: Resolved => <span class='font-weight-bold'>In Progress</span><br>
                    Reason: ".htmlspecialchars($reason !== '' ? $reason : 'Not specified')."
                </p>
            </details>",
            date('Y-m-d H:i:s')
        )
    );

    $response['status'] = 'success';

} else {
    $response['message'] = 'Unable to reopen this ticket.';
}

echo json_encode($response);
exit;