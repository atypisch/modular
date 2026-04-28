<?php
/**
 * Simuze music tables – md_music_albums, md_music_playlists.
 * Added after 002; run once for installs that already had 002.
 */
$db = $GLOBALS['db'] ?? null;
if (!$db instanceof PDO) {
    throw new RuntimeException('Database connection not available.');
}

$statements = [
    "CREATE TABLE IF NOT EXISTS md_music_albums (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT UNSIGNED DEFAULT NULL,
        created_by INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        deleted_at DATETIME DEFAULT NULL,
        INDEX idx_post_id (post_id),
        INDEX idx_created_by (created_by),
        INDEX idx_deleted (deleted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS md_music_playlists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT UNSIGNED DEFAULT NULL,
        created_by INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        deleted_at DATETIME DEFAULT NULL,
        INDEX idx_post_id (post_id),
        INDEX idx_created_by (created_by),
        INDEX idx_deleted (deleted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($statements as $sql) {
    try {
        $db->exec($sql);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            continue;
        }
        throw $e;
    }
}
