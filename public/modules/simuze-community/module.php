<?php
/**
 * Module Name: Simuze Community
 * Version: 1.0.0
 * Description: Custom community system for Simuze - Users, Groups, Activity, Messages, Reviews & Reports
 * Author: Atypisch - Marten Timan
 * Text Domain: simuze-community
 * Requires PHP: 7.4
 * Requires Modular: 1.0
 */

// Mark Modular context so plugin classes and bridge can branch
if (!defined('SIMUZE_MODULAR_RUN')) {
    define('SIMUZE_MODULAR_RUN', true);
}

// Plugin constants (same as WP plugin)
if (!defined('SIMUZE_COMMUNITY_VERSION')) {
    define('SIMUZE_COMMUNITY_VERSION', '1.0.0');
}
if (!defined('SIMUZE_COMMUNITY_PLUGIN_DIR')) {
    define('SIMUZE_COMMUNITY_PLUGIN_DIR', dirname(__FILE__) . '/');
}
if (!defined('SIMUZE_COMMUNITY_PLUGIN_URL')) {
    $base = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    define('SIMUZE_COMMUNITY_PLUGIN_URL', $base . $path . '/modules/simuze-community/');
}
if (!defined('SIMUZE_DB_PREFIX')) {
    define('SIMUZE_DB_PREFIX', 'simuze_');
}

// Allow plugin include guards to pass (they check ABSPATH)
if (!defined('ABSPATH')) {
    define('ABSPATH', defined('MODULAR_PATH') ? MODULAR_PATH . '/' : __DIR__ . '/../../');
}

// Load Modular runtime bootstrap (provides $wpdb compat and get_option for plugin classes)
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/modular-runtime.php';

// Modular-native repository classes (no WP dependency)
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-music-repository.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-user-repository.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-group-repository.php';

// Load dependencies (same list as simuze-community.php)
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-database.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-auth.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-user.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-group.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-activity.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-message.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-friend.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-follow.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-notification.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-security.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-privacy.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-search.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-review.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-report.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/api/class-rest-api.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-song.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-album.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-simezu-payments.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-download-handler.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-audio-converter.php';
// Commerce flow (class-purchase-flow, class-download-flow, class-commerce-api) removed — payments handled via Simezu webhook
// In Modular: always load admin class (module-page.php handles auth). In WP: only on admin.
if (defined('SIMUZE_MODULAR_RUN') || is_admin()) {
    require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/class-admin.php';
}
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-templates.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-geo.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-helpers.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-avatar.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/ajax-music-upload.php';
// Activation / deactivation
add_action('module_activate', function ($slug) {
    if ($slug === 'simuze-community') {
        $db = Simuze_Database::get_instance();
        $db->maybe_run_migrations();
    }
});

add_action('module_deactivate', function ($slug) {
    if ($slug === 'simuze-community') {
        // Optional: cleanup or flag
    }
});

// Init: run database version check and auth (same as plugin load_components)
add_action('init', function () {
    if (!isset($GLOBALS['db']) || !$GLOBALS['db'] instanceof PDO) {
        return;
    }
    // Point auth to Modular proxy so frontend never calls Simezu directly
    $authUrl = function_exists('md_home_url') ? md_home_url('/api/auth') : '';
    if ($authUrl !== '' && get_option('simuze_auth_api_url') !== $authUrl) {
        update_option('simuze_auth_api_url', $authUrl);
    }
    $database = Simuze_Database::get_instance();
    $database->maybe_run_migrations();
    Simuze_Auth::get_instance();
    if (class_exists('Simuze_REST_API')) {
        Simuze_REST_API::get_instance();
    }
    if (class_exists('Simuze_Shortcodes')) {
        Simuze_Shortcodes::get_instance();
    }
    if (class_exists('Simuze_Admin')) {
        Simuze_Admin::get_instance();
    }
    if (class_exists('Simuze_Download_Handler')) {
        Simuze_Download_Handler::get_instance();
    }
}, 10);

