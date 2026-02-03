<?php
/**
 * Sync users table: add missing columns (deleted_at, username, updated_at, etc.)
 * Run once via browser: http://localhost/modular/admin/sql/add_users_deleted_at.php
 * Or from CLI: php add_users_deleted_at.php
 */

require_once dirname(__DIR__) . '/config/config.php';

$db = $GLOBALS['db'] ?? null;
if (!$db) {
    die("Database connection not available.\n");
}

header('Content-Type: text/html; charset=utf-8');
echo "<pre>\n";

try {
    $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($columns, 'Field');

    $added = [];

    // Add username if missing
    if (!in_array('username', $colNames)) {
        $db->exec("ALTER TABLE users ADD COLUMN username VARCHAR(255) DEFAULT NULL AFTER id");
        $colNames[] = 'username';
        $added[] = 'username';
        // Backfill from name or email (email is unique so safe for UNIQUE username)
        if (in_array('name', $colNames)) {
            $db->exec("UPDATE users SET username = COALESCE(NULLIF(TRIM(name), ''), email) WHERE username IS NULL");
        }
        if (in_array('email', $colNames)) {
            $db->exec("UPDATE users SET username = COALESCE(username, email) WHERE username IS NULL OR username = ''");
        }
        $db->exec("UPDATE users SET username = CONCAT('user', id) WHERE username IS NULL OR username = ''");
        $db->exec("ALTER TABLE users MODIFY COLUMN username VARCHAR(255) NOT NULL");
        try {
            $db->exec("ALTER TABLE users ADD UNIQUE KEY username (username)");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) throw $e;
        }
    }

    if (!in_array('first_name', $colNames)) {
        $db->exec("ALTER TABLE users ADD COLUMN first_name VARCHAR(255) DEFAULT NULL AFTER username");
        $colNames[] = 'first_name';
        $added[] = 'first_name';
    }

    if (!in_array('last_name', $colNames)) {
        $db->exec("ALTER TABLE users ADD COLUMN last_name VARCHAR(255) DEFAULT NULL AFTER first_name");
        $colNames[] = 'last_name';
        $added[] = 'last_name';
    }

    if (!in_array('status', $colNames)) {
        $db->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER role");
        $colNames[] = 'status';
        $added[] = 'status';
    }

    if (!in_array('email_verified', $colNames)) {
        $db->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
        $colNames[] = 'email_verified';
        $added[] = 'email_verified';
    }

    if (!in_array('updated_at', $colNames)) {
        $after = in_array('created_at', $colNames) ? 'created_at' : 'id';
        $db->exec("ALTER TABLE users ADD COLUMN updated_at DATETIME DEFAULT NULL AFTER {$after}");
        $db->exec("UPDATE users SET updated_at = COALESCE(created_at, NOW()) WHERE updated_at IS NULL");
        $db->exec("ALTER TABLE users MODIFY COLUMN updated_at DATETIME NOT NULL");
        $colNames[] = 'updated_at';
        $added[] = 'updated_at';
    }

    if (!in_array('deleted_at', $colNames)) {
        $db->exec("ALTER TABLE users ADD COLUMN deleted_at DATETIME DEFAULT NULL AFTER updated_at");
        $colNames[] = 'deleted_at';
        $added[] = 'deleted_at';
    }

    if (in_array('deleted_at', $colNames)) {
        try {
            $db->exec("ALTER TABLE users ADD INDEX idx_deleted (deleted_at)");
            $added[] = 'idx_deleted';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') === false) throw $e;
        }
    }

    if (!empty($added)) {
        echo "✓ Added: " . implode(', ', $added) . "\n";
        echo "\n✓ Users table is now in sync. You can use Users, Forms, Media, and Posts with author info.\n";
    } else {
        echo "✓ Users table already has all required columns. No change needed.\n";
    }
} catch (PDOException $e) {
    echo "✗ Error: " . htmlspecialchars($e->getMessage()) . "\n";
    exit(1);
}

echo "\n<a href=\"/modular/admin/users\">← Back to Users</a>\n";
echo "</pre>\n";
