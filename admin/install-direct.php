<?php
/**
 * Direct Database Installation
 * Creates all tables without using migration system
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';

if (!isset($GLOBALS['db'])) {
    die("Database connection not available");
}

$db = $GLOBALS['db'];
$errors = [];
$success = [];

// Read the SQL from the migration file
$migrationFile = CORE_PATH . '/migrations/001_core_schema.php';
if (!file_exists($migrationFile)) {
    die("Migration file not found: {$migrationFile}");
}

$migrationContent = file_get_contents($migrationFile);

// Extract SQL from the migration file
if (preg_match('/\$sql\s*=\s*["\']([^"\']+)["\']/s', $migrationContent, $matches)) {
    $sql = $matches[1];
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
            try {
                $db->exec($statement);
                // Extract table name from CREATE TABLE statement
                if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?(\w+)`?/i', $statement, $tableMatch)) {
                    $success[] = "Created table: {$tableMatch[1]}";
                }
            } catch (\PDOException $e) {
                // If table already exists, that's okay
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    $errors[] = $e->getMessage();
                }
            }
        }
    }
    
    // Create default roles and capabilities
    try {
        $defaultRoles = [
            ['admin', 'Administrator', 'Full system access'],
            ['editor', 'Editor', 'Can create and edit content'],
            ['author', 'Author', 'Can create and edit own content'],
            ['user', 'User', 'Basic user access']
        ];
        
        $defaultCapabilities = [
            'post:create', 'post:edit:own', 'post:edit:any', 'post:delete:own', 'post:delete:any',
            'post:publish', 'media:upload', 'media:edit', 'media:delete',
            'user:create', 'user:edit', 'user:delete', 'user:promote',
            'plugin:activate', 'plugin:deactivate', 'settings:edit', 'theme:edit'
        ];
        
        $stmt = $db->prepare("INSERT IGNORE INTO roles (name, display_name, description, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        foreach ($defaultRoles as $role) {
            $stmt->execute($role);
        }
        $success[] = "Created default roles";
        
        $stmt = $db->prepare("INSERT IGNORE INTO capabilities (name) VALUES (?)");
        foreach ($defaultCapabilities as $cap) {
            $stmt->execute([$cap]);
        }
        $success[] = "Created default capabilities";
        
        // Assign all capabilities to admin role
        $adminRoleId = $db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();
        $capIds = $db->query("SELECT id FROM capabilities")->fetchAll(\PDO::FETCH_COLUMN);
        $stmt = $db->prepare("INSERT IGNORE INTO role_capabilities (role_id, capability_id) VALUES (?, ?)");
        foreach ($capIds as $capId) {
            $stmt->execute([$adminRoleId, $capId]);
        }
        $success[] = "Assigned capabilities to admin role";
        
    } catch (\Exception $e) {
        $errors[] = "Error creating default data: " . $e->getMessage();
    }
    
    // Create default admin user if users table is empty
    try {
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
            $success[] = "Created default admin user (username: admin, password: admin)";
        }
    } catch (\Exception $e) {
        $errors[] = "Error creating admin user: " . $e->getMessage();
    }
    
    // Record migration
    try {
        $stmt = $db->prepare("INSERT IGNORE INTO schema_migrations (version, name) VALUES (?, ?)");
        $stmt->execute(['001', 'core_schema']);
    } catch (\Exception $e) {
        // Ignore if table doesn't exist yet or already recorded
    }
    
} else {
    $errors[] = "Could not extract SQL from migration file";
}

header('Content-Type: application/json');
echo json_encode([
    'success' => empty($errors),
    'success_messages' => $success,
    'errors' => $errors
]);


