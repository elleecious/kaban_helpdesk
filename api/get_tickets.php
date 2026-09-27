<?php
// Ensure active session for user ID
include('../config/connect.php');
include('../includes/session.php');
include("../library/functions.php");
header('Content-Type: application/json');

// Replace with your actual user session variable
$login_id = $_SESSION['login_id'] ?? null;

if (!$login_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $sql = "SELECT 
                t.id AS ticket_id, 
                t.ticket_number, 
                t.subject, 
                t.priority, 
                t.status, 
                t.resolution_due_at,
                TIMESTAMPDIFF(MINUTE, NOW(), t.resolution_due_at) AS minutes_left,
                u.name AS requester_name
            FROM tickets t
            JOIN users u ON t.created_by = u.id
            WHERE t.assigned_to = ? 
              AND t.status NOT IN ('Resolved', 'Closed')
            ORDER BY 
                FIELD(t.priority, 'Critical', 'High', 'Medium', 'Low'),
                t.resolution_due_at ASC";

    $getTicketQueue = retrieve($sql, array($login_id));

    $tickets = [];

    foreach ($getTicketQueue as $row) {
        $minutesLeft = $row['minutes_left'];
        
        if ($minutesLeft < 0) {
            $slaDisplay = '<h5><span class="badge rounded-pill badge-danger">Overdue</span></h5>';
        } else {
            // Assumes format_waiting_time() function is available
            $slaDisplay = htmlspecialchars(format_waiting_time($minutesLeft)) . ' left';
        }

        $statusBadge = match($row['status']) {
            'In Progress' => 'badge-warning', 
            'Pending'     => 'badge-info',
            'Resolved'    => 'badge-success',
            'Closed'      => 'badge-secondary',
            default       => 'badge-secondary'
        };

        $tickets[] = [
            'ticket_id'      => $row['ticket_id'],
            'ticket_number'  => htmlspecialchars($row['ticket_number']),
            'subject'        => htmlspecialchars($row['subject']),
            'priority'       => htmlspecialchars($row['priority']),
            'sla_display'    => $slaDisplay,
            'status'         => htmlspecialchars($row['status']),
            'status_badge'   => $statusBadge,
            'requester_name' => htmlspecialchars($row['requester_name'])
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $tickets]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>