// Public routes: profile, user/{handle}, group/{handle}, messages, groups
add_filter('public_route', function ($response, $pathInfo) {
    if ($response !== null && $response !== '') {
        return $response;
    }
    $pathInfo = is_string($pathInfo) ? trim($pathInfo, '/') : '';
    $segments = $pathInfo === '' ? [] : array_values(array_filter(explode('/', $pathInfo)));
    if (empty($segments)) {
        return null;
    }
    $theme_path = defined('THEMES_PATH') && defined('ACTIVE_THEME')
        ? (THEMES_PATH . '/' . ACTIVE_THEME)
        : (defined('PUBLIC_PATH') ? PUBLIC_PATH . '/themes/simuze' : __DIR__ . '/../../themes/simuze');
    $theme_path = is_dir($theme_path) ? $theme_path : (defined('PUBLIC_PATH') ? PUBLIC_PATH . '/themes/default' : '');
    if ($theme_path === '' || !is_dir($theme_path)) {
        return null;
    }
    $GLOBALS['simuze_theme_path'] = $theme_path;
    $page_title = 'Simuze';
    $content = '';

    // profile (current user)
    if ($segments[0] === 'profile') {
        set_query_var('simuze_tab', $segments[1] ?? ($_GET['tab'] ?? 'dashboard'));
        if (class_exists('Simuze_Shortcodes')) {
            $content = Simuze_Shortcodes::get_instance()->render_profile(['username' => '']);
        }
        $page_title = 'Profile';
    }
    // dashboard (alias for own profile dashboard)
    elseif ($segments[0] === 'dashboard') {
        // If user is already logged in via Simezu, redirect to canonical /user/{username}
        if (class_exists('Simuze_Auth')) {
            try {
                $auth = Simuze_Auth::get_instance();
                $current = $auth->get_current_user();
                if ($current && !empty($current->username) && function_exists('base_url')) {
                    $target = base_url('/user/' . rawurlencode($current->username));
                    header('Location: ' . $target, true, 302);
                    exit;
                }
            } catch (\Throwable $e) {
                // fall through to login prompt
            }
        }
        // Not logged in (or no Simezu user detected): show login prompt via profile renderer
        set_query_var('simuze_tab', $segments[1] ?? ($_GET['tab'] ?? 'dashboard'));
        if (class_exists('Simuze_Shortcodes')) {
            $content = Simuze_Shortcodes::get_instance()->render_profile(['username' => '']);
        }
        $page_title = 'Dashboard';
    }
    // user/{username}[/tab]
    elseif ($segments[0] === 'user' && !empty($segments[1])) {
        set_query_var('simuze_username', $segments[1]);
        set_query_var('simuze_tab', $segments[2] ?? ($_GET['tab'] ?? 'dashboard'));
        if (class_exists('Simuze_Shortcodes')) {
            $content = Simuze_Shortcodes::get_instance()->render_profile(['username' => $segments[1]]);
        }
        $page_title = 'Profile';
    }
    // group/{handle}[/tab]
    elseif ($segments[0] === 'group' && !empty($segments[1])) {
        set_query_var('simuze_group', $segments[1]);
        set_query_var('group_slug', $segments[1]);
        set_query_var('simuze_group_tab', $segments[2] ?? ($_GET['tab'] ?? 'dashboard'));
        if (class_exists('Simuze_Shortcodes')) {
            $content = Simuze_Shortcodes::get_instance()->render_group(['slug' => '']);
        }
        $page_title = 'Group';
    }
    // groups[/create]
    elseif ($segments[0] === 'groups') {
        if (isset($segments[1]) && $segments[1] === 'create') {
            set_query_var('simuze_create_group', 1);
            if (is_file(SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/create-group.php')) {
                ob_start();
                include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/create-group.php';
                $content = ob_get_clean();
            } else {
                $content = '<div class="p-4"><h2>Create group</h2><p>Group creation form.</p></div>';
            }
        } else {
            if (class_exists('Simuze_Shortcodes')) {
                $content = Simuze_Shortcodes::get_instance()->render_groups([]);
            }
        }
        $page_title = 'Groups';
    }
    // Clean group handles without /group/ prefix, e.g. /boyle/ or /boyle/dashboard/
    elseif (!empty($segments[0])) {
        // Avoid hijacking known core routes
        $excluded = array(
            'user', 'profile', 'group', 'groups', 'music', 'network', 'live', 'weblog',
            'about', 'store', 'dashboard', 'api', 'admin', 'login', 'logout',
            'wp-admin', 'wp-content', 'wp-includes', 'wp-json',
            'feed', 'category', 'tag', 'author', 'page', 'post', 'track', 'album', 'playlist'
        );

        if (!in_array($segments[0], $excluded, true) && class_exists('Simuze_Group')) {
            $maybe_group = Simuze_Group::get_by_unique_handle($segments[0]);

            if ($maybe_group) {
                // Expose handle/slug to templates and shortcodes
                set_query_var('simuze_group', $segments[0]);
                set_query_var('group_slug', $segments[0]);
                set_query_var('simuze_group_tab', $segments[1] ?? ($_GET['tab'] ?? 'dashboard'));

                if (class_exists('Simuze_Shortcodes')) {
                    $content = Simuze_Shortcodes::get_instance()->render_group([
                        'slug' => $segments[0],
                    ]);
                }

                $page_title = 'Group';
            }
        }
    }
    // messages
    elseif ($segments[0] === 'messages') {
        if (class_exists('Simuze_Shortcodes')) {
            $content = Simuze_Shortcodes::get_instance()->render_messages([]);
        }
        $page_title = 'Messages';
    }
    // discography (optional)
    elseif ($segments[0] === 'discography') {
        if (class_exists('Simuze_Shortcodes')) {
            $content = Simuze_Shortcodes::get_instance()->render_discography([]);
        }
        $page_title = 'Discography';
    }
    // activity
    elseif ($segments[0] === 'activity') {
        if (class_exists('Simuze_Shortcodes')) {
            $content = Simuze_Shortcodes::get_instance()->render_activity([]);
        }
        $page_title = 'Activity';
    }
    // friends
    elseif ($segments[0] === 'friends') {
        if (class_exists('Simuze_Shortcodes')) {
            $content = Simuze_Shortcodes::get_instance()->render_friends([]);
        }
        $page_title = 'Friends';
    }
    // music/album/{slug} — standalone album view (so /music/album/passing-shadows works)
    elseif ($segments[0] === 'music' && isset($segments[1]) && $segments[1] === 'album' && !empty($segments[2])) {
        $album_slug = $segments[2];
        $album = class_exists('MusicRepository') ? MusicRepository::findAlbumBySlug($album_slug) : null;
        if (!$album) {
            $content = '<div class="simuze-notice"><p>' . __('Album not found.', 'simuze') . '</p></div>';
            $page_title = 'Album not found';
        } else {
            $group = null;
            if (!empty($album['group_id']) && class_exists('Simuze_Group')) {
                $group = Simuze_Group::get($album['group_id']);
            }
            $tracks = class_exists('MusicRepository') ? MusicRepository::findTracksByAlbum((int) $album['id']) : [];
            $album_single_tpl = SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/album-single.php';
            if (is_file($album_single_tpl)) {
                ob_start();
                include $album_single_tpl;
                $content = ob_get_clean();
            } else {
                $edit_url = '';
                if ($group && method_exists($group, 'get_url')) {
                    $edit_url = rtrim($group->get_url(), '/') . '/music?edit=true&album_id=' . (int) $album['id'] . '&group_id=' . (int) $album['group_id'];
                }
                $artwork_url = class_exists('MusicRepository') ? MusicRepository::getAlbumArtworkUrl($album) : '';
                $content = '<div class="album-single-view"><h1>' . esc_html($album['album_name'] ?? 'Album') . '</h1>';
                if ($artwork_url) {
                    $content .= '<p><img src="' . esc_url($artwork_url) . '" alt="" style="max-width:300px;"></p>';
                }
                if (!empty($album['description'])) {
                    $content .= '<div class="album-description">' . nl2br(esc_html($album['description'])) . '</div>';
                }
                if (!empty($tracks)) {
                    $content .= '<ul class="album-tracks">';
                    foreach ($tracks as $t) {
                        $content .= '<li>' . esc_html($t['song_name'] ?? 'Track') . '</li>';
                    }
                    $content .= '</ul>';
                }
                if ($edit_url) {
                    $content .= '<p><a href="' . esc_url($edit_url) . '" class="button">' . __('Edit album', 'simuze') . '</a></p>';
                }
                $content .= '</div>';
            }
            $page_title = $album['album_name'] ?? 'Album';
        }
    }
    else {
        return null;
    }

    if ($content === '') {
        return null;
    }

    ob_start();
    $GLOBALS['post'] = ['title' => $page_title];
    if (is_file($theme_path . '/header.php')) {
        include $theme_path . '/header.php';
    }
    echo $content;
    if (is_file($theme_path . '/footer.php')) {
        include $theme_path . '/footer.php';
    }
    return ob_get_clean();
}, 10, 2);

// Admin menu: add Simuze Community items (Modular uses admin_menu_items filter)
add_filter('admin_menu_items', function ($items) {
    $adminUrl = defined('ADMIN_URL') ? ADMIN_URL : '';
    $base = $adminUrl . '/module-page.php?module=simuze-community&page=';
    $sub = [
        ['label' => 'Overview', 'url' => $base . 'index'],
        ['label' => 'Groups', 'url' => $base . 'groups'],
        ['label' => 'Group Types', 'url' => $base . 'group-types'],
        ['label' => 'Reviews', 'url' => $base . 'reviews'],
        ['label' => 'Reports', 'url' => $base . 'reports'],
        ['label' => 'Messages', 'url' => $base . 'messages'],
        ['label' => 'Activity', 'url' => $base . 'activity'],
        ['label' => 'Settings', 'url' => $base . 'settings'],
    ];
    $items[] = [
        'label'   => 'Simuze Community',
        'url'     => $base . 'index',
        'icon'    => 'fas fa-users',
        'match'   => 'simuze-community',
        'sub'     => $sub,
    ];
    return $items;
});
