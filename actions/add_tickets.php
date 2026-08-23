<?php

    include('../includes/connect.php');
    include('../includes/session.php');
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');

    $getEmployee = retrieve("SELECT name FROM users WHERE id=?",array($login_id));

    if (empty($login_id)) {
        $response['message'] = 'You must be logged in to create a ticket.';
        exit;
    }

    $subject = htmlspecialchars($_POST['subject']);
    $description = htmlspecialchars($_POST['description']);
    $category = htmlspecialchars($_POST['category']);
    $priority = htmlspecialchars($_POST['priority']);
    $attachment = $_FILES['attachment']['name'];
    $attachment_location = "/uploads/tickets/".$attachment;

    $sla_rules = retrieve("SELECT cat.name AS category_name, sla.response_hours AS resp_hours, sla.resolution_hours AS reso_hours 
        FROM sla_rules AS sla INNER JOIN categories AS cat ON sla.category_id=cat.id
        WHERE sla.category_id = ? AND sla.priority = ?",array($category, $priority));

    $response_due_at   = null;
    $resolution_due_at = null;

    $get_category = retrieve("SELECT * FROM categories",array());
    $category_code = $get_category[0]['code'];

    if (!empty($sla_rules)) {
        if (isset($sla_rules[0]['response_hours'])) {
            $response_due_at = date("Y-m-d H:i:s", strtotime('+' . (int)$sla_rules[0]['response_hours'] . ' hours'));
        }
        if (isset($sla_rules[0]['resolution_hours'])) {
            $resolution_due_at = date("Y-m-d H:i:s", strtotime('+' . (int)$sla_rules[0]['resolution_hours'] . ' hours'));
        }
    }

    try {

        $ticket_number = generateTicketNumber($pdo, $category_code);

        manage("INSERT INTO tickets(ticket_number,subject,description,priority,status,created_at,
            response_due_at,resolution_due_at,created_by,assigned_to,category_id) 
            VALUES(?,?,?,?,?,?,?,?,?,?,?)",array($ticket_number,$subject,$description,$priority,'Open',date("Y-m-d H:i:s"),
            $response_due_at,$resolution_due_at,$login_id,null,$category));

        manage("INSERT INTO logs (computer_name,ip_address,page,action,details,date)
            VALUES (?,?,?,?,?,?)",
        array(gethostbyaddr($_SERVER['REMOTE_ADDR']),getLocalIP(),"Create Ticket","CREATE",         
            "<details>
                <p>Create Ticket</p>
                <p>
                    Ticket Number: <span class='font-weight-bold'>".$ticket_number."</span><br>
                    Subject: <span class='font-weight-bold'>".$subject."</span><br>
                    Category: <span class='font-weight-bold'>".$sla_rules[0]['category_name']."</span><br>
                    Priority: <span class='font-weight-bold'>".$priority."</span><br>
                    Created By: <span class='font-weight-bold'>".$getEmployee[0]['name']."</span><br>
                    Date Created: <span class='font-weight-bold'>".date("Y-m-d H:i:s")."</span><br>
                </p>
            </details>", date('Y-m-d H:i:s a')));


        $ticketId = $pdo->lastInsertId();
        if (!$ticketId) {
            $response['status'] = 'error';
            $response['message'] = 'Failed to create a ticket';
            exit;
        }

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {

            $allowedTypes = ['image/png','image/jpeg', 'application/pdf','application/msword','text/plain'];
            $maxSize = 5 * 1024 * 1024;
            $fileType = mime_content_type($_FILES['attachment']['tmp_name']);

            if (!in_array($fileType, $allowedTypes) || $_FILES['attachment']['size'] > $maxSize) {
                $response['status'] = 'warning';
                $response['message'] = 'Ticket created, but attachment was rejected (invalid type or too large).';
                $response['ticket_id'] = $ticketId;
                echo json_encode($response);
                exit;
            }

            $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $newFileName = 'ticket_' . $ticketId . '_' . uniqid() . '.' . $ext;
            $uploadDir   = __DIR__ . '/uploads/tickets/';
            $uploadPath  = $uploadDir . $newFileName;

             if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                $response['status'] = 'warning';
                $response['message'] = 'Ticket created, but attachment could not be saved (server storage issue).';
                $response['ticket_id'] = $ticketId;
                exit;
            }

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadPath)) {
                
                manage("INSERT INTO attachments (ticket_id, file_url, uploaded_at) VALUES (?,?,?)",
                    array($ticketId, 'uploads/tickets/'.$newFileName,date('Y-m-d H:i:s')));

                manage("INSERT INTO logs (computer_name,ip_address,page,action,details,date)
                    VALUES (?,?,?,?,?,?)",
                    array(
                        gethostbyaddr($_SERVER['REMOTE_ADDR']),
                        getLocalIP(),
                        "Add Attachment",
                        "ADD",
                        "<details>
                            <p>Attachment Uploaded</p>
                            <p>
                                Ticket #: ".$ticketId."<br>
                                File: ".$newFileName."<br>
                            </p>
                        </details>",
                        date('Y-m-d H:i:s a')));

            } else {
                $response['status'] = 'warning';
                $response['message'] = 'Ticket created, but the attachment failed to upload.';
                $response['ticket_id'] = $ticketId;
                exit;
            }
        }

        $response['status'] = 'success';
        $response['message'] = 'Ticket submitted successfully.';
        $response['ticket_id'] = $ticketId;

    } catch (Exception $e){   
        $response['status'] = 'error';
        $response['message'] = 'Something went wrong please try again';
    }

    echo json_encode($response);
?>