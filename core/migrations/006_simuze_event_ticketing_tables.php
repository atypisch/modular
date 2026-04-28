<?php
/**
 * Simuze event ticketing tables.
 * - simuze_event_orders
 * - simuze_event_tickets
 */
$db = $GLOBALS['db'] ?? null;
if (!$db instanceof PDO) {
    throw new RuntimeException('Database connection not available.');
}

$statements = [
    "CREATE TABLE IF NOT EXISTS simuze_event_orders (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        buyer_user_id INT NULL DEFAULT NULL,
        buyer_email VARCHAR(255) DEFAULT NULL,
        quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
        unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        currency CHAR(3) NOT NULL DEFAULT 'EUR',
        status ENUM('pending','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
        external_reference VARCHAR(191) DEFAULT NULL,
        payment_provider VARCHAR(50) NOT NULL DEFAULT 'simezu',
        metadata JSON DEFAULT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        KEY idx_event (event_id),
        KEY idx_buyer_user (buyer_user_id),
        KEY idx_status (status),
        UNIQUE KEY uq_ext_ref (external_reference)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS simuze_event_tickets (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        order_id BIGINT UNSIGNED DEFAULT NULL,
        buyer_user_id INT NULL DEFAULT NULL,
        buyer_email VARCHAR(255) DEFAULT NULL,
        ticket_code VARCHAR(64) NOT NULL,
        qr_token VARCHAR(128) NOT NULL,
        status ENUM('valid','used','cancelled','refunded') NOT NULL DEFAULT 'valid',
        issued_at DATETIME NOT NULL,
        used_at DATETIME DEFAULT NULL,
        metadata JSON DEFAULT NULL,
        KEY idx_event (event_id),
        KEY idx_order (order_id),
        KEY idx_buyer_user (buyer_user_id),
        KEY idx_status (status),
        UNIQUE KEY uq_ticket_code (ticket_code),
        UNIQUE KEY uq_qr_token (qr_token)
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

