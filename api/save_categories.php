<?php
    include('../config/connect.php');
    include("../includes/session.php");
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');

    $edit_category_id = htmlspecialchars($_POST['edit_category_id']);
    $edit_category_code = htmlspecialchars($_POST['edit_category_code']);
    $edit_category_name = htmlspecialchars($_POST['edit_category_name']);

    $get_username = retrieve("SELECT username FROM users WHERE id=?",array($login_id));

    $getCategory = retrieve("SELECT * FROM category WHERE id=?",array($edit_category_id));
    $category = $getCategory[0];

    $save_category_sql = manage("UPDATE categories SET name=?, code=? WHERE id=?",array($edit_category_name,$edit_category_code,$edit_category_id));

    $logs_result = manage("INSERT INTO logs (username, computer_name,ip_address,page,action,details,date)
            VALUES (?,?,?,?,?,?,?)",
        array($get_username[0]['username'], gethostbyaddr($_SERVER['REMOTE_ADDR']),getLocalIP(),"Manage Categories","UPDATE",         
            "<details>
                <p>Update Category</p>
                <p>
                    Category Code: ".$category['code']." => <span class='font-weight-bold'>".$edit_category_code."</span><br>
                    Category Name: ".$category['name']."  => <span class='font-weight-bold'>".$edit_category_name."</span><br>
                </p>
            </details>", date('Y-m-d H:i:s a')));
    
    if ($save_category_sql && $logs_result) {
        $response = array('status' => 'success', 'message' => 'Category updated successfully');
    } else {
        $response = array('status' => 'error', 'message' => 'Failed to update');
    }
    
    echo json_encode($response);

?>