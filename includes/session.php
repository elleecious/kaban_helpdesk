<?php
    include_once('connect.php');
    session_start();

    if (isset($_SESSION['login_id'])) {
        $login_id = $_SESSION['login_id'];
        $get_account = retrieve("SELECT * FROM users WHERE id=?", array($login_id));

        if ($get_account) {
            $user = $get_account[0];
            $user_id = $user['id'];
            $name = $user['name'];
            $email = $user['email'];
            $username = $user['username'];
            $role = $user['role'];
            $department=$user['department'];

        } else {
            $name = $role = 'Unknown';
        }
    } else {
        $name = $role = 'Not logged in';
    }

    if (!isset($_SESSION['login_id'])) {
        header("location: index.php");
    }
    
?>
