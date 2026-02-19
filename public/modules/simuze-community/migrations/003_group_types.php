<?php
/**
 * Simuze Community – group_types table (Artist, Label, City, etc.)
 * Aligned with WP plugin: name, slug, icon, optional description.
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
CREATE TABLE IF NOT EXISTS {$prefix}group_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    UNIQUE KEY uq_slug (slug),
    KEY idx_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
