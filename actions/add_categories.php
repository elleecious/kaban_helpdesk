<?php
    include('../includes/connect.php');
    include("../includes/session.php");
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');

    $category_name = $_POST['category_name'];
    $category_code = $_POST['category_code'];

    $add_category_users_sql = manage("INSERT INTO categories (name,code,created_at) VALUES(?,?,?)",array($category_name,$category_code,date('Y-m-d H:i:s')));

    $logs_result = manage("INSERT INTO logs (username, computer_name,ip_address,page,action,details,date)
            VALUES (?,?,?,?,?,?,?)",
        array($login_id, gethostbyaddr($_SERVER['REMOTE_ADDR']),getLocalIP(),"Add Category","ADD",         
            "<details>
                <p>Add Category</p>
                <p>
                    Category Code: <span class='font-weight-bold'>".$category_code."</span><br>
                    Category Name: <span class='font-weight-bold'>".$category_name."</span><br>
                </p>
            </details>", date('Y-m-d H:i:s a')));

        if ($add_category_users_sql && $logs_result) {

            $response['status'] = 'success';
            $response['message'] = 'Added category successfully';
        } else {
            
            $response['status'] = 'success';
            $response['message'] = 'Failed to add category';
        }

    
    echo json_encode($response);

?>