<?php

    //counting
    $get_staff = retrieve("SELECT * FROM users",array());
    $count_staff = count($get_staff);


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
