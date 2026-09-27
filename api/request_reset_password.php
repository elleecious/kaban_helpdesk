<?php
include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');
include("../library/notify.php");

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'Invalid request');

// Only Admin/IT Manager allowed
if (!in_array($_SESSION['role'], ['Administrator', 'IT Manager'])) {
    $response['message'] = 'You do not have permission to reset passwords.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$userId = $input['user_id'] ?? null;
$newPassword = $input['new_password'] ?? '';

if (!$userId || strlen($newPassword) < 4) {
    $response['message'] = 'Invalid user or password too short.';
    echo json_encode($response);
    exit;
}

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$get_target_user = retrieve("SELECT id, name, email FROM users WHERE id = ?", array($userId));
if (empty($get_target_user)) {
    $response['message'] = 'User not found.';
    echo json_encode($response);
    exit;
}
$targetUser = $get_target_user[0];

$get_admin = retrieve("SELECT name FROM users WHERE id = ?", array($_SESSION['login_id']));
$adminName = $get_admin[0]['name'] ?? 'Unknown';

$rowsAffected = manage(
    "UPDATE users SET password_hash = ? WHERE id = ?",
    array($hashedPassword, $userId)
);

if ($rowsAffected > 0) {

    create_notification(
        $userId,
        'password_reset',
        "Your password was reset by an administrator. Please contact them to receive your new password securely.",
        null, null, null
    );

    $logs_result = manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
        VALUES (?,?,?,?,?,?,?)",
        array(
            $adminName,
            gethostbyaddr($_SERVER['REMOTE_ADDR']),
            getLocalIP(),
            "Manage Users",
            "RESET PASSWORD",
            "<details><p>Password reset for user: ".$targetUser['name']." (".$targetUser['email'].")</p>
            <p>Reset by: <span class='font-weight-bold'>".$adminName."</span></p></details>",
            date('Y-m-d H:i:s')
        )
    );

    $response['status'] = 'success';
    $respomse['message'] = 'Password reset successfully.';
} else {
    $response['status'] = 'error';
    $response['message'] = 'Failed to reset password.';
}

echo json_encode($response);
exit;

?>