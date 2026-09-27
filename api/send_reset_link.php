<?php

include("../includes/session.php");
include('../library/password_reset.php');

header('Content-Type: application/json');

if (empty($_SESSION['login_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Invalid user.']);
    exit;
}

$result = request_password_reset($userId);
echo json_encode(['success' => $result]);

?>