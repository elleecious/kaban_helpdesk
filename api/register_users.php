<?php
include('../config/connect.php');
include('../library/functions.php');
require_once('../library/registration_options.php');

if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

function respond($status, $message, $code = 200) {
    http_response_code($code);
    echo json_encode(array('status' => $status, 'message' => $message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Invalid request', 405);
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['reg_csrf']) || !hash_equals($_SESSION['reg_csrf'], $token)) {
    respond('error', 'Your session expired. Please refresh the page and try again.', 403);
}

// Honeypot: real users never fill this hidden field
if (!empty($_POST['website'])) {
    respond('success', 'Your registration request has been submitted.');
}

// Inputs
$name = trim(preg_replace('/\s+/', ' ', $_POST['name'] ?? ''));
$email = strtolower(trim($_POST['email'] ?? ''));
$role  = "Employee" ?? '');
$department = trim($_POST['department'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

// Validation
if (!preg_match('/^[\p{L}\p{M}][\p{L}\p{M} .\'-]{1,98}$/u', $name)) {
    respond('error', 'Please enter your full name (letters only, 2 to 100 characters).');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond('error', 'Please enter a valid email address.');
}
$domain = substr(strrchr($email, '@'), 1);
if (!in_array($domain, REGISTRATION_EMAIL_DOMAINS, true)) {
    respond('error', 'Please use your company email address.');
}
if (!in_array($role, registration_roles(), true)) {
    respond('error', 'Please select a valid role.');
}
if (!in_array($department, registration_departments(), true)) {
    respond('error', 'Please select a valid department.');
}
if (strlen($password) < 8) {
    respond('error', 'Password must be at least 8 characters.');
}
if (strlen($password) > 72) {
    respond('error', 'Password must be 72 characters or fewer.');   // bcrypt ignores anything past 72 bytes
}
if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    respond('error', 'Password must contain both letters and numbers.');
}
if (!hash_equals($password, $confirm)) {
    respond('error', 'Passwords do not match.');
}

try {
    // Global throttle: at most 20 registration requests per hour
    $recent = retrieve("SELECT COUNT(*) AS c FROM logs WHERE action = ? AND date >= ?",
                       array('REGISTER', date('Y-m-d H:i:s', time() - 3600)));
    if ((int)$recent[0]['c'] >= 20) {
        respond('error', 'Too many requests right now. Please try again later.', 429);
    }

    // Duplicate email
    $check_email = retrieve("SELECT id FROM users WHERE email = ?", array($email));
    if (count($check_email) > 0) {
        respond('warning', 'An account with this email already exists.');
    }

    // Username from the email, made unique if needed
    $username = preg_replace('/[^a-z0-9._-]/', '', explode('@', $email)[0]);
    if ($username === '') { $username = 'user'; }
    $base = $username;
    while (count(retrieve("SELECT id FROM users WHERE username = ?", array($username))) > 0) {
        $username = $base . random_int(100, 999);
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    unset($password, $confirm);
    $now = date('Y-m-d H:i:s');

    $add_user = manage("INSERT INTO users (name,username,email,password_hash,role,department,status,created_at)
                    VALUES (?,?,?,?,?,?,?,?)",
                    array($name, $username, $email, $hashed_password, $role, $department, 1, $now));

    $e = function ($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };

    $logs_result = manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
            VALUES (?,?,?,?,?,?,?)",
    array($username, gethostbyaddr($_SERVER['REMOTE_ADDR']), getLocalIP(), "Registration", "REGISTER",
        "<details>
            <p>New account registered</p>
            <p>
                Name: <span class='font-weight-bold'>".$e($name)."</span><br>
                Email: <span class='font-weight-bold'>".$e($email)."</span><br>
                Role: <span class='font-weight-bold'>".$e($role)."</span><br>
                Department: <span class='font-weight-bold'>".$e($department)."</span><br>
                Username: <span class='font-weight-bold'>".$e($username)."</span><br>
            </p>
        </details>", $now));

    if ($add_user && $logs_result) {
       respond('success', 'Welcome, ' . $name . '! Your account is ready. Redirecting you to the login page...');
    }

    respond('error', 'Failed to submit your request. Please try again.');

} catch (Throwable $ex) {
    error_log('register_users.php: ' . $ex->getMessage());
    respond('error', 'Something went wrong. Please try again.', 500);
}