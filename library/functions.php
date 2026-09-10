<?php

    function generateTicketNumber($pdo, $category_code) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O, 1/I to avoid confusion
        do{
            $random = '';
            for ($i = 0; $i < 7; $i++) {
                $random .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $candidate = $category_code . '-' . $random;

            $exists = retrieve("SELECT id FROM tickets WHERE ticket_number = ?", array($candidate));
        } while (!empty($exists));

        return $candidate;
    }

    function generateCrfNumber($year) {
        do {
            $randomPart = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 7));
            $crfNumber = "CR-{$year}-{$randomPart}";

            $existing = retrieve("SELECT crf_number FROM change_requests WHERE crf_number = ?", array($crfNumber));
        } while (!empty($existing));

        return $crfNumber;
    }

    function getRequiredApprovals($changeType) {
        switch ($changeType) {
            case 'Standard':
                return ['IT Supervisor'];
            case 'Normal':
                return ['IT Manager'];
            case 'Major':
                return ['IT Manager', 'GM/Management'];   // both required, in sequence
            case 'Emergency':
                return ['IT Manager', 'IT Supervisor', 'IT Support Specialist'];   // OR Authorized Personnel — handled as an "either" case below
            default:
                return [];
        }
    }

    function getMajorChangeStatus($changeRequestId) {
        $approvals = retrieve(
            "SELECT approval_level, decision FROM change_approvals WHERE change_request_id = ?",
            array($changeRequestId)
        );

        foreach ($approvals as $a) {
            if ($a['decision'] === 'Rejected') {
                return 'Rejected'; // any single rejection kills it — no vote counting needed
            }
        }

        $approvedLevels = array_column(array_filter($approvals, fn($a) => $a['decision'] === 'Approved'), 'approval_level');

        if (in_array('IT Manager', $approvedLevels) && in_array('GM/Management', $approvedLevels)) {
            return 'Approved'; // both required gates cleared
        }

        return 'Under Review'; // still waiting on one or both
    }

    function isEmergencyApproved($changeRequestId) {
        // Path A: IT Manager alone approved it
        $itManagerApproval = retrieve(
            "SELECT id FROM change_approvals WHERE change_request_id = ? AND approval_level = 'IT Manager' AND decision = 'Approved'",
            array($changeRequestId)
        );
        if (!empty($itManagerApproval)) return true;

        // Path B: BOTH IT Support and IT Supervisor approved it
        $authorizedApprovals = retrieve(
            "SELECT approval_level FROM change_approvals WHERE change_request_id = ? AND approval_level IN ('IT Support','IT Supervisor') AND decision = 'Approved'",
            array($changeRequestId)
        );
        $approvedLevels = array_column($authorizedApprovals, 'approval_level');
        if (in_array('IT Support', $approvedLevels) && in_array('IT Supervisor', $approvedLevels)) {
            return true;
        }

        return false;
    }

    function get_priority_code($priority) {
        $map = array(
            'Critical' => 'P1',
            'High'     => 'P2',
            'Medium'   => 'P3',
            'Low'      => 'P4'
        );
        return $map[$priority] ?? '';
    }

    // Converts total minutes into a short "Xd Yh Zm" / "Xh Ym" / "Xm" string
    function format_waiting_time($minutes) {
        if ($minutes < 60) {
            return $minutes . 'm';
        }

        if ($minutes < 1440) { // less than 24 hours
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            return $hours . 'h ' . $remainingMinutes . 'm';
        }

        // 1 day or more
        $days = floor($minutes / 1440);
        $remainingMinutes = $minutes % 1440;
        $hours = floor($remainingMinutes / 60);
        $mins = $remainingMinutes % 60;

        return $days . 'd ' . $hours . 'h ' . $mins . 'm';
    }

    /**
 * Converts total minutes into a friendly "time ago" string
 */
    function time_ago($minutes) {
        if ($minutes < 1) {
            return 'just now';
        }
        if ($minutes < 60) {
            return $minutes . ' min' . ($minutes != 1 ? 's' : '') . ' ago';
        }
        if ($minutes < 1440) { // less than 24 hours
            $hours = floor($minutes / 60);
            return $hours . ' hr' . ($hours != 1 ? 's' : '') . ' ago';
        }
        if ($minutes < 43200) { // less than 30 days
            $days = floor($minutes / 1440);
            return $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
        }
        // fallback for anything older — just show the actual date
        return 'a while ago';
    }

    function getLocalIP(){
        $hostname = gethostname();
        $ip = gethostbyname($hostname);

        if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
            return $ip;
        } else {
            return "Unable to determine local IP Address";
        }
    }
?>
