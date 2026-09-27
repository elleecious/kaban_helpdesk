<?php
 include('../config/connect.php');
include("../includes/session.php");
include('../library/functions.php');

header('Content-Type: application/json');

// Adjust to your actual auth/role check
if (!isset($_SESSION['login_id']) || in_array($_SESSION['role'], ['Administrator', 'IT Manager']) === false) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$currentStatus = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $currentStatus === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Prevent an admin from locking their own account out
if ($id === (int)$_SESSION['login_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot change your own status']);
    exit;
}

$get_admin = retrieve("SELECT name FROM users WHERE id = ?", array($_SESSION['login_id']));
$adminName = $get_admin[0]['name'] ?? 'Unknown';

$newStatus = $currentStatus == 1 ? 0 : 1;

try {
    $rows = manage(
        "UPDATE users SET status = ? WHERE id = ?",
        [$newStatus, $id]
    );

    if ($rows > 0) {

        manage("INSERT INTO logs (username, computer_name, ip_address, page, action, details, date)
            VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $adminName,
                gethostbyaddr($_SERVER['REMOTE_ADDR']),
                getLocalIP(),
                "Manage Users",
                $newStatus == 1 ? "Activated User" : "Deactivated User",
                "User ID: $id",
                date('Y-m-d H:i:s')
            ]
        );

        echo json_encode([
            'success'   => true,
            'newStatus' => $newStatus,
            'message'   => $newStatus == 1 ? 'User activated' : 'User deactivated'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made or user not found']);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

?>