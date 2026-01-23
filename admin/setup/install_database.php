<?php
/**
 * Database Installation Script
 * Creates all necessary database tables using migrations
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Check if database connection exists
if (!isset($GLOBALS['db'])) {
    die("Database connection not available. Please check your configuration.");
}

// Load Migration class
require_once CORE_PATH . '/classes/Migration.php';

use Modular\Migration;

$migration = new Migration($GLOBALS['db']);

// Run the core schema migration
$migrationFile = CORE_PATH . '/migrations/001_core_schema.php';

if (!file_exists($migrationFile)) {
    die("Migration file not found: {$migrationFile}");
}

// Include and execute the migration
try {
    require $migrationFile;
    echo "Database installation completed successfully!\n";
    echo "All tables have been created.\n";
} catch (Exception $e) {
    die("Installation failed: " . $e->getMessage() . "\n");
}


