<?php
/**
 * Simuze Community – messaging tables
 * Run on module activation (after 001_groups).
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
CREATE TABLE IF NOT EXISTS {$prefix}conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL DEFAULT 'direct',
    title VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_type (type),
    KEY idx_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$db->exec("
CREATE TABLE IF NOT EXISTS {$prefix}conversation_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'member',
    joined_at DATETIME NOT NULL,
    last_read_at DATETIME DEFAULT NULL,
    UNIQUE KEY uq_conv_user (conversation_id, user_id),
    KEY idx_user (user_id),
    FOREIGN KEY (conversation_id) REFERENCES {$prefix}conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$db->exec("
CREATE TABLE IF NOT EXISTS {$prefix}messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    edited_at DATETIME DEFAULT NULL,
    KEY idx_conversation (conversation_id),
    KEY idx_created (created_at),
    FOREIGN KEY (conversation_id) REFERENCES {$prefix}conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
