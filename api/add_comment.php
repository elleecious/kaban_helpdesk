<?php
include('../config/connect.php');
include('../includes/session.php');
include('../library/functions.php');
include("../library/notify.php");

header('Content-Type: application/json');
$response = array('status' => 'error', 'message' => 'Invalid request');

$ticketId = $_POST['ticket_id'] ?? null;
$comment = trim($_POST['comment'] ?? '');
$agentId = $_SESSION['login_id'];

if (!$ticketId) {
    $response['status']  = 'error';
    $response['message'] = 'Ticket ID required.';
    echo json_encode($response);
    exit;
}


$it_support = retrieve("SELECT username, name FROM users WHERE id = ?", array($agentId));
$it_supportUser = $it_support['0']['username'] ?? 'Unknown';
$it_supportName = $it_support[0]['name'] ?? 'Unknown';

$add_comments_sql = manage("INSERT INTO comments (ticket_id, author_id, body, created_at) VALUES (?, ?,?,?)", array($ticketId, $agentId, $comment, date("Y-m-d H:i:s")));

$getTicket = retrieve("SELECT * FROM tickets WHERE id=?",array($ticketId));
$ticket = $getTicket[0];

if ($add_comments_sql) {

    $ticketLink = "ticket_detail.php?id=$ticketId";

    if ($agentId == $ticket['created_by']) {
        // Employee (ticket creator) commented and notify the assigned IT agent
        if (!empty($ticket['assigned_to'])) {
            create_notification(
                $ticket['assigned_to'],
                'add_comment',
                "{$it_supportName} replied to your comment: \"" . mb_strimwidth($comment, 0, 100, '...') . "\"",
                $ticketLink,
                $ticketId
            );
        }
    } else {
        // IT agent commented and notify the employee who created the ticket
        create_notification(
            $ticket['created_by'],
            'add_comment',
            "{$it_supportName} replied to your ticket <br> \"" . mb_strimwidth($comment, 0, 100, '...') . "\"",
            $ticketLink,
            $ticketId
        );
    }

    manage("INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
            VALUES (?,?,?,?,?,?,?)",
        array($it_supportUser,gethostbyaddr($_SERVER['REMOTE_ADDR']),getLocalIP(),"TICKET DETAIL","COMMENT",         
            "<details>
                <p>Comment on ".$getTicket[0]['ticket_number']."</p>
                <p>
                    Commented By ".$it_supportName."<br>
                    Comment: ".$comment."<br>
                </p>
            </details>", date('Y-m-d H:i:s a')));

    $response['status'] = 'success';
} else {
    $response['message'] = 'Failed to send a comment. Please try again.';
}

echo json_encode($response);

?>