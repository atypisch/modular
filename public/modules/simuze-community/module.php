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

// Activation: DB tables are installed automatically by Module::activate() from
// migrations/ (e.g. 001_simuze_community_schema.sql). No manual SQL step needed.
add_action('module_activate', function ($slug) {
    if ($slug !== 'simuze-community') {
        return;
    }
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
    // Admin menu: Overview, Groups, Group Types, Reviews, Reports, Messages, Settings (last) – aligned with WP Simuze Community
    add_filter('admin_menu_items', function ($items) {
        $base = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=';
        $items[] = [
            'label' => 'Community',
            'url' => $base . 'index',
            'icon' => 'fas fa-users',
            'match' => 'simuze-community',
            'sub' => [
                ['label' => 'Simuze', 'url' => $base . 'index'],
                ['label' => 'Shows', 'url' => $base . 'shows'],
                ['label' => 'Venues', 'url' => $base . 'venues'],
                ['label' => 'Cities', 'url' => $base . 'cities'],
                ['label' => 'Groups', 'url' => $base . 'groups'],
                ['label' => 'Group Types', 'url' => $base . 'group-types'],
                ['label' => 'Reviews', 'url' => $base . 'reviews'],
                ['label' => 'Reports', 'url' => $base . 'reports'],
                ['label' => 'Messages', 'url' => $base . 'messages'],
                ['label' => 'Activity', 'url' => $base . 'activity'],
                ['label' => 'Settings', 'url' => $base . 'settings'],
            ],
        ];
        return $items;
    });

    // Public routes: /group/{slug}[/tab], /user/{slug}, /discography, /activity, etc.
    add_filter('public_route', function ($response, $pathInfo) {
        $path = is_string($pathInfo) ? trim($pathInfo, '/') : '';
        if ($path === '') return $response;
        $parts = array_values(array_filter(explode('/', $path)));
        $seg   = $parts[0] ?? '';

        // group/{slug}[/tab/*]
        if ($seg === 'group' && !empty($parts[1])) {
            $groupSlug = $parts[1];
            $groupTab  = $parts[2] ?? 'overview';
            $groupSubTab = $parts[3] ?? '';

            // Load the group object
            $group = null;
            if (class_exists('Simuze_Group')) {
                $group = Simuze_Group::findBySlug($groupSlug);
            }
            if (!$group && isset($GLOBALS['db'])) {
                $row = md_db_row("SELECT * FROM simuze_groups WHERE unique_name = ? AND deleted_at IS NULL LIMIT 1", [$groupSlug]);
                if ($row && class_exists('Simuze_Group')) {
                    $group = new Simuze_Group($row);
                }
            }
            if (!$group) {
                return $response; // let core router 404
            }

            // Map tab to template
            $tabTemplateMap = [
                'music'       => 'group-music.php',
                'members'     => 'group-members.php',
                'activity'    => 'group-activity-public.php',
                'artists'     => 'group-artists.php',
                'discography' => 'group-discography.php',
                'sales'       => 'group-sales.php',
                'settings'    => 'group-settings.php',
                'dashboard'   => 'group-dashboard.php',
            ];
            $tabFile = isset($tabTemplateMap[$groupTab])
                ? SIMUZE_COMMUNITY_PATH . '/templates/' . $tabTemplateMap[$groupTab]
                : SIMUZE_COMMUNITY_PATH . '/templates/group-public.php';

            // overview/default
            if ($groupTab === 'overview' || !array_key_exists($groupTab, $tabTemplateMap)) {
                $tabFile = SIMUZE_COMMUNITY_PATH . '/templates/group-public.php';
            }
            if (!is_file($tabFile)) {
                $tabFile = SIMUZE_COMMUNITY_PATH . '/templates/group-public.php';
            }

            ob_start();
            if (function_exists('include_header')) include_header();
            if (is_file($tabFile)) {
                include $tabFile;
            }
            if (function_exists('include_footer')) include_footer();
            return ob_get_clean();
        }

        // user/{username}[/tab]
        if ($seg === 'user' && !empty($parts[1])) {
            $username = $parts[1];
            $tpl = SIMUZE_COMMUNITY_PATH . '/templates/profile.php';
            if (is_file($tpl)) {
                // Look up the Simuze_User by username
                $user = null;
                if (class_exists('Simuze_User')) {
                    $user = Simuze_User::get_by_username($username);
                }
                if (!$user) {
                    return $response; // let core router 404
                }
                ob_start();
                if (function_exists('include_header')) include_header();
                include $tpl;
                if (function_exists('include_footer')) include_footer();
                return ob_get_clean();
            }
        }

        return $response;
    });
});
