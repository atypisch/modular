<?php
// Setup Step 1: Database configuration
// - Check current DB connection based on config.php / config.local.php
// - If connection fails or needs changing, show form and write overrides to config.local.php
// - On success, continue to step2 (migrations + first admin user)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../core/config-check.php';

$configDir        = __DIR__ . '/../config';
$configFile       = $configDir . '/config.php';
$configLocalFile  = $configDir . '/config.local.php';

// Ensure required directories are present/writable
$directoryErrors = getDirectoryErrors();

// Default values
$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'modular';
$db_user = 'root';
$db_pass = 'root';
$connectionOk = false;
$error = '';

// Load existing config to prefill values and inspect connection status, if present
if (file_exists($configFile)) {
    require_once $configFile;

    if (defined('DB_HOST')) $db_host = DB_HOST;
    if (defined('DB_PORT')) $db_port = DB_PORT;
    if (defined('DB_NAME')) $db_name = DB_NAME;
    if (defined('DB_USER')) $db_user = DB_USER;
    if (defined('DB_PASS')) $db_pass = DB_PASS;

    if (isset($GLOBALS['db']) && empty($directoryErrors)) {
        $connectionOk = true;
    } elseif (isset($GLOBALS['db_error'])) {
        $error = 'Database connection failed: ' . $GLOBALS['db_error']->getMessage();
    }
}

// Handle form submission: test and persist DB settings into config.local.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($directoryErrors)) {
    $db_host = trim($_POST['db_host'] ?? $db_host);
    $db_port = trim($_POST['db_port'] ?? $db_port);
    $db_name = trim($_POST['db_name'] ?? $db_name);
    $db_user = trim($_POST['db_user'] ?? $db_user);
    $db_pass = $_POST['db_pass'] !== '' ? $_POST['db_pass'] : $db_pass;

    if ($db_host === '' || $db_port === '' || $db_name === '' || $db_user === '') {
        $error = 'All fields except password are required.';
    } else {
        try {
            $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
            $test = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $test->query('SELECT 1');

            if (!is_dir($configDir)) {
                mkdir($configDir, 0755, true);
            }

            // Write config.local.php with DB_* overrides
            $localContent = "<?php\n"
                . "if (!defined('DB_HOST')) define('DB_HOST', '" . addslashes($db_host) . "');\n"
                . "if (!defined('DB_PORT')) define('DB_PORT', '" . addslashes($db_port) . "');\n"
                . "if (!defined('DB_USER')) define('DB_USER', '" . addslashes($db_user) . "');\n"
                . "if (!defined('DB_PASS')) define('DB_PASS', '" . addslashes($db_pass) . "');\n"
                . "if (!defined('DB_NAME')) define('DB_NAME', '" . addslashes($db_name) . "');\n";

            file_put_contents($configLocalFile, $localContent);

            // Re-load config to ensure $GLOBALS['db'] is initialised with new settings
            if (file_exists($configFile)) {
                require $configFile;
            }

            if (isset($GLOBALS['db'])) {
                header('Location: step2.php');
                exit;
            }

            $error = 'Configuration saved, but database connection still failed. Please verify your credentials.';
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
                                    <?php foreach ($directoryErrors as $msg): ?>
                                        <li><?php echo htmlspecialchars($msg); ?></li>
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
            <?php elseif ($connectionOk): ?>
                <div class="rounded-md bg-green-50 p-4">
                    <p class="text-sm text-green-700">
                        ✓ Database connection successful. You can continue to create the first admin user.
                    </p>
                </div>
            <?php endif; ?>

            <form method="POST" class="mt-8 space-y-6" <?php if (!empty($directoryErrors)) echo 'style="opacity: 0.5; pointer-events: none;"'; ?>>
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="db_host" class="sr-only">Database Host</label>
                        <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($db_host); ?>" required
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Database Host">
                    </div>
                    <div>
                        <label for="db_port" class="sr-only">Database Port</label>
                        <input type="text" id="db_port" name="db_port" value="<?php echo htmlspecialchars($db_port); ?>" required
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Database Port (e.g. 3306 or 8889)">
                    </div>
                    <div>
                        <label for="db_name" class="sr-only">Database Name</label>
                        <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($db_name); ?>" required
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Database Name">
                    </div>
                    <div>
                        <label for="db_user" class="sr-only">Database User</label>
                        <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($db_user); ?>" required
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Database User">
                    </div>
                    <div>
                        <label for="db_pass" class="sr-only">Database Password</label>
                        <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($db_pass); ?>"
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Database Password">
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Save &amp; Continue to Step 2
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

