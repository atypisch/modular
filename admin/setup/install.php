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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <h1 class="text-2xl font-bold mb-6">Modular CMS Setup</h1>
            
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
                    
                    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <strong>Installation completed successfully!</strong><br>
                        All database tables have been created.<br>
                        ' . ($result['count'] == 0 ? 'Default admin user created:<br>Username: <strong>admin</strong><br>Password: <strong>admin</strong><br>Please change these credentials immediately!<br>' : '') . '
                        <a href="/modular/admin/auth/login.php" class="underline font-bold">Click here to login</a>
                    </div>';
                } catch (Exception $e) {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
                    </div>';
                }
            } else {
            ?>
                <form method="post" class="space-y-6">
                    <div class="bg-yellow-50 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                        This will create all necessary database tables and a default admin user.
                        Make sure your database configuration is correct before proceeding.
                    </div>
                    
                    <div>
                        <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Install Database Tables
                        </button>
                    </div>
                </form>
            <?php } ?>
        </div>
    </div>
</body>
</html>
