<?php
/**
 * Simuze music tables (md_ prefix).
 * Creates: md_music_uploads, md_music_reports, md_music_reviews, md_music_review_comments,
 * md_music_albums, md_music_playlists.
 */
$db = $GLOBALS['db'] ?? null;
if (!$db instanceof PDO) {
    throw new RuntimeException('Database connection not available.');
}

$statements = [
    "CREATE TABLE IF NOT EXISTS md_music_uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        media_id INT UNSIGNED DEFAULT NULL,
        file_path VARCHAR(500) NOT NULL DEFAULT '',
        mime_type VARCHAR(100) NOT NULL DEFAULT 'audio/mpeg',
        file_size INT UNSIGNED NOT NULL DEFAULT 0,
        uploaded_by INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        deleted_at DATETIME DEFAULT NULL,
        INDEX idx_media_id (media_id),
        INDEX idx_uploaded_by (uploaded_by),
        INDEX idx_deleted (deleted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS md_music_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reporter_id INT UNSIGNED NOT NULL,
        content_type VARCHAR(50) NOT NULL,
        content_id INT UNSIGNED NOT NULL,
        report_type VARCHAR(50) NOT NULL,
        comment TEXT DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        reviewed_by INT UNSIGNED DEFAULT NULL,
        reviewed_at DATETIME DEFAULT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_reporter (reporter_id),
        INDEX idx_content (content_type, content_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS md_music_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reviewer_id INT UNSIGNED NOT NULL,
        content_type VARCHAR(50) NOT NULL,
        content_id INT UNSIGNED NOT NULL,
        rating TINYINT UNSIGNED DEFAULT NULL,
        body TEXT DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_reviewer (reviewer_id),
        INDEX idx_content (content_type, content_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS md_music_review_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        body TEXT NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        deleted_at DATETIME DEFAULT NULL,
        INDEX idx_review (review_id),
        INDEX idx_user (user_id),
        INDEX idx_deleted (deleted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

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
