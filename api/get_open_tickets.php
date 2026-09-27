<?php
include('../config/connect.php');
include('../includes/session.php');
include("../library/functions.php");

header('Content-Type: application/json');

try {
    $sql = "SELECT id, ticket_number, subject, priority, 
                   TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS waiting_minutes
            FROM tickets 
            WHERE assigned_to IS NULL AND status = 'Open'
            ORDER BY FIELD(priority, 'Critical', 'High', 'Medium', 'Low'),
                     created_at ASC";

    $getUnAssignedTickets = retrieve($sql, array());

    $tickets = [];

    foreach ($getUnAssignedTickets as$row) {
        $priorityClass = match($row['priority']) {
            'Low'      => 'text-low',
            'Medium'   => 'text-medium',
            'High'     => 'text-high',
            'Critical' => 'text-critical',
            default    => ''
        };

        $tickets[] = [
            'id'             => $row['id'],
            'ticket_number'  => htmlspecialchars($row['ticket_number']),
            'subject'        => htmlspecialchars($row['subject']),
            'priority'       => htmlspecialchars($row['priority']),
            'priority_class' => $priorityClass,
            'waiting_time'   => htmlspecialchars(format_waiting_time($row['waiting_minutes']))
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $tickets]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>