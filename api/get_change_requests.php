<?php
include('../config/connect.php');
include('../includes/session.php');
include("../library/functions.php");

header('Content-Type: application/json');

try {
    $select = "SELECT cr.id, cr.crf_number, cr.change_title, cr.department, cr.change_type,
                      u.name, cr.status, cr.created_at
               FROM change_requests AS cr
               INNER JOIN users AS u ON cr.requestor_id = u.id";
    if ($role === "IT Support Specialist") {
         $sql = "$select WHERE cr.change_type = ? ORDER BY cr.created_at DESC";
        $params = array('Emergency');
    } elseif ($role === "IT Supervisor") {
        $sql    = "$select WHERE cr.change_type IN (?, ?) ORDER BY cr.created_at DESC";
        $params = array('Standard', 'Emergency');

    } elseif ($role === "IT Manager") {
        $sql    = "$select WHERE cr.change_type IN (?, ?, ?) ORDER BY cr.created_at DESC";
        $params = array('Normal', 'Major', 'Emergency');

    } elseif ($role === "General Manager") {
        $sql = "$select
                WHERE cr.change_type = 'Major'
                  AND EXISTS (
                        SELECT 1 FROM change_approvals ca
                        WHERE ca.change_request_id = cr.id
                          AND ca.approval_level = 'IT Manager'
                          AND ca.decision = 'Approved'
                  )
                  AND NOT EXISTS (
                        SELECT 1 FROM change_approvals ca2
                        WHERE ca2.change_request_id = cr.id
                          AND ca2.approval_level = 'General Manager'
                  )
                ORDER BY cr.created_at DESC";
        $params = array();

    }
    elseif ($role === "Administrator") {
        $sql    = "$select WHERE cr.change_type IN (?, ?, ?, ?) ORDER BY cr.created_at DESC";
        $params = array('Normal', 'Standard', 'Major', 'Emergency');
    } else {
        echo json_encode(['status' => 'success', 'data' => []]);
        exit;
    }

    $rows = retrieve($sql, $params);

    $requests = [];
    foreach ($rows as $row) {
        $requests[] = [
            'id'            => $row['id'],
            'crf_number'    => $row['crf_number'],
            'change_title'  => $row['change_title'],
            'department'    => $row['department'],
            'change_type'   => $row['change_type'],
            'status'        => $row['status'],
            'created_by'    => $row['name'],
            'created_at'    => $row['created_at'],
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $requests]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>