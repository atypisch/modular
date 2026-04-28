CREATE TABLE IF NOT EXISTS activity_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED DEFAULT NULL,
    action      VARCHAR(100) NOT NULL,
    object_type VARCHAR(100) NOT NULL DEFAULT '',
    object_id   INT UNSIGNED DEFAULT NULL,
    meta        JSON DEFAULT NULL,
    created_at  DATETIME NOT NULL,
    INDEX idx_action      (action),
    INDEX idx_object      (object_type, object_id),
    INDEX idx_user        (user_id),
    INDEX idx_created_at  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
