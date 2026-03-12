<?php
/**
 * Bootstrap file for Modular CMS
 * Defines important constants and includes core files
 */

// Note: Most paths are defined in config.php
if (!defined('CORE_PATH')) {
    define('CORE_PATH', MODULAR_PATH . '/core');
}

if (is_file(CORE_PATH . '/config/update.php')) {
    require_once CORE_PATH . '/config/update.php';
}

// Include core files
require_once CORE_PATH . '/config-check.php';
require_once CORE_PATH . '/functions/auth.php';
if (is_file(CORE_PATH . '/functions/simezu_auth.php')) {
    require_once CORE_PATH . '/functions/simezu_auth.php';
}
require_once CORE_PATH . '/functions/csrf.php';
require_once CORE_PATH . '/functions/cache.php';
require_once CORE_PATH . '/functions/admin.php';
require_once CORE_PATH . '/functions/hooks.php';
require_once CORE_PATH . '/functions/http.php';
require_once CORE_PATH . '/functions/database.php';

// Load core classes
require_once CORE_PATH . '/classes/Hooks.php';
require_once CORE_PATH . '/classes/Module.php';
require_once CORE_PATH . '/classes/ModuleManager.php';

// Initialize module manager and load active modules
// Only if modules table exists to prevent errors
if (isset($GLOBALS['db'])) {
    try {
        $stmt = $GLOBALS['db']->query("SHOW TABLES LIKE 'modules'");
        if ($stmt->rowCount() > 0) {
            $moduleManager = new \Modular\ModuleManager($GLOBALS['db']);
            $GLOBALS['module_manager'] = $moduleManager;
            // Fire init so modules can register admin menu items (e.g. add_filter('admin_menu_items'))
            if (function_exists('do_action')) {
                do_action('init');
            }
            // Refresh $GLOBALS auth state with full Simuze_User DB record now that modules are loaded
            if (function_exists('_simezu_populate_globals_from_auth')) {
                _simezu_populate_globals_from_auth();
            }
        }
    } catch (\PDOException $e) {
        // Modules table doesn't exist yet - that's okay
        error_log("Modules table not found. Module system will be available after installation.");
    }
}

// Security headers for admin (send before any output)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // CSP: allow same origin, Leaflet/MarkerCluster assets and OpenStreetMap/Carto tiles for network map
    $csp = implode(' ', [
        "default-src 'self';",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com;",
        "style-src 'self' 'unsafe-inline' https://unpkg.com;",
        "img-src 'self' data: blob: https://tile.openstreetmap.org https://*.tile.openstreetmap.org https://*.basemaps.cartocdn.com;",
        "font-src 'self';",
        "frame-ancestors 'self';",
    ]);
    header("Content-Security-Policy: " . $csp);
}

// Set error reporting in development
error_reporting(E_ALL);
ini_set('display_errors', 1);
