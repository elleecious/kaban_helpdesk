<?php
include('../config/connect.php');

header('Content-Type: application/json');
header('Cache-Control: no-store');

$count = retrieve(
    "SELECT COUNT(*) AS cnt FROM tickets WHERE status = 'Open' AND (assigned_to IS NULL OR assigned_to = '')",
    array()
)[0]['cnt'];

echo json_encode(['count' => (int)$count]);

?>