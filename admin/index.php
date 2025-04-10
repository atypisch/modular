<?php
// Start session and check if user is logged in
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/data/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

// Get the requested page from URL
$page = basename($_SERVER['REQUEST_URI']);
$page = str_replace('/modular/admin/', '', $page);
$page = str_replace('.php', '', $page);

// If no specific page is requested, default to dashboard
if (empty($page)) {
    $page = 'dashboard';
}

// Set page title based on the page
$page_title = ucfirst($page);

// Load the structure page
$structure_path = __DIR__ . '/structure/' . $page . '/index.php';

if (file_exists($structure_path)) {
    require_once $structure_path;
} else {
    // Handle 404
    header("HTTP/1.0 404 Not Found");
    $page_title = '404 - Page Not Found';
    $content = '<div class="error-page">Page not found</div>';
    require_once __DIR__ . '/templates/header.php';
    echo $content;
    require_once __DIR__ . '/templates/footer.php';
}
?>
