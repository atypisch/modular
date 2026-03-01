<?php
/**
 * Database Installation Page
 * Run this page to install all database tables
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/config/config.php';

// Check if already installed
$installedFile = __DIR__ . '/config/installed.php';
$isInstalled = file_exists($installedFile);

// Handle installation
$error = null;
$success = false;
$output = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        $db = $GLOBALS['db'];
        $output = '';
        $errors = [];
        
        // Read the SQL from the migration file
        $migrationFile = CORE_PATH . '/migrations/001_core_schema.php';
        
        if (!file_exists($migrationFile)) {
            throw new Exception("Migration file not found: {$migrationFile}");
        }
        
        $migrationContent = file_get_contents($migrationFile);
        
        // Extract SQL from the migration file - handle multi-line strings with escaped quotes
        $sql = null;
        
        // Try double quotes first (most common)
        if (preg_match('/\$sql\s*=\s*"((?:[^"\\\\]|\\\\.)*)"/s', $migrationContent, $matches)) {
            $sql = $matches[1];
            // Unescape
            $sql = str_replace('\\"', '"', $sql);
            $sql = str_replace('\\n', "\n", $sql);
        } 
        // Try single quotes
        elseif (preg_match("/\$sql\s*=\s*'((?:[^'\\\\]|\\\\.)*)'/s", $migrationContent, $matches)) {
            $sql = $matches[1];
            $sql = str_replace("\\'", "'", $sql);
            $sql = str_replace('\\n', "\n", $sql);
        }
        // Fallback to simpler regex
        elseif (preg_match('/\$sql\s*=\s*["\']([^"\']+)["\']/s', $migrationContent, $matches)) {
            $sql = $matches[1];
        }
        
        if ($sql === null) {
            throw new Exception("Could not extract SQL from migration file. Please use direct install method at /modular/admin/install-tables-direct.php");
        }
        
        // Remove comment lines
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        
        // Split into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
                try {
                    $db->exec($statement);
                    // Extract table name from CREATE TABLE statement
                    if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?(\w+)`?/i', $statement, $tableMatch)) {
                        $output .= "✓ Created table: {$tableMatch[1]}\n";
                    }
                } catch (\PDOException $e) {
                    // If table already exists, that's okay
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate') === false) {
                        $errors[] = $e->getMessage();
                        $output .= "✗ Error: " . $e->getMessage() . "\n";
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
            $output .= "✓ Created default roles\n";
            
            $stmt = $db->prepare("INSERT IGNORE INTO capabilities (name) VALUES (?)");
            foreach ($defaultCapabilities as $cap) {
                $stmt->execute([$cap]);
            }
            $output .= "✓ Created default capabilities\n";
            
            // Assign all capabilities to admin role
            $adminRoleId = $db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();
            $capIds = $db->query("SELECT id FROM capabilities")->fetchAll(\PDO::FETCH_COLUMN);
            $stmt = $db->prepare("INSERT IGNORE INTO role_capabilities (role_id, capability_id) VALUES (?, ?)");
            foreach ($capIds as $capId) {
                $stmt->execute([$adminRoleId, $capId]);
            }
            $output .= "✓ Assigned capabilities to admin role\n";
            
        } catch (\Exception $e) {
            $errors[] = "Error creating default data: " . $e->getMessage();
            $output .= "✗ Error creating default data: " . $e->getMessage() . "\n";
        }
        
        // Record migration
        try {
            $stmt = $db->prepare("INSERT IGNORE INTO schema_migrations (version, name) VALUES (?, ?)");
            $stmt->execute(['001', 'core_schema']);
        } catch (\Exception $e) {
            // Ignore if already recorded
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
                $output .= "✓ Default admin user created:\n  Username: admin\n  Password: admin\n";
            }
        } catch (\Exception $e) {
            $errors[] = "Error creating admin user: " . $e->getMessage();
            $output .= "✗ Error creating admin user: " . $e->getMessage() . "\n";
        }
        
        // Create the installed file
        try {
            file_put_contents($installedFile, '<?php return true;');
        } catch (\Exception $e) {
            // Ignore file write errors
        }
        
        if (empty($errors)) {
            $success = true;
        } else {
            throw new Exception("Installation completed with errors:\n" . implode("\n", $errors));
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        if (!empty($output)) {
            $error .= "\n\nOutput:\n" . $output;
        }
    }
}

// Check which tables exist
$existingTables = [];
if (isset($GLOBALS['db'])) {
    try {
        $stmt = $GLOBALS['db']->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        // Ignore errors
    }
}

$requiredTables = [
    'users', 'roles', 'capabilities', 'role_capabilities', 'user_roles',
    'post_types', 'posts', 'component_definitions', 'media', 'relationships',
    'taxonomies', 'terms', 'post_terms', 'post_type_taxonomies',
    'forms', 'form_submissions', 'actions', 'action_queue', 'settings',
    'post_revisions', 'activity_log', 'schema_migrations'
];

$missingTables = array_diff($requiredTables, $existingTables);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modular CMS - Database Installation</title>
    <link rel="stylesheet" href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/assets/css/base.css">
    <style>
        .install-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .status-box {
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .status-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .status-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .status-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .table-list {
            list-style: none;
            padding: 0;
        }
        .table-list li {
            padding: 0.5rem;
            margin: 0.25rem 0;
            border-radius: 4px;
        }
        .table-exists {
            background: #d4edda;
            color: #155724;
        }
        .table-missing {
            background: #fff3cd;
            color: #856404;
        }
        pre {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #2d5016;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
        }
        .btn:hover {
            background: #1f350f;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <h1>Modular CMS - Database Installation</h1>
        
        <?php if ($success): ?>
            <div class="status-box status-success">
                <h2>✓ Installation Successful!</h2>
                <p>All database tables have been created successfully.</p>
                <?php if ($output): ?>
                    <pre><?php echo htmlspecialchars($output); ?></pre>
                <?php endif; ?>
                <p><a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/auth/login.php" class="btn">Go to Login</a></p>
            </div>
        <?php elseif ($error): ?>
            <div class="status-box status-error">
                <h2>✗ Installation Error</h2>
                <p><?php echo htmlspecialchars($error); ?></p>
                <?php if ($output): ?>
                    <pre><?php echo htmlspecialchars($output); ?></pre>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="status-box status-info">
            <h2>Database Status</h2>
            <p><strong>Database:</strong> <?php echo DB_NAME; ?></p>
            <p><strong>Host:</strong> <?php echo DB_HOST; ?>:<?php echo DB_PORT; ?></p>
            <p><strong>Connection:</strong> <?php echo isset($GLOBALS['db']) ? '✓ Connected' : '✗ Not Connected'; ?></p>
        </div>
        
        <?php if (!empty($existingTables) || !empty($missingTables)): ?>
            <div class="status-box">
                <h2>Table Status</h2>
                <p><strong>Existing Tables (<?php echo count($existingTables); ?>):</strong></p>
                <ul class="table-list">
                    <?php foreach ($existingTables as $table): ?>
                        <li class="table-exists">✓ <?php echo htmlspecialchars($table); ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if (!empty($missingTables)): ?>
                    <p><strong>Missing Tables (<?php echo count($missingTables); ?>):</strong></p>
                    <ul class="table-list">
                        <?php foreach ($missingTables as $table): ?>
                            <li class="table-missing">✗ <?php echo htmlspecialchars($table); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><strong>✓ All required tables exist!</strong></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$success && !$isInstalled): ?>
            <form method="POST" style="margin-bottom: 1rem;">
                <div class="status-box status-info">
                    <h2>Ready to Install</h2>
                    <p>Click the button below to create all necessary database tables.</p>
                    <p><strong>Warning:</strong> This will create all tables. If tables already exist, they will not be overwritten (using CREATE TABLE IF NOT EXISTS).</p>
                </div>
                <button type="submit" name="install" class="btn">Install Database Tables</button>
            </form>
            
            <?php if (!empty($missingTables)): ?>
            <div class="status-box status-info">
                <h2>Alternative Installation</h2>
                <p>If the installation above doesn't work, try the direct installation methods:</p>
                <div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/install-tables-direct.php" class="btn">Direct Install All Tables</a>
                    <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/create-missing-tables.php" class="btn">Create Missing Tables Only</a>
                    <?php if (in_array('component_definitions', $missingTables)): ?>
                    <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/fix-component-definitions.php" class="btn">Fix component_definitions</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php elseif ($isInstalled && empty($missingTables)): ?>
            <div class="status-box status-success">
                <h2>✓ Installation Complete</h2>
                <p>All database tables are installed and ready.</p>
                <p><a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/auth/login.php" class="btn">Go to Login</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

