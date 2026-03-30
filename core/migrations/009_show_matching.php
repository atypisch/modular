<?php
/**
 * Migration 009: Show Matching System
 *
 * - Adds `mood` column to simuze_events (for show mood tagging)
 * - Creates simuze_show_invites table (pre-slot artist invites from organizers)
 */

$db = $GLOBALS['db'] ?? null;
if (!$db instanceof PDO) {
    throw new RuntimeException('Database connection not available.');
}

// Add mood column to simuze_events
$cols = $db->query("SHOW COLUMNS FROM simuze_events LIKE 'mood'")->fetchAll();
if (empty($cols)) {
    $db->exec("ALTER TABLE simuze_events ADD COLUMN mood VARCHAR(100) DEFAULT NULL AFTER genres");
    error_log('Migration 009: Added mood column to simuze_events.');
}

// Create simuze_show_invites table
$db->exec("
    CREATE TABLE IF NOT EXISTS simuze_show_invites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        show_id INT NOT NULL,
        artist_group_id INT NOT NULL,
        invited_by_user_id INT NOT NULL,
        status ENUM('pending','accepted','declined','cancelled') NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_show (show_id),
        KEY idx_artist (artist_group_id),
        KEY idx_status (status),
        UNIQUE KEY uq_show_artist (show_id, artist_group_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
error_log('Migration 009: Ensured simuze_show_invites table exists.');
