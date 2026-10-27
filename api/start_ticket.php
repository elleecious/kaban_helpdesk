<?php
include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');
include('../library/notify.php');

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'Invalid request');

$agentId = $login_id;
$ticketId = $_POST['ticket_id'] ?? null;

if (!$ticketId) {
    $response['message'] = 'No ticket specified.';
    echo json_encode($response);
    exit;
}

global $pdo;

// Get ticket info first — needed for logging/notification, and to confirm ownership. Must be assigned to me and still Open
$getTicket = retrieve(
    "SELECT ticket_number, subject, created_by
     FROM tickets
     WHERE id = ? AND assigned_to = ? AND status = 'Open'",
    array($ticketId, $agentId)
);

if (empty($getTicket)) {
    $response['message'] = 'Unable to start — ticket may have already been updated or is not assigned to you.';
    echo json_encode($response);
    exit;
}

$ticket = $getTicket[0];
$ticket_number = $ticket['ticket_number'];

$get_agent = retrieve("SELECT name FROM users WHERE id = ?", array($agentId));
$agentUser = $get_agent[0]['username'] ?? 'Unknown';
$agentName = $get_agent[0]['name'] ?? 'Unknown';

// Leads to notify, excluding the person who just started the ticket
$leads = retrieve(
    "SELECT id FROM users
     WHERE role IN ('IT Helpdesk Support','IT Manager','IT Supervisor') AND id != ?",
    array($agentId)
);
$leadsIds = array_column($leads, 'id');

// Only status changes here — assigned_to stays untouched, it's already correct
$rowsAffected = manage(
    "UPDATE tickets
     SET status = 'In Progress',
        started_at = COALESCE(started_at, NOW()), updated_at = NOW()
     WHERE id = ? AND assigned_to = ? AND status = 'Open'",
    array($ticketId, $agentId)
);

if ($rowsAffected > 0) {

    $link = "ticket_detail.php?id={$ticketId}";

   // Notify Helpdesk Support, Supervisor and Manager
    if (!empty($leadsIds)) {
        create_notification_bulk(
            $leadsIds,
            'ticket_in_progress',
            "Ticket #{$ticket_number} was started by {$agentName} and is now In Progress.",
            $link,
            $ticketId
        );
    }

    // Notify the employee
    create_notification(
        $ticket['created_by'],
        'ticket_in_progress',
        "Your ticket #{$ticket_number} is now being worked on by {$agentName}",
        $link,
        $ticketId
    );

    $logs_result = manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
        VALUES (?,?,?,?,?,?,?)",
        array(
            $agentUser,
            $_SERVER['REMOTE_ADDR'],
            getLocalIP(),
            "My Ticket Queue",
            "START TICKET",
            "<details><p>Started Ticket</p><p>Ticket #: {$ticket_number} — Status: Open => <span class='font-weight-bold'>In Progress</span></p></details>",
            date('Y-m-d H:i:s')
        )
    );

    $response['status'] = 'success';
} else {
    $response['message'] = 'Unable to start ticket — it may have already been updated.';
}

echo json_encode($response);
exit;

?>