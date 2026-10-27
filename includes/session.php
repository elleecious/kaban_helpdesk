<?php

    session_set_cookie_params(0);
    session_start();

    $login_id = $_SESSION['login_id'] ?? null;   // changed: no warning when logged out

    // Track last activity (at most one write per minute per session)
    if (!empty($login_id) && empty($skip_activity)) {
        if (!isset($_SESSION['last_activity_write']) || time() - $_SESSION['last_activity_write'] >= 60) {
            manage("UPDATE users SET last_activity_at = NOW() WHERE id = ?", array($login_id));
            $_SESSION['last_activity_write'] = time();
        }
    }

    if (isset($_SESSION['login_id'])) {

        $get_account = retrieve("SELECT * FROM users WHERE id=?", array($login_id));

        if ($get_account) {
            $user = $get_account[0];

            // new: kick out accounts that were deactivated while logged in
            if ($user['status'] != 1) {
                session_unset();
                session_destroy();
                header("location: index.php");
                exit;
            }

            $user_id = $user['id'];
            $name = $user['name'];
            $email = $user['email'];
            $username = $user['username'];
            $role = $user['role'];
            $department = $user['department'];

        } else {
            $name = $role = 'Unknown';
        }
    } else {
        $name = $role = 'Not logged in';
    }

    if (!isset($_SESSION['login_id'])) {
        header("location: index.php");
        exit;
    }

?>