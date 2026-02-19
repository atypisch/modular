<?php
/**
 * Dispatcher for module admin pages.
 * URL: admin/module-page.php?module=simuze-community&page=groups
 * Includes: public/modules/{module}/admin/{page}.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/admin/config/config.php';
}
require_once __DIR__ . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '') . '/auth/login.php');
    exit;
}

$module = isset($_GET['module']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['module']) : '';
$page = isset($_GET['page']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['page']) : '';

if ($module === '' || $page === '') {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '') . '/modules');
    exit;
}

if (!function_exists('is_module_active') || !is_module_active($module)) {
    $_SESSION['error'] = 'Module is not active.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '') . '/modules');
    exit;
}

$file = defined('PUBLIC_PATH') ? PUBLIC_PATH : (dirname(__DIR__) . '/public');
$file .= '/modules/' . $module . '/admin/' . $page . '.php';

if (!is_file($file)) {
    http_response_code(404);
    echo 'Module page not found.';
    exit;
}

$page_title = ucfirst(str_replace('-', ' ', $page));

// For POST requests, include the module page first so it can redirect (e.g. after add/edit/delete)
// before any output. Otherwise "headers already sent" occurs when toolbar/header has been output.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include $file;
    exit;
}

require_once ADMIN_PATH . '/templates/header.php';
include $file;
require_once ADMIN_PATH . '/templates/footer.php';
