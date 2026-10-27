<?php

    include('../config/connect.php');
    include('../includes/session.php');
    include('../library/functions.php');

    header('Content-Type: application/json');
    $response = array('status' => 'error', 'message' => 'Invalid request');

    if (empty($login_id)) {
        $response['message'] = 'You must be logged in to update a ticket.';
        echo json_encode($response);
        exit;
    }

    $getEmployee = retrieve("SELECT * FROM users WHERE id=?", array($login_id));

    $ticket_id   = $_POST['ticket_id'] ?? null;
    $subject     = htmlspecialchars($_POST['edit_subject'] ?? '');
    $description = htmlspecialchars($_POST['edit_description'] ?? '');
    $category    = htmlspecialchars($_POST['edit_category'] ?? '');
    $priority    = htmlspecialchars($_POST['edit_priority'] ?? '');

    if (!$ticket_id || !ctype_digit((string)$ticket_id)) {
        $response['message'] = 'Invalid ticket reference.';
        echo json_encode($response);
        exit;
    }

    if ($subject === '' || $description === '' || $category === '' || $priority === '') {
        $response['message'] = 'Please fill out all required fields.';
        echo json_encode($response);
        exit;
    }

    // Confirm ticket exists (IDOR guard)
    $existingTicket = retrieve("SELECT * FROM tickets WHERE id = ?", array($ticket_id));
    if (empty($existingTicket)) {
        $response['message'] = 'Ticket not found.';
        echo json_encode($response);
        exit;
    }
    $existingTicket = $existingTicket[0];

    // --- Re-derive SLA due dates in case category/priority changed ---
    $sla_rules = retrieve("SELECT cat.name AS category_name, sla.response_minutes, sla.resolution_hours 
        FROM sla_rules AS sla INNER JOIN categories AS cat ON sla.category_id=cat.id
        WHERE sla.category_id = ? AND sla.priority = ?", array($category, $priority));

    if (empty($sla_rules)) {
        // Fall back to a general rule for this priority (category_id IS NULL = default row)
        $sla_rules = retrieve(
            "SELECT response_minutes, resolution_hours FROM sla_rules 
            WHERE category_id IS NULL AND priority = ?",
            array($priority));
    }

    if (empty($sla_rules)) {
        // Should never happen if defaults are seeded, but guard anyway
        $response['message'] = 'SLA Rules missing. Please contact Admin.';
        echo json_encode($response);
        exit;
    }

    $category_name = $sla_rules[0]['category_name'] ?? null;

    $response_due_at   = date("Y-m-d H:i:s", strtotime('+60 minutes'));   // fallback: Low priority
    $resolution_due_at = date("Y-m-d H:i:s", strtotime('+120 hours'));    // fallback: Low priority

    if (isset($sla_rules[0]['response_minutes'])) {
        $response_due_at = date("Y-m-d H:i:s", strtotime('+' . (int)$sla_rules[0]['response_minutes'] . ' minutes'));
    }
    if (isset($sla_rules[0]['resolution_hours'])) {
        $resolution_due_at = date("Y-m-d H:i:s", strtotime('+' . (int)$sla_rules[0]['resolution_hours'] . ' hours'));
    }

    try {

        manage("UPDATE tickets 
            SET subject = ?, description = ?, priority = ?, category_id = ?, 
                response_due_at = ?, resolution_due_at = ?, updated_at = ? 
            WHERE id = ?",
            array($subject, $description, $priority, $category,
                $response_due_at, $resolution_due_at, date("Y-m-d H:i:s"), $ticket_id));

        manage("INSERT INTO logs (username, computer_name, ip_address, page, action, details, date)
            VALUES (?,?,?,?,?,?,?)",
        array($getEmployee[0]['username'], gethostbyaddr($_SERVER['REMOTE_ADDR']), getLocalIP(), "Edit Ticket", "UPDATE",
            "<details>
                <p>Edit Ticket</p>
                <p>
                    Ticket Number: <span class='font-weight-bold'>".$existingTicket['ticket_number']."</span><br>
                    Subject: <span class='font-weight-bold'>".$subject."</span><br>
                    Category: <span class='font-weight-bold'>".$category_name."</span><br>
                    Priority: <span class='font-weight-bold'>".$priority."</span><br>
                    Updated By: <span class='font-weight-bold'>".$getEmployee[0]['name']."</span><br>
                    Date Updated: <span class='font-weight-bold'>".date("Y-m-d H:i:s")."</span><br>
                </p>
            </details>", date('Y-m-d H:i:s')));

        // --- Optional new attachment ---
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $allowedMimeTypes = [
                'image/png'          => 'png',
                'image/jpeg'         => 'jpg',
                'application/pdf'    => 'pdf',
                'application/msword' => 'doc',
                'text/plain'         => 'txt'
            ];

            $maxSize = 10 * 1024 * 1024; // 10 MB

            $originalFileName = basename($_FILES['attachment']['name']);
            $fileTmpPath = $_FILES['attachment']['tmp_name'] ?? '';
            $fileSize    = $_FILES['attachment']['size'] ?? 0;

            if (!is_uploaded_file($fileTmpPath)) {
                $response['status']    = 'warning';
                $response['message']   = 'Ticket updated, but attachment upload failed on server.';
                $response['ticket_id'] = $ticket_id;
                echo json_encode($response);
                exit;
            }

            $rawMime  = mime_content_type($fileTmpPath);
            $fileType = trim(explode(';', $rawMime)[0]);

            // Validate size and MIME type
            if (!array_key_exists($fileType, $allowedMimeTypes) || $fileSize > $maxSize) {
                $response['status']    = 'warning';
                $response['message']   = 'Ticket updated, but attachment was rejected (invalid type or too large).';
                $response['ticket_id'] = $ticket_id;
                echo json_encode($response);
                exit;
            }

            // Derive extension safely from validated MIME type instead of raw client filename
            $ext = $allowedMimeTypes[$fileType];
            $newFileName = 'ticket_' . $ticket_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $uploadDir  = '../uploads/tickets/';
            $uploadPath = $uploadDir . $newFileName;

            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                $response['status']    = 'warning';
                $response['message']   = 'Ticket updated, but attachment could not be saved (server storage issue).';
                $response['ticket_id'] = $ticket_id;
                echo json_encode($response);
                exit;
            }

            if (move_uploaded_file($fileTmpPath, $uploadPath)) {

                manage("INSERT INTO attachments (ticket_id, file_name, file_url, uploaded_at) VALUES (?,?,?,?)",
                        array($ticket_id, $originalFileName, '/uploads/tickets/' . $newFileName, date('Y-m-d H:i:s'))
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
                            <p>Attachment Uploaded (on Edit)</p>
                            <p>
                                Ticket #: " . $ticket_id . "<br>
                                File: " . $newFileName . "<br>
                            </p>
                        </details>",
                        date('Y-m-d H:i:s')
                    )
                );

            } else {
                $response['status']    = 'warning';
                $response['message']   = 'Ticket updated, but the attachment failed to upload.';
                $response['ticket_id'] = $ticket_id;
                echo json_encode($response);
                exit;
            }
        }

        $response['status']    = 'success';
        $response['message']   = 'Ticket updated successfully.';
        $response['ticket_id'] = $ticket_id;

    } catch (Exception $e) {
        $response['status']  = 'error';
        $response['message'] = 'Something went wrong, please try again';
    }

    echo json_encode($response);
?>