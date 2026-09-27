<?php

    // User Total Tickets
    $count_user_tickets = retrieve("SELECT COUNT(*) AS cnt FROM tickets WHERE created_by=?", array($login_id))[0]['cnt'];

    // User Total Open Tickets
    $count_user_open_tickets = retrieve("SELECT COUNT(*) AS cnt FROM tickets WHERE status = 'Open' AND created_by=?", array($login_id))[0]['cnt'];

    // User Resolved Tickets
    $count_user_resolved_tickets = retrieve("SELECT COUNT(*) AS cnt FROM tickets WHERE status = 'Resolved' AND created_by=?", array($login_id))[0]['cnt'];

    // User Awaiting Response
    $count_user_awaiting = retrieve("SELECT COUNT(*) AS cnt FROM tickets WHERE status = 'In Progress' AND created_by=?", array($login_id))[0]['cnt'];


    //Count staff
    $count_staff = retrieve("SELECT COUNT(*) AS cnt FROM users",array())[0]['cnt'];

    $count_tickets = retrieve("SELECT COUNT(*) AS cnt FROM tickets",array())[0]['cnt'];

    $count_it_agents = retrieve("SELECT COUNT(*) AS cnt FROM users WHERE role=?",array("IT Support Specialist"))[0]['cnt'];


    //IT Support Counting

    //Count Open
    $count_open_tickets = retrieve("SELECT COUNT(*) AS cnt FROM tickets WHERE status = 'Open'", array())[0]['cnt'];

    //Assigned to me
    $count_tickets_assigned_to_me = retrieve("SELECT COUNT(*) AS cnt FROM tickets WHERE assigned_to = ?",array($login_id))[0]['cnt'];

    //In Progress
    $count_it_in_progress = retrieve("SELECT COUNT(*) AS cnt FROM tickets WHERE status = 'In Progress' AND assigned_to=?", array($login_id))[0]['cnt'];

    // Overdue (SLA)
    $count_it_overdue = retrieve("SELECT COUNT(*) AS cnt FROM tickets WHERE 
        assigned_to = ? AND status NOT IN ('Resolved', 'Closed') AND resolution_due_at < NOW()",
        array($login_id))[0]['cnt'];

    //Resolved Today
    $count_it_resolve_tickets = retrieve("SELECT COUNT(*) AS cnt FROM tickets WHERE 
        status = 'Resolved' AND assigned_to = ? AND DATE(resolved_at) = CURDATE()",
        array($login_id))[0]['cnt'];

    //Average Resolution Time (Last 30 Days)
    $avg_resolution_time = retrieve("SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) AS avg_resolution_minutes
        FROM tickets
        WHERE status IN ('Resolved', 'Closed') AND assigned_to = ? AND resolved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        array($login_id))[0]['avg_resolution_minutes'];
    
    // Calculate compliance rate (percentage of tickets resolved within SLA in the last 30 days)
    $total_resolved_30d = retrieve("SELECT COUNT(*) AS cnt FROM tickets WHERE
        status IN ('Resolved', 'Closed') AND assigned_to = ? AND resolved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        array($login_id))[0]['cnt'];
    $on_time_30d = retrieve("SELECT COUNT(*) AS cnt FROM tickets WHERE
        status IN ('Resolved', 'Closed') AND assigned_to = ? AND resolved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND resolved_at <= resolution_due_at",
        array($login_id))[0]['cnt'];
    
    // Calculate compliance rate
    $compliance_rate = $total_resolved_30d > 0 ? round(($on_time_30d / $total_resolved_30d) * 100) : 100;

    // Format average resolution time for display
    $avg_hours_display = $avg_resolution_time ? round($avg_resolution_time / 60, 1) . 'h' : '—';

    // Count Manage Requests for Manager
    $count_manage_requests_manager = retrieve("SELECT COUNT(*) AS cnt FROM change_requests WHERE status = 'Submitted' AND change_type = 'Normal'", array())[0]['cnt'];
    
    
    $dashboardStats = retrieve(
        "SELECT 
            SUM(CASE WHEN status NOT IN ('Resolved','Closed') THEN 1 ELSE 0 END) AS open_tickets,
            SUM(CASE WHEN resolution_due_at < NOW() AND status NOT IN ('Resolved','Closed') THEN 1 ELSE 0 END) AS overdue_count,
            SUM(CASE WHEN status IN ('Resolved','Closed') AND resolved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS total_resolved_30d,
            SUM(CASE WHEN status IN ('Resolved','Closed') AND resolved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND resolved_at <= resolution_due_at THEN 1 ELSE 0 END) AS on_time_30d,
            AVG(CASE WHEN status IN ('Resolved','Closed') AND resolved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                    THEN TIMESTAMPDIFF(MINUTE, created_at, resolved_at) END) AS avg_resolution_minutes
        FROM tickets",
        array());
    $stats = $dashboardStats[0];

    $complianceRate = $stats['total_resolved_30d'] > 0
        ? round(($stats['on_time_30d'] / $stats['total_resolved_30d']) * 100): 100;

    $avgHoursDisplay = $stats['avg_resolution_minutes'] ? round($stats['avg_resolution_minutes'] / 60, 1) . 'h' : '—';

?>