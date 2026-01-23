<?php
/**
 * Test and Auto-Install Script
 * Checks all tables and installs missing ones
 */

require_once __DIR__ . '/config/config.php';
require_once CORE_PATH . '/functions/database.php';

header('Content-Type: text/plain');

echo "=== Modular CMS - Database Check & Auto-Install ===\n\n";

// Check database connection
if (!isset($GLOBALS['db'])) {
    die("ERROR: Database connection not available\n");
}

$db = $GLOBALS['db'];
echo "✓ Database connection: OK\n";
echo "Database: " . DB_NAME . "\n";
echo "Host: " . DB_HOST . ":" . DB_PORT . "\n\n";

// Check existing tables
echo "Checking existing tables...\n";
$stmt = $db->query("SHOW TABLES");
$existingTables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
echo "Found " . count($existingTables) . " existing tables\n\n";

// Required tables
$requiredTables = [
    'users', 'roles', 'capabilities', 'role_capabilities', 'user_roles',
    'post_types', 'posts', 'component_definitions', 'media', 'relationships',
    'taxonomies', 'terms', 'post_terms', 'post_type_taxonomies',
    'forms', 'form_submissions', 'actions', 'action_queue', 'settings',
    'post_revisions', 'activity_log', 'schema_migrations'
];

$missingTables = array_diff($requiredTables, $existingTables);

if (empty($missingTables)) {
    echo "✓ All required tables exist!\n\n";
} else {
    echo "✗ Missing " . count($missingTables) . " tables:\n";
    foreach ($missingTables as $table) {
        echo "  - $table\n";
    }
    echo "\n";
    
    // Auto-install
    echo "=== Auto-Installing Missing Tables ===\n\n";
    
    $migrationFile = CORE_PATH . '/migrations/001_core_schema.php';
    if (!file_exists($migrationFile)) {
        die("ERROR: Migration file not found: {$migrationFile}\n");
    }
    
    $migrationContent = file_get_contents($migrationFile);
    
    if (preg_match('/\$sql\s*=\s*["\']([^"\']+)["\']/s', $migrationContent, $matches)) {
        $sql = $matches[1];
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $created = 0;
        $errors = 0;
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
                try {
                    $db->exec($statement);
                    if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?(\w+)`?/i', $statement, $tableMatch)) {
                        $tableName = $tableMatch[1];
                        if (in_array($tableName, $missingTables)) {
                            echo "✓ Created table: $tableName\n";
                            $created++;
                        }
                    }
                } catch (\PDOException $e) {
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate') === false) {
                        echo "✗ Error: " . $e->getMessage() . "\n";
                        $errors++;
                    }
                }
            }
        }
        
        echo "\nCreated $created tables";
        if ($errors > 0) {
            echo " with $errors errors";
        }
        echo "\n\n";
        
        // Create default data
        echo "=== Creating Default Data ===\n\n";
        
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
            echo "✓ Created default roles\n";
            
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
            echo "✓ Created default capabilities\n";
            
            // Assign capabilities to admin
            $adminRoleId = $db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();
            $capIds = $db->query("SELECT id FROM capabilities")->fetchAll(\PDO::FETCH_COLUMN);
            $stmt = $db->prepare("INSERT IGNORE INTO role_capabilities (role_id, capability_id) VALUES (?, ?)");
            foreach ($capIds as $capId) {
                $stmt->execute([$adminRoleId, $capId]);
            }
            echo "✓ Assigned capabilities to admin role\n";
            
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
                echo "✓ Created default admin user (username: admin, password: admin)\n";
            }
            
            // Record migration
            $stmt = $db->prepare("INSERT IGNORE INTO schema_migrations (version, name) VALUES (?, ?)");
            $stmt->execute(['001', 'core_schema']);
            echo "✓ Recorded migration\n";
            
        } catch (\Exception $e) {
            echo "✗ Error creating default data: " . $e->getMessage() . "\n";
        }
    }
}

// Final check
echo "\n=== Final Check ===\n\n";
$stmt = $db->query("SHOW TABLES");
$finalTables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
$stillMissing = array_diff($requiredTables, $finalTables);

if (empty($stillMissing)) {
    echo "✓ SUCCESS: All " . count($requiredTables) . " required tables exist!\n";
} else {
    echo "✗ WARNING: Still missing " . count($stillMissing) . " tables:\n";
    foreach ($stillMissing as $table) {
        echo "  - $table\n";
    }
}

echo "\n=== Test Complete ===\n";


