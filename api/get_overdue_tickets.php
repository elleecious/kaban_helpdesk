<?php

$skip_activity = true;

include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');

header('Content-Type: application/json');

try {
    $rows = retrieve(
        "SELECT id, ticket_number, subject, priority,
                CASE
                    WHEN resolution_due_at < NOW() THEN 'Resolution'
                    WHEN status = 'Open' AND response_due_at < NOW() THEN 'Response'
                END AS breach_type
         FROM tickets
         WHERE status NOT IN ('Resolved', 'Closed', 'Deleted')
           AND (
                resolution_due_at < NOW()
                OR (status = 'Open' AND response_due_at < NOW())
           )
         ORDER BY FIELD(priority, 'Critical','High','Medium','Low'), resolution_due_at ASC",
        array()
    );

    $tickets = [];
    foreach ($rows as $r) {
        $tickets[] = [
            'id'            => $r['id'],
            'ticket_number' => htmlspecialchars($r['ticket_number']),
            'subject'       => $r['subject'],
            'priority'      => htmlspecialchars($r['priority']),
            'breach_type'   => $r['breach_type'],
        ];
    }

    echo json_encode(['status' => 'success', 'tickets' => $tickets]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to load overdue tickets.']);
}
exit;