<?php
/**
 * System Module: Activity — Hook registrations
 * Logs admin and content events to the activity_log table.
 */

use Modular\Hooks;

if (!function_exists('activity_log')) {
    /**
     * Write an entry to the activity log.
     */
    function activity_log(string $action, string $objectType = '', $objectId = null, array $meta = []) {
        if (!isset($GLOBALS['db']) || !($GLOBALS['db'] instanceof PDO)) {
            return;
        }
        try {
            $userId = null;
            if (function_exists('get_current_user_id')) {
                $userId = get_current_user_id() ?: null;
            }
            $stmt = $GLOBALS['db']->prepare("
                INSERT INTO activity_log (user_id, action, object_type, object_id, meta, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $action,
                $objectType,
                $objectId,
                !empty($meta) ? json_encode($meta) : null,
            ]);
        } catch (\PDOException $e) {
            error_log("Activity log write failed: " . $e->getMessage());
        }
    }
}

Hooks::addAction('post_saved',   function ($id, $data) { activity_log('post_saved',   'post', $id); }, 99, 2);
Hooks::addAction('post_deleted', function ($id)         { activity_log('post_deleted', 'post', $id); }, 99);
Hooks::addAction('user_login',   function ($id)         { activity_log('user_login',   'user', $id); }, 99);
