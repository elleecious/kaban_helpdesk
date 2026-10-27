<?php
include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'Invalid request');

$ticketId = $_POST['ticket_id'] ?? null;

if (!$ticketId) {
    $response['message'] = 'No ticket specified.';
    echo json_encode($response);
    exit;
}

// Only the person who created the ticket can delete it, and it must not be deleted already
$getTicket = retrieve(
    "SELECT ticket_number, subject, status, assigned_to
     FROM tickets
     WHERE id = ? AND created_by = ? AND deleted_at IS NULL",
    array($ticketId, $login_id)
);

if (empty($getTicket)) {
    $response['message'] = 'Ticket not found, already deleted, or not yours to delete.';
    echo json_encode($response);
    exit;
}
$ticket = $getTicket[0];

// Once IT has started working on it, it can no longer be deleted
if ($ticket['status'] !== 'Open') {
    $response['message'] = 'This ticket can no longer be deleted because IT has already started working on it.';
    echo json_encode($response);
    exit;
}

// Soft delete: nothing is removed, attachments and history are kept
$rowsAffected = manage(
    "UPDATE tickets
     SET status = 'Deleted', deleted_at = NOW(), deleted_by = ?, updated_at = NOW()
     WHERE id = ? AND created_by = ? AND status = 'Open' AND deleted_at IS NULL",
    array($login_id, $ticketId, $login_id)
);

if ($rowsAffected > 0) {

    $logs_result = manage(
        "INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
         VALUES (?,?,?,?,?,?,?)",
        array(
            $username,
            $_SERVER['REMOTE_ADDR'],
            getLocalIP(),
            "Ticket Detail",
            "DELETED TICKET",
            "<details>
                <p>Ticket Deleted by Requester</p>
                <p>
                    Ticket #: ".htmlspecialchars($ticket['ticket_number'])."<br>
                    Subject: ".htmlspecialchars($ticket['subject'])."
                </p>
            </details>",
            date('Y-m-d H:i:s')
        )
    );

    $response['status'] = 'success';

} else {
    $response['message'] = 'Unable to delete this ticket. It may have just been picked up by IT.';
}

echo json_encode($response);
exit;

?>