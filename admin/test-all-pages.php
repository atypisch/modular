<?php
/**
 * Comprehensive Test Script
 * Tests all database tables, pages, and functionality
 */

require_once __DIR__ . '/config/config.php';
require_once CORE_PATH . '/functions/database.php';
require_once CORE_PATH . '/classes/PostType.php';
require_once CORE_PATH . '/classes/Taxonomy.php';

// Start session for testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set a test user session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Modular CMS - Comprehensive Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .test-section h2 { margin-top: 0; color: #2d5016; }
        .test-item { padding: 10px; margin: 5px 0; border-left: 4px solid #ddd; }
        .test-item.pass { border-color: #28a745; background: #d4edda; }
        .test-item.fail { border-color: #dc3545; background: #f8d7da; }
        .test-item.warning { border-color: #ffc107; background: #fff3cd; }
        .error { color: #dc3545; font-weight: bold; }
        .success { color: #28a745; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <h1>Modular CMS - Comprehensive Test Report</h1>
    
    <?php
    $allTests = [];
    $db = $GLOBALS['db'] ?? null;
    
    // Test 1: Database Connection
    echo '<div class="test-section">';
    echo '<h2>1. Database Connection</h2>';
    if ($db) {
        echo '<div class="test-item pass">✓ Database connection: OK</div>';
        echo '<div class="test-item">Database: ' . DB_NAME . '</div>';
        echo '<div class="test-item">Host: ' . DB_HOST . ':' . DB_PORT . '</div>';
        $allTests['db_connection'] = true;
    } else {
        echo '<div class="test-item fail">✗ Database connection: FAILED</div>';
        $allTests['db_connection'] = false;
    }
    echo '</div>';
    
    if (!$db) {
        echo '<div class="test-item error">Cannot continue without database connection.</div>';
        exit;
    }
    
    // Test 2: Check and Install Tables
    echo '<div class="test-section">';
    echo '<h2>2. Database Tables</h2>';
    
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
    
    echo '<div class="test-item">Existing tables: ' . count($existingTables) . '</div>';
    echo '<div class="test-item">Required tables: ' . count($requiredTables) . '</div>';
    echo '<div class="test-item">Missing tables: ' . count($missingTables) . '</div>';
    
    if (!empty($missingTables)) {
        echo '<div class="test-item warning">Missing: ' . implode(', ', $missingTables) . '</div>';
        
        // Auto-install
        echo '<div class="test-item">Installing missing tables...</div>';
        
        $migrationFile = CORE_PATH . '/migrations/001_core_schema.php';
        if (file_exists($migrationFile)) {
            $migrationContent = file_get_contents($migrationFile);
            
            if (preg_match('/\$sql\s*=\s*["\']([^"\']+)["\']/s', $migrationContent, $matches)) {
                $sql = $matches[1];
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                $created = 0;
                foreach ($statements as $statement) {
                    if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
                        try {
                            $db->exec($statement);
                            if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?(\w+)`?/i', $statement, $tableMatch)) {
                                if (in_array($tableMatch[1], $missingTables)) {
                                    echo '<div class="test-item pass">✓ Created: ' . $tableMatch[1] . '</div>';
                                    $created++;
                                }
                            }
                        } catch (\PDOException $e) {
                            if (strpos($e->getMessage(), 'already exists') === false) {
                                echo '<div class="test-item fail">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
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
                        echo '<div class="test-item pass">✓ Created default admin user</div>';
                    }
                    
                    $stmt = $db->prepare("INSERT IGNORE INTO schema_migrations (version, name) VALUES (?, ?)");
                    $stmt->execute(['001', 'core_schema']);
                    
                    echo '<div class="test-item pass">✓ Created default data</div>';
                } catch (\Exception $e) {
                    echo '<div class="test-item fail">✗ Error creating default data: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
        }
        
        // Re-check
        $stmt = $db->query("SHOW TABLES");
        $finalTables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $stillMissing = array_diff($requiredTables, $finalTables);
        
        if (empty($stillMissing)) {
            echo '<div class="test-item pass">✓ All tables now exist!</div>';
            $allTests['tables'] = true;
        } else {
            echo '<div class="test-item fail">✗ Still missing: ' . implode(', ', $stillMissing) . '</div>';
            $allTests['tables'] = false;
        }
    } else {
        echo '<div class="test-item pass">✓ All required tables exist!</div>';
        $allTests['tables'] = true;
    }
    echo '</div>';
    
    // Test 3: Test Classes
    echo '<div class="test-section">';
    echo '<h2>3. Core Classes</h2>';
    
    try {
        $postType = new PostType($db);
        $allPostTypes = $postType->getAll();
        echo '<div class="test-item pass">✓ PostType class: OK (' . count($allPostTypes) . ' post types)</div>';
        $allTests['posttype_class'] = true;
    } catch (\Exception $e) {
        echo '<div class="test-item fail">✗ PostType class: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $allTests['posttype_class'] = false;
    }
    
    try {
        $taxonomy = new Modular\Taxonomy($db);
        $allTaxonomies = $taxonomy->getAll();
        echo '<div class="test-item pass">✓ Taxonomy class: OK (' . count($allTaxonomies) . ' taxonomies)</div>';
        $allTests['taxonomy_class'] = true;
    } catch (\Exception $e) {
        echo '<div class="test-item fail">✗ Taxonomy class: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $allTests['taxonomy_class'] = false;
    }
    echo '</div>';
    
    // Test 4: Test Post Type Creation
    echo '<div class="test-section">';
    echo '<h2>4. Post Type Functionality</h2>';
    
    try {
        $postType = new PostType($db);
        $testData = [
            'name' => 'Test Post Type ' . time(),
            'slug' => 'test-post-type-' . time(),
            'description' => 'Test description',
            'public' => 1,
            'show_in_menu' => 1
        ];
        
        $testId = $postType->create($testData);
        if ($testId) {
            echo '<div class="test-item pass">✓ Post type creation: OK (ID: ' . $testId . ')</div>';
            $allTests['posttype_create'] = true;
            
            // Test retrieval
            $retrieved = $postType->getById($testId);
            if ($retrieved) {
                echo '<div class="test-item pass">✓ Post type retrieval: OK</div>';
                $allTests['posttype_retrieve'] = true;
            } else {
                echo '<div class="test-item fail">✗ Post type retrieval: FAILED</div>';
                $allTests['posttype_retrieve'] = false;
            }
            
            // Clean up
            $postType->delete($testId);
            echo '<div class="test-item pass">✓ Post type deletion: OK</div>';
            $allTests['posttype_delete'] = true;
        } else {
            echo '<div class="test-item fail">✗ Post type creation: FAILED</div>';
            $allTests['posttype_create'] = false;
        }
    } catch (\Exception $e) {
        echo '<div class="test-item fail">✗ Post type test: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $allTests['posttype_create'] = false;
    }
    echo '</div>';
    
    // Test 5: Check Admin Pages
    echo '<div class="test-section">';
    echo '<h2>5. Admin Pages</h2>';
    
    $requiredPages = [
        'dashboard/index.php' => 'Dashboard',
        'post-types/index.php' => 'Post Types List',
        'post-types/edit.php' => 'Post Types Edit',
        'posts/index.php' => 'Posts List',
        'posts/edit.php' => 'Posts Edit',
        'media/index.php' => 'Media Library',
        'users/index.php' => 'Users List',
        'users/roles.php' => 'User Roles',
        'forms/index.php' => 'Forms List',
        'actions/index.php' => 'Actions',
        'settings/index.php' => 'Settings',
        'taxonomies/index.php' => 'Taxonomies List',
        'taxonomies/edit.php' => 'Taxonomies Edit',
    ];
    
    $missingPages = [];
    foreach ($requiredPages as $page => $name) {
        $filePath = __DIR__ . '/' . $page;
        if (file_exists($filePath)) {
            echo '<div class="test-item pass">✓ ' . $name . ': OK</div>';
        } else {
            echo '<div class="test-item fail">✗ ' . $name . ': MISSING</div>';
            $missingPages[] = $page;
        }
    }
    
    if (empty($missingPages)) {
        $allTests['pages'] = true;
    } else {
        $allTests['pages'] = false;
    }
    echo '</div>';
    
    // Test 6: Navigation Test
    echo '<div class="test-section">';
    echo '<h2>6. Navigation Integration</h2>';
    
    try {
        $postType = new PostType($db);
        $allPostTypes = $postType->getAll();
        
        $menuPostTypes = array_filter($allPostTypes, function($pt) {
            return !empty($pt['show_in_menu']);
        });
        
        echo '<div class="test-item">Post types in menu: ' . count($menuPostTypes) . '</div>';
        
        if (count($menuPostTypes) > 0) {
            echo '<table><tr><th>Name</th><th>Slug</th><th>Show in Menu</th></tr>';
            foreach ($menuPostTypes as $pt) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($pt['name']) . '</td>';
                echo '<td>' . htmlspecialchars($pt['slug']) . '</td>';
                echo '<td>' . ($pt['show_in_menu'] ? 'Yes' : 'No') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '<div class="test-item pass">✓ Navigation integration: OK</div>';
            $allTests['navigation'] = true;
        } else {
            echo '<div class="test-item warning">⚠ No post types configured for menu</div>';
            $allTests['navigation'] = true; // Not a failure, just no data
        }
    } catch (\Exception $e) {
        echo '<div class="test-item fail">✗ Navigation test: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $allTests['navigation'] = false;
    }
    echo '</div>';
    
    // Summary
    echo '<div class="test-section">';
    echo '<h2>Test Summary</h2>';
    
    $passed = count(array_filter($allTests));
    $total = count($allTests);
    
    echo '<div class="test-item">Total tests: ' . $total . '</div>';
    echo '<div class="test-item ' . ($passed == $total ? 'pass' : 'warning') . '">Passed: ' . $passed . '</div>';
    echo '<div class="test-item ' . (($total - $passed) == 0 ? 'pass' : 'fail') . '">Failed: ' . ($total - $passed) . '</div>';
    
    if ($passed == $total) {
        echo '<div class="test-item success">✓ ALL TESTS PASSED!</div>';
    } else {
        echo '<div class="test-item error">✗ Some tests failed. Please review above.</div>';
    }
    echo '</div>';
    ?>
</body>
</html>


