<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';  // Load config first to define paths
require_once __DIR__ . '/bootstrap.php';       // Then load bootstrap
require_once __DIR__ . '/data/init.php';       // Finally load initialization

// Require authentication (but don't require admin for dashboard)
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . (function_exists('admin_url') ? admin_url('auth/login.php') : (defined('ADMIN_URL') ? ADMIN_URL . '/auth/login.php' : '/modular/admin/auth/login.php')));
    exit;
}

// Atelier SPA: when the `admin_spa` flag is on, serve the new Vue SPA for every
// admin page (the old server-rendered views are superseded). Setup is exempt;
// real files (api/, auth/, assets/, spa.php) are served by Apache directly.
if (isset($GLOBALS['db']) && strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/setup') === false) {
    $spaOn = false;
    try {
        $stmt = $GLOBALS['db']->prepare("SELECT option_value FROM settings WHERE option_name = 'admin_spa' LIMIT 1");
        $stmt->execute();
        $spaOn = in_array((string) $stmt->fetchColumn(), ['1', 'true', 'on', 'yes'], true);
    } catch (Throwable $e) {
        $spaOn = false;
    }
    if ($spaOn) { require __DIR__ . '/spa.php'; exit; }
}

// Get the requested page from URL (uses BASE_PATH from config / config.local.php)
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_admin_path = (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '') . '/admin';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the part of the path *after* the base admin path
if (strpos($request_path, $base_admin_path) === 0) {
    $page = substr($request_path, strlen($base_admin_path));
} else {
    $page = '';
}

$page = trim($page, '/'); // Remove leading/trailing slashes

// If no specific page is requested, default to dashboard
if (empty($page) || $page === 'index.php') {
    require_once __DIR__ . '/dashboard/index.php';
    exit;
}

// Handle direct file requests first (like post-types/post-types-edit.php)
$requested_file = __DIR__ . '/' . $page;

// If .php is not included in the request, try to find the file
if (!str_contains($page, '.php')) {
    // First try with .php extension
    $requested_file_php = $requested_file . '.php';
    if (file_exists($requested_file_php)) {
        require_once $requested_file_php;
        exit;
    }
    // If that doesn't work, try as directory with index.php
    $directory_index = $requested_file . '/index.php';
    if (file_exists($directory_index)) {
        require_once $directory_index;
        exit;
    }
    // Also try the original path without extension (for files that might exist)
    if (file_exists($requested_file)) {
        require_once $requested_file;
        exit;
    }
} else {
    // If .php is included, try directly
    if (file_exists($requested_file)) {
        require_once $requested_file;
        exit;
    }
}

// Check for index.php in subdirectories (fallback)
$directory_index = dirname($requested_file) . '/index.php';
if (file_exists($directory_index)) {
    require_once $directory_index;
    exit;
}

// If we get here, the page wasn't found
header("HTTP/1.0 404 Not Found");
$page_title = '404 - Page Not Found';
$content = '<div class="container mx-auto px-4 py-8">
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        The requested page could not be found: ' . htmlspecialchars($page) . '
    </div>
</div>';
require_once __DIR__ . '/templates/header.php';
echo $content;
require_once __DIR__ . '/templates/footer.php';
?>
