<?php
    include('../includes/connect.php');
    include('../includes/session.php');
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');

    $name = $_POST['name'];
    $email = filter_var($_POST['email'],FILTER_VALIDATE_EMAIL);
    $role = $_POST['role'];
    $department = $_POST['department'];
    $username = explode("@",$email)[0];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $check_email = retrieve("SELECT * FROM users WHERE email=?",array($email));
    if (count($check_email) > 0) {
        $response = array(
            "status" => "error",
            "message" => "Email is already registered",
        );
    }

    if ($password == $confirm_password) {
        $add_users_sql = manage("INSERT INTO users (name,username,email,password_hash,role,department,created_at) 
                        VALUES(?,?,?,?,?,?,?)",
                        array($name,$username,$email,$hashed_password,$role,$department,date('Y-m-d H:i:s')));

        $logs_result = manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
                    VALUES (?,?,?,?,?,?,?)",
                array($login_id, gethostbyaddr($_SERVER['REMOTE_ADDR']),getLocalIP(),"Add User","ADD",         
                    "<details>
                        <p>Add User</p>
                        <p>
                            Name: <span class='font-weight-bold'>".$name."</span><br>
                            Email: <span class='font-weight-bold'>".$email."</span><br>
                            Role: <span class='font-weight-bold'>".$role."</span><br>
                            Department: <span class='font-weight-bold'>".$department."</span><br>
                            Username: <span class='font-weight-bold'>".$username."</span><br>
                        </p>
                    </details>", date('Y-m-d H:i:s a')));

        if ($add_users_sql && $logs_result) {
            $response = array(
                'status'=>'success',
                'message'=>'Added '.$name.' successfully',
                'username'=>$username);
        } else {
            $response = array('status' => 'error', 'message' => 'Failed to add a user');
        }
    } else{
        $response = array('status' => 'error', 'message' => 'Passwords do not match');
    }
    
    echo json_encode($response);

?>