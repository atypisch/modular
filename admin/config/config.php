<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base paths
define('MODULAR_PATH', dirname(__DIR__));
define('ADMIN_PATH', MODULAR_PATH . '/admin');
define('PUBLIC_PATH', MODULAR_PATH . '/public');
define('THEMES_PATH', MODULAR_PATH . '/public/themes');
define('DEFAULT_THEME', 'default');

// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'modular');

// Create database connection with error handling
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    // Test the connection
    $db->query("SELECT 1");
    
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
