<?php
    
    include('../includes/connect.php');
    include('../includes/session.php');
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');

    $ticketId = $_POST['ticket_id'] ?? null;

    if (!$ticketId) {
        $response['message'] = 'No ticket specified.';
        echo json_encode($response);
        exit;
    }

    global $pdo;

    $stmt = $pdo->prepare("SELECT ticket_number, subject, priority, status FROM tickets WHERE id = ? AND assigned_to IS NULL");
    $stmt->execute(array($ticketId));
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    $agentName = retrieve("SELECT name FROM users WHERE id=?",array($login_id));

    if (!$ticket) {
        $response['status'] = 'already_claimed';
        $response['message'] = 'This ticket was just claimed by someone else.';
        echo json_encode($response);
        exit;
    }

    $rowsAffected = manage("UPDATE tickets SET assigned_to = ?, status = 'In Progress' 
        WHERE id = ? AND assigned_to IS NULL",array($login_id, $ticketId));

    if ($rowsAffected > 0) {

        manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
            VALUES (?,?,?,?,?,?,?)",
            array(
                $login_id,gethostbyaddr($_SERVER['REMOTE_ADDR']),getLocalIP(),
                "IT Support Queue",
                "UPDATE",
                "<details>
                    <p>Pick Up Unassigned Ticket</p>
                    <p>
                        Ticket #: ".$ticket['ticket_number']."<br>
                        Subject: ".$ticket['subject']."<br>
                        Priority: ".$ticket['priority']."<br>
                        Status: ".$ticket['status']." => <span class='font-weight-bold'>In Progress</span><br>
                        Assigned To: <span class='font-weight-bold'>".$agentName[0]['name']."</span><br>
                    </p>
                </details>",
                date('Y-m-d H:i:s a')));


        $response['status'] = 'success';
        $response['ticket_id'] = $ticketId;
    } else {
        $response['status'] = 'info';
        $response['message'] = 'This ticket was just claimed by someone else.';
    }

    echo json_encode($response);
    exit;
    
?>