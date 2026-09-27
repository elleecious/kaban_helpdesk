<?php
/**
 * Create a new notification for a user.
 *
 * @param int         $user_id            Recipient user id
 * @param string      $type               'ticket_created' | 'ticket_assigned' | 'resolved_ticket' |
 *                                         'sla_warning' | 'new_comment' | 'change_approval'
 * @param string      $message            Short human-readable message
 * @param string      $link               Relative URL to send the user to when clicked
 * @param int|null    $ticket_id          Related ticket id, if applicable
 * @param int|null    $change_request_id  Related change request id, if applicable
 * @return bool                           True if the insert succeeded
 */
function create_notification($user_id, $type, $message, $link, $ticket_id = null, $change_request_id = null) {
    $sql = "INSERT INTO notifications (user_id, type, ticket_id, change_request_id, message, link)
            VALUES (?, ?, ?, ?, ?, ?)";

    $rows = manage($sql, [$user_id, $type, $ticket_id, $change_request_id, $message, $link]);

    return $rows > 0;
}

/**
 * Create the same notification for multiple recipients at once.
 * Useful for "notify all agents in category X" on ticket creation.
 *
 * @param int[] $user_ids
 */
function create_notification_bulk(array $user_ids, $type, $message, $link, $ticket_id = null, $change_request_id = null) {
    foreach ($user_ids as $user_id) {
        create_notification($user_id, $type, $message, $link, $ticket_id, $change_request_id);
    }
}

/**
 * Get the unread notification count for a user (for the bell badge).
 *
 * @param int $user_id
 * @return int
 */
function get_unread_count($user_id) {
    $sql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
    $result = retrieve($sql, [$user_id]);
    return isset($result[0]['unread_count']) ? (int) $result[0]['unread_count'] : 0;
}

/**
 * Get the most recent notifications for a user (for the dropdown).
 *
 * @param int $user_id
 * @param int $limit
 * @return array
 */
function get_recent_notifications($user_id, $limit = 10) {
    // LIMIT can't be a bound param reliably in all PDO emulation modes, so cast + inline it safely.
    $limit = (int) $limit;
    $sql = "SELECT id, type, ticket_id, change_request_id, message, link, is_read, created_at
            FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT $limit";
    return retrieve($sql, [$user_id]);
}

/**
 * Mark a single notification as read.
 * Checks user_id too so a user can't mark someone else's notification read via IDOR.
 *
 * @param int $notification_id
 * @param int $user_id
 * @return bool
 */
function mark_as_read($notification_id, $user_id) {
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $rows = manage($sql, [$notification_id, $user_id]);
    return $rows > 0;
}

/**
 * Mark all of a user's notifications as read (dropdown "mark all read" action).
 *
 * @param int $user_id
 * @return bool
 */
function mark_all_as_read($user_id) {
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
    manage($sql, [$user_id]);
    return true;
}

?>