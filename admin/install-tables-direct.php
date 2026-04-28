<?php
/**
 * Direct Table Installation
 * Installs all missing tables directly
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
    <title>Installing Tables...</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #0c5460; }
        pre { background: #fff; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Installing Missing Tables...</h1>
    <pre>
<?php

$output = [];
$errors = [];

// Read SQL from migration file
$migrationFile = CORE_PATH . '/migrations/001_core_schema.php';
if (!file_exists($migrationFile)) {
    die("ERROR: Migration file not found: {$migrationFile}\n");
}

$migrationContent = file_get_contents($migrationFile);

// Extract SQL - handle both single and double quotes
if (preg_match('/\$sql\s*=\s*["\']([^"\']+)["\']/s', $migrationContent, $matches)) {
    $sql = $matches[1];
    
    // Handle escaped quotes in the SQL string
    $sql = str_replace('\\"', '"', $sql);
    $sql = str_replace("\\'", "'", $sql);
    
    // Split into statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $created = 0;
    $skipped = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || preg_match('/^\s*--/', $statement)) {
            continue;
        }
        
        // Remove comments
        $statement = preg_replace('/--.*$/m', '', $statement);
        $statement = trim($statement);
        
        if (empty($statement)) {
            continue;
        }
        
        try {
            $db->exec($statement);
            
            // Extract table name
            if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?(\w+)`?/i', $statement, $tableMatch)) {
                $tableName = $tableMatch[1];
                echo "<span class='success'>✓ Created table: {$tableName}</span>\n";
                $output[] = "Created: {$tableName}";
                $created++;
            }
        } catch (\PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // If table already exists, that's fine
            if (strpos($errorMsg, 'already exists') !== false || 
                strpos($errorMsg, 'Duplicate') !== false ||
                strpos($errorMsg, 'Base table') !== false) {
                if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?(\w+)`?/i', $statement, $tableMatch)) {
                    $tableName = $tableMatch[1];
                    echo "<span class='info'>→ Table already exists: {$tableName}</span>\n";
                    $skipped++;
                }
            } else {
                // Real error
                echo "<span class='error'>✗ Error: {$errorMsg}</span>\n";
                $errors[] = $errorMsg;
                
                // Try to extract table name for better error reporting
                if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?(\w+)`?/i', $statement, $tableMatch)) {
                    echo "<span class='error'>  Failed table: {$tableMatch[1]}</span>\n";
                }
            }
        }
    }
    
    echo "\n";
    echo "<strong>Summary:</strong>\n";
    echo "Created: {$created} tables\n";
    echo "Skipped (already exist): {$skipped} tables\n";
    echo "Errors: " . count($errors) . "\n";
    
    // Create default data
    echo "\n<strong>Creating default data...</strong>\n";
    
    try {
        // Roles
        $defaultRoles = [
            ['admin', 'Administrator', 'Full system access'],
            ['editor', 'Editor', 'Can create and edit content'],
            ['author', 'Author', 'Can create and edit own content'],
            ['user', 'User', 'Basic user access']
        ];
        
        $stmt = $db->prepare("INSERT IGNORE INTO roles (name, display_name, description, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        foreach ($defaultRoles as $role) {
            $stmt->execute($role);
        }
        echo "<span class='success'>✓ Created default roles</span>\n";
        
        // Capabilities
        $defaultCapabilities = [
            'post:create', 'post:edit:own', 'post:edit:any', 'post:delete:own', 'post:delete:any',
            'post:publish', 'media:upload', 'media:edit', 'media:delete',
            'user:create', 'user:edit', 'user:delete', 'user:promote',
            'plugin:activate', 'plugin:deactivate', 'settings:edit', 'theme:edit'
        ];
        
        $stmt = $db->prepare("INSERT IGNORE INTO capabilities (name) VALUES (?)");
        foreach ($defaultCapabilities as $cap) {
            $stmt->execute([$cap]);
        }
        echo "<span class='success'>✓ Created default capabilities</span>\n";
        
        // Assign capabilities to admin
        $adminRoleId = $db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();
        $capIds = $db->query("SELECT id FROM capabilities")->fetchAll(\PDO::FETCH_COLUMN);
        $stmt = $db->prepare("INSERT IGNORE INTO role_capabilities (role_id, capability_id) VALUES (?, ?)");
        foreach ($capIds as $capId) {
            $stmt->execute([$adminRoleId, $capId]);
        }
        echo "<span class='success'>✓ Assigned capabilities to admin role</span>\n";
        
        // Admin user
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            $username = 'admin';
            $email = 'admin@example.com';
            $password = password_hash('admin', PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users 
                (username, email, password, status, email_verified, created_at, updated_at)
                VALUES (?, ?, ?, 'active', 1, NOW(), NOW())
            ");
            
            $stmt->execute([$username, $email, $password]);
            echo "<span class='success'>✓ Created default admin user (username: admin, password: admin)</span>\n";
        } else {
            echo "<span class='info'>→ Admin user already exists</span>\n";
        }
        
        // Record migration
        try {
            $stmt = $db->prepare("INSERT IGNORE INTO schema_migrations (version, name) VALUES (?, ?)");
            $stmt->execute(['001', 'core_schema']);
            echo "<span class='success'>✓ Recorded migration</span>\n";
        } catch (\Exception $e) {
            // Ignore
        }
        
    } catch (\Exception $e) {
        echo "<span class='error'>✗ Error creating default data: " . htmlspecialchars($e->getMessage()) . "</span>\n";
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
    
} else {
    echo "<span class='error'>ERROR: Could not extract SQL from migration file</span>\n";
    echo "File content preview:\n";
    echo htmlspecialchars(substr($migrationContent, 0, 500)) . "...\n";
}

?>
    </pre>
    
    <p><a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/install.php">← Back to Install Page</a></p>
    <p><a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/test-all-pages.php">Run Tests →</a></p>
</body>
</html>


