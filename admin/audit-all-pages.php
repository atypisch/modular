<?php
/**
 * Comprehensive System Audit
 * Checks all pages, database tables, and system integrity
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
    <title>Modular CMS - System Audit</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; font-size: 12px; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .test-section h2 { margin-top: 0; color: #2d5016; border-bottom: 2px solid #2d5016; padding-bottom: 10px; }
        .test-item { padding: 8px; margin: 3px 0; border-left: 4px solid #ddd; font-size: 11px; }
        .test-item.pass { border-color: #28a745; background: #d4edda; }
        .test-item.fail { border-color: #dc3545; background: #f8d7da; }
        .test-item.warning { border-color: #ffc107; background: #fff3cd; }
        .test-item.info { border-color: #17a2b8; background: #d1ecf1; }
        .error { color: #dc3545; font-weight: bold; }
        .success { color: #28a745; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 11px; }
        th, td { padding: 6px; text-align: left; border: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .summary { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Modular CMS - Comprehensive System Audit</h1>
    <p><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <?php
    $allTests = [];
    $db = $GLOBALS['db'] ?? null;
    $errors = [];
    $warnings = [];
    $info = [];
    
    // ============================================
    // 1. DATABASE CONNECTION
    // ============================================
    echo '<div class="test-section">';
    echo '<h2>1. Database Connection</h2>';
    if ($db) {
        echo '<div class="test-item pass">✓ Database connection: OK</div>';
        echo '<div class="test-item info">Database: ' . DB_NAME . '</div>';
        echo '<div class="test-item info">Host: ' . DB_HOST . ':' . DB_PORT . '</div>';
        $allTests['db_connection'] = true;
    } else {
        echo '<div class="test-item fail">✗ Database connection: FAILED</div>';
        $allTests['db_connection'] = false;
        $errors[] = 'Database connection failed';
    }
    echo '</div>';
    
    if (!$db) {
        echo '<div class="test-item error">Cannot continue without database connection.</div>';
        exit;
    }
    
    // ============================================
    // 2. DATABASE TABLES
    // ============================================
    echo '<div class="test-section">';
    echo '<h2>2. Database Tables</h2>';
    
    $requiredTables = [
        'users', 'roles', 'capabilities', 'role_capabilities', 'user_roles',
        'post_types', 'posts', 'component_definitions', 'media', 'relationships',
        'taxonomies', 'terms', 'post_terms', 'post_type_taxonomies',
        'forms', 'form_submissions', 'actions', 'action_queue', 'settings',
        'post_revisions', 'activity_log', 'schema_migrations', 'modules'
    ];
    
    try {
        $stmt = $db->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $missingTables = array_diff($requiredTables, $existingTables);
        
        echo '<div class="test-item info">Existing tables: ' . count($existingTables) . '</div>';
        echo '<div class="test-item info">Required tables: ' . count($requiredTables) . '</div>';
        
        if (!empty($missingTables)) {
            echo '<div class="test-item warning">⚠ Missing tables: ' . implode(', ', $missingTables) . '</div>';
            echo '<div class="test-item"><a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/install.php">Install Missing Tables</a> | <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/install-modules-table.php">Install Modules Table</a></div>';
            $allTests['tables'] = false;
            $warnings[] = 'Missing database tables: ' . implode(', ', $missingTables);
        } else {
            echo '<div class="test-item pass">✓ All required tables exist!</div>';
            $allTests['tables'] = true;
        }
        
        // Check table structures
        echo '<h3>Table Structure Check</h3>';
        $criticalTables = ['users', 'post_types', 'posts', 'modules'];
        foreach ($criticalTables as $table) {
            if (in_array($table, $existingTables)) {
                try {
                    $stmt = $db->query("DESCRIBE `{$table}`");
                    $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    echo '<div class="test-item pass">✓ Table `' . $table . '` structure OK (' . count($columns) . ' columns)</div>';
                } catch (\PDOException $e) {
                    echo '<div class="test-item fail">✗ Error checking table `' . $table . '`: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    $errors[] = "Error checking table structure: {$table}";
                }
            }
        }
    } catch (\PDOException $e) {
        echo '<div class="test-item fail">✗ Error checking tables: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $errors[] = 'Error checking database tables';
        $allTests['tables'] = false;
    }
    echo '</div>';
    
    // ============================================
    // 3. CORE CLASSES
    // ============================================
    echo '<div class="test-section">';
    echo '<h2>3. Core Classes</h2>';
    
    $coreClasses = [
        'Modular\\Hooks' => CORE_PATH . '/classes/Hooks.php',
        'Modular\\Module' => CORE_PATH . '/classes/Module.php',
        'Modular\\ModuleManager' => CORE_PATH . '/classes/ModuleManager.php',
        'Modular\\PostType' => CORE_PATH . '/classes/PostType.php',
        'Modular\\Taxonomy' => CORE_PATH . '/classes/Taxonomy.php',
        'Modular\\Migration' => CORE_PATH . '/classes/Migration.php',
    ];
    
    foreach ($coreClasses as $className => $filePath) {
        if (file_exists($filePath)) {
            try {
                require_once $filePath;
                if (class_exists($className)) {
                    echo '<div class="test-item pass">✓ ' . $className . ': OK</div>';
                } else {
                    echo '<div class="test-item fail">✗ ' . $className . ': Class not found after require</div>';
                    $errors[] = "Class not found: {$className}";
                }
            } catch (\Exception $e) {
                echo '<div class="test-item fail">✗ ' . $className . ': Error - ' . htmlspecialchars($e->getMessage()) . '</div>';
                $errors[] = "Error loading class: {$className} - " . $e->getMessage();
            }
        } else {
            echo '<div class="test-item fail">✗ ' . $className . ': File not found</div>';
            $errors[] = "Class file not found: {$filePath}";
        }
    }
    echo '</div>';
    
    // ============================================
    // 4. ADMIN PAGES
    // ============================================
    echo '<div class="test-section">';
    echo '<h2>4. Admin Pages</h2>';
    
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
        
        // Forms
        'forms/index.php' => 'Forms List',
        'forms/builder.php' => 'Forms Builder',
        'forms/delete.php' => 'Forms Delete',
        
        // Taxonomies
        'taxonomies/index.php' => 'Taxonomies List',
        'taxonomies/edit.php' => 'Taxonomies Edit',
        'taxonomies/save.php' => 'Taxonomies Save',
        'taxonomies/delete.php' => 'Taxonomies Delete',
        
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
    $pageCount = 0;
    $pagePassCount = 0;
    
    foreach ($adminPages as $page => $name) {
        $pageCount++;
        $filePath = __DIR__ . '/' . $page;
        if (file_exists($filePath)) {
            // Check for syntax errors
            $output = [];
            $return = 0;
            @exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $return);
            
            if ($return === 0) {
                echo '<div class="test-item pass">✓ ' . $name . '</div>';
                $pagePassCount++;
            } else {
                $errorMsg = implode(' ', $output);
                echo '<div class="test-item fail">✗ ' . $name . ': Syntax Error</div>';
                echo '<div class="test-item info" style="margin-left: 20px;">' . htmlspecialchars($errorMsg) . '</div>';
                $errorPages[] = $page;
                $errors[] = "Syntax error in: {$page}";
            }
        } else {
            echo '<div class="test-item fail">✗ ' . $name . ': MISSING</div>';
            $missingPages[] = $page;
            $errors[] = "Missing page: {$page}";
        }
    }
    
    echo '<div class="test-item info">Pages checked: ' . $pageCount . ' | Passed: ' . $pagePassCount . ' | Failed: ' . (count($errorPages) + count($missingPages)) . '</div>';
    
    if (empty($missingPages) && empty($errorPages)) {
        $allTests['pages'] = true;
    } else {
        $allTests['pages'] = false;
    }
    echo '</div>';
    
    // ============================================
    // 5. CORE FUNCTIONS
    // ============================================
    echo '<div class="test-section">';
    echo '<h2>5. Core Functions</h2>';
    
    $requiredFunctions = [
        'is_logged_in',
        'check_active_path',
        'is_post_types_page',
        'is_posts_page',
        'is_media_page',
        'is_users_page',
        'is_settings_page',
        'is_forms_page',
        'is_actions_page',
        'is_modules_page',
        'add_action',
        'do_action',
        'add_filter',
        'apply_filters',
    ];
    
    foreach ($requiredFunctions as $func) {
        if (function_exists($func)) {
            echo '<div class="test-item pass">✓ ' . $func . '()</div>';
        } else {
            echo '<div class="test-item fail">✗ ' . $func . '(): NOT FOUND</div>';
            $errors[] = "Function not found: {$func}";
        }
    }
    echo '</div>';
    
    // ============================================
    // 6. FILE PERMISSIONS & PATHS
    // ============================================
    echo '<div class="test-section">';
    echo '<h2>6. File Permissions & Paths</h2>';
    
    $criticalPaths = [
        ADMIN_PATH => 'Admin Path',
        CORE_PATH => 'Core Path',
        PUBLIC_PATH => 'Public Path',
        PUBLIC_PATH . '/modules' => 'Modules Directory',
        PUBLIC_PATH . '/themes' => 'Themes Directory',
        PUBLIC_PATH . '/uploads' => 'Uploads Directory',
    ];
    
    foreach ($criticalPaths as $path => $name) {
        if (is_dir($path)) {
            $writable = is_writable($path) ? 'writable' : 'not writable';
            $status = is_writable($path) ? 'pass' : 'warning';
            echo '<div class="test-item ' . $status . '">' . ($status === 'pass' ? '✓' : '⚠') . ' ' . $name . ': ' . $writable . '</div>';
            if (!$writable && strpos($path, 'uploads') !== false) {
                $warnings[] = "Uploads directory not writable: {$path}";
            }
        } else {
            echo '<div class="test-item fail">✗ ' . $name . ': NOT FOUND</div>';
            $errors[] = "Directory not found: {$path}";
        }
    }
    echo '</div>';
    
    // ============================================
    // 7. MODULE SYSTEM
    // ============================================
    echo '<div class="test-section">';
    echo '<h2>7. Module System</h2>';
    
    // Check modules table
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'modules'");
        if ($stmt->rowCount() > 0) {
            echo '<div class="test-item pass">✓ Modules table exists</div>';
            
            // Check for modules
            $modulesDir = PUBLIC_PATH . '/modules';
            if (is_dir($modulesDir)) {
                $moduleDirs = array_filter(glob($modulesDir . '/*'), 'is_dir');
                echo '<div class="test-item info">Modules found: ' . count($moduleDirs) . '</div>';
                
                foreach ($moduleDirs as $dir) {
                    $moduleFile = $dir . '/module.php';
                    if (file_exists($moduleFile)) {
                        echo '<div class="test-item pass">✓ Module: ' . basename($dir) . '</div>';
                    } else {
                        echo '<div class="test-item warning">⚠ Module directory without module.php: ' . basename($dir) . '</div>';
                    }
                }
            } else {
                echo '<div class="test-item warning">⚠ Modules directory not found</div>';
            }
        } else {
            echo '<div class="test-item warning">⚠ Modules table does not exist</div>';
            echo '<div class="test-item"><a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/install-modules-table.php">Install Modules Table</a></div>';
            $warnings[] = 'Modules table not installed';
        }
    } catch (\PDOException $e) {
        echo '<div class="test-item fail">✗ Error checking modules: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $errors[] = 'Error checking modules table';
    }
    echo '</div>';
    
    // ============================================
    // SUMMARY
    // ============================================
    echo '<div class="test-section summary">';
    echo '<h2>Audit Summary</h2>';
    
    $passed = count(array_filter($allTests));
    $total = count($allTests);
    
    echo '<div class="test-item">Total test categories: ' . $total . '</div>';
    echo '<div class="test-item ' . ($passed == $total ? 'pass' : 'warning') . '">Passed: ' . $passed . '</div>';
    echo '<div class="test-item ' . (($total - $passed) == 0 ? 'pass' : 'fail') . '">Failed: ' . ($total - $passed) . '</div>';
    
    if (!empty($errors)) {
        echo '<h3>Errors (' . count($errors) . '):</h3>';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li class="error">' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
    }
    
    if (!empty($warnings)) {
        echo '<h3>Warnings (' . count($warnings) . '):</h3>';
        echo '<ul>';
        foreach ($warnings as $warning) {
            echo '<li class="warning">' . htmlspecialchars($warning) . '</li>';
        }
        echo '</ul>';
    }
    
    if ($passed == $total && empty($errors)) {
        echo '<div class="test-item success" style="font-size: 16px; padding: 15px; margin-top: 20px;">✓ ALL TESTS PASSED! System is healthy.</div>';
    } else {
        echo '<div class="test-item error" style="font-size: 16px; padding: 15px; margin-top: 20px;">✗ Some issues found. Please review above.</div>';
    }
    echo '</div>';
    ?>
</body>
</html>

