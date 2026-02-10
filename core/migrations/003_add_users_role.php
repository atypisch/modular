<?php
/**
 * Add role column to users table (for admin user management).
 * Core schema uses roles/user_roles; admin UI expects a simple users.role column.
 * This migration adds it if missing so bulk actions and user edit work.
 */

require_once __DIR__ . '/../../admin/config/config.php';
require_once __DIR__ . '/../classes/Migration.php';

use Modular\Migration;

if (!isset($GLOBALS['db'])) {
    die("Database connection not available\n");
}

$migration = new Migration($GLOBALS['db']);

$version = '003';
$name    = 'add_users_role';

if ($migration->hasRun($version)) {
    if (php_sapi_name() === 'cli') {
        echo "Migration {$version} already executed.\n";
    }
    return;
}

$db = $GLOBALS['db'];

if (!function_exists('modular_add_column_if_missing')) {
    function modular_add_column_if_missing(PDO $db, string $table, string $column, string $definition): void {
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        $exists = (int) $stmt->fetchColumn() > 0;

        if ($exists) {
            return;
        }

        $quoted = '`' . str_replace('`', '``', $column) . '`';
        $sql = "ALTER TABLE `{$table}` ADD COLUMN {$quoted} {$definition}";
        $db->exec($sql);
    }
}

try {
    modular_add_column_if_missing(
        $db,
        'users',
        'role',
        "VARCHAR(50) NOT NULL DEFAULT 'user' AFTER last_name"
    );

    $migration->recordMigration($version, $name);
    if (php_sapi_name() === 'cli') {
        echo "Migration {$version} ({$name}) completed.\n";
    }
} catch (\Exception $e) {
    throw new \Exception("Migration {$version} failed: " . $e->getMessage());
}
