<?php
include('../includes/connect.php');

header('Content-Type: application/json');
session_start();

$response = array('status' => 'success', 'agents' => array());

global $pdo;

try {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.name,
                (SELECT COUNT(*) FROM tickets t WHERE t.assigned_to = u.id AND t.status NOT IN ('Resolved','Closed')) AS open_count
         FROM users u
         WHERE u.role = 'IT Support Specialist'
         ORDER BY u.name ASC"
    );
    $stmt->execute();
    $response['agents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    $response['status'] = 'error';
    $response['message'] = 'Unable to load agents.';
}

echo json_encode($response);
exit;

?>