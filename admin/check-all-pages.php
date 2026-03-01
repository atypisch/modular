<?php
/**
 * Comprehensive Page Checker
 * Checks all admin pages for errors and missing files
 */

require_once __DIR__ . '/config/config.php';
require_once CORE_PATH . '/functions/database.php';

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
    <title>Modular CMS - Page Checker</title>
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
    <h1>Modular CMS - Page Checker</h1>
    
    <?php
    $allTests = [];
    $db = $GLOBALS['db'] ?? null;
    
    // Test 1: Database Connection
    echo '<div class="test-section">';
    echo '<h2>1. Database Connection</h2>';
    if ($db) {
        echo '<div class="test-item pass">✓ Database connection: OK</div>';
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
    
    // Test 2: Check Database Tables
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
    
    if (!empty($missingTables)) {
        echo '<div class="test-item warning">Missing: ' . implode(', ', $missingTables) . '</div>';
        echo '<div class="test-item"><a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/install.php">Install Missing Tables</a></div>';
        $allTests['tables'] = false;
    } else {
        echo '<div class="test-item pass">✓ All required tables exist!</div>';
        $allTests['tables'] = true;
    }
    echo '</div>';
    
    // Test 3: Check All Admin Pages
    echo '<div class="test-section">';
    echo '<h2>3. Admin Pages</h2>';
    
    $adminPages = [
        // Dashboard
        'dashboard/index.php' => 'Dashboard',
        'dashboard/analytics.php' => 'Dashboard Analytics',
        'dashboard/reports.php' => 'Dashboard Reports',
        
        // Post Types
        'post-types/index.php' => 'Post Types List',
        'post-types/edit.php' => 'Post Types Edit',
        'post-types/save.php' => 'Post Types Save',
        'post-types/delete.php' => 'Post Types Delete',
        'post-types/fields.php' => 'Post Types Fields',
        'post-types/field-edit.php' => 'Post Types Field Edit',
        'post-types/field-save.php' => 'Post Types Field Save',
        'post-types/field-delete.php' => 'Post Types Field Delete',
        'post-types/field-order.php' => 'Post Types Field Order',
        'post-types/component-builder.php' => 'Post Types Component Builder',
        
        // Posts
        'posts/index.php' => 'Posts List',
        'posts/edit.php' => 'Posts Edit',
        'posts/save.php' => 'Posts Save',
        'posts/delete.php' => 'Posts Delete',
        
        // Media
        'media/index.php' => 'Media Library',
        'media/upload.php' => 'Media Upload',
        'media/edit.php' => 'Media Edit',
        'media/delete.php' => 'Media Delete',
        'media/upload-handler.php' => 'Media Upload Handler',
        
        // Forms
        'forms/index.php' => 'Forms List',
        'forms/builder.php' => 'Forms Builder',
        'forms/delete.php' => 'Forms Delete',
        
        // Taxonomies
        'taxonomies/index.php' => 'Taxonomies List',
        'taxonomies/edit.php' => 'Taxonomies Edit',
        'taxonomies/save.php' => 'Taxonomies Save',
        'taxonomies/delete.php' => 'Taxonomies Delete',
        'taxonomies/terms.php' => 'Taxonomies Terms',
        'taxonomies/terms/edit.php' => 'Taxonomies Terms Edit',
        'taxonomies/terms/save.php' => 'Taxonomies Terms Save',
        'taxonomies/terms/delete.php' => 'Taxonomies Terms Delete',
        
        // Users
        'users/index.php' => 'Users List',
        'users/edit.php' => 'Users Edit',
        'users/save.php' => 'Users Save',
        'users/delete.php' => 'Users Delete',
        'users/roles.php' => 'Users Roles',
        
        // Settings
        'settings/index.php' => 'Settings',
        'settings/appearance.php' => 'Settings Appearance',
        'settings/security.php' => 'Settings Security',
        
        // Actions
        'actions/index.php' => 'Actions',
        
        // Profile
        'profile/index.php' => 'Profile',
        
        // Modules
        'modules/index.php' => 'Modules',
    ];
    
    $missingPages = [];
    $errorPages = [];
    
    foreach ($adminPages as $page => $name) {
        $filePath = __DIR__ . '/' . $page;
        if (file_exists($filePath)) {
            // Try to check for syntax errors
            $output = [];
            $return = 0;
            exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $return);
            
            if ($return === 0) {
                echo '<div class="test-item pass">✓ ' . $name . ': OK</div>';
            } else {
                echo '<div class="test-item fail">✗ ' . $name . ': Syntax Error</div>';
                $errorPages[] = $page;
            }
        } else {
            echo '<div class="test-item fail">✗ ' . $name . ': MISSING</div>';
            $missingPages[] = $page;
        }
    }
    
    if (empty($missingPages) && empty($errorPages)) {
        $allTests['pages'] = true;
    } else {
        $allTests['pages'] = false;
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

