<?php

include('../includes/connect.php');
include('../includes/session.php');
header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'Invalid request');

$ticketId = $_POST['ticket_id'] ?? null;
$agentId = $_POST['agent_id'] ?? null;

if (!$ticketId || !$agentId) {
    $response['message'] = 'Missing ticket or agent.';
    echo json_encode($response);
    exit;
}

$rowsAffected = manage(
    "UPDATE tickets SET assigned_to = ?, status = 'Open' 
     WHERE id = ? AND assigned_to IS NULL",
    array($agentId, $ticketId)
);

if ($rowsAffected > 0) {
    $logs_result = manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
        VALUES (?,?,?,?,?,?,?)",
        array(
            $login_id,
            gethostbyaddr($_SERVER['REMOTE_ADDR']),
            getLocalIP(),
            "Supervisor - Assign Ticket",
            "UPDATE",
            "<details><p>Manually Assigned Ticket</p><p>Ticket #: ".$ticketId." — Assigned To: <span class='font-weight-bold'>Agent ID ".$agentId."</span></p></details>",
            date('Y-m-d H:i:s a')
        )
    );

    $response['status'] = 'success';
} else {
    $response['message'] = 'This ticket was already assigned.';
}

echo json_encode($response);
exit;

?>