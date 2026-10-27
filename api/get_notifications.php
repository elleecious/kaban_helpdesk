<?php

$skip_activity = true;

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

// Fetch counts and notifications, paginated
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

$unread_count = get_unread_count($current_user_id);
$total_count = get_total_notification_count($current_user_id);
$notifications = get_recent_notifications($current_user_id, $limit, $offset);

echo json_encode([
    'status' => 'success',
    'unread_count' => $unread_count,
    'notifications' => $notifications,
    'total_count' => $total_count,
    'has_more' => ($offset + $limit) < $total_count
]);
exit;