<?php
/**
 * System Module: Users — Hook registrations
 */

use Modular\Hooks;

// Fired when user logs in
Hooks::addAction('user_login', function ($userId) {
    // Update last_login timestamp
    if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
        try {
            $stmt = $GLOBALS['db']->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (\PDOException $e) {
            error_log("Users system module: failed to update login timestamp: " . $e->getMessage());
        }
    }
});
