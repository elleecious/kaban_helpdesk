<?php

include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'Invalid request');


$current_password = htmlspecialchars($_POST['current_password']);
$new_password = htmlspecialchars($_POST['new_password']);

$get_username = retrieve("SELECT username FROM users WHERE id=?",array($login_id));

$hashed_password = retrieve("SELECT password_hash FROM users WHERE id=?",array($login_id));

if (!password_verify($current_password, $hashed_password[0]['password_hash'])) {
    $response['message'] = 'Current password is incorrect.';
    echo json_encode($response);
    exit;
}

// Prevent using the current password as the new password
if (password_verify($new_password, $hashed_password[0]['password_hash'])) {
    $response['message'] = 'New password cannot be the same as your current password.';
    echo json_encode($response);
    exit;
}

$new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

$save_pass_sql = manage("UPDATE users SET password_hash=? WHERE id=?",array($new_hashed_password, $login_id));
$logs_result = manage("INSERT INTO logs (username, computer_name,ip_address,page,action,details,date)
        VALUES (?,?,?,?,?,?,?)",
    array($get_username[0]['username'], gethostbyaddr($_SERVER['REMOTE_ADDR']),getLocalIP(),"UPDATE PASSWORD","UPDATE",         
        "<details>
            <p>Update Password</p>
            <p>Name: ".$name."</p>
            <p>Date: ".date('Y-m-d H:i:s')."</p>
        </details>", date('Y-m-d H:i:s')));

if ($save_pass_sql && $logs_result) {
    $response['status'] = 'success';
    $response['message'] = 'Password changed successfully.';
} else {
    $response['status'] = 'error';
    $response['message'] = 'Failed to change password.';
}

echo json_encode($response);
?>