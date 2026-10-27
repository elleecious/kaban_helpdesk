<?php

include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');

header('Content-Type: application/json; charset=utf-8');

try {
    $get_users = retrieve(
        "SELECT u.*, 
                (u.last_activity_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)) AS is_online,
                TIMESTAMPDIFF(MINUTE, u.last_activity_at, NOW()) AS minutes_ago
         FROM users u",
        array()
    );

    $formattedData = array();

    if ($get_users && count($get_users) > 0) {
        foreach ($get_users as $user) {
            $ago = isset($user['minutes_ago']) ? last_seen_ago($user['minutes_ago']) : null;
            $formattedData[] = array(
                'id'  => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
                'department' => $user['department'],
                'username' => $user['username'],
                'status'  => (int)$user['status'],
                'is_online'  => !empty($user['is_online']),
                'ago' => $ago,
                'last_activity_at' => $user['last_activity_at'],
                'last_login_at' => $user['last_login_at'],
                'created_at'  => $user['created_at']
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