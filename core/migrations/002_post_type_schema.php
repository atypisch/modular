<?php
/**
 * Post Type Schema Extension Migration
 * Version: 002
 *
 * - Adds mode + schema JSON to post_types
 * - Adds data + layout JSON to posts
 *
 * This migration is additive and safe to run on existing installs.
 */

require_once __DIR__ . '/../../admin/config/config.php';
require_once __DIR__ . '/../classes/Migration.php';

use Modular\Migration;

if (!isset($GLOBALS['db'])) {
    die("Database connection not available\n");
}

$migration = new Migration($GLOBALS['db']);

$version = '002';
$name    = 'post_type_schema';

if ($migration->hasRun($version)) {
    if (php_sapi_name() === 'cli') {
        echo "Migration {$version} already executed.\n";
    }
    return;
}

$db = $GLOBALS['db'];

/**
 * Helper: add column if it does not exist.
 */
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
    // Extend post_types
    modular_add_column_if_missing(
        $db,
        'post_types',
        'mode',
        "VARCHAR(20) NOT NULL DEFAULT 'fixed' AFTER description"
    );

    modular_add_column_if_missing(
        $db,
        'post_types',
        'schema',
        "JSON NULL AFTER settings"
    );

    // Extend posts
    modular_add_column_if_missing(
        $db,
        'posts',
        'data',
        "JSON NULL AFTER meta_data"
    );

    modular_add_column_if_missing(
        $db,
        'posts',
        'layout',
        "JSON NULL AFTER data"
    );

    // Record migration
    $migration->recordMigration($version, $name);
    if (php_sapi_name() === 'cli') {
        echo "Migration {$version} ({$name}) completed.\n";
    }
} catch (\Exception $e) {
    throw new \Exception("Migration {$version} failed: " . $e->getMessage());
}

