<?php
/**
 * Module Name: Simuze Community
 * Module URI: https://simuze.com
 * Version: 1.0.0
 * Description: Community features for Simuze: users, groups, activity, messages, reviews, reports; optional commerce (orders, downloads, payouts).
 * Author: Atypisch / Simuze
 * Author URI: https://atypisch.nl
 * Text Domain: simuze-community
 * Requires PHP: 8.1
 * Requires Modular: 1.0
 */

// Prevent direct access
if (!defined('MODULAR_PATH')) {
    return;
}

define('SIMUZE_COMMUNITY_VERSION', '1.0.0');
define('SIMUZE_COMMUNITY_PATH', __DIR__);
define('SIMUZE_DB_PREFIX', 'simuze_');

// Activation: run migrations (groups, messages, etc.)
add_action('module_activate', function ($slug) {
    if ($slug !== 'simuze-community') {
        return;
    }
    // Migrations run automatically by Module::activate() from migrations/
});

add_action('module_deactivate', function ($slug) {
    if ($slug !== 'simuze-community') {
        return;
    }
    // Optional: cleanup or flag
});

// Init: register routes/components when needed
add_action('init', function () {
    if (!function_exists('is_module_active') || !is_module_active('simuze-community')) {
        return;
    }
    // Admin menu: add Community > Groups
    add_filter('admin_menu_items', function ($items) {
        $base = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=';
        $items[] = [
            'label' => 'Community',
            'url' => $base . 'groups',
            'icon' => 'fas fa-users',
            'match' => 'simuze-community',
            'sub' => [
                ['label' => 'Groups', 'url' => $base . 'groups'],
                ['label' => 'Messages', 'url' => $base . 'messages'],
            ],
        ];
        return $items;
    });

    // Public routes (shortcode-equivalent: /profile, /messages, /groups)
    add_filter('public_route', function ($response, $pathInfo) {
        $path = is_string($pathInfo) ? trim($pathInfo, '/') : '';
        if ($path === '') return $response;
        $seg = explode('/', $path)[0];
        if (in_array($seg, ['profile', 'messages', 'groups'], true)) {
            $titles = ['profile' => 'Profile', 'messages' => 'Messages', 'groups' => 'Groups'];
            return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . $titles[$seg] . '</title></head><body><h1>' . htmlspecialchars($titles[$seg]) . '</h1><p>Simuze Community – ' . htmlspecialchars($seg) . ' (placeholder). Log in or use the admin to manage content.</p></body></html>';
        }
        return $response;
    });
});
