<?php
include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');

header('Content-Type: application/json');

try {
    $sql = "SELECT action, username, date FROM logs ORDER BY date DESC LIMIT 3";

    $get_recent_logs = retrieve($sql, array());

    $logs = [];

    foreach ($get_recent_logs as $row) {
        $logs[] = [
            'action'   => htmlspecialchars($row['action']),
            'username' => htmlspecialchars($row['username']),
            'date'     => htmlspecialchars($row['date']),
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $logs]);

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch logs']);
}

?>