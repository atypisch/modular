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

// Set up URLs
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
define('SITE_URL', $protocol . '://' . $host . '/modular');
define('ADMIN_URL', SITE_URL . '/admin');

// Define asset paths
define('ADMIN_ASSETS_PATH', ADMIN_URL . '/assets');
define('ADMIN_CSS_PATH', ADMIN_ASSETS_PATH . '/css');

// Database configuration
define('DB_HOST', 'localhost');
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
    // Log the error
    error_log("Database connection failed: " . $e->getMessage());
    
    // Show a clean error message
    die('
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Connection Error - Modular CMS</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    background-color: #f5f5f5;
                }
                .error-box {
                    background: white;
                    padding: 2rem;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    text-align: center;
                    max-width: 500px;
                }
                h1 {
                    color: #dc3545;
                    margin-top: 0;
                }
                p {
                    color: #666;
                    line-height: 1.5;
                }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h1>Database Connection Error</h1>
                <p>Unable to connect to the database. Please check your database configuration and ensure the database server is running.</p>
                <p>If you are the administrator, please check the error log for more details.</p>
            </div>
        </body>
        </html>
    ');
}

// Define common CSS files to be loaded
$additional_styles = '
    <link rel="stylesheet" href="' . ADMIN_CSS_PATH . '/base.css">
    <link rel="stylesheet" href="' . ADMIN_CSS_PATH . '/auth.css">
    <link rel="stylesheet" href="' . ADMIN_CSS_PATH . '/responsive.css">
';

?>
