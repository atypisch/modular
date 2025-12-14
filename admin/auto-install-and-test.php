<?php
/**
 * Auto-Install and Test Script
 * Automatically installs missing tables and tests all functionality
 */

require_once __DIR__ . '/config/config.php';
require_once CORE_PATH . '/functions/database.php';
require_once CORE_PATH . '/classes/PostType.php';
require_once CORE_PATH . '/classes/Taxonomy.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';

$db = $GLOBALS['db'] ?? null;

if (!$db) {
    die("ERROR: Database connection not available\n");
}

echo "=== Modular CMS - Auto Install & Test ===\n\n";

// Step 1: Check and install tables
echo "Step 1: Checking database tables...\n";
$requiredTables = [
    'users', 'roles', 'capabilities', 'role_capabilities', 'user_roles',
    'post_types', 'posts', 'component_definitions', 'media', 'relationships',
    'taxonomies', 'terms', 'post_terms', 'post_type_taxonomies',
    'forms', 'form_submissions', 'actions', 'action_queue', 'settings',
    'post_revisions', 'activity_log', 'schema_migrations'
];

$stmt = $db->query("SHOW TABLES");
$existingTables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
$missingTables = array_diff($requiredTables, $existingTables);

if (!empty($missingTables)) {
    echo "Installing " . count($missingTables) . " missing tables...\n";
    
    $migrationFile = CORE_PATH . '/migrations/001_core_schema.php';
    if (file_exists($migrationFile)) {
        $migrationContent = file_get_contents($migrationFile);
        
        if (preg_match('/\$sql\s*=\s*["\']([^"\']+)["\']/s', $migrationContent, $matches)) {
            $sql = $matches[1];
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
                    try {
                        $db->exec($statement);
                    } catch (\PDOException $e) {
                        if (strpos($e->getMessage(), 'already exists') === false && 
                            strpos($e->getMessage(), 'Duplicate') === false) {
                            echo "ERROR: " . $e->getMessage() . "\n";
                        }
                    }
                }
            }
            
            // Create default data
            try {
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
                
                $adminRoleId = $db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();
                $capIds = $db->query("SELECT id FROM capabilities")->fetchAll(\PDO::FETCH_COLUMN);
                $stmt = $db->prepare("INSERT IGNORE INTO role_capabilities (role_id, capability_id) VALUES (?, ?)");
                foreach ($capIds as $capId) {
                    $stmt->execute([$adminRoleId, $capId]);
                }
                
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
                }
                
                $stmt = $db->prepare("INSERT IGNORE INTO schema_migrations (version, name) VALUES (?, ?)");
                $stmt->execute(['001', 'core_schema']);
                
                echo "✓ Tables installed and default data created\n";
            } catch (\Exception $e) {
                echo "ERROR creating default data: " . $e->getMessage() . "\n";
            }
        }
    }
} else {
    echo "✓ All tables exist\n";
}

// Step 2: Test Post Type creation
echo "\nStep 2: Testing Post Type creation...\n";
try {
    $postType = new PostType($db);
    $testData = [
        'name' => 'Test Post Type',
        'slug' => 'test-post-type',
        'description' => 'Test description',
        'public' => 1,
        'show_in_menu' => 1
    ];
    
    // Check if test post type already exists
    $existing = $postType->getBySlug('test-post-type');
    if ($existing) {
        echo "Test post type already exists, skipping creation\n";
    } else {
        $testId = $postType->create($testData);
        if ($testId) {
            echo "✓ Post type created successfully (ID: $testId)\n";
            
            // Verify it appears in getAll()
            $allPostTypes = $postType->getAll();
            $found = false;
            foreach ($allPostTypes as $pt) {
                if ($pt['id'] == $testId) {
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                echo "✓ Post type appears in navigation list\n";
            } else {
                echo "✗ Post type NOT found in navigation list\n";
            }
        } else {
            echo "✗ Failed to create post type\n";
        }
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Step 3: Verify navigation integration
echo "\nStep 3: Verifying navigation integration...\n";
try {
    $postType = new PostType($db);
    $allPostTypes = $postType->getAll();
    $menuPostTypes = array_filter($allPostTypes, function($pt) {
        return !empty($pt['show_in_menu']);
    });
    
    echo "Post types configured for menu: " . count($menuPostTypes) . "\n";
    foreach ($menuPostTypes as $pt) {
        echo "  - " . $pt['name'] . " (slug: " . $pt['slug'] . ")\n";
    }
    
    if (count($menuPostTypes) > 0) {
        echo "✓ Navigation integration working\n";
    } else {
        echo "⚠ No post types configured for menu yet\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Step 4: Final verification
echo "\nStep 4: Final verification...\n";
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

echo "\n=== Installation Complete ===\n";
echo "You can now:\n";
echo "1. Visit /modular/admin/test-all-pages.php for detailed testing\n";
echo "2. Visit /modular/admin/post-types to create post types\n";
echo "3. Created post types will automatically appear in navigation\n";

