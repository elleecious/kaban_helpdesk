<?php
include('../includes/connect.php');
include('../library/functions.php');
session_start();

if (isset($_SESSION['login_id'])) {
    $login_id = $_SESSION['login_id'];
} else {
    $login_id = "Unknown";
}

$get_username = retrieve("SELECT * FROM users WHERE id=?",array($login_id));

session_unset();

if (ini_get("session.use_cookies")) {

    manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
            VALUES (?,?,?,?,?,?,?)
            ",array($login_id, gethostbyaddr($_SERVER['REMOTE_ADDR']),
            getLocalIP(),"HOME",
            "LOGOUT",
            "<details>
                <p>User Logout</p>
                <p>Username: ".$get_username[0]['username']."</p>
            </details>",
            date("Y-m-d H:i:s a")
        )
    );

    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"], $params["secure"], $params["httponly"]
    );
}

session_destroy();

echo json_encode(array('status' => 'success', 'message' => 'Logged out successfully'));
?>
