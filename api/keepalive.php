<?php
require_once('../includes/session.php');
header('Content-Type: application/json');

if (empty($login_id)) {            // use whatever your session var is
    http_response_code(401);
    echo json_encode(['status' => 'expired']);
    exit;
}
echo json_encode(['status' => 'success']);   // session.php already refreshed last_activity

?>