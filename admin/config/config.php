<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Local override: database credentials and optional BASE_PATH, DB_PORT, etc.
// Copy config.local.php.example to config.local.php and set your credentials there.
// config.local.php is in .gitignore – do not put real credentials in config.php.
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

// Define base paths (only if not already set, e.g. when public/index.php is included from root)
if (!defined('MODULAR_PATH')) define('MODULAR_PATH', dirname(dirname(__DIR__)));
if (!defined('ADMIN_PATH')) define('ADMIN_PATH', MODULAR_PATH . '/admin');
if (!defined('CORE_PATH')) define('CORE_PATH', MODULAR_PATH . '/core');
if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', MODULAR_PATH . '/public');
if (!defined('THEMES_PATH')) define('THEMES_PATH', MODULAR_PATH . '/public/themes');
if (!defined('MODULES_PATH')) define('MODULES_PATH', MODULAR_PATH . '/public/modules');
if (!defined('DEFAULT_THEME')) define('DEFAULT_THEME', 'default');

// Setup helpers (isInstalled, directory checks)
require_once CORE_PATH . '/config-check.php';

// Database configuration – fallback only. Prefer config.local.php for all environments.
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', '3306');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
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
            // Avoid "Cannot execute queries while other unbuffered queries are active"
            // by forcing MySQL to buffer results for prepared statements.
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );
    
    // Test the connection
    $testStmt = $db->query("SELECT 1");
    // Consume and close result to avoid unbuffered cursor conflicts.
    $testStmt->fetch();
    $testStmt->closeCursor();
    
    // Make database connection available globally
    $GLOBALS['db'] = $db;

} catch(PDOException $e) {
    $GLOBALS['db_error'] = $e;
}

// Run pending migrations on every admin request (except setup) so that a fresh install
// gets all core tables (users, roles, posts, etc.) on first visit to /admin.
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$isAdminRequest = (strpos($requestUri, '/admin') !== false);
$isSetupRequest = (strpos($requestUri, '/admin/setup') !== false);
// Avoid running migrations during auth flows (login/register), since they can
// trigger unbuffered cursor conflicts that prevent authentication from working.
$isAuthRequest = (strpos($requestUri, '/admin/auth/') !== false);
if (isset($GLOBALS['db']) && $isAdminRequest && !$isSetupRequest && !$isAuthRequest) {
    require_once CORE_PATH . '/functions/database.php';
    if (function_exists('run_pending_migrations')) {
        run_pending_migrations();
    }
}

// Set up URLs: base path for assets/links (auto-detect or from config.local.php)
if (!defined('BASE_PATH')) {
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $pos = strpos($script, '/admin');
    if ($pos !== false) {
        $base = rtrim(substr($script, 0, $pos), '/');
    } else {
        $base = dirname($script);
        if ($base === '/' || $base === '\\' || $base === '.') {
            $base = '';
        }
        $base = rtrim($base, '/');
        // If we're in /public (e.g. script is /simuze-mdlr/public/index.php), use parent as app base
        if (substr($base, -7) === '/public') {
            $base = substr($base, 0, -7) ?: '/modular';
        }
        if ($base === '') {
            $base = '/modular';
        }
    }
    define('BASE_PATH', $base);
}
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
if (!defined('SITE_URL')) define('SITE_URL', $protocol . '://' . $host . BASE_PATH);
if (!defined('ADMIN_URL')) define('ADMIN_URL', SITE_URL . '/admin');
if (!defined('ADMIN_BASE_PATH')) define('ADMIN_BASE_PATH', BASE_PATH . '/admin');

// Define asset paths
if (!defined('ADMIN_ASSETS_PATH')) define('ADMIN_ASSETS_PATH', ADMIN_URL . '/assets');
if (!defined('ADMIN_CSS_PATH')) define('ADMIN_CSS_PATH', ADMIN_ASSETS_PATH . '/css');

// Uploads: configurable so external/app base path works
if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
}
if (!defined('UPLOADS_WEB_PATH')) {
    define('UPLOADS_WEB_PATH', BASE_PATH . '/public/uploads');
}

// Define common CSS files to be loaded
$additional_styles = '
    <link rel="stylesheet" href="' . ADMIN_CSS_PATH . '/base.css">
    <link rel="stylesheet" href="' . ADMIN_CSS_PATH . '/auth.css">
    <link rel="stylesheet" href="' . ADMIN_CSS_PATH . '/responsive.css">
';

// Helper for admin URLs (use this instead of hardcoding /modular/admin so copy-installs work)
if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        $base = defined('ADMIN_URL') ? ADMIN_URL : (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') . '/admin' : '/admin');
        $path = ltrim((string) $path, '/');
        return $base . ($path !== '' ? '/' . $path : '');
    }
}

?>
