<?php
/**
 * Simuze Community – reviews table (content moderation / review queue)
 * Aligned with WP plugin: reviewer_id, content_type, content_id, status, notes.
 */

$configPath = defined('MODULAR_PATH') ? MODULAR_PATH . '/admin/config/config.php' : '';
if ($configPath && is_file($configPath)) {
    require_once $configPath;
}
$db = $GLOBALS['db'] ?? null;
if (!$db instanceof PDO) {
    return;
}

$prefix = defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_';

$db->exec("
CREATE TABLE IF NOT EXISTS {$prefix}reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    content_type VARCHAR(50) NOT NULL,
    content_id INT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_reviewer (reviewer_id),
    KEY idx_content (content_type, content_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
