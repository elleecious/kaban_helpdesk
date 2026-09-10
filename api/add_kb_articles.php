<?php

include('../config/connect.php');
include("../includes/session.php");
include('../library/functions.php');

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'Invalid request');

$kba_title = trim($_POST['kba_title'] ?? '');
$kba_description = trim($_POST['kba_description'] ?? '');
$kba_category = trim($_POST['kba_category'] ?? '');

if (empty($kb_category)) {
    $response['status'] = 'warning';
    $response['message'] = 'Please select a category';
}

$get_username = retrieve("SELECT * FROM users WHERE id=?",array($login_id));

$add_kba_sql = manage("INSERT INTO kb_articles (category_id, title, body, created_by, created_at) 
    VALUES(?,?,?,?,?)",array($kba_category, $kba_title, $kba_description, $login_id, date("Y-m-d H:i:s")));

$logs_result = manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
                    VALUES (?,?,?,?,?,?,?)",
                array($get_username[0]['username'], gethostbyaddr($_SERVER['REMOTE_ADDR']),getLocalIP(),"Add KB Articles","ADD",         
                    "<details>
                        <p>Add User</p>
                        <p>
                            Title: <span class='font-weight-bold'>".$kba_title."</span><br>
                            Created By: <span class='font-weight-bold'>".$get_username[0]['username']."</span><br>
                        </p>
                    </details>", date('Y-m-d H:i:s a')));

if ($add_kba_sql && $logs_result) {
    $response['status'] = 'success';
    $response['message'] = 'Added Knowledge Articles successfully';
} else {
    $response['status'] = 'error';
    $response['message'] = 'Failed to add KB Articles';
}

echo json_encode($response);


?>