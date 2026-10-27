<?php
session_start();
include('../config/connect.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['login_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$ticketId = filter_input(INPUT_GET, 'ticket_id', FILTER_VALIDATE_INT);
if (!$ticketId) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Missing ticket_id']));
}

$comments = retrieve(
    "SELECT c.id, c.body, c.created_at, u.name AS author_name, u.role AS author_role
     FROM comments AS c
     LEFT JOIN users AS u ON c.author_id = u.id
     WHERE c.ticket_id = ?
     ORDER BY c.id ASC",
    array($ticketId)
);

$comments = $comments ?: [];

echo json_encode(['success' => true, 'comments' => $comments]);

?>