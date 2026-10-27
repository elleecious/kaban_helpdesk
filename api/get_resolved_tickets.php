<?php
include('../config/connect.php');
include('../includes/session.php');
include("../library/functions.php");

header('Content-Type: application/json');

try {
    $resolvedByMe = retrieve(
        "SELECT t.id, t.ticket_number, t.subject, t.resolved_at, u.name,
                TIMESTAMPDIFF(MINUTE, t.resolved_at, NOW()) AS resolved_minutes_ago
         FROM tickets t INNER JOIN users u ON t.assigned_to=u.id
         WHERE t.status = 'Resolved'
         ORDER BY t.resolved_at DESC
         LIMIT 5",
        array()
    );

    $formattedData = array();

    foreach ($resolvedByMe as $resolved) {$formattedData[] = array(
            'id'            => $resolved['id'],
            'ticket_number' => $resolved['ticket_number'],
            'subject'       => $resolved['subject'],
            'name'          => $resolved['name'],
            'time_ago'      => time_ago($resolved['resolved_minutes_ago'])
        );
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