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
require_once CORE_PATH . '/functions/csrf.php';
require_once CORE_PATH . '/functions/cache.php';
require_once CORE_PATH . '/functions/admin.php';
require_once CORE_PATH . '/functions/hooks.php';
require_once CORE_PATH . '/functions/common.php';
require_once CORE_PATH . '/functions/template-helpers.php';

// Simezu auth — decodes JWT cookie early so $GLOBALS['is_logged_in'] is available to all templates
if (is_file(CORE_PATH . '/functions/simezu_auth.php')) {
    require_once CORE_PATH . '/functions/simezu_auth.php';
}

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
            if (function_exists('_simezu_populate_globals_from_auth')) {
                _simezu_populate_globals_from_auth();
            }
        }
    } catch (\PDOException $e) {
        // Modules table doesn't exist yet - that's okay
        error_log("Modules table not found. Module system will be available after installation.");
    }

    if (is_file(CORE_PATH . '/functions/atypisch-sync-visual-schema.php')) {
        require_once CORE_PATH . '/functions/atypisch-sync-visual-schema.php';
        if (function_exists('atypisch_sync_visual_fields_into_post_types')) {
            atypisch_sync_visual_fields_into_post_types();
        }
    }
}

// Security headers for admin + public (send before any output)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // CSP: allow same origin by default, plus Simezu + Leaflet/MarkerCluster CDN assets
    // Simezu embed + API domains (local + production)
    $simezuEmbed = defined('SIMEZU_EMBED_URL') ? rtrim(SIMEZU_EMBED_URL, '/') : 'https://simezu.com';
    $simezuHost  = parse_url($simezuEmbed, PHP_URL_SCHEME) . '://' . parse_url($simezuEmbed, PHP_URL_HOST);

    // Zero external runtime dependencies: every library/font is vendored locally
    // (Chart.js, Tiptap, CodeMirror, fonts, FontAwesome, Vue). No CDN script origins.
    $scriptSrc = [
        "'self'",
        "'unsafe-inline'",
        "'unsafe-eval'",
        $simezuHost,
    ];

    $styleSrc = [
        "'self'",
        "'unsafe-inline'",
        $simezuHost,
    ];

    $imgSrc = [
        "'self'",
        "data:",
        "blob:",
        $simezuHost,
        "https://*.basemaps.cartocdn.com",
    ];

    $fontSrc = [
        "'self'",
        "data:",
    ];

    // Embeds: allow YouTube/Vimeo players to be framed by the admin editor.
    $frameSrc = [
        "'self'",
        "https://www.youtube.com",
        "https://www.youtube-nocookie.com",
        "https://player.vimeo.com",
    ];

    $csp = sprintf(
        "default-src 'self'; script-src %s; style-src %s; img-src %s; font-src %s; frame-src %s; frame-ancestors 'self';",
        implode(' ', array_unique($scriptSrc)),
        implode(' ', array_unique($styleSrc)),
        implode(' ', array_unique($imgSrc)),
        implode(' ', array_unique($fontSrc)),
        implode(' ', array_unique($frameSrc))
    );

    header("Content-Security-Policy: " . $csp);
}

// Set error reporting in development
error_reporting(E_ALL);
ini_set('display_errors', 1);
