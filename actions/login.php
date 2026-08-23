<?php

include('../includes/connect.php');
include('../library/functions.php');

session_start();

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'An error occured.');

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';


if ($email === '' || $password === '') {
    $response['message'] = 'Email and password are required.';
    exit;
}


$user = retrieve("SELECT * FROM users WHERE email = ?", array($email));

if ($user) {
    $user = $user[0];
    if (password_verify($password,$user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['login_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
                VALUES (?,?,?,?,?,?,?)
            ",array(
                $user['username'],
                gethostbyaddr($_SERVER['REMOTE_ADDR']),
                getLocalIP(),
                "Login",
                "LOGIN",
                "<details>
                    <p>User Login</p>
                    <p>Email: ".$email."</p>
                </details>",
                date("Y-m-d H:i:s a")
            )
        );

        $response = array(
            'status' => 'success', 
            'message' => 'Login successful.',
            'role' => $user['role']);
    } else {
        $response['message'] = 'Invalid username or password';
    }
} else {
    $response['message'] = 'Invalid username or password';
}

echo json_encode($response);

?>