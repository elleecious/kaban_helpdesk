<?php
// Auto-close Resolved tickets that the requester never confirmed or reopened.
// Run from cron / Task Scheduler (command line only), e.g. once an hour.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

include(__DIR__ . '/../config/connect.php');
include(__DIR__ . '/../library/functions.php');
include(__DIR__ . '/../library/notify.php');

$autoCloseDays = 2;   // change to 1 or 2 if you want it faster

// Resolved long enough ago, not deleted
$tickets = retrieve(
    "SELECT id, ticket_number, created_by
     FROM tickets
     WHERE status = 'Resolved'
       AND deleted_at IS NULL
       AND resolved_at IS NOT NULL
       AND resolved_at <= DATE_SUB(NOW(), INTERVAL {$autoCloseDays} DAY)",
    array()
);

$closed = 0;

foreach ($tickets as $t) {

    // Re-check the status in the UPDATE itself, so a ticket the requester
    // reopened a second ago is not closed by mistake.
    $rowsAffected = manage(
        "UPDATE tickets
         SET status = 'Closed', closed_at = NOW(), updated_at = NOW()
         WHERE id = ? AND status = 'Resolved'",
        array($t['id'])
    );

    if ($rowsAffected > 0) {
        $closed++;

        create_notification(
            $t['created_by'],
            'ticket_closed',
            "Your ticket #{$t['ticket_number']} was closed automatically after {$autoCloseDays} days. If the issue is still not fixed, please submit a new ticket.",
            "ticket_detail.php?id={$t['id']}",
            $t['id']
        );

        manage(
            "INSERT INTO logs (username,computer_name,ip_address,page,action,details,date)
             VALUES (?,?,?,?,?,?,?)",
            array(
                'System',
                gethostname(),
                '127.0.0.1',
                "Auto Close",
                "AUTO CLOSE",
                "<details>
                    <p>Ticket Auto-Closed</p>
                    <p>
                        Ticket #: ".htmlspecialchars($t['ticket_number'])."<br>
                        Status: Resolved => <span class='font-weight-bold'>Closed</span><br>
                        Reason: no response from requester within {$autoCloseDays} days
                    </p>
                </details>",
                date('Y-m-d H:i:s')
            )
        );
    }
}

echo date('Y-m-d H:i:s') . " - Auto-closed {$closed} ticket(s)\n";

?>