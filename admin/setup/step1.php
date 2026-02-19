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
    <link href="../assets/css/install.css" rel="stylesheet">
</head>
<body class="install-page setup-step">
    <div class="min-h-screen">
        <div class="setup-card space-y-8">
            <div>
                <h1 class="setup-title">Modular CMS Setup</h1>
                <p class="setup-subtitle">Step 1: Database Configuration</p>
            </div>

            <?php if (!empty($directoryErrors)): ?>
                <div class="msg-error">
                    <h3 style="margin:0 0 0.5rem 0;font-size:0.875rem;">Directory Permission Errors</h3>
                    <ul style="margin:0;padding-left:1.25rem;">
                        <?php foreach ($directoryErrors as $msg): ?>
                            <li><?php echo htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="msg-error">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php elseif ($connectionOk): ?>
                <div class="msg-success border rounded px-4 py-3">
                    <p>✓ Database connection successful. You can continue to create the first admin user.</p>
                </div>
            <?php endif; ?>

            <div class="install-card">
            <form method="POST" class="space-y-6" <?php if (!empty($directoryErrors)) echo 'style="opacity: 0.5; pointer-events: none;"'; ?>>
                <div>
                    <label for="db_host">Database Host</label>
                    <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($db_host); ?>" required placeholder="Database Host">
                </div>
                <div>
                    <label for="db_port">Database Port</label>
                    <input type="text" id="db_port" name="db_port" value="<?php echo htmlspecialchars($db_port); ?>" required placeholder="Database Port (e.g. 3306 or 8889)">
                </div>
                <div>
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($db_name); ?>" required placeholder="Database Name">
                </div>
                <div>
                    <label for="db_user">Database User</label>
                    <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($db_user); ?>" required placeholder="Database User">
                </div>
                <div>
                    <label for="db_pass">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($db_pass); ?>" placeholder="Database Password">
                </div>
                <div>
                    <button type="submit">Save &amp; Continue to Step 2</button>
                </div>
            </form>
            </div>
        </div>
    </div>
</body>
</html>

