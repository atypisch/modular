<?php
/**
 * Show slot pool (band availability) + event slot invitations.
 *
 * Tables:
 * - simuze_show_slot_pool: band availability by location + optional date range
 * - simuze_event_slot_invitations: event-specific pending invitations for a slot
 */

$db = $GLOBALS['db'] ?? null;
if (!$db instanceof PDO) {
    throw new RuntimeException('Database connection not available.');
}

$statements = [
    "CREATE TABLE IF NOT EXISTS simuze_show_slot_pool (
        id INT AUTO_INCREMENT PRIMARY KEY,
        band_group_id INT NOT NULL,
        location_level ENUM('country','province','city') NOT NULL,
        country VARCHAR(100) NOT NULL DEFAULT '',
        province VARCHAR(100) NOT NULL DEFAULT '',
        city_id INT DEFAULT NULL,
        from_date DATE DEFAULT NULL,
        to_date DATE DEFAULT NULL,
        created_by_user_id INT NOT NULL,
        status ENUM('active','expired') NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        KEY idx_band_group_id (band_group_id),
        KEY idx_status (status),
        KEY idx_location (location_level, country, province, city_id),
        KEY idx_timeframe (from_date, to_date),
        KEY idx_creator (created_by_user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS simuze_event_slot_invitations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        slot_id INT NOT NULL,
        band_group_id INT NOT NULL,
        pool_entry_id INT DEFAULT NULL,
        status ENUM('pending','accepted','declined','cancelled') NOT NULL DEFAULT 'pending',
        created_by_user_id INT NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        KEY idx_event_id (event_id),
        KEY idx_slot_id (slot_id),
        KEY idx_band_group_id (band_group_id),
        KEY idx_status (status),
        KEY idx_pool_entry_id (pool_entry_id),
        CONSTRAINT fk_slot_inv_event
            FOREIGN KEY (event_id) REFERENCES simuze_events(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($statements as $sql) {
    $db->exec($sql);
}

// No data migration required.

