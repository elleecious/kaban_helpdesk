<?php

$skip_activity = true;

include('../config/connect.php');
include('../includes/session.php');
include("../library/functions.php");
header('Content-Type: application/json');

try {
    $get_pickup_tickets = retrieve(
        "SELECT id, ticket_number, subject, priority, 
                TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS waiting_minutes
         FROM tickets 
         WHERE assigned_to IS NULL AND status = 'Open'
         ORDER BY FIELD(priority, 'Critical','High','Medium','Low'), created_at ASC",
        array()
    );

    $formattedData = array();

    if ($get_pickup_tickets && count($get_pickup_tickets) > 0) {
        foreach ($get_pickup_tickets as $ticket) {$formattedData[] = array(
                'id'            => $ticket['id'],
                'ticket_number' => $ticket['ticket_number'],
                'subject'       => $ticket['subject'],
                'priority'      => $ticket['priority'],
                'waiting_time'  => format_waiting_time($ticket['waiting_minutes'])
            );
        }
    }

    echo json_encode([
        'status' => 'success',
        'data'   => $formattedData
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}
exit;
?>