<?php
/**
 * Install Modules Table
 * Creates the modules table if it doesn't exist
 */

require_once __DIR__ . '/config/config.php';
require_once CORE_PATH . '/functions/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Install Modules Table</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; }
    </style>
</head>
<body>
    <h1>Install Modules Table</h1>
    
    <?php
    $db = $GLOBALS['db'] ?? null;
    
    if (!$db) {
        echo '<p class="error">Database connection failed!</p>';
        exit;
    }
    
    echo '<p class="info">Checking for modules table...</p>';
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'modules'");
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        echo '<p class="success">✓ Modules table already exists!</p>';
    } else {
        echo '<p class="info">Creating modules table...</p>';
        
        $sql = "
        CREATE TABLE IF NOT EXISTS modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            slug VARCHAR(255) NOT NULL UNIQUE,
            version VARCHAR(50) NOT NULL DEFAULT '1.0.0',
            description TEXT,
            author VARCHAR(255),
            author_url VARCHAR(500),
            module_url VARCHAR(500),
            text_domain VARCHAR(100),
            requires_php VARCHAR(20),
            requires_modular VARCHAR(20),
            active TINYINT(1) NOT NULL DEFAULT 0,
            settings JSON,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_active (active),
            INDEX idx_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        try {
            $db->exec($sql);
            echo '<p class="success">✓ Modules table created successfully!</p>';
        } catch (\PDOException $e) {
            echo '<p class="error">✗ Error creating modules table: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
    
    // Verify all required tables
    echo '<h2>Database Tables Status</h2>';
    
    $requiredTables = [
        'users', 'roles', 'capabilities', 'role_capabilities', 'user_roles',
        'post_types', 'posts', 'component_definitions', 'media', 'relationships',
        'taxonomies', 'terms', 'post_terms', 'post_type_taxonomies',
        'forms', 'form_submissions', 'actions', 'action_queue', 'settings',
        'post_revisions', 'activity_log', 'schema_migrations', 'modules'
    ];
    
    $stmt = $db->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    $missingTables = array_diff($requiredTables, $existingTables);
    
    echo '<p>Existing tables: ' . count($existingTables) . '</p>';
    echo '<p>Required tables: ' . count($requiredTables) . '</p>';
    
    if (empty($missingTables)) {
        echo '<p class="success">✓ All required tables exist!</p>';
    } else {
        echo '<p class="error">✗ Missing tables: ' . implode(', ', $missingTables) . '</p>';
        echo '<p><a href="/modular/admin/install.php">Install Missing Tables</a></p>';
    }
    
    echo '<p><a href="/modular/admin/modules">Go to Modules Page</a></p>';
    ?>
</body>
</html>

