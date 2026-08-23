<?php

    include('../includes/connect.php');
    include('../includes/session.php');
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'tickets' => array());

    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT ticket_number, subject, priority, 
                TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS waiting_minutes
        FROM tickets
        WHERE assigned_to IS NULL AND status = 'Open'
        ORDER BY 
            FIELD(priority, 'Critical','High','Medium','Low'),
            created_at ASC"
    );
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tickets as $t) {
        $tickets_out[] = array(
            'ticket_number'   => $t['ticket_number'],
            'subject'         => $t['subject'],
            'priority'        => $t['priority'],
            'waiting'         => format_waiting_time($t['waiting_minutes']),
            'waiting_minutes' => $t['waiting_minutes'],
        );
    }

    $response['status'] = 'success';
    $response['tickets'] = $tickets_out;
    echo json_encode($response);
    exit;

?>