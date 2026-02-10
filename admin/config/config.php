<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Optional local override (e.g. for MAMP: DB_PORT 8889, BASE_PATH)
$configLocalPath = __DIR__ . '/config.local.php';
if (file_exists($configLocalPath)) {
    require_once $configLocalPath;
}

// Configure session settings for better persistence
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters before starting session
    ini_set('session.cookie_lifetime', 604800); // 7 days
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', 604800); // 7 days
    ini_set('session.use_strict_mode', 1);
}

// Define base paths
define('MODULAR_PATH', dirname(dirname(__DIR__)));
define('ADMIN_PATH', MODULAR_PATH . '/admin');
define('CORE_PATH', MODULAR_PATH . '/core');
define('PUBLIC_PATH', MODULAR_PATH . '/public');
define('THEMES_PATH', MODULAR_PATH . '/public/themes');
define('DEFAULT_THEME', 'default');

// Setup helpers (isInstalled, directory checks)
require_once CORE_PATH . '/config-check.php';

// Database configuration (override in config.local.php for MAMP)
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', '3306'); // MAMP: 8889
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', 'root');
if (!defined('DB_NAME')) define('DB_NAME', 'modular');

// Create database connection with error handling.
// During setup we want to detect connection errors without hard-dying.
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $db = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );
    
    // Test the connection
    $db->query("SELECT 1");
    
    // Make database connection available globally
    $GLOBALS['db'] = $db;

} catch(PDOException $e) {
    $GLOBALS['db_error'] = $e;
}

// Only run migrations when we're in the admin area and not on the setup flow.
// Otherwise, visiting / would recreate tables and show "Go to Admin" instead of setup;
// and visiting /admin/setup/ would recreate tables before the user completes step 2.
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$isAdminRequest = (strpos($requestUri, '/admin') !== false);
$isSetupRequest = (strpos($requestUri, '/admin/setup') !== false);
if (isset($GLOBALS['db']) && function_exists('isInstalled') && isInstalled() && $isAdminRequest && !$isSetupRequest) {
    require_once CORE_PATH . '/functions/database.php';
    if (function_exists('run_pending_migrations')) {
        run_pending_migrations();
    }
}

// Set up URLs: base path for assets/links (auto-detect or from config.local.php)
if (!defined('BASE_PATH')) {
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $pos = strpos($script, '/admin');
    define('BASE_PATH', $pos !== false ? rtrim(substr($script, 0, $pos), '/') : '/modular');
}
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
define('SITE_URL', $protocol . '://' . $host . BASE_PATH);
define('ADMIN_URL', SITE_URL . '/admin');
define('ADMIN_BASE_PATH', BASE_PATH . '/admin');

// Define asset paths
define('ADMIN_ASSETS_PATH', ADMIN_URL . '/assets');
define('ADMIN_CSS_PATH', ADMIN_ASSETS_PATH . '/css');

// Define common CSS files to be loaded
$additional_styles = '
    <link rel="stylesheet" href="' . ADMIN_CSS_PATH . '/base.css">
    <link rel="stylesheet" href="' . ADMIN_CSS_PATH . '/auth.css">
    <link rel="stylesheet" href="' . ADMIN_CSS_PATH . '/responsive.css">
';

?>
