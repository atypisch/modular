<?php
/**
 * Simuze Community tables for network page and module.
 * Creates simuze_group_types and simuze_groups if missing (idempotent).
 */
$db = $GLOBALS['db'] ?? null;
if (!$db instanceof PDO) {
    return; // Skip when DB not available (caller must ensure $GLOBALS['db'] is set)
}

$statements = [
    "CREATE TABLE IF NOT EXISTS simuze_group_types (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        icon VARCHAR(100) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY deleted_at (deleted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "INSERT IGNORE INTO simuze_group_types (name, slug, icon) VALUES
    ('Artist', 'artist', 'music'),
    ('Label', 'label', 'tag'),
    ('Venue', 'venue', 'map-marker'),
    ('City', 'city', 'city'),
    ('Province', 'province', 'map'),
    ('Country', 'country', 'globe')",

    "CREATE TABLE IF NOT EXISTS simuze_groups (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        unique_name VARCHAR(255) DEFAULT NULL,
        unique_handle VARCHAR(64) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        group_type_id BIGINT UNSIGNED DEFAULT NULL,
        creator_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
        avatar_url VARCHAR(500) DEFAULT NULL,
        header_url VARCHAR(500) DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'public',
        city VARCHAR(100) DEFAULT NULL,
        province VARCHAR(100) DEFAULT NULL,
        country VARCHAR(100) DEFAULT NULL,
        geo_id VARCHAR(50) DEFAULT NULL,
        lat DECIMAL(10,8) DEFAULT NULL,
        lng DECIMAL(11,8) DEFAULT NULL,
        settings LONGTEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        UNIQUE KEY unique_name (unique_name),
        UNIQUE KEY unique_handle (unique_handle),
        KEY group_type_id (group_type_id),
        KEY creator_id (creator_id),
        KEY status (status),
        KEY location (city, province, country),
        KEY deleted_at (deleted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($statements as $sql) {
    try {
        $db->exec($sql);
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'already exists') !== false || strpos($msg, 'Duplicate') !== false) {
            continue;
        }
        throw $e;
    }
}
