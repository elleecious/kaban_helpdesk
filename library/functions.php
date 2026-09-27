<?php

    $config_shorcuts = [
        [
            'title' => 'All Tickets',
            'icon'  => 'fa-tags',
            'id'    => 'all_tickets',
        ],
        [
            'title' => 'Manage Users',
            'icon'  => 'fa-users',
            'id'    => 'manage_users',
        ],
        [
            'title' => 'Manage Category SLA',
            'icon'  => 'fa-tags',
            'id'    => 'manage_cat_sla',
        ],
        [
            'title' => 'Full Reports',
            'icon'  => 'fa-line-chart',
            'id'    => 'full_reports',
        ],
        [
            'title' => 'Knowledge Base',
            'icon'  => 'fa-book-open',
            'id'    => 'knowledge_base',
        ],
        [
            'title' => 'Change Request',
            'icon'  => 'fa-refresh',
            'id'    => 'change_request',
        ],
        [
            'title' => 'User Control',
            'icon'  => 'fa-user-cog',
            'id'    => 'user_control',
        ],
        [
            'title' => 'Logs',
            'icon'  => 'fa-history',
            'id'    => 'logs',
        ],
    ];

    // Generates a unique ticket number based on the category code and a random alphanumeric string.
    function generateTicketNumber($pdo, $category_code) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
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

    // Generates a unique CRF number based on the year and a random alphanumeric string.
    function generateCrfNumber($year) {
        do {
            $randomPart = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 7));
            $crfNumber = "CR-{$year}-{$randomPart}";

            $existing = retrieve("SELECT crf_number FROM change_requests WHERE crf_number = ?", array($crfNumber));
        } while (!empty($existing));

        return $crfNumber;
    }

    // Returns an array of required approval levels based on the change type.
    function getRequiredApprovals($changeType) {
        switch ($changeType) {
            case 'Standard':
                return ['IT Supervisor'];
            case 'Normal':
                return ['IT Manager'];
            case 'Major':
                return ['IT Manager', 'General Manager'];   // both required, in sequence
            case 'Emergency':
                return ['IT Manager', 'IT Supervisor', 'IT Support Specialist'];   // OR Authorized Personnel — handled as an "either" case below
            default:
                return [];
        }
    }
    
    // Determines if a given change request has been fully approved based on its type and recorded approvals.
    function isChangeRequestApproved($changeRequestId, $changeType) {
        $approvals = retrieve(
            "SELECT approval_level, decision FROM change_approvals WHERE change_request_id = ?",
            array($changeRequestId)
        );

        $requiredApprovals = getRequiredApprovals($changeType);
        $approvedLevels = array_column(array_filter($approvals, fn($a) => $a['decision'] === 'Approved'), 'approval_level');

        if ($changeType === 'Emergency') {
            // For Emergency, either IT Manager OR BOTH IT Support and IT Supervisor must approve
            $itManagerApproved = in_array('IT Manager', $approvedLevels);
            $supportApproved = in_array('IT Support Specialist', $approvedLevels);
            $supervisorApproved = in_array('IT Supervisor', $approvedLevels);

            return $itManagerApproved || ($supportApproved && $supervisorApproved);
        }

        // For other types, all required approvals must be present
        return empty(array_diff($requiredApprovals, $approvedLevels));
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

        if (in_array('IT Manager', $approvedLevels) && in_array('General Manager', $approvedLevels)) {
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


    // Maps a change type + current approval state to the approval_level this action represents.
// Returns null if the given role isn't authorized to act at this stage.
    function getApprovalLevelForAction($changeType, $role, $changeRequestId) {

        if ($changeType === 'Standard') {
            return $role === 'IT Supervisor' ? 'IT Supervisor' : null;
        }

        if ($changeType === 'Normal') {
            return $role === 'IT Manager' ? 'IT Manager' : null;
        }

        if ($changeType === 'Major') {
            // Sequential: IT Manager must approve before GM can act at all
            $itManagerDecision = retrieve(
                "SELECT decision FROM change_approvals WHERE change_request_id = ? AND approval_level = 'IT Manager'",
                array($changeRequestId)
            );

            if ($role === 'IT Manager') {
                // Only allowed if no IT Manager decision exists yet
                return empty($itManagerDecision) ? 'IT Manager' : null;
            }

            if ($role === 'General Manager' || $role === 'Management') {
                // Only allowed once IT Manager has approved
                $approved = !empty($itManagerDecision) && $itManagerDecision[0]['decision'] === 'Approved';
                return $approved ? 'General Manager' : null;
            }

            return null;
        }

        if ($changeType === 'Emergency') {
            if ($role === 'IT Manager') return 'IT Manager';
            if ($role === 'IT Support Specialist') return 'IT Support';
            if ($role === 'IT Supervisor') return 'IT Supervisor';
            return null;
        }

        return null;
    }

    // Recalculates overall change_requests.status from the change_approvals rows recorded so far.
    function recalculateChangeStatus($changeRequestId, $changeType) {

        $approvals = retrieve(
            "SELECT approval_level, decision FROM change_approvals WHERE change_request_id = ?",
            array($changeRequestId)
        );

        $decisionByLevel = [];
        foreach ($approvals as $a) {
            $decisionByLevel[$a['approval_level']] = $a['decision'];
        }

        if ($changeType === 'Standard' || $changeType === 'Normal') {
            $level = $changeType === 'Standard' ? 'IT Supervisor' : 'IT Manager';
            if (($decisionByLevel[$level] ?? null) === 'Approved') return 'Approved';
            if (($decisionByLevel[$level] ?? null) === 'Rejected') return 'Rejected';
            return 'Under Review';
        }

        if ($changeType === 'Major') {
            if (($decisionByLevel['IT Manager'] ?? null) === 'Rejected') return 'Rejected';
            if (($decisionByLevel['General Manager'] ?? null) === 'Rejected') return 'Rejected';
            if (($decisionByLevel['IT Manager'] ?? null) === 'Approved'
                && ($decisionByLevel['General Manager'] ?? null) === 'Approved') {
                return 'Approved';
            }
            return 'Under Review';
        }

        if ($changeType === 'Emergency') {
            // Path A: IT Manager alone
            if (($decisionByLevel['IT Manager'] ?? null) === 'Approved') return 'Approved';

            // Path B: BOTH IT Support and IT Supervisor
            if (($decisionByLevel['IT Support Specialist'] ?? null) === 'Approved'
                && ($decisionByLevel['IT Supervisor'] ?? null) === 'Approved') {
                return 'Approved';
            }

            // Reject only once every path that was actually attempted has failed
            $itManagerRejected = ($decisionByLevel['IT Manager'] ?? null) === 'Rejected';
            $supportRejected = ($decisionByLevel['IT Support Specialist'] ?? null) === 'Rejected';
            $supervisorRejected = ($decisionByLevel['IT Supervisor'] ?? null) === 'Rejected';

            if ($itManagerRejected && ($supportRejected || $supervisorRejected)) return 'Rejected';

            return 'Under Review';
        }

        return 'Under Review';
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

    // Retrieves the computer name of the client machine based on the IP address.
    function getComputerName($ip) {
        $hostname = gethostbyaddr($ip);
        return $hostname ?: "Unknown";
    }
?>
