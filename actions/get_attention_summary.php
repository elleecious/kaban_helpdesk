<?php

    include('../includes/connect.php');
    include('../includes/session.php');
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'success', 'unassigned_count' => 0, 'escalated_count' => 0);

    global $pdo;

    // Unassigned tickets waiting more than 1 hour
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS cnt 
        FROM tickets 
        WHERE assigned_to IS NULL 
        AND status = 'Open' 
        AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    );
    $stmt->execute();
    $response['unassigned_count'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

    // Tickets escalated by an agent
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS cnt 
        FROM tickets WHERE status = 'Escalated'");
    $stmt->execute();
    $response['escalated_count'] = (int) $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

    echo json_encode($response);
    exit;

?>