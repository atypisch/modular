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

// Template files expect this constant to exist.
// In this Modular variant we don't run the WordPress plugin bootstrap file,
// so we must define it explicitly.
define('SIMUZE_COMMUNITY_PLUGIN_DIR', rtrim(__DIR__, '/') . '/');

// Used for asset URLs when enqueuing module scripts/styles.
// Keep it best-effort; templates that rely only on PLUGIN_DIR should work regardless.
define(
    'SIMUZE_COMMUNITY_PLUGIN_URL',
    (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '') . '/public/modules/simuze-community/'
);

// Mark as running in Modular context (guards in class files check this)
if (!defined('SIMUZE_MODULAR_RUN')) {
    define('SIMUZE_MODULAR_RUN', true);
}

// Load community class files (required for both web requests and AJAX)
$_sc_includes = __DIR__ . '/includes/';
foreach ([
    'modular-bridge.php',
    'class-database.php',
    'class-auth.php',            // Simuze_Auth — required by profile.php
    'class-user.php',            // Simuze_User — user lookups in all subpages
    'class-avatar.php',
    'class-group.php',
    'class-activity.php',        // Simuze_Activity — activity tab + dashboard
    'class-friend.php',          // Simuze_Friend — friends tab + counts
    'class-follow.php',          // Simuze_Follow — follow counts
    'class-message.php',         // Simuze_Message — messages tab + unread count
    'commerce/class-song.php',           // Simuze_Song — purchase details
    'commerce/class-album.php',          // Simuze_Album — purchase details
    'commerce/class-download-flow.php',  // Simuze_Download_Flow::get_user_purchases()
    'class-user-repository.php',
    'class-group-repository.php',
    'class-music-repository.php',
    'class-venue-repository.php',
    'class-event-repository.php',
    'class-activity-post-repository.php',
    'class-event-ticketing.php',
    'class-geo.php',
    'class-rest-api.php',
    'class-location-avatar.php',
    'class-show-matcher.php',
    'ajax-music-upload.php',
    'ajax-shows.php',
] as $_sc_file) {
    $p = $_sc_includes . $_sc_file;
    if (is_file($p)) {
        require_once $p;
    }
}
unset($_sc_includes, $_sc_file, $p);

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
                $group = Simuze_Group::get_by_unique_name($groupSlug);
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

            // Route through group.php (main wrapper with header/avatar/nav)
            // Pass $tab so group.php can skip get_query_var()
            $tab = $groupTab ?: 'overview';
            $groupTplFile = SIMUZE_COMMUNITY_PATH . '/templates/group.php';

            ob_start();
            if (function_exists('include_header')) include_header();
            if (is_file($groupTplFile)) {
                include $groupTplFile;
            }
            if (function_exists('include_footer')) include_footer();
            return ob_get_clean();
        }

        // embed/artist/{id} — no header/footer, clean iframeable HTML
        if ($seg === 'embed' && ($parts[1] ?? '') === 'artist' && !empty($parts[2])) {
            $artist_id = (int) $parts[2];
            $tpl = THEMES_PATH . '/simuze/template-parts/embed-artist.php';
            if (is_file($tpl)) {
                ob_start();
                include $tpl;
                return ob_get_clean();
            }
            return $response;
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
                // Pass subpage tab to template (e.g. /user/marten/playlists → tab=playlists)
                if (!empty($parts[2])) {
                    if (!isset($GLOBALS['simuze_query_vars'])) {
                        $GLOBALS['simuze_query_vars'] = [];
                    }
                    $GLOBALS['simuze_query_vars']['simuze_tab'] = trim($parts[2], '/');
                }
                // Pass 4th segment as playlist slug (e.g. /user/marten/playlists/my-playlist)
                if (!empty($parts[3])) {
                    if (!isset($GLOBALS['simuze_query_vars'])) {
                        $GLOBALS['simuze_query_vars'] = [];
                    }
                    $GLOBALS['simuze_query_vars']['simuze_playlist_slug'] = trim($parts[3], '/');
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

    // Playlist AJAX handlers
    add_action('wp_ajax_simuze_create_playlist',                'simuze_ajax_create_playlist');
    add_action('wp_ajax_nopriv_simuze_create_playlist',         'simuze_ajax_create_playlist');
    add_action('wp_ajax_simuze_edit_playlist',                  'simuze_ajax_edit_playlist');
    add_action('wp_ajax_simuze_delete_playlist',                'simuze_ajax_delete_playlist');
    add_action('wp_ajax_simuze_get_playlist_tracks_ajax',       'simuze_get_playlist_tracks_ajax');
    add_action('wp_ajax_nopriv_simuze_get_playlist_tracks_ajax','simuze_get_playlist_tracks_ajax');
    add_action('wp_ajax_simuze_reorder_playlist_tracks',        'simuze_ajax_reorder_playlist_tracks');
    add_action('wp_ajax_simuze_remove_from_playlist',           'simuze_ajax_remove_from_playlist');
    add_action('wp_ajax_simuze_add_to_playlist',                'simuze_ajax_add_to_playlist');
    add_action('wp_ajax_nopriv_simuze_add_to_playlist',         'simuze_ajax_add_to_playlist');
    add_action('wp_ajax_simuze_get_user_playlists_ajax',        'simuze_get_user_playlists_ajax');
    add_action('wp_ajax_nopriv_simuze_get_user_playlists_ajax', 'simuze_get_user_playlists_ajax');

    // Show AJAX handlers
    add_action('wp_ajax_simuze_delete_show',              'simuze_ajax_delete_show');
    add_action('wp_ajax_nopriv_simuze_delete_show',       'simuze_ajax_delete_show');
    add_action('wp_ajax_simuze_toggle_attendance',        'simuze_ajax_toggle_attendance');
    add_action('wp_ajax_simuze_post_event_comment',       'simuze_ajax_post_event_comment');
    add_action('wp_ajax_simuze_add_guestlist_entry',      'simuze_ajax_add_guestlist_entry');
    add_action('wp_ajax_simuze_get_organiser_dashboard',  'simuze_ajax_get_organiser_dashboard');
    add_action('wp_ajax_simuze_search_artists',           'simuze_ajax_search_artists');
    add_action('wp_ajax_nopriv_simuze_search_artists',    'simuze_ajax_search_artists');
});
