<?php
// Define base paths
define('MODULAR_PATH', dirname(__DIR__));
define('ADMIN_PATH', MODULAR_PATH . '/admin');
define('PUBLIC_PATH', MODULAR_PATH . '/public');
define('THEMES_PATH', PUBLIC_PATH . '/themes');
define('DEFAULT_THEME', 'default');

// Set up URLs
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
define('SITE_URL', $protocol . '://' . $host . '/modular');
define('ADMIN_URL', SITE_URL . '/admin'); 