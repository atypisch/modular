<?php
/**
 * Simuze Community – groups tables
 * Run on module activation.
 */

$configPath = dirname(__DIR__, 4) . '/admin/config/config.php';
if (!is_file($configPath)) {
    $configPath = defined('MODULAR_PATH') ? MODULAR_PATH . '/admin/config/config.php' : '';
}
if ($configPath && is_file($configPath)) {
    require_once $configPath;
}
$db = $GLOBALS['db'] ?? null;
if (!$db instanceof PDO) {
    return;
}

$prefix = 'simuze_';

$db->exec("
CREATE TABLE IF NOT EXISTS {$prefix}groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    group_type VARCHAR(100) NOT NULL DEFAULT 'artist',
    owner_id INT NOT NULL,
    unique_handle VARCHAR(255) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    UNIQUE KEY uq_slug (slug),
    KEY idx_owner (owner_id),
    KEY idx_type (group_type),
    KEY idx_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$db->exec("
CREATE TABLE IF NOT EXISTS {$prefix}group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'member',
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_group_user (group_id, user_id),
    KEY idx_user (user_id),
    FOREIGN KEY (group_id) REFERENCES {$prefix}groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
