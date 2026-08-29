<?php
    include('../includes/connect.php');
    include("../includes/session.php");
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');

    $edit_sla_id = htmlspecialchars($_POST['edit_sla_id']);
    $edit_sla_cat_id = htmlspecialchars($_POST['edit_sla_cat_id']);
    $edit_sla_priority = htmlspecialchars($_POST['edit_sla_priority']);
    $edit_sla_response_hours = htmlspecialchars($_POST['edit_sla_response_hours']);
    $edit_sla_resolution_hours = htmlspecialchars($_POST['edit_sla_resolution_hours']);

    $getSLA = retrieve("SELECT cat.id AS cat_id, cat.name AS cat_name, sla.id AS sla_id, 
                        sla.priority AS priority, sla.response_hours AS response_hours, sla.resolution_hours AS resolution_hours 
                        FROM sla_rules AS sla INNER JOIN categories AS cat ON sla.category_id=cat.id WHERE sla.id=?",array($edit_sla_id));
    $sla_rules = $getSLA[0];

    $save_sla_sql = manage("UPDATE sla_rules SET category_id=?, priority=?, response_hours=?, resolution_hours=? WHERE id=?",
        array($edit_sla_cat_id,$edit_sla_priority,$edit_sla_response_hours,$edit_sla_resolution_hours,$edit_sla_id));

    $getCat = retrieve("SELECT name FROM categories WHERE id=?",array($edit_sla_cat_id));
    $category_name=$getCat[0]['name'];

    $logs_result = manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
            VALUES (?,?,?,?,?,?,?)",
        array($login_id,gethostbyaddr($_SERVER['REMOTE_ADDR']),getLocalIP(),"Update SLA RULES","UPDATE",         
            "<details>
                <p>Update SLA Rules</p>
                <p>
                    Category: ".$sla_rules['cat_name']." => <span class='font-weight-bold'>".$category_name."</span><br>
                    Priority: ".$sla_rules['priority']." => <span class='font-weight-bold'>".$edit_sla_priority."</span><br>
                    Response Hours: ".$sla_rules['response_hours']." => <span class='font-weight-bold'>".$edit_sla_response_hours."</span><br>
                    Resolution Hours: ".$sla_rules['resolution_hours']." => <span class='font-weight-bold'>".$edit_sla_resolution_hours."</span><br>
                </p>
            </details>", date('Y-m-d H:i:s a')));
    
    if ($save_sla_sql && $logs_result) {
        $response = array('status' => 'success', 'message' => 'SLA Rules updated successfully');
    } else {
        $response = array('status' => 'error', 'message' => 'Failed to update');
    }
    
    echo json_encode($response);

?>