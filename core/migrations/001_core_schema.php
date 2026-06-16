<?php
/**
 * Core schema migration – creates users, roles, post_types, posts, media, etc.
 * Uses admin/sql/schema.sql as source; ensures default admin user exists before post_types/taxonomies inserts.
 */
$db = $GLOBALS['db'] ?? null;
if (!$db instanceof PDO) {
    throw new RuntimeException('Database connection not available.');
}

$schemaFile = __DIR__ . '/../../admin/sql/schema.sql';
if (!is_readable($schemaFile)) {
    throw new RuntimeException('Core schema file not found: ' . $schemaFile);
}

$sql = file_get_contents($schemaFile);
$sql = preg_replace('/--[^\n]*\n/m', "\n", $sql);
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if ($statement === '') {
        continue;
    }
    $statement = trim($statement);
    if ($statement === '') {
        continue;
    }

    // Before inserting into post_types or taxonomies we need user id 1 (created_by, updated_by)
    if (preg_match('/\bINSERT\b/i', $statement) && (stripos($statement, 'post_types') !== false || stripos($statement, 'taxonomies') !== false)) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM users");
            $count = (int) $stmt->fetchColumn();
            if ($count === 0) {
                $hash = password_hash('admin', PASSWORD_DEFAULT);
                $db->exec("INSERT INTO users (username, email, password, status, email_verified, created_at, updated_at) VALUES ('admin', 'admin@example.com', " . $db->quote($hash) . ", 'active', 1, NOW(), NOW())");
            }
        } catch (Throwable $e) {
            // users table might not exist yet if order is wrong
        }
    }

    try {
        $db->exec($statement . ';');
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'already exists') !== false || strpos($msg, 'Duplicate') !== false) {
            continue;
        }
        throw $e;
    }
}
