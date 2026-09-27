<?php
include("../config/connect.php");
include("../includes/session.php");
require_once __DIR__ . '/../library/notify.php';

header('Content-Type: application/json');

$current_user_id = $_SESSION['login_id'] ?? null;

if (!$current_user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthenticated']);
    exit;
}

// Mark All Read
if (isset($_GET['action']) && $_GET['action'] === 'mark_all') {
    mark_all_as_read($current_user_id);
    echo json_encode(['status' => 'success']);
    exit;
}

// Mark One Read
if (isset($_GET['action']) && $_GET['action'] === 'mark_one' && isset($_GET['id'])) {
    $ok = mark_as_read((int)$_GET['id'], $current_user_id);
    echo json_encode(['status' => $ok ? 'success' : 'error']);
    exit;
}

// Fetch counts and notifications
$unread_count = get_unread_count($current_user_id);
$notifications = get_recent_notifications($current_user_id, 10);

echo json_encode([
    'status' => 'success',
    'unread_count' => $unread_count,
    'notifications' => $notifications
]);
exit;