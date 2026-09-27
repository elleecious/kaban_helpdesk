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

    if (empty($ticket_id)) {
        $response['message'] = 'Missing ticket ID.';
        echo json_encode($response);
        exit;
    }

    // Confirm the ticket actually exists before touching it
    $existingTicket = retrieve("SELECT * FROM tickets WHERE id = ?", array($ticket_id));
    if (empty($existingTicket)) {
        $response['message'] = 'Ticket not found.';
        echo json_encode($response);
        exit;
    }

    if (empty($subject) || empty($description) || empty($category) || empty($priority)) {
        $response['message'] = 'Subject, description, category, and priority are required.';
        echo json_encode($response);
        exit;
    }

    $get_category = retrieve("SELECT name, code FROM categories WHERE id=?", array($category));
    if (empty($get_category)) {
        $response['message'] = 'Invalid category.';
        echo json_encode($response);
        exit;
    }
    $category_name = $get_category[0]['name'];

    try {

        $updated = manage(
            "UPDATE tickets SET subject = ?, description = ?, category_id = ?, priority = ? WHERE id = ?",
            array($subject, $description, $category, $priority, $ticket_id)
        );

        if ($updated === false) {
            $response['message'] = 'Failed to update ticket.';
            echo json_encode($response);
            exit;
        }

        manage("INSERT INTO logs (username, computer_name, ip_address, page, action, details, date)
            VALUES (?,?,?,?,?,?,?)",
        array($getEmployee[0]['username'], gethostbyaddr($_SERVER['REMOTE_ADDR']), getLocalIP(), "Edit Ticket", "UPDATE",
            "<details>
                <p>Update Ticket</p>
                <p>
                    Ticket ID: <span class='font-weight-bold'>".$ticket_id."</span><br>
                    Subject: <span class='font-weight-bold'>".$subject."</span><br>
                    Category: <span class='font-weight-bold'>".$category_name."</span><br>
                    Priority: <span class='font-weight-bold'>".$priority."</span><br>
                    Updated By: <span class='font-weight-bold'>".$getEmployee[0]['name']."</span><br>
                    Date Updated: <span class='font-weight-bold'>".date("Y-m-d H:i:s")."</span><br>
                </p>
            </details>", date('Y-m-d H:i:s')));

        // Optional new attachment
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
            $originalFileName = basename($_FILES['attachment']['name']);

            if (!is_uploaded_file($fileTmpPath)) {
                $response['status']  = 'warning';
                $response['message'] = 'Ticket updated, but attachment upload failed on server.';
                echo json_encode($response);
                exit;
            }

            $rawMime  = mime_content_type($fileTmpPath);
            $fileType = trim(explode(';', $rawMime)[0]);

            if (!array_key_exists($fileType, $allowedMimeTypes) || $fileSize > $maxSize) {
                $response['status']  = 'warning';
                $response['message'] = 'Ticket updated, but attachment was rejected (invalid type or too large).';
                echo json_encode($response);
                exit;
            }

            $ext = $allowedMimeTypes[$fileType];
            $newFileName = 'ticket_' . $ticket_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $uploadDir = '../uploads/tickets/';
            $uploadPath = $uploadDir . $newFileName;

            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                $response['status']  = 'warning';
                $response['message'] = 'Ticket updated, but attachment could not be saved (server storage issue).';
                echo json_encode($response);
                exit;
            }

            if (move_uploaded_file($fileTmpPath, $uploadPath)) {

                $attachInserted = manage(
                    "INSERT INTO attachments (ticket_id, file_name, file_url, uploaded_at) VALUES (?,?,?,?)",
                    array($ticket_id, $originalFileName, '/uploads/tickets/' . $newFileName, date('Y-m-d H:i:s'))
                );

                if ($attachInserted === false) {
                    $response['status']  = 'warning';
                    $response['message'] = 'Ticket updated, but attachment record failed to save.';
                    echo json_encode($response);
                    exit;
                }

                manage(
                    "INSERT INTO logs (username, computer_name, ip_address, page, action, details, date) VALUES (?,?,?,?,?,?,?)",
                    array(
                        $getEmployee[0]['username'],
                        gethostbyaddr($_SERVER['REMOTE_ADDR']),
                        getLocalIP(),
                        "Add Attachment",
                        "ADD",
                        "<details>
                            <p>Attachment Uploaded on Edit</p>
                            <p>
                                Ticket #: " . $ticket_id . "<br>
                                File: " . $originalFileName . "<br>
                            </p>
                        </details>",
                        date('Y-m-d H:i:s')
                    )
                );

            } else {
                $response['status']  = 'warning';
                $response['message'] = 'Ticket updated, but the attachment failed to upload.';
                echo json_encode($response);
                exit;
            }
        }

        $response['status'] = 'success';
        $response['message'] = 'Ticket updated successfully.';
        $response['ticket_id'] = $ticket_id;

    } catch (Exception $e) {
        error_log('Ticket update error: ' . $e->getMessage());
        $response['status'] = 'error';
        $response['message'] = 'Something went wrong please try again';
    }

    echo json_encode($response);
?>