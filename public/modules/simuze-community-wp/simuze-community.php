<?php
/**
 * Plugin Name: Simuze Community
 * Plugin URI: https://simuze.com
 * Description: Custom community system for Simuze - Users, Groups, Activity, Messages, Reviews & Reports
 * Version: 1.0.0
 * Author: Atypisch - Marten Timan
 * Author URI: http://www.atypisch.nl
 * License: GPL v2 or later
 * Text Domain: simuze-community
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SIMUZE_COMMUNITY_VERSION', '1.0.0');
define('SIMUZE_COMMUNITY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIMUZE_COMMUNITY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SIMUZE_COMMUNITY_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Database table prefix
define('SIMUZE_DB_PREFIX', 'simuze_');

// External Auth API endpoint (configurable via settings)
define('SIMUZE_AUTH_API_URL', get_option('simuze_auth_api_url', 'https://api.simezu.com/auth'));

/**
 * Main plugin class
 */
class Simuze_Community {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize components
        add_action('plugins_loaded', array($this, 'load_components'), 10);
        add_action('init', array($this, 'init_components'), 20);
        // Once: clear location backfill flags so province/country/city get lat/lng (for map icons)
        add_action('init', array($this, 'maybe_trigger_location_backfill'), 1);
        
        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core classes
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
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-payout.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-review.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-report.php';
        
        // API
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/api/class-rest-api.php';
        
        // Commerce classes
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-song.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-album.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-discount.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-order.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-download-token.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-cart.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-simezu-payments.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-download-handler.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-seller-account.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-audio-converter.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-purchase-flow.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/commerce/class-download-flow.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/api/class-commerce-api.php';
        
        // Admin
        if (is_admin()) {
            require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/class-admin.php';
        }
        
        // Frontend
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-templates.php';
        
        // Utilities
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-geo.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-helpers.php';
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-avatar.php';
        
        // Subscription system
        require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/subscriptions/class-subscription-loader.php';
    }
    
    /**
     * Load components after plugins_loaded
     */
    public function load_components() {
        // Initialize database and run migrations
        $database = Simuze_Database::get_instance();
        $database->maybe_run_migrations();
        
        // Initialize auth
        Simuze_Auth::get_instance();
        
        // Initialize REST API
        Simuze_REST_API::get_instance();
        
        // Initialize shortcodes
        Simuze_Shortcodes::get_instance();
        
        // Initialize admin
        if (is_admin()) {
            Simuze_Admin::get_instance();
        }
        
        // Initialize Commerce API
        new Simuze_Commerce_API();
        
        // Initialize Download Handler
        Simuze_Download_Handler::get_instance();
        
        // Initialize Purchase Flow
        Simuze_Purchase_Flow::get_instance();
    }
    
    /**
     * Once: clear location backfill options so province/country/city backfills run and fill lat/lng.
     */
    public function maybe_trigger_location_backfill() {
        if (get_option('simuze_location_icons_backfill_triggered')) {
            return;
        }
        delete_option('simuze_province_geo_backfilled');
        delete_option('simuze_country_geo_backfilled');
        delete_option('simuze_city_geo_backfilled');
        update_option('simuze_location_icons_backfill_triggered', 1);
    }

    /**
     * Initialize components on init
     */
    public function init_components() {
        // Set up current user
        $this->setup_current_user();
        
        // Register rewrite rules
        $this->register_rewrite_rules();
        
        // Prevent 404 BEFORE WordPress sets it
        add_filter('pre_handle_404', array($this, 'prevent_404_for_groups'), 10, 2);
        
        // Prevent 404 BEFORE WordPress sets it - this runs before handle_404()
        add_filter('pre_handle_404', array($this, 'prevent_404_for_groups'), 10, 2);
        
        // Handle template redirects - use priority 0 to run as early as possible, before WordPress sets 404
        add_action('template_redirect', array($this, 'handle_template_redirect'), 0);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Register rewrite rules for profile and user pages
     */
    private function register_rewrite_rules() {
        // Add query vars
        add_filter('query_vars', function($vars) {
            $vars[] = 'simuze_profile';
            $vars[] = 'simuze_username';
            $vars[] = 'simuze_group';
            $vars[] = 'simuze_tab';
            $vars[] = 'simuze_playlist';
            $vars[] = 'simuze_create_group';
            $vars[] = 'simuze_profile_redirect';
            return $vars;
        });
        
        // Also register via add_rewrite_tag for better compatibility
        add_rewrite_tag('%simuze_tab%', '([^/]+)');
        add_rewrite_tag('%simuze_group_tab%', '([^/]+)');
        
        // Register query vars via filter (in addition to add_rewrite_tag above)
        add_filter('query_vars', function($vars) {
            $vars[] = 'simuze_profile';
            $vars[] = 'simuze_username';
            $vars[] = 'simuze_group';
            $vars[] = 'simuze_group_tab';
            $vars[] = 'simuze_create_group';
            $vars[] = 'simuze_playlist';
            $vars[] = 'simuze_unique_handle';
            $vars[] = 'album_slug';
            $vars[] = 'track_slug';
            return $vars;
        });
        
        // SIMEZU integration routes - /user/profile and /user/account (redirect to current user)
        add_rewrite_rule(
            '^user/profile/?$',
            'index.php?simuze_profile=1&simuze_profile_redirect=1',
            'top'
        );
        
        add_rewrite_rule(
            '^user/account/?$',
            'index.php?simuze_profile=1&simuze_profile_redirect=1&simuze_tab=settings',
            'top'
        );
        
        // Profile page (own profile)
        add_rewrite_rule(
            '^profile/?$',
            'index.php?simuze_profile=1',
            'top'
        );
        
        // User playlist page
        add_rewrite_rule(
            '^user/([^/]+)/playlists/([^/]+)/?$',
            'index.php?simuze_profile=1&simuze_username=$matches[1]&simuze_playlist=$matches[2]',
            'top'
        );
        
        // User profile tab pages (must come before general user profile rule)
        add_rewrite_rule(
            '^user/([^/]+)/([^/]+)/?$',
            'index.php?simuze_profile=1&simuze_username=$matches[1]&simuze_tab=$matches[2]',
            'top'
        );
        
        // User profile by username
        add_rewrite_rule(
            '^user/([^/]+)/?$',
            'index.php?simuze_profile=1&simuze_username=$matches[1]',
            'top'
        );
        
        // Group tab pages (must come before general group rule)
        add_rewrite_rule(
            '^group/([^/]+)/([^/]+)/?$',
            'index.php?simuze_group=$matches[1]&simuze_group_tab=$matches[2]',
            'top'
        );
        
        // Group page
        add_rewrite_rule(
            '^group/([^/]+)/?$',
            'index.php?simuze_group=$matches[1]',
            'top'
        );
        
        // Create group page
        add_rewrite_rule(
            '^groups/create/?$',
            'index.php?simuze_create_group=1',
            'top'
        );
    }
    
    /**
     * Handle template redirects for profile/group pages
     */
    public function handle_template_redirect() {
        global $wp_query;
        
        // Prevent 404 from being set too early
        // Check REQUEST_URI first to catch group handles before WordPress sets 404
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $parsed_url = parse_url($request_uri);
        $path = isset($parsed_url['path']) ? trim($parsed_url['path'], '/') : '';
        
        // Remove WordPress subdirectory from path if present
        $home_path = parse_url(home_url(), PHP_URL_PATH);
        if ($home_path && $home_path !== '/') {
            $home_path = trim($home_path, '/');
            if (strpos($path, $home_path) === 0) {
                $path = substr($path, strlen($home_path));
                $path = trim($path, '/');
            }
        }
        
        $path_parts = explode('/', $path);
        $first_segment = !empty($path_parts) ? $path_parts[0] : '';
        
        // Create group page
        if (get_query_var('simuze_create_group')) {
            $this->render_create_group_page();
            exit;
        }
        
        // Playlist player page
        if (get_query_var('simuze_profile') && get_query_var('simuze_playlist')) {
            $this->render_playlist_page();
            exit;
        }
        
        // Profile page - check both query var and URL path
        // Also check for /user/profile and /user/account specifically
        $is_profile_page = get_query_var('simuze_profile') || 
                          $first_segment === 'user' || 
                          $first_segment === 'profile' ||
                          (isset($path_parts[0]) && $path_parts[0] === 'user' && isset($path_parts[1]) && ($path_parts[1] === 'profile' || $path_parts[1] === 'account'));
        
        if ($is_profile_page) {
            // Make sure we exit early to prevent other templates from loading
            error_log('[Simuze Community] handle_template_redirect - Profile page detected, rendering profile');
            $this->render_profile_page();
            exit;
        }
        
        // Group page (can be triggered by simuze_group query var or unique_handle)
        if (get_query_var('simuze_group')) {
            $this->render_group_page();
            exit;
        }
        
        // Also handle groups loaded via unique_handle (from theme's rewrite rules)
        $unique_handle = get_query_var('simuze_unique_handle');
        
        // If query var is not set, try to get from REQUEST_URI (rewrite rules might not be flushed)
        if (!$unique_handle && !empty($first_segment)) {
            // Check if this looks like a group handle (not user, profile, group, or standard pages)
            $excluded_segments = array('user', 'profile', 'group', 'music', 'network', 'live', 'weblog', 'about', 'store', 'dashboard', 'wp-admin', 'wp-content', 'wp-includes', 'wp-json', 'feed', 'category', 'tag', 'author', 'page', 'post', 'track', 'album', 'playlist');
            
            // Check if this is a group handle with a tab (e.g., /test-group/dashboard)
            if (!in_array($first_segment, $excluded_segments) && !empty($path_parts[1])) {
                // This might be a group handle with a tab - try to find it
                if (class_exists('Simuze_Group')) {
                    $group = Simuze_Group::get_by_unique_handle($first_segment);
                    if ($group) {
                        // Check if second segment is a valid tab
                        $valid_tabs = array('dashboard', 'music', 'members', 'activity', 'invites', 'settings');
                        $tab_segment = $path_parts[1];
                        if (in_array($tab_segment, $valid_tabs)) {
                            // Prevent 404
                            $wp_query->is_404 = false;
                            $wp_query->is_page = false;
                            $wp_query->is_singular = false;
                            // Set the tab in query var for template
                            set_query_var('simuze_group_tab', $tab_segment);
                            $this->render_group_page_by_group($group);
                            exit;
                        }
                    }
                }
            } elseif (!in_array($first_segment, $excluded_segments) && empty($path_parts[1])) {
                // This might be a group handle without tab - try to find it
                if (class_exists('Simuze_Group')) {
                    $group = Simuze_Group::get_by_unique_handle($first_segment);
                    if ($group) {
                        // Prevent 404
                        $wp_query->is_404 = false;
                        $wp_query->is_page = false;
                        $wp_query->is_singular = false;
                        $this->render_group_page_by_group($group);
                        exit;
                    }
                }
            }
        }
        
        if ($unique_handle && class_exists('Simuze_Group')) {
            $group = Simuze_Group::get_by_unique_handle($unique_handle);
            
            if ($group && !get_query_var('album_slug') && !get_query_var('track_slug')) {
                // This is a group profile page, render it
                // Prevent 404
                $wp_query->is_404 = false;
                $wp_query->is_page = false;
                $wp_query->is_singular = false;
                $this->render_group_page_by_group($group);
                exit;
            }
        }
    }
    
    /**
     * Prevent 404 for group pages - runs BEFORE WordPress sets 404
     */
    public function prevent_404_for_groups($preempt, $wp_query) {
        // Check REQUEST_URI to see if this might be a group handle
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $parsed_url = parse_url($request_uri);
        $path = isset($parsed_url['path']) ? trim($parsed_url['path'], '/') : '';
        
        // Remove WordPress subdirectory from path if present
        $home_path = parse_url(home_url(), PHP_URL_PATH);
        if ($home_path && $home_path !== '/') {
            $home_path = trim($home_path, '/');
            if (strpos($path, $home_path) === 0) {
                $path = substr($path, strlen($home_path));
                $path = trim($path, '/');
            }
        }
        
        $path_parts = explode('/', $path);
        $first_segment = !empty($path_parts) ? $path_parts[0] : '';
        
        // Check if this looks like a group handle (not user, profile, group, or standard pages)
        $excluded_segments = array('user', 'profile', 'group', 'music', 'network', 'live', 'weblog', 'about', 'store', 'dashboard', 'wp-admin', 'wp-content', 'wp-includes', 'wp-json', 'feed', 'category', 'tag', 'author', 'page', 'post', 'track', 'album', 'playlist');
        
        if (!empty($first_segment) && !in_array($first_segment, $excluded_segments) && empty($path_parts[1])) {
            // This might be a group handle - try to find it
            if (class_exists('Simuze_Group')) {
                $group = Simuze_Group::get_by_unique_handle($first_segment);
                if ($group) {
                    // Found a group - prevent 404 and let handle_template_redirect handle it
                    $wp_query->is_404 = false;
                    return true; // Return true to prevent WordPress from setting 404
                }
            }
        }
        
        // Also check query var
        $unique_handle = get_query_var('simuze_unique_handle');
        if ($unique_handle && class_exists('Simuze_Group')) {
            $group = Simuze_Group::get_by_unique_handle($unique_handle);
            if ($group && !get_query_var('album_slug') && !get_query_var('track_slug')) {
                $wp_query->is_404 = false;
                return true; // Return true to prevent WordPress from setting 404
            }
        }
        
        return $preempt; // Let WordPress handle it normally
    }
    
    /**
     * Render create group page
     */
    private function render_create_group_page() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/create-group.php';
    }
    
    /**
     * Render profile page
     * Supports: /profile/, /profile/?user={simezu_id}, /user/{username}/, /user/profile, /user/account
     */
    private function render_profile_page() {
        $auth = Simuze_Auth::get_instance();
        $username = get_query_var('simuze_username');
        $profile_redirect = get_query_var('simuze_profile_redirect');
        
        // Get tab from query var (clean URL) or from GET parameter (legacy)
        $tab = get_query_var('simuze_tab');
        if (empty($tab)) {
            $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        }
        
        // Handle SIMEZU integration routes: /user/profile and /user/account
        // These show the actual logged-in user's profile
        if ($profile_redirect) {
            error_log('[Simuze Community] render_profile_page - profile_redirect detected, getting current user');
            $current_user = $auth->get_current_user();
            
            error_log('[Simuze Community] render_profile_page - get_current_user() returned: ' . ($current_user ? 'User ID ' . $current_user->id : 'null'));
            
            // If no user found via JWT token, try to get from SIMEZU API directly
            if (!$current_user) {
                error_log('[Simuze Community] render_profile_page - No user from JWT, trying SIMEZU API');
                // Try to get user from SIMEZU API using cookie/session
                $current_user = $this->get_user_from_simezu_session();
                error_log('[Simuze Community] render_profile_page - get_user_from_simezu_session() returned: ' . ($current_user ? 'User ID ' . $current_user->id : 'null'));
            }
            
            if ($current_user) {
                // Set $user and skip the rest of the logic - go directly to rendering
                error_log('[Simuze Community] render_profile_page - User found (ID: ' . $current_user->id . '), rendering profile directly');
                $user = $current_user;
                
                // Render the profile immediately
                get_header();
                include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile.php';
                get_footer();
                return;
            } else {
                // Not logged in - redirect to login
                error_log('[Simuze Community] render_profile_page - No user found, redirecting to login');
                $login_url = '/simezu/login.php?app=simuze&redirect=' . urlencode(home_url('/user/profile'));
                wp_redirect($login_url);
                exit;
            }
        }
        
        // If username not in query var, try to get from URL path
        if (empty($username)) {
            $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $parsed_url = parse_url($request_uri);
            $path = isset($parsed_url['path']) ? trim($parsed_url['path'], '/') : '';
            $path_parts = explode('/', $path);
            
            if (!empty($path_parts) && $path_parts[0] === 'user' && !empty($path_parts[1])) {
                $username = $path_parts[1];
                
                // If there's a third part, it's the tab
                if (!empty($path_parts[2]) && empty($tab)) {
                    $tab = sanitize_text_field($path_parts[2]);
                }
            }
        }
        
        // Default to dashboard if no tab specified
        if (empty($tab)) {
            $tab = 'dashboard';
        }
        $user = null;
        
        // Check for ?user={simezu_id} parameter (from Simezu redirect)
        if (isset($_GET['user']) && !empty($_GET['user'])) {
            $simezu_user_id = sanitize_text_field($_GET['user']);
            
            // Try to find user by Simezu ID
            $user = Simuze_User::get_by_simezu_id($simezu_user_id);
            
            // If not found locally, the user might need to be synced on first login
            if (!$user) {
                // Check if current user matches the requested ID
                $current_user = $auth->get_current_user();
                if ($current_user && $current_user->simezu_id === $simezu_user_id) {
                    $user = $current_user;
                }
            }
        }
        // Check for username in URL path (/user/{username}/)
        elseif (!empty($username)) {
            // Decode URL-encoded username
            $username = urldecode($username);
            
            $user = Simuze_User::get_by_username($username);
            
            // If user was found but had invalid username (migrated), redirect to new username
            if ($user && $user->username !== $username && (strpos($username, '@') !== false || preg_match('/[^a-z0-9-]/', $username))) {
                wp_redirect($user->get_url());
                exit;
            }
            
            if (!$user) {
                // Try to sync from Simezu
                $user = $this->sync_user_from_simezu($username);
            }
        }
        // Default: show current logged-in user's profile
        else {
            $user = $auth->get_current_user();
            
            // If no user found via JWT token, try to get from SIMEZU API directly
            if (!$user) {
                error_log('[Simuze Community] render_profile_page - No user from JWT in default case, trying SIMEZU API');
                $user = $this->get_user_from_simezu_session();
                error_log('[Simuze Community] render_profile_page - get_user_from_simezu_session() returned: ' . ($user ? 'User ID ' . $user->id : 'null'));
            }
            
            // Not logged in - redirect to Simezu login
            if (!$user) {
                error_log('[Simuze Community] render_profile_page - No user found in default case, redirecting to login');
                $login_url = '/simezu/login.php?app=simuze&redirect=' . urlencode(home_url('/profile/'));
                wp_redirect($login_url);
                exit;
            }
            
            // Redirect /profile/ to /user/{username}/ for consistency
            $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            if (strpos($request_uri, '/profile/') !== false && strpos($request_uri, '/user/') === false) {
                error_log('[Simuze Community] render_profile_page - Redirecting /profile/ to /user/{username}/');
                wp_redirect($user->get_url());
                exit;
            }
        }
        
        if (!$user) {
            status_header(404);
            get_header();
            echo '<div class="simuze-error"><h1>User not found</h1><p>The user you are looking for does not exist.</p></div>';
            get_footer();
            return;
        }
        
        // Render the profile
        get_header();
        
        // Include profile template
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile.php';
        
        get_footer();
    }
    
    /**
     * Render group page
     */
    private function render_group_page() {
        $slug = get_query_var('simuze_group');
        $group = Simuze_Group::get_by_slug($slug);
        
        if (!$group) {
            status_header(404);
            get_header();
            echo '<div class="simuze-error"><h1>Group not found</h1><p>The group you are looking for does not exist.</p></div>';
            get_footer();
            return;
        }
        
        $this->render_group_page_by_group($group);
    }
    
    /**
     * Render group page by group object
     */
    private function render_group_page_by_group($group) {
        // Ensure $group is available for template
        if (!isset($group) || !$group) {
            status_header(404);
            get_header();
            echo '<div class="simuze-error"><h1>Group not found</h1><p>The group you are looking for does not exist.</p></div>';
            get_footer();
            return;
        }
        
        // Set global $group variable for template
        global $wp_query;
        
        // CRITICAL: Prevent WordPress from treating this as 404
        $wp_query->is_404 = false;
        $wp_query->is_page = false;
        $wp_query->is_singular = false;
        $wp_query->is_home = false;
        $wp_query->is_archive = false;
        
        // Set status header to 200
        status_header(200);
        
        // Make sure $group is available in template scope
        // Get tab from query var (clean URL) or fall back to GET parameter
        $tab = get_query_var('simuze_group_tab');
        if (empty($tab)) {
            $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        }
        // Set tab in GET for backward compatibility with templates
        $_GET['tab'] = $tab;
        
        if (current_user_can('manage_options')) {
            error_log('SIMUZE DEBUG: render_group_page_by_group - Rendering group: ' . $group->name . ' (ID: ' . $group->id . ', handle: ' . $group->unique_handle . ', tab: ' . $tab . ')');
        }
        
        get_header();
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group.php';
        get_footer();
    }
    
    /**
     * Render playlist player page - uses existing theme template
     */
    private function render_playlist_page() {
        $username = get_query_var('simuze_username');
        $playlist_slug = get_query_var('simuze_playlist');
        
        // Use existing theme template (single-playlist.php)
        // Set up variables that the template expects
        $_SERVER['REQUEST_URI'] = '/user/' . $username . '/playlists/' . $playlist_slug . '/';
        
        // Load the existing theme template
        $template = get_template_directory() . '/single-playlist.php';
        
        if (file_exists($template)) {
            include $template;
        } else {
            status_header(404);
            get_header();
            echo '<div class="simuze-error"><h1>Playlist template not found</h1></div>';
            get_footer();
        }
    }
    
    /**
     * Get user from SIMEZU session (when JWT token not available via local verification)
     * Uses SIMEZU API to get current user - preserves JWT tokens for other applications
     * Uses official SIMEZU API endpoints with proper headers
     */
    private function get_user_from_simezu_session() {
        // Get JWT token from cookies (preserve for other applications)
        $token = null;
        if (isset($_COOKIE['accessToken'])) {
            $token = $_COOKIE['accessToken'];
            error_log('[Simuze Community] get_user_from_simezu_session - Found accessToken cookie');
        } elseif (isset($_COOKIE['simuze_jwt_token'])) {
            $token = $_COOKIE['simuze_jwt_token'];
            error_log('[Simuze Community] get_user_from_simezu_session - Found simuze_jwt_token cookie');
        } else {
            error_log('[Simuze Community] get_user_from_simezu_session - No token found in cookies');
            // Try to get from JavaScript if available (for debugging)
            if (isset($_GET['debug_token'])) {
                $token = sanitize_text_field($_GET['debug_token']);
                error_log('[Simuze Community] get_user_from_simezu_session - Using debug token');
            }
        }
        
        if (!$token) {
            error_log('[Simuze Community] get_user_from_simezu_session - No token available, cannot fetch user');
            return null;
        }
        
        // SIMEZU App credentials (from integration docs)
        $app_id = 'a1719436-ecd9-11f0-ba55-155569101740';
        $api_key = 'a1719440-ecd9-11f0-ba55-155569101740';
        
        // Get base API URL - SIMEZU API is at /simezu/api
        $base_url = 'http://localhost/simezu/api';
        $base_url = rtrim($base_url, '/');
        
        // Try SIMEZU API endpoints in order of preference
        $endpoints = array(
            '/auth/users/me',           // Current user endpoint (from SIMEZU docs)
            '/auth/me',                 // Alternative /me endpoint
            '/account/batch/me',        // Batch endpoint (from profile templates)
        );
        
        // Build headers - SIMEZU requires X-App-ID, X-API-Key, and Authorization
        $headers = array(
            'Content-Type' => 'application/json',
            'X-App-ID' => $app_id,
            'X-API-Key' => $api_key,
            'Authorization' => 'Bearer ' . $token,
        );
        
        error_log('[Simuze Community] get_user_from_simezu_session - Using SIMEZU API with App ID: ' . $app_id);
        
        // Try each endpoint until one works
        foreach ($endpoints as $endpoint) {
            $full_url = $base_url . $endpoint;
            error_log('[Simuze Community] get_user_from_simezu_session - Trying SIMEZU endpoint: ' . $full_url);
            error_log('[Simuze Community] get_user_from_simezu_session - Headers: X-App-ID=' . $app_id . ', Authorization=Bearer ' . substr($token, 0, 20) . '...');
            
            $response = wp_remote_get($full_url, array(
                'headers' => $headers,
                'timeout' => 15,
                'sslverify' => false,
                'cookies' => $_COOKIE, // Forward all cookies to preserve session
                'redirection' => 0, // Don't follow redirects
            ));
            
            if (is_wp_error($response)) {
                $error_msg = $response->get_error_message();
                error_log('[Simuze Community] get_user_from_simezu_session - API Error for ' . $endpoint . ': ' . $error_msg);
                continue; // Try next endpoint
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            error_log('[Simuze Community] get_user_from_simezu_session - Response status: ' . $status_code . ' for ' . $endpoint);
            if ($status_code !== 200) {
                error_log('[Simuze Community] get_user_from_simezu_session - Response body (first 500 chars): ' . substr($body, 0, 500));
            }
            
            if ($status_code === 200) {
                $user_data = json_decode($body, true);
                
                // Handle different response formats from SIMEZU
                $user_id = null;
                if (!empty($user_data['id'])) {
                    $user_id = $user_data['id'];
                } elseif (!empty($user_data['user_id'])) {
                    $user_id = $user_data['user_id'];
                } elseif (!empty($user_data['sub'])) {
                    $user_id = $user_data['sub'];
                }
                
                if ($user_data && $user_id) {
                    error_log('[Simuze Community] get_user_from_simezu_session - Successfully got user from: ' . $endpoint);
                    error_log('[Simuze Community] get_user_from_simezu_session - User ID: ' . $user_id);
                    error_log('[Simuze Community] get_user_from_simezu_session - User data keys: ' . implode(', ', array_keys($user_data)));
                    
                    // Normalize user_data to have 'id' field
                    if (!isset($user_data['id']) || $user_data['id'] !== $user_id) {
                        $user_data['id'] = $user_id;
                    }
                    
                    // Get or create user in our database
                    $user = Simuze_User::get_by_simezu_id($user_data['id']);
                    
                    // Fallback: if not found by simezu_id, try by email (user might have been created with different simezu_id)
                    if (!$user && !empty($user_data['email'])) {
                        $user = Simuze_User::get_by_email($user_data['email']);
                        if ($user) {
                            // Update simezu_id if it's different
                            if ($user->simezu_id !== $user_data['id']) {
                                error_log('[Simuze Community] get_user_from_simezu_session - Updating simezu_id for existing user: ' . $user->id . ' (old: ' . $user->simezu_id . ', new: ' . $user_data['id'] . ')');
                                $user->update(array('simezu_id' => $user_data['id']));
                            }
                        }
                    }
                    
                    if (!$user) {
                        // Create new user
                        $username = $user_data['username'] ?? null;
                        if (empty($username) && !empty($user_data['email'])) {
                            $username = $this->generate_username_from_email($user_data['email']);
                        }
                        
                        // Handle different data formats from SIMEZU
                        $display_name = $user_data['display_name'] ?? null;
                        if (empty($display_name)) {
                            if (!empty($user_data['firstName']) || !empty($user_data['lastName'])) {
                                $display_name = trim(($user_data['firstName'] ?? '') . ' ' . ($user_data['lastName'] ?? ''));
                            } elseif (!empty($user_data['first_name']) || !empty($user_data['last_name'])) {
                                $display_name = trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''));
                            } else {
                                $display_name = $user_data['email'] ?? $username;
                            }
                        }
                        
                        $user = Simuze_User::create(array(
                            'simezu_id' => $user_data['id'],
                            'username' => $username,
                            'email' => $user_data['email'] ?? '',
                            'display_name' => $display_name,
                            'avatar_url' => $user_data['avatar_url'] ?? $user_data['avatarUrl'] ?? null,
                        ));
                        
                        // If creation failed (duplicate email), try to find existing user
                        if (!$user && !empty($user_data['email'])) {
                            error_log('[Simuze Community] get_user_from_simezu_session - User creation failed, trying to find by email: ' . $user_data['email']);
                            $user = Simuze_User::get_by_email($user_data['email']);
                            if ($user) {
                                // Update simezu_id and other fields
                                $update_data = array(
                                    'simezu_id' => $user_data['id'],
                                    'display_name' => $display_name,
                                );
                                $user->update($update_data);
                                error_log('[Simuze Community] get_user_from_simezu_session - Found existing user by email and updated: ' . $user->id);
                            }
                        }
                        
                        if ($user) {
                            error_log('[Simuze Community] get_user_from_simezu_session - Created new user: ' . $user->id);
                        }
                    } else {
                        // Update existing user
                        $display_name = $user_data['display_name'] ?? null;
                        if (empty($display_name)) {
                            if (!empty($user_data['firstName']) || !empty($user_data['lastName'])) {
                                $display_name = trim(($user_data['firstName'] ?? '') . ' ' . ($user_data['lastName'] ?? ''));
                            } elseif (!empty($user_data['first_name']) || !empty($user_data['last_name'])) {
                                $display_name = trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''));
                            } else {
                                $display_name = $user_data['email'] ?? $user->display_name;
                            }
                        }
                        
                        $update_data = array(
                            'email' => $user_data['email'] ?? $user->email,
                            'display_name' => $display_name,
                        );
                        $user->update($update_data);
                        
                        error_log('[Simuze Community] get_user_from_simezu_session - Updated existing user: ' . $user->id);
                    }
                    
                    return $user;
                } else {
                    error_log('[Simuze Community] get_user_from_simezu_session - No user data in response from ' . $endpoint);
                    error_log('[Simuze Community] get_user_from_simezu_session - Response body: ' . substr($body, 0, 500));
                }
            } else {
                error_log('[Simuze Community] get_user_from_simezu_session - Endpoint ' . $endpoint . ' returned status: ' . $status_code);
                if ($status_code !== 404) {
                    error_log('[Simuze Community] get_user_from_simezu_session - Response body: ' . substr($body, 0, 500));
                }
            }
        }
        
        // If all endpoints failed, log comprehensive error
        error_log('[Simuze Community] get_user_from_simezu_session - All endpoints failed. Available cookies: ' . print_r(array_keys($_COOKIE), true));
        return null;
    }
    
    /**
     * Generate username from email (helper)
     */
    private function generate_username_from_email($email) {
        $username = strtolower(explode('@', $email)[0]);
        $username = preg_replace('/[^a-z0-9-]/', '', $username);
        if (empty($username)) {
            $username = 'user' . time();
        }
        if (strlen($username) < 3) {
            $username = $username . '123';
        }
        $base_username = $username;
        $counter = 1;
        while (Simuze_User::get_by_username($username)) {
            $username = $base_username . '-' . $counter;
            $counter++;
        }
        return $username;
    }
    
    /**
     * Sync user from Simezu API
     */
    private function sync_user_from_simezu($username_or_id) {
        // Try to fetch user from Simezu API
        $api_url = get_option('simuze_auth_api_url', 'http://localhost/simezu/api');
        $api_url = rtrim($api_url, '/');
        
        // Ensure we have the correct endpoint path
        if (strpos($api_url, '/api') === false && strpos($api_url, '/api.php') === false) {
            $api_url = rtrim($api_url, '/') . '/api';
        }
        
        $endpoint = '/auth/users/' . urlencode($username_or_id);
        $full_url = rtrim($api_url, '/') . $endpoint;
        
        $response = wp_remote_get($full_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 10,
            'sslverify' => false,
        ));
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $user_data = json_decode($body, true);
        
        if (!$user_data || empty($user_data['id'])) {
            return null;
        }
        
        // Get or create user
        $user = Simuze_User::get_by_simezu_id($user_data['id']);
        if (!$user) {
            $username = $user_data['username'] ?? null;
            if (empty($username) && !empty($user_data['email'])) {
                $username = $this->generate_username_from_email($user_data['email']);
            }
            
            $user = Simuze_User::create(array(
                'simezu_id' => $user_data['id'],
                'username' => $username,
                'email' => $user_data['email'] ?? '',
                'display_name' => $user_data['display_name'] ?? $user_data['email'] ?? $username,
                'avatar_url' => $user_data['avatar_url'] ?? null,
            ));
        }
        
        return $user;
    }
    
    /**
     * Setup current user from external auth
     */
    private function setup_current_user() {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if ($current_user) {
            // Set global
            $GLOBALS['simuze_current_user'] = $current_user;
            
            // Make available in JS
            add_action('wp_footer', function() use ($current_user) {
                ?>
                <script>
                window.simuze = window.simuze || {};
                window.simuze.currentUser = <?php echo json_encode($current_user->to_array()); ?>;
                </script>
                <?php
            });
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'simuze-community',
            SIMUZE_COMMUNITY_PLUGIN_URL . 'assets/css/community.css',
            array(),
            SIMUZE_COMMUNITY_VERSION
        );
        
        // Full-width styles for profile and group pages
        wp_enqueue_style(
            'simuze-profile-fullwidth',
            get_template_directory_uri() . '/css/profile-fullwidth.css',
            array('simuze-community'),
            SIMUZE_COMMUNITY_VERSION
        );
        
        // Chart.js for dashboard metrics (CDN)
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            array(),
            '4.4.1',
            true
        );
        
        // Cropper.js for image cropping (CSP-safe host)
        wp_enqueue_style(
            'cropperjs',
            'https://unpkg.com/cropperjs@1.6.1/dist/cropper.min.css',
            array(),
            '1.6.1'
        );
        wp_enqueue_script(
            'cropperjs',
            'https://unpkg.com/cropperjs@1.6.1/dist/cropper.min.js',
            array(),
            '1.6.1',
            true
        );
        
        wp_enqueue_script(
            'simuze-community',
            SIMUZE_COMMUNITY_PLUGIN_URL . 'assets/js/community.js',
            array('jquery'),
            SIMUZE_COMMUNITY_VERSION,
            true
        );
        
        // Mentions script
        wp_enqueue_script(
            'simuze-mentions',
            SIMUZE_COMMUNITY_PLUGIN_URL . 'assets/js/mentions.js',
            array('jquery', 'simuze-community'),
            SIMUZE_COMMUNITY_VERSION,
            true
        );
        
        // Activity script (WYSIWYG, embeds)
        wp_enqueue_script(
            'simuze-activity',
            SIMUZE_COMMUNITY_PLUGIN_URL . 'assets/js/activity.js',
            array('jquery', 'simuze-community'),
            SIMUZE_COMMUNITY_VERSION,
            true
        );
        
        // Group wizard script (for modal)
        wp_enqueue_script(
            'simuze-group-wizard',
            SIMUZE_COMMUNITY_PLUGIN_URL . 'assets/js/group-wizard.js',
            array('jquery', 'simuze-community'),
            SIMUZE_COMMUNITY_VERSION,
            true
        );
        
        // Create group page script (with cropping)
        wp_enqueue_script(
            'simuze-create-group',
            SIMUZE_COMMUNITY_PLUGIN_URL . 'assets/js/create-group.js',
            array('jquery', 'simuze-community', 'cropperjs'),
            SIMUZE_COMMUNITY_VERSION,
            true
        );
        
        // Load music upload script on upload pages
        $is_upload_page = get_query_var('simuze_profile') && isset($_GET['tab']) && $_GET['tab'] === 'upload';
        if ($is_upload_page || (is_page() && get_query_var('simuze_profile'))) {
            // Check if we're on an upload tab
            $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
            if ($tab === 'upload' || $tab === '') {
                wp_enqueue_script(
                    'simuze-music-upload',
                    get_template_directory_uri() . '/js/music-upload.js',
                    array('jquery'),
                    '1.0.1',
                    true
                );
                
                // Localize music upload script
                wp_localize_script('simuze-music-upload', 'simuze_upload', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('simuze_upload_nonce'),
                    'group_id' => isset($_GET['group_id']) ? intval($_GET['group_id']) : 0,
                    'group_name' => isset($_GET['group_name']) ? sanitize_text_field($_GET['group_name']) : '',
                    'i18n' => array(
                        'upload_error' => __('Upload failed', 'simuze'),
                        'upload_success' => __('Upload successful', 'simuze'),
                    ),
                ));
            }
        }
        
        // Localize script
        wp_localize_script('simuze-community', 'simuzeCommunity', array(
            'apiUrl' => rest_url('simuze/v1/'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'homeUrl' => home_url('/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'currentUser' => isset($GLOBALS['simuze_current_user']) ? $GLOBALS['simuze_current_user']->to_array() : null,
            'loginUrl' => Simuze_Auth::get_instance()->get_login_url(),
            'registerUrl' => Simuze_Auth::get_instance()->get_register_url(),
            'defaultAvatar' => get_template_directory_uri() . '/images/profile_default.jpg',
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'simuze') === false) {
            return;
        }
        
        wp_enqueue_style(
            'simuze-community-admin',
            SIMUZE_COMMUNITY_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SIMUZE_COMMUNITY_VERSION
        );
        
        wp_enqueue_script(
            'simuze-community-admin',
            SIMUZE_COMMUNITY_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SIMUZE_COMMUNITY_VERSION,
            true
        );
        
        // Localize script for admin
        wp_localize_script('simuze-community-admin', 'simuzeCommunity', array(
            'apiUrl' => rest_url('simuze/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'simuze-community',
            false,
            dirname(SIMUZE_COMMUNITY_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        Simuze_Database::get_instance()->create_tables();
        
        // Set default options
        add_option('simuze_auth_api_key', '');
        add_option('simuze_auth_api_url', 'https://api.simezu.com/auth');
        add_option('simuze_community_version', SIMUZE_COMMUNITY_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize plugin
Simuze_Community::get_instance();

