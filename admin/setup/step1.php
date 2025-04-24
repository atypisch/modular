<?php
// This file will contain the form for the database setup.
// After the form is submitted, it will test the DB connection and write the details to /admin/config/database.php.
// If successful, it will redirect to step2.php. 

session_start();
require_once __DIR__ . '/../../core/config-check.php';

// Check if config already exists and redirect if it does
if (file_exists('../config/config.php')) {
    header('Location: ../index.php');
    exit;
}

// Check directory dependencies
$directoryErrors = getDirectoryErrors();

// Process form submission
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($directoryErrors)) {
    $db_host = $_POST['db_host'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    
    // Validate inputs
    if (empty($db_host) || empty($db_name) || empty($db_user)) {
        $error = 'All fields except password are required.';
    } else {
        // Test connection
        try {
            $conn = new PDO(
                "mysql:host=$db_host;dbname=$db_name",
                $db_user,
                $db_pass
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Connection successful, create config file
            $config_content = "<?php\nreturn [\n" .
                "    'db_host' => '" . addslashes($db_host) . "',\n" .
                "    'db_name' => '" . addslashes($db_name) . "',\n" .
                "    'db_user' => '" . addslashes($db_user) . "',\n" .
                "    'db_pass' => '" . addslashes($db_pass) . "',\n" .
                "];\n";
            
            // Create config directory if it doesn't exist
            if (!file_exists('../config')) {
                mkdir('../config', 0755, true);
            }
            
            // Write config file
            if (file_put_contents('../config/config.php', $config_content)) {
                // Create necessary database tables
                $tables = [
                    "CREATE TABLE IF NOT EXISTS users (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        email VARCHAR(255) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL,
                        role VARCHAR(50) NOT NULL DEFAULT 'user',
                        email_verified BOOLEAN DEFAULT FALSE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )",
                    "CREATE TABLE IF NOT EXISTS routes (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        slug VARCHAR(255) NOT NULL UNIQUE,
                        template VARCHAR(255) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )"
                ];
                
                foreach ($tables as $sql) {
                    $conn->exec($sql);
                }
                
                $success = true;
                header('Location: step2.php');
                exit;
            } else {
                $error = 'Could not write config file. Please check permissions.';
            }
        } catch (PDOException $e) {
            $error = 'Database connection failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modular CMS Setup - Step 1</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h1 class="text-center text-3xl font-extrabold text-gray-900">
                    Modular CMS Setup
                </h1>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Step 1: Database Configuration
                </p>
            </div>

            <?php if (!empty($directoryErrors)): ?>
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Directory Permission Errors
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    <?php foreach ($directoryErrors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <?php echo htmlspecialchars($error); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="mt-8 space-y-6" <?php if (!empty($directoryErrors)) echo 'style="opacity: 0.5; pointer-events: none;"'; ?>>
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="db_host" class="sr-only">Database Host</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" required
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Database Host">
                    </div>
                    <div>
                        <label for="db_name" class="sr-only">Database Name</label>
                        <input type="text" id="db_name" name="db_name" required
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Database Name">
                    </div>
                    <div>
                        <label for="db_user" class="sr-only">Database User</label>
                        <input type="text" id="db_user" name="db_user" required
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Database User">
                    </div>
                    <div>
                        <label for="db_pass" class="sr-only">Database Password</label>
                        <input type="password" id="db_pass" name="db_pass"
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Database Password">
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Continue to Step 2
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 