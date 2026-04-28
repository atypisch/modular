<?php
/**
 * System Module: Notifications — Hook registrations
 */

use Modular\Hooks;

if (!function_exists('notify_user')) {
    /**
     * Queue a notification for a user.
     */
    function notify_user(int $userId, string $type, string $message, array $meta = []) {
        if (!isset($GLOBALS['db']) || !($GLOBALS['db'] instanceof PDO)) {
            return;
        }
        try {
            $stmt = $GLOBALS['db']->prepare("
                INSERT INTO notifications (user_id, type, message, meta, is_read, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            $stmt->execute([
                $userId,
                $type,
                $message,
                !empty($meta) ? json_encode($meta) : null,
            ]);
        } catch (\PDOException $e) {
            error_log("Notification write failed: " . $e->getMessage());
        }
    }
}
