<?php

    include('../config/connect.php');
    include('../includes/session.php');
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');

    $getEmployee = retrieve("SELECT * FROM users WHERE id=?",array($login_id));

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

    $sla_rules = retrieve("SELECT cat.name AS category_name, sla.response_minutes, sla.resolution_hours 
        FROM sla_rules AS sla INNER JOIN categories AS cat ON sla.category_id=cat.id
        WHERE sla.category_id = ? AND sla.priority = ?",array($category, $priority));


    if (empty($sla_rules)) {
    // Fall back to a general rule for this priority (category_id IS NULL = default row)
        $sla_rules = retrieve(
            "SELECT response_minutes, resolution_hours FROM sla_rules 
            WHERE category_id IS NULL AND priority = ?",
            array($priority));
    }

    if (empty($sla_rules)) {
        // Should never happen if defaults are seeded, but guard anyway
        $response['message'] = 'SLA Rules  missing. Please contact Admin.';
        echo json_encode($response);
        exit;
    }

    $category_name = $sla_rules[0]['category_name'];

    $response_due_at   = null;
    $resolution_due_at = null;

    $get_category = retrieve("SELECT * FROM categories",array());
    $category_code = $get_category[0]['code'];

    $response_due_at   = date("Y-m-d H:i:s", strtotime('+60 minutes'));  // fallback: Low priority
    $resolution_due_at = date("Y-m-d H:i:s", strtotime('+120 hours'));   // fallback: Low priority

    if (!empty($sla_rules)) {
        if (isset($sla_rules[0]['response_minutes'])) {
            $response_due_at = date("Y-m-d H:i:s", strtotime('+' . (int)$sla_rules[0]['response_minutes'] . ' minutes'));
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

        manage("INSERT INTO logs (username, computer_name,ip_address,page,action,details,date)
            VALUES (?,?,?,?,?,?,?)",
        array($getEmployee[0]['username'],gethostbyaddr($_SERVER['REMOTE_ADDR']),getLocalIP(),"Create Ticket","CREATE",         
            "<details>
                <p>Create Ticket</p>
                <p>
                    Ticket Number: <span class='font-weight-bold'>".$ticket_number."</span><br>
                    Subject: <span class='font-weight-bold'>".$subject."</span><br>
                    Category: <span class='font-weight-bold'>".$category_name."</span><br>
                    Priority: <span class='font-weight-bold'>".$priority."</span><br>
                    Created By: <span class='font-weight-bold'>".$getEmployee[0]['name']."</span><br>
                    Date Created: <span class='font-weight-bold'>".date("Y-m-d H:i:s")."</span><br>
                </p>
            </details>", date('Y-m-d H:i:s')));


    $ticketId = $pdo->lastInsertId();
    if (!$ticketId) {
        $response['status'] = 'error';
        $response['message'] = 'Failed to create a ticket';
        exit;
    }

    
    $ticketId = $pdo->lastInsertId();

    if (!$ticketId) {
        $response['status'] = 'error';
        $response['message'] = 'Failed to create a ticket';
        echo json_encode($response);
        exit;
    }

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $allowedMimeTypes = [
            'image/png'       => 'png',
            'image/jpeg'      => 'jpg',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'text/plain'      => 'txt'
        ];
        
        $maxSize = 10 * 1024 * 1024; // 10 MB

        $fileTmpPath = $_FILES['attachment']['tmp_name'] ?? '';
        $fileSize    = $_FILES['attachment']['size'] ?? 0;

        if (!is_uploaded_file($fileTmpPath)) {
            $response['status']    = 'warning';
            $response['message']   = 'Ticket created, but attachment upload failed on server.';
            $response['ticket_id'] = $ticketId;
            echo json_encode($response);
            exit;
        }

        
        $rawMime  = mime_content_type($fileTmpPath);
        $fileType = trim(explode(';', $rawMime)[0]);

        // Validate size and MIME type
        if (!array_key_exists($fileType, $allowedMimeTypes) || $fileSize > $maxSize) {
            $response['status'] = 'warning';
            $response['message'] = 'Ticket created, but attachment was rejected (invalid type or too large).';
            $response['ticket_id'] = $ticketId;
            echo json_encode($response);
            exit;
        }

        // 2. Derive extension safely from validated MIME type instead of raw client filename
        $ext = $allowedMimeTypes[$fileType];
        $newFileName = 'ticket_' . $ticketId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $uploadDir =  '../uploads/tickets/';
        $uploadPath = $uploadDir . $newFileName;

        // 3. Verify target directory permissions
        if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
            $response['status'] = 'warning';
            $response['message'] = 'Ticket created, but attachment could not be saved (server storage issue).';
            $response['ticket_id'] = $ticketId;
            echo json_encode($response);
            exit;
        }

        // 4. Move temporary file to final path
        if (move_uploaded_file($fileTmpPath, $uploadPath)) {

            manage(
                "INSERT INTO attachments (ticket_id, file_url, uploaded_at) VALUES (?,?,?)",
                array($ticketId, '../uploads/tickets/' . $newFileName, date('Y-m-d H:i:s'))
            );

            manage(
                "INSERT INTO logs (username, computer_name, ip_address, page, action, details, date) VALUES (?,?,?,?,?,?,?)",
                array(
                    $getEmployee[0]['username'],
                    gethostbyaddr($_SERVER['REMOTE_ADDR']),
                    getLocalIP(),
                    "Add Attachment",
                    "ADD",
                    "<details>
                        <p>Attachment Uploaded</p>
                        <p>
                            Ticket #: " . $ticketId . "<br>
                            File: " . $newFileName . "<br>
                        </p>
                    </details>",
                    date('Y-m-d H:i:s')
                )
            );

        } else {
            $response['status']    = 'warning';
            $response['message']   = 'Ticket created, but the attachment failed to upload.';
            $response['ticket_id'] = $ticketId;
            echo json_encode($response);
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