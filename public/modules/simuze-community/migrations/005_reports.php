<?php
/**
 * Simuze Community – reports table (user reports on content)
 * Aligned with WP plugin: reporter_id, content_type, content_id, report_type, comment, status, reviewed_by.
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
CREATE TABLE IF NOT EXISTS {$prefix}reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    content_type VARCHAR(50) NOT NULL,
    content_id INT UNSIGNED NOT NULL,
    report_type VARCHAR(50) NOT NULL,
    comment TEXT DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_reporter (reporter_id),
    KEY idx_content (content_type, content_id),
    KEY idx_status (status),
    KEY idx_reviewed_by (reviewed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
