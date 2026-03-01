<?php
/**
 * Create Missing Tables Directly
 * Creates the 6 missing tables without foreign key constraints
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
    <title>Creating Missing Tables...</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #0c5460; }
        pre { background: #fff; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Creating Missing Tables...</h1>
    <pre>
<?php

$tables = [
    'post_types' => "
CREATE TABLE IF NOT EXISTS post_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'file',
    supports TEXT NOT NULL,
    public TINYINT(1) NOT NULL DEFAULT 1,
    show_in_menu TINYINT(1) NOT NULL DEFAULT 1,
    menu_position INT DEFAULT NULL,
    menu_icon VARCHAR(50) DEFAULT NULL,
    hierarchical TINYINT(1) NOT NULL DEFAULT 0,
    has_archive TINYINT(1) NOT NULL DEFAULT 1,
    exclude_from_search TINYINT(1) NOT NULL DEFAULT 0,
    publicly_queryable TINYINT(1) NOT NULL DEFAULT 1,
    show_in_rest TINYINT(1) NOT NULL DEFAULT 1,
    rest_base VARCHAR(50) DEFAULT NULL,
    capability_type VARCHAR(50) NOT NULL DEFAULT 'post',
    settings JSON,
    created_by INT NOT NULL,
    updated_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    INDEX idx_slug (slug),
    INDEX idx_deleted (deleted_at),
    INDEX idx_created_by (created_by),
    INDEX idx_updated_by (updated_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
",
    'posts' => "
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_type_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    slug VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    password VARCHAR(255) DEFAULT NULL,
    comment_status VARCHAR(20) NOT NULL DEFAULT 'open',
    ping_status VARCHAR(20) NOT NULL DEFAULT 'open',
    visibility VARCHAR(20) NOT NULL DEFAULT 'public',
    featured_image INT DEFAULT NULL,
    template VARCHAR(255) DEFAULT NULL,
    `order` INT NOT NULL DEFAULT 0,
    component_data JSON,
    meta_data JSON,
    author_id INT NOT NULL,
    created_by INT NOT NULL,
    updated_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    published_at DATETIME DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL,
    INDEX idx_post_type (post_type_id),
    INDEX idx_status (status),
    INDEX idx_slug (slug),
    INDEX idx_published (published_at),
    INDEX idx_deleted (deleted_at),
    INDEX idx_author (author_id),
    INDEX idx_created_by (created_by),
    INDEX idx_updated_by (updated_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
",
    'component_definitions' => "
CREATE TABLE IF NOT EXISTS component_definitions (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
",
    'post_terms' => "
CREATE TABLE IF NOT EXISTS post_terms (
    post_id INT NOT NULL,
    term_id INT NOT NULL,
    PRIMARY KEY (post_id, term_id),
    INDEX idx_post_id (post_id),
    INDEX idx_term_id (term_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
",
    'post_type_taxonomies' => "
CREATE TABLE IF NOT EXISTS post_type_taxonomies (
    post_type_id INT NOT NULL,
    taxonomy_id INT NOT NULL,
    PRIMARY KEY (post_type_id, taxonomy_id),
    INDEX idx_post_type_id (post_type_id),
    INDEX idx_taxonomy_id (taxonomy_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
",
    'post_revisions' => "
CREATE TABLE IF NOT EXISTS post_revisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    component_data JSON,
    meta_data JSON,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_post (post_id),
    INDEX idx_created (created_at),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"
];

$created = 0;
$errors = 0;

foreach ($tables as $tableName => $sql) {
    try {
        $db->exec($sql);
        echo "<span class='success'>✓ Created table: {$tableName}</span>\n";
        $created++;
        
        // Set default value for supports column in post_types
        if ($tableName === 'post_types') {
            try {
                $db->exec("UPDATE post_types SET supports = '[\"title\",\"editor\",\"thumbnail\"]' WHERE supports IS NULL OR supports = ''");
            } catch (\Exception $e) {
                // Ignore if no rows to update
            }
        }
    } catch (\PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<span class='info'>→ Table already exists: {$tableName}</span>\n";
        } else {
            echo "<span class='error'>✗ Error creating {$tableName}: " . htmlspecialchars($e->getMessage()) . "</span>\n";
            $errors++;
        }
    }
}

echo "\n";
echo "<strong>Summary:</strong>\n";
echo "Created: {$created} tables\n";
echo "Errors: {$errors}\n";

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

?>
    </pre>
    
    <p><a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/install.php">← Back to Install Page</a></p>
    <p><a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/test-all-pages.php">Run Tests →</a></p>
</body>
</html>

