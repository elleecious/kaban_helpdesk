<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("This script can only be run from the command line, not a browser.\n");
}

require_once __DIR__ . '/../config/connect.php';
const ADMIN_ROLE = 'Administrator'; // <-- confirm this matches your users.role enum

$username = $argv[1] ?? null;
$password = $argv[2] ?? null;
$fullName = $argv[3] ?? $username;

if (!$username || !$password) {
    fwrite(STDERR, "Usage: php admin/seed_admin.php <username> <password> [name]\n");
    exit(1);
}

if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// Check if the username already exists
$existing = retrieve(
    'SELECT id FROM users WHERE username = ?',
    [$username]
);

if (!empty($existing)) {
    // Update existing account: refresh password + ensure role is Administrator
    $rows = manage(
        'UPDATE users SET password_hash = ?, role = ?, name = ? WHERE username = ?',
        [$hash, ADMIN_ROLE, $fullName, $username]
    );
    echo "Existing user '{$username}' updated and set to " . ADMIN_ROLE . ".\n";
} else {
    // Create new admin account
    $rows = manage(
        'INSERT INTO users (username, password_hash, name, role) VALUES (?, ?, ?, ?)',
        [$username, $hash, $fullName, ADMIN_ROLE]
    );
    echo "Admin account '{$username}' created successfully.\n";
}

?>