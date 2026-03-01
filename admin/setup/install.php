<?php
// Database setup page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load initialization file
require_once __DIR__ . '/../init.php';

// Check if already installed
if (file_exists(__DIR__ . '/../config/installed.php')) {
    die('CMS is already installed. Remove the installed.php file to reinstall.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modular CMS Setup</title>
    <link href="../assets/css/install.css" rel="stylesheet">
</head>
<body class="install-page">
    <div class="min-h-screen">
        <div class="install-card">
            <h1 class="mb-6">Modular CMS Setup</h1>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    // Load configuration
                    require_once __DIR__ . '/../config/config.php';
                    
                    // Load Migration class
                    require_once CORE_PATH . '/classes/Migration.php';
                    
                    use Modular\Migration;
                    
                    $migration = new Migration($GLOBALS['db']);
                    
                    // Run the core schema migration
                    $migrationFile = CORE_PATH . '/migrations/001_core_schema.php';
                    
                    if (!file_exists($migrationFile)) {
                        throw new Exception("Migration file not found: {$migrationFile}");
                    }
                    
                    // Include and execute the migration
                    ob_start();
                    require $migrationFile;
                    $output = ob_get_clean();
                    
                    // Create default admin user if users table is empty
                    $stmt = $GLOBALS['db']->query("SELECT COUNT(*) as count FROM users");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result['count'] == 0) {
                        $username = 'admin';
                        $email = 'admin@example.com';
                        $password = password_hash('admin', PASSWORD_DEFAULT);
                        
                        $stmt = $GLOBALS['db']->prepare("
                            INSERT INTO users 
                            (username, email, password, status, email_verified, created_at, updated_at)
                            VALUES (?, ?, ?, 'active', 1, NOW(), NOW())
                        ");
                        
                        $stmt->execute([$username, $email, $password]);
                    }
                    
                    // Create the installed file
                    file_put_contents(__DIR__ . '/../config/installed.php', '<?php return true;');
                    
                    echo '<div class="msg-success border rounded px-4 py-3 mb-4">
                        <strong>Installation completed successfully!</strong><br>
                        All database tables have been created.<br>
                        ' . ($result['count'] == 0 ? 'Default admin user created:<br>Username: <strong>admin</strong><br>Password: <strong>admin</strong><br>Please change these credentials immediately!<br>' : '') . '
                        <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/auth/login.php" class="underline font-bold">Click here to login</a>
                    </div>';
                } catch (Exception $e) {
                    echo '<div class="msg-error border rounded px-4 py-3 mb-4">
                        <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
                    </div>';
                }
            } else {
            ?>
                <form method="post" class="space-y-6">
                    <div class="msg-warn border rounded px-4 py-3">
                        This will create all necessary database tables and a default admin user.
                        Make sure your database configuration is correct before proceeding.
                    </div>
                    <div>
                        <button type="submit">Install Database Tables</button>
                    </div>
                </form>
            <?php } ?>
        </div>
    </div>
</body>
</html>
