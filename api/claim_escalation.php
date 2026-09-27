<?php
include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');
include('../library/notify.php');

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'Invalid request');

if (!in_array($_SESSION['role'], ['IT Supervisor', 'IT Manager'])) {
    $response['message'] = 'You do not have permission to do this.';
    echo json_encode($response);
    exit;
}

$ticketId = $_POST['ticket_id'] ?? null;
$supervisorId = $login_id;

$getTicket = retrieve("SELECT id AS ticket_id, ticket_number, created_by, escalated_by FROM tickets WHERE id = ? AND status = 'Escalated'", array($ticketId));
if (empty($getTicket)) {
    $response['message'] = 'Ticket not found or no longer escalated.';
    echo json_encode($response);
    exit;
}
$ticket = $getTicket[0];

$ticket_id = $ticket['ticket_id'];

$get_supervisor = retrieve("SELECT username, name FROM users WHERE id = ?", array($supervisorId));
$supervisorName = $get_supervisor[0]['name'] ?? 'Unknown';
$supervisorUser = $get_supervisor[0]['username'] ?? 'Unknown';

$rowsAffected = manage(
    "UPDATE tickets SET assigned_to = ?, status = 'In Progress', updated_at = NOW() 
     WHERE id = ? AND status = 'Escalated'",
    array($supervisorId, $ticketId)
);

if ($rowsAffected > 0) {

    // Notify the original agent (so they know it's off their plate) and the requester
    create_notification(
        $ticket['escalated_by'],
        'escalation_taken',
        "{$supervisorName} has taken over ticket #{$ticket['ticket_number']}",
        "ticket_detail.php?id={$ticketId}",
        $ticketId
    );

    //Notify the employee that the supervisor
    create_notification(
        $ticket['created_by'],
        'ticket_update',
        "Your ticket #{$ticket['ticket_number']} is now being handled by <br> {$supervisorName}",
        "ticket_detail.php?id={$ticketId}",
        $ticketId
    );

    $logs_result = manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
        VALUES (?,?,?,?,?,?,?)",
        array(
            $supervisorUser,
            $_SERVER['REMOTE_ADDR'],
            getLocalIP(),
            "Ticket Detail",
            "UPDATE",
            "<details>
                <p>Escalation Taken Over</p><p>Ticket #: ".$ticket['ticket_number']." — Taken by: <span class='font-weight-bold'>".$supervisorName."</span></p>
            </details>",
            date('Y-m-d H:i:s')
        )
    );

    $response['status'] = 'success';
    $response['ticket_id'] = $ticket_id;
} else {
    $response['message'] = 'Unable to take over — ticket may have already been handled.';
}

echo json_encode($response);
exit;

?>