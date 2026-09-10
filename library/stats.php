<?php

    //counting
    $get_staff = retrieve("SELECT * FROM users",array());
    $count_staff = count($get_staff);

    $get_total_tickets = retrieve("SELECT * FROM tickets",array());
    $count_tickets = count($get_total_tickets);

    $get_it_agents = retrieve("SELECT * FROM users WHERE role=?",array("IT Support Specialist"));
    $count_it_agents = count($get_it_agents);
    
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