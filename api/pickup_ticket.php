<?php
    include('../config/connect.php');
    include('../includes/session.php');
    include('../library/functions.php');
    include("../library/notify.php");

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');

    $ticketId = $_POST['ticket_id'] ?? null;

    if (!$ticketId) {
        $response['message'] = 'No ticket specified.';
        echo json_encode($response);
        exit;
    }

    global $pdo;


    $getTickets = retrieve("SELECT ticket_number, subject, priority, created_by, status FROM tickets WHERE id = ? AND assigned_to IS NULL",array($ticketId));
    $ticket = $getTickets[0];

    $ticket_number = $ticket['ticket_number'];

    $agent = retrieve("SELECT name, username FROM users WHERE id=?",array($login_id));
    $agentName = $agent[0]['name'] ?? 'Unknown';
    $agentUser = $agent[0]['username'] ?? 'Unknown';

    $leads = retrieve("SELECT id FROM users WHERE role IN ('IT Manager', 'IT Supervisor')",array());
    $leadsIds = array_column($leads,'id');

    if (!$ticket) {
        $response['status'] = 'already_claimed';
        $response['message'] = 'This ticket was just claimed by someone else.';
        echo json_encode($response);
        exit;
    }

    $rowsAffected = manage("UPDATE tickets SET assigned_to = ?, status = 'In Progress' 
        WHERE id = ? AND assigned_to IS NULL",array($login_id, $ticketId));

    if ($rowsAffected > 0) {

        $link = "ticket_detail.php?id=$ticketId";

         create_notification_bulk(
            $leadsIds,
            'resolved_ticket',
            "Ticket #{$ticket_number} was picked up by <br> {$agentName}",
            "ticket_detail.php?id={$ticketId}",
            $ticketId
        );

        create_notification(
            $ticket['created_by'],
            'ticket_assigned',
            "Your ticket #{$ticket_number} has been picked up and is now In Progress",
            $link,
            $ticketId
        );

        manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
            VALUES (?,?,?,?,?,?,?)",
            array(
                $agentUser, 
                gethostbyaddr($_SERVER['REMOTE_ADDR']),
                getLocalIP(),
                "DASHBOARD",
                "PICK UP TICKET",
                "<details>
                    <p>Pick Up Unassigned Ticket</p>
                    <p>
                        Ticket #: ".$ticket['ticket_number']."<br>
                        Subject: ".$ticket['subject']."<br>
                        Priority: ".$ticket['priority']."<br>
                        Status: ".$ticket['status']." => <span class='font-weight-bold'>In Progress</span><br>
                        Assigned To: <span class='font-weight-bold'>".$agentName."</span><br>
                    </p>
                </details>",
                date('Y-m-d H:i:s a')));


        $response['status'] = 'success';
        $response['ticket_number'] = $ticket_number;
    } else {
        $response['status'] = 'info';
        $response['message'] = 'This ticket was just claimed by someone else.';
    }

    echo json_encode($response);
    exit;
    
?>