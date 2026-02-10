<?php
/**
 * Legacy helper: initialize database tables.
 *
 * This script now delegates to the core migration system (001_core_schema.php),
 * so it always installs the **current** schema instead of the old init_database.sql layout.
 */

if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/database.php';

if (!isset($GLOBALS['db'])) {
    die("Error: Database connection not available.\n");
}

echo "Running core migrations (001_core_schema and beyond)...\n";

$result = run_pending_migrations();
if (!empty($result['error'])) {
    die("Migration error: " . $result['error'] . "\n");
}

if (!empty($result['ran'])) {
    echo "Executed migrations:\n";
    foreach ($result['ran'] as $m) {
        echo " - {$m}\n";
    }
} else {
    echo "No pending migrations. Schema already up to date.\n";
}

// Ensure at least one admin user exists
try {
    $db = $GLOBALS['db'];
    $stmt = $db->query("SELECT COUNT(*) AS cnt FROM users");
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ((int)($row['cnt'] ?? 0) === 0) {
        $username = 'admin';
        $email = 'admin@example.com';
        $password = password_hash('admin', PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO users 
            (username, email, password, status, email_verified, created_at, updated_at)
            VALUES (?, ?, ?, 'active', 1, NOW(), NOW())
        ");
        $stmt->execute([$username, $email, $password]);

        echo "Default admin user created.\n";
        echo "Username: admin\n";
        echo "Password: admin\n";
        echo "Please change these credentials immediately after first login.\n";
    } else {
        echo "Users already present; default admin not created.\n";
    }
} catch (\Throwable $e) {
    echo "Warning: could not ensure default admin user: " . $e->getMessage() . "\n";
}
