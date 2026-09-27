    <?php
    include('../config/connect.php');
    include("../includes/session.php");
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response['status'] = 'error';
    $response['message'] = 'Invalid request';
    // $response = array('status' => 'error', 'message' => 'Invalid request');

    $category = $_POST['category'];
    $priority = $_POST['priority'];
    $response_minutes = $_POST['response_minutes'];
    $resolution_hours = $_POST['resolution_hours'];  

    $getCategory=retrieve("SELECT name FROM categories WHERE id=?",array($category));
    $get_username = retrieve("SELECT * FROM users WHERE id=?",array($login_id));

    if (empty($getCategory)) {
        $response['status'] = 'error';
        $response['message'] = 'Category not found';
        exit;
    }

    $category_name=$getCategory[0]['name'];

    $allowed_priorities = ['Critical', 'High', 'Medium', 'Low'];
    if (!in_array($priority, $allowed_priorities)) {
        $response['status'] = 'error';
        $response['message'] = 'Invalid priority';
        echo json_encode($response);
        exit;
    }

    if (!is_numeric($response_minutes) || !is_numeric($resolution_hours) || $response_minutes < 0 || $resolution_hours < 0) {      
        $response['status'] = 'error';
        $response['message'] = 'Hours must be valid positive numbers';
        echo json_encode($response);
        exit;
    }

    $existing = retrieve("SELECT id FROM sla_rules WHERE category_id=? AND priority=?", array($category, $priority));
    if (!empty($existing)) {
        $response['status'] = 'error';
        $response['message'] = 'SLA rule for this category and priority already exists';
        echo json_encode($response);
        exit;
    }

    $add_sla_rules_sql = manage("INSERT INTO sla_rules(category_id,priority,response_minutes,resolution_hours) VALUES(?,?,?,?)",array($category,$priority,$response_minutes,$resolution_hours));
    $logs_result = manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
            VALUES (?,?,?,?,?,?,?)",
        array($get_username[0]['username'],gethostbyaddr($_SERVER['REMOTE_ADDR']),getLocalIP(),"Add Category","ADD",         
            "<details>
                <p>Add SLA Rules</p>
                <p>
                    Category Name: <span class='font-weight-bold'>".$category_name."</span><br>
                    Priority: <span class='font-weight-bold'>".$priority."</span><br>
                    Response Hours: <span class='font-weight-bold'>".$response_minutes."</span><br>
                    Resolution Hours: <span class='font-weight-bold'>".$resolution_hours."</span><br>
                </p>
            </details>", date('Y-m-d H:i:s a')));

    if ($add_sla_rules_sql && $logs_result) {
        $response['status'] = 'success';
        $response['message'] = 'Added SLA Rules successfully';
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Failed to add SLA Rules';
    }

    
    echo json_encode($response);

?>