<?php

include('../config/connect.php');
include('../library/functions.php');

session_start();

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'An error occured.');

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $response['status']  = 'error';
    $response['message'] = 'Email and password are required.';
    echo json_encode($response);
    exit;
}

$user = retrieve("SELECT * FROM users WHERE email = ?", array($email));

if ($user) {
    $user = $user[0];

    if (password_verify($password, $user['password_hash'])) {

        if ($user['status'] != 1) {
            $response['status']  = 'Account Deactivated';
            $response['message'] = 'Your account is deactivated. Please contact the Administrator.';
            echo json_encode($response);
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['login_id'] = $user['id'];
        $_SESSION['role']     = $user['role'];

        manage(
            "UPDATE users SET last_login_at = NOW(), last_activity_at = NOW() WHERE id = ?",
            array($user['id'])
        );

        manage(
            "INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
             VALUES (?,?,?,?,?,?,?)",
            array(
                $user['username'],
                $_SERVER['REMOTE_ADDR'],
                getLocalIP(),
                "Login",
                "LOGIN",
                "<details>
                    <p>User Login</p>
                    <p>Email: ".htmlspecialchars($email)."</p>
                </details>",
                date("Y-m-d H:i:s")
            )
        );

        $response = array(
            'status'  => 'success',
            'message' => 'Login successful.',
            'role'    => $user['role']
        );

    } else {
        $response['status']  = 'error';
        $response['message'] = 'Invalid username or password';
    }

} else {
    $response['status']  = 'error';
    $response['message'] = 'Invalid username or password';
}

echo json_encode($response);
exit;