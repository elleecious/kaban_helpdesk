<?php
include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');
include('../library/notify.php');

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'Invalid request');

$ticketId = $_POST['ticket_id'] ?? null;
$reason = trim($_POST['reason'] ?? '');
$agentId = $login_id;

if (!$ticketId || empty($reason)) {
    $response['message'] = 'Ticket and reason are required.';
    echo json_encode($response);
    exit;
}

$getTicket = retrieve("SELECT ticket_number, subject FROM tickets WHERE id = ? AND assigned_to = ?", array($ticketId, $agentId));
if (empty($getTicket)) {
    $response['message'] = 'Ticket not found or not assigned to you.';
    echo json_encode($response);
    exit;
}
$ticket = $getTicket[0];

$get_agent = retrieve("SELECT name FROM users WHERE id = ?", array($agentId));
$agentName = $get_agent[0]['name'] ?? 'Unknown';

// Note: assigned_to stays as-is — we're NOT unassigning, just flagging + storing who escalated it
$rowsAffected = manage(
    "UPDATE tickets 
     SET status = 'Escalated', escalated_by = ?, escalation_reason = ?, updated_at = NOW() 
     WHERE id = ? AND assigned_to = ?",
    array($agentId, $reason, $ticketId, $agentId)
);

if ($rowsAffected > 0) {

    // Notify all Supervisors — adjust if you only want a specific supervisor notified
    $supervisors = retrieve("SELECT id FROM users WHERE role = 'IT Supervisor'", array());
    foreach ($supervisors as $sup) {
        create_notification(
            $sup['id'],
            'ticket_escalated',
            "Ticket #{$ticket['ticket_number']} was escalated by {$agentName}",
            "ticket_detail.php?id={$ticketId}",
            $ticketId
        );
    }

    $logs_result = manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
        VALUES (?,?,?,?,?,?,?)",
        array(
            $agentName,
            $_SERVER['REMOTE_ADDR'],
            getLocalIP(),
            "Ticket Detail",
            "UPDATE",
            "<details><p>Ticket Escalated</p><p>Ticket #: ".$ticket['ticket_number']." — Reason: ".$reason."</p></details>",
            date('Y-m-d H:i:s')
        )
    );

    $response['status'] = 'success';
} else {
    $response['message'] = 'Unable to escalate this ticket.';
}

echo json_encode($response);
exit;

?>