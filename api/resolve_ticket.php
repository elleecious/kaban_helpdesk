<?php
include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');
include("../library/notify.php");

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

$ticket = retrieve("SELECT id, subject, created_by, ticket_number FROM tickets WHERE id = ?", array($ticketId));
if (empty($ticket)) {
    $response['message'] = 'Ticket not found.';
    echo json_encode($response);
    exit;
}
$ticket = $ticket[0];
$ticket_number = $ticket['ticket_number'];

// Get the resolving agent's name for the log entry
$agent = retrieve("SELECT name FROM users WHERE id = ?", array($agentId));
$agentName = $agent[0]['name'] ?? 'Unknown';
$agentUser = $agent[0]['username'] ?? 'Unknown';

$leads = retrieve("SELECT id FROM users WHERE role IN ('IT Manager', 'IT Supervisor')", []);
$leadsIds = array_column($leads,'id');



// Only the assigned Agent can resolve their own ticket, and only if it isn't already Resolved/Closed
$rowsAffected = manage(
    "UPDATE tickets 
     SET status = 'Resolved', resolved_at = NOW(), resolution_notes = ?, updated_at = ? 
     WHERE id = ? AND assigned_to = ? AND status NOT IN ('Resolved','Closed')",
    array($notes, date('Y-m-d H:i:s'), $ticketId, $agentId));

if ($rowsAffected > 0) {

     $link = "ticket_detail.php?id=$ticketId";

    //Notifies Supervisor and Manager that they resolved the ticket
    create_notification_bulk(
        $leadsIds,
        'resolved_ticket',
        "Ticket #{$ticket_number} was resolved by <br> {$agentName}",
        "ticket_detail.php?id={$ticketId}",
        $ticketId
    );

    //Notifies the agent
    create_notification(
        $ticket['created_by'],
        'resolved_ticket',
        "Your ticket #{$ticket_number} has been resolved - <br> please confirm or reopen if the issue persists",
        "ticket_detail.php?id=$ticketId",
        $ticketId
    );

    $logs_result = manage("INSERT INTO logs (username, computer_name,ip_address,page,action,details,date)
        VALUES (?,?,?,?,?,?,?)",
        array(
            $agentUser,
            gethostbyaddr($_SERVER['REMOTE_ADDR']),
            getLocalIP(),
            "Ticket Detail",
            "UPDATE",
            "<details>
                <p>Ticket Resolved</p>
                <p>
                    Ticket #: ".$ticketId."<br>
                    Status: In Progress => <span class='font-weight-bold'>Resolved</span><br>
                    Resolved By: <span class='font-weight-bold'>".$agentName."</span><br>
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