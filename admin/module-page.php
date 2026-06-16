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
require_once CORE_PATH . '/functions/http.php';   // md_esc_* helpers (embed mode)

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
$embed = true;   // always Atelier-styled (the legacy admin chrome has been removed)

// In embed mode, keep module-page redirects within embed mode (after add/edit/delete).
if ($embed && function_exists('header_register_callback')) {
    header_register_callback(function () {
        foreach (headers_list() as $h) {
            if (stripos($h, 'Location:') === 0) {
                $loc = trim(substr($h, strlen('Location:')));
                if (strpos($loc, 'module-page.php') !== false && strpos($loc, 'embed=') === false) {
                    header('Location: ' . $loc . (strpos($loc, '?') !== false ? '&' : '?') . 'embed=1', true);
                }
            }
        }
    });
}

// For POST requests, include the module page first so it can redirect (e.g. after add/edit/delete)
// before any output. Otherwise "headers already sent" occurs when toolbar/header has been output.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include $file;
    exit;
}

if ($embed) {
    // Bare content styled with the Atelier design system, for embedding in the SPA
    // (the new sidebar/topbar come from the SPA shell around this iframe).
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Security-Policy: frame-ancestors \'self\';');
    }
    $spa = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/assets/spa';
    $v = function ($f) { $p = __DIR__ . '/assets/spa/' . $f; return is_file($p) ? '?v=' . filemtime($p) : ''; };
    echo '<!DOCTYPE html><html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . md_esc_html($page_title) . '</title>';
    // Atelier design system only — no old admin CSS. The compat layer maps legacy
    // module classes (.card/.button/.admin-table/...) onto the new tokens.
    $vendor = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/assets/vendor';
    echo '<link rel="stylesheet" href="' . md_esc_attr($vendor) . '/fontawesome/css/all.min.css">';
    echo '<link rel="stylesheet" href="' . md_esc_attr($spa) . '/styles.css' . $v('styles.css') . '">';
    echo '<link rel="stylesheet" href="' . md_esc_attr($spa) . '/module-embed.css' . $v('module-embed.css') . '">';
    echo '<style>body.md-embed{margin:0;padding:24px 32px}</style>';
    echo '</head><body class="md-embed">';
    include $file;
    // Keep internal navigation (links + form posts to module-page.php) in embed mode.
    echo '<script>(function(){'
        . 'function keep(u){try{var url=new URL(u,location.href);if(url.pathname.indexOf("module-page.php")>=0&&!url.searchParams.has("embed")){url.searchParams.set("embed","1");return url.toString();}}catch(e){}return u;}'
        . 'document.addEventListener("click",function(e){var a=e.target.closest&&e.target.closest("a[href]");if(a&&a.getAttribute("href")){a.setAttribute("href",keep(a.getAttribute("href")));}},true);'
        . 'document.querySelectorAll("form").forEach(function(f){if(f.getAttribute("action")){f.setAttribute("action",keep(f.getAttribute("action")));}else{f.setAttribute("action",keep(location.href));}});'
        . '})();</script>';
    echo '</body></html>';
    exit;
}

require_once ADMIN_PATH . '/templates/header.php';
include $file;
require_once ADMIN_PATH . '/templates/footer.php';
