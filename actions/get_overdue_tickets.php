<?php

    include('../includes/connect.php');
    include('../includes/session.php');
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'success', 'tickets' => array());

    global $pdo; // adjust to your actual PDO variable name

    // $stmt = $pdo->prepare(
    //     "SELECT id, subject, priority
    //     FROM tickets
    //     WHERE sla_due_at < NOW() 
    //     AND status NOT IN ('Resolved', 'Closed')
    //     ORDER BY sla_due_at ASC"
    // );

    $stmt = $pdo->prepare(
    "SELECT id, subject, priority,
            CASE 
                WHEN status = 'Open' AND response_due_at < NOW() THEN 'Response'
                WHEN status NOT IN ('Resolved','Closed') AND resolution_due_at < NOW() THEN 'Resolution'
            END AS breach_type
     FROM tickets
     WHERE status NOT IN ('Resolved', 'Closed')
       AND (
            (status = 'Open' AND response_due_at < NOW())
            OR resolution_due_at < NOW()
       )
     ORDER BY resolution_due_at ASC"
    );
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tickets_out = array();
    foreach ($tickets as $t) {
        $tickets_out[] = array(
            'id'       => $t['id'],
            'subject'  => $t['subject'],
            'priority' => $t['priority'],
        );
    }

    $response['tickets'] = $tickets_out;
    echo json_encode($response);
    exit;

?>