<?php
include('notify.php');


function request_password_reset($userId) {
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    manage("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0", [$userId]);

    $sql = "INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)";
    manage($sql, [$userId, $tokenHash, $expiresAt]);

    $resetLink = "reset_password.php?token=" . $token;

    create_notification(
        $userId,
        'password_reset',
        "A password reset was requested. Click the link to set a new password. This link expires in 1 hour.",
        $resetLink
    );

    return true;
}

function verify_reset_token($token) {
    $tokenHash = hash('sha256', $token);

    $sql = "SELECT id, user_id, expires_at, used FROM password_resets WHERE token_hash = ? LIMIT 1";
    $row = fetch_one($sql, [$tokenHash]);

    if (!$row) return false;
    if ($row['used']) return false;
    if (strtotime($row['expires_at']) < time()) return false;

    return $row;
}

function complete_password_reset($token, $newPassword) {
    $row = verify_reset_token($token);
    if (!$row) {
        return ['success' => false, 'error' => 'Invalid or expired link.'];
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

    manage("UPDATE users SET password = ? WHERE id = ?", [$hashed, $row['user_id']]);
    manage("UPDATE password_resets SET used = 1 WHERE id = ?", [$row['id']]);

    create_notification(
        $row['user_id'],
        'password_reset',
        "Your password was successfully changed.",
        null
    );

    return ['success' => true];
}

?>