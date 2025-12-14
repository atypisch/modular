<?php
/**
 * Fix Component Definitions Table
 * Creates the component_definitions table with proper escaping
 */

require_once __DIR__ . '/config/config.php';

if (!isset($GLOBALS['db'])) {
    die("Database connection not available");
}

$db = $GLOBALS['db'];

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Creating component_definitions Table...</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #0c5460; }
        pre { background: #fff; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Creating component_definitions Table...</h1>
    <pre>
<?php

$sql = "CREATE TABLE IF NOT EXISTS component_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_type_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    `schema` JSON NOT NULL,
    is_fixed TINYINT(1) NOT NULL DEFAULT 1,
    is_flexible TINYINT(1) NOT NULL DEFAULT 0,
    `order` INT NOT NULL DEFAULT 0,
    group_name VARCHAR(255) DEFAULT NULL,
    access_control JSON,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_post_type (post_type_id),
    INDEX idx_type (type),
    UNIQUE KEY unique_slug (post_type_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $db->exec($sql);
    echo "<span class='success'>✓ Created table: component_definitions</span>\n";
    
    // Verify it exists
    $stmt = $db->query("SHOW TABLES LIKE 'component_definitions'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='success'>✓ Verified: component_definitions table exists</span>\n";
    }
    
    // Final check
    echo "\n<strong>Final Check:</strong>\n";
    $stmt = $db->query("SHOW TABLES");
    $finalTables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    
    $requiredTables = [
        'users', 'roles', 'capabilities', 'role_capabilities', 'user_roles',
        'post_types', 'posts', 'component_definitions', 'media', 'relationships',
        'taxonomies', 'terms', 'post_terms', 'post_type_taxonomies',
        'forms', 'form_submissions', 'actions', 'action_queue', 'settings',
        'post_revisions', 'activity_log', 'schema_migrations'
    ];
    
    $stillMissing = array_diff($requiredTables, $finalTables);
    
    if (empty($stillMissing)) {
        echo "<span class='success'>✓ SUCCESS: All " . count($requiredTables) . " required tables exist!</span>\n";
    } else {
        echo "<span class='error'>✗ Still missing " . count($stillMissing) . " tables:</span>\n";
        foreach ($stillMissing as $table) {
            echo "  - {$table}\n";
        }
    }
    
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "<span class='info'>→ Table already exists: component_definitions</span>\n";
    } else {
        echo "<span class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
        echo "\n<strong>SQL that failed:</strong>\n";
        echo htmlspecialchars($sql);
    }
}

?>
    </pre>
    
    <p><a href="/modular/admin/install.php">← Back to Install Page</a></p>
    <p><a href="/modular/admin/test-all-pages.php">Run Tests →</a></p>
</body>
</html>

