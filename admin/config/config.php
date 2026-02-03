<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306'); // Default MySQL port, change to 8889 for MAMP
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'modular');

// Create database connection with error handling
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

    // Run pending migrations so post_types.mode/schema, posts.data/layout etc. exist
    require_once CORE_PATH . '/functions/database.php';
    if (function_exists('run_pending_migrations')) {
        run_pending_migrations();
    }

} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Set up URLs
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
define('SITE_URL', $protocol . '://' . $host . '/modular');
define('ADMIN_URL', SITE_URL . '/admin');

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
