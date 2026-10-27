<?php
include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');
include('../library/notify.php');

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'Invalid request');

// Roles that may close a Resolved ticket on the requester's behalf.
// Adjust to the exact values stored in users.role
$forceCloseRoles = ['IT Supervisor', 'IT Manager'];
$ticketId = $_POST['ticket_id'] ?? null;

if (!$ticketId) {
    $response['message'] = 'No ticket specified.';
    echo json_encode($response);
    exit;
}

// Ticket must be Resolved and not deleted
$getTicket = retrieve(
    "SELECT ticket_number, created_by, assigned_to
     FROM tickets
     WHERE id = ? AND status = 'Resolved' AND deleted_at IS NULL",
    array($ticketId)
);

if (empty($getTicket)) {
    $response['message'] = 'Ticket not found or it is no longer Resolved.';
    echo json_encode($response);
    exit;
}
$ticket = $getTicket[0];

$isRequester  = ($ticket['created_by'] == $login_id);
$canForceClose  = in_array($role, $forceCloseRoles);

// Not the requester? Then it must be a Supervisor/Manager override.
if (!$isRequester) {
    if (!$canForceClose) {
        $response['message'] = 'Only the requester can close a Resolved ticket.';
        echo json_encode($response);
        exit;
    }
    // Keep the "second pair of eyes": whoever resolved it cannot also close it
    if ($ticket['assigned_to'] == $login_id) {
        $response['message'] = 'You resolved this ticket, so someone else needs to close it.';
        echo json_encode($response);
        exit;
    }
}

$rowsAffected = manage(
    "UPDATE tickets
     SET status = 'Closed', closed_at = NOW(), updated_at = NOW()
     WHERE id = ? AND status = 'Resolved'",
    array($ticketId)
);

if ($rowsAffected > 0) {

    $link = "ticket_detail.php?id={$ticketId}";

    if ($isRequester) {
        // Requester confirmed the fix: tell the person who resolved it
        if (!empty($ticket['assigned_to'])) {
            create_notification(
                $ticket['assigned_to'],
                'ticket_closed',
                "Ticket #{$ticket['ticket_number']} was confirmed fixed by {$name} and is now Closed",
                $link,
                $ticketId
            );
        }
        $logAction  = "CONFIRM CLOSE";
        $logHeading = "Ticket Confirmed Fixed";
    } else {
        // Supervisor/Manager override: tell the requester, and the resolver if it is someone else
        create_notification(
            $ticket['created_by'],
            'ticket_closed',
            "Your ticket #{$ticket['ticket_number']} was closed by {$name}. If the issue is still not fixed, please submit a new ticket.",
            $link,
            $ticketId
        );
        if (!empty($ticket['assigned_to']) && $ticket['assigned_to'] != $ticket['created_by']) {
            create_notification(
                $ticket['assigned_to'],
                'ticket_closed',
                "Ticket #{$ticket['ticket_number']} was closed by {$name}",
                $link,
                $ticketId
            );
        }
        $logAction  = "FORCE CLOSE";
        $logHeading = "Ticket Closed by Supervisor/Manager";
    }

    $logs_result = manage(
        "INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
         VALUES (?,?,?,?,?,?,?)",
        array(
            $username,
            $_SERVER['REMOTE_ADDR'],
            getLocalIP(),
            "Ticket Detail",
            $logAction,
            "<details>
                <p>{$logHeading}</p>
                <p>
                    Ticket #: ".htmlspecialchars($ticket['ticket_number'])."<br>
                    Status: Resolved => <span class='font-weight-bold'>Closed</span><br>
                    Closed by: <span class='font-weight-bold'>".htmlspecialchars($name)."</span>
                </p>
            </details>",
            date('Y-m-d H:i:s')
        )
    );

    $response['status'] = 'success';

} else {
    $response['message'] = 'Unable to close this ticket. It may have already been closed or reopened.';
}

echo json_encode($response);
exit;

?>