<?php
/**
 * Simuze events: ticketing + location extensions.
 * - Adds simuze_cities.province
 * - Adds simuze_events ticket fields
 */
$db = $GLOBALS['db'] ?? null;
if (!$db instanceof PDO) {
    throw new RuntimeException('Database connection not available.');
}

$column_exists = static function (PDO $db, string $table, string $column): bool {
    $stmt = $db->prepare(
        "SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?"
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
};

$index_exists = static function (PDO $db, string $table, string $index): bool {
    $stmt = $db->prepare(
        "SELECT COUNT(*)
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND INDEX_NAME = ?"
    );
    $stmt->execute([$table, $index]);
    return (int) $stmt->fetchColumn() > 0;
};

if (!$column_exists($db, 'simuze_cities', 'province')) {
    $db->exec("ALTER TABLE simuze_cities ADD COLUMN province VARCHAR(100) NOT NULL DEFAULT '' AFTER country");
}
if (!$index_exists($db, 'simuze_cities', 'idx_province')) {
    $db->exec("ALTER TABLE simuze_cities ADD INDEX idx_province (province)");
}
if (!$index_exists($db, 'simuze_cities', 'idx_location')) {
    $db->exec("ALTER TABLE simuze_cities ADD INDEX idx_location (country, province, name)");
}

if (!$column_exists($db, 'simuze_events', 'ticket_price')) {
    $db->exec("ALTER TABLE simuze_events ADD COLUMN ticket_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER open_slots");
}
if (!$column_exists($db, 'simuze_events', 'ticket_currency')) {
    $db->exec("ALTER TABLE simuze_events ADD COLUMN ticket_currency CHAR(3) NOT NULL DEFAULT 'EUR' AFTER ticket_price");
}
if (!$column_exists($db, 'simuze_events', 'ticket_sales_enabled')) {
    $db->exec("ALTER TABLE simuze_events ADD COLUMN ticket_sales_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER ticket_currency");
}
if (!$index_exists($db, 'simuze_events', 'idx_ticket_sales')) {
    $db->exec("ALTER TABLE simuze_events ADD INDEX idx_ticket_sales (ticket_sales_enabled)");
}

