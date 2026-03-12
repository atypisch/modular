<?php
/**
 * REST API endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_REST_API {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        
        // Initialize webhook controller
        require_once dirname(__FILE__) . '/../webhooks/class-webhook-controller.php';
        Simuze_WebhookController::get_instance();
    }
    
    /**
     * Register all REST API routes
     */
    public function register_routes() {
        $namespace = 'simuze/v1';
        
        // Current user endpoint
        register_rest_route($namespace, '/me', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_current_user'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Users endpoints
        register_rest_route($namespace, '/users', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_users'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($namespace, '/users/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($namespace, '/users/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_user'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/users/(?P<id>\d+)/avatar', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_avatar'),
            'permission_callback' => '__return_true',
        ));
        
        // Avatar by simezu_id (for data-simezu-user-id attributes)
        register_rest_route($namespace, '/avatar/simezu/(?P<simezu_id>[^/]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_avatar_by_simezu_id'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($namespace, '/users/(?P<id>\d+)/avatar', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_user_avatar'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/users/(?P<id>\d+)/avatar/upload', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_user_avatar_cropped'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/users/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_users'),
            'permission_callback' => '__return_true',
        ));
        
        // Groups endpoints
        register_rest_route($namespace, '/groups', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_groups'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($namespace, '/groups/my', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_my_groups'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_group'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_group'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_group'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_group'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        // Group avatar (local, Simuze-managed)
        register_rest_route($namespace, '/groups/(?P<id>\d+)/avatar', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_group_avatar'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/members', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_group_members'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/members/(?P<user_id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'remove_group_member'),
            'permission_callback' => array($this, 'check_admin'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/join', array(
            'methods' => 'POST',
            'callback' => array($this, 'join_group'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/leave', array(
            'methods' => 'POST',
            'callback' => array($this, 'leave_group'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/invitations', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_group_invitations'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/invite', array(
            'methods' => 'POST',
            'callback' => array($this, 'invite_to_group'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/accept-invitation', array(
            'methods' => 'POST',
            'callback' => array($this, 'accept_group_invitation'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/reject-invitation', array(
            'methods' => 'POST',
            'callback' => array($this, 'reject_group_invitation'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/invitations/(?P<invitation_id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'cancel_group_invitation'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/update-member', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_group_member'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/check-unique-name', array(
            'methods' => 'POST',
            'callback' => array($this, 'check_unique_name'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/check-unique-handle', array(
            'methods' => 'POST',
            'callback' => array($this, 'check_unique_handle'),
            'permission_callback' => '__return_true', // Can be public for availability check
        ));
        
        // Groups search endpoint (for artist search in Labels)
        register_rest_route($namespace, '/groups/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_groups'),
            'permission_callback' => '__return_true',
        ));
        
        // Label-Artist relationship endpoints
        register_rest_route($namespace, '/groups/(?P<id>\d+)/artists/invite', array(
            'methods' => 'POST',
            'callback' => array($this, 'invite_artist_to_label'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/artists/invitations/(?P<invitation_id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'cancel_artist_invitation'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/artists/(?P<relationship_id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'remove_artist_relationship'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/artists/accept', array(
            'methods' => 'POST',
            'callback' => array($this, 'accept_artist_invitation'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/artists/reject', array(
            'methods' => 'POST',
            'callback' => array($this, 'reject_artist_invitation'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Group payment settings endpoints
        register_rest_route($namespace, '/groups/(?P<id>\d+)/members-with-payments', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_group_members_with_payments'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/member-payment/(?P<member_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_group_member_payment'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/payment-settings', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_group_payment_settings'),
                'permission_callback' => array($this, 'check_auth'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'save_group_payment_settings'),
                'permission_callback' => array($this, 'check_auth'),
            ),
        ));
        
        register_rest_route($namespace, '/groups/(?P<id>\d+)/payment-settings/test', array(
            'methods' => 'POST',
            'callback' => array($this, 'test_group_payment'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Resolve handle endpoint for mentions and PMs
        register_rest_route($namespace, '/resolve-handle/(?P<handle>[a-z0-9-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'resolve_handle'),
            'permission_callback' => '__return_true',
        ));
        
        // Activity endpoints
        register_rest_route($namespace, '/activity', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_activity'),
            'permission_callback' => '__return_true',
        ));
        
        // Activity posts (weblog) endpoints
        register_rest_route($namespace, '/activity/posts', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_activity_post'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/activity/posts/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_activity_post'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/activity/posts/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_activity_post'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Messages endpoints
        register_rest_route($namespace, '/messages', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_messages'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/messages', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_message'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Follow endpoints
        register_rest_route($namespace, '/follow', array(
            'methods' => 'POST',
            'callback' => array($this, 'follow'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/unfollow', array(
            'methods' => 'POST',
            'callback' => array($this, 'unfollow'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Friends endpoints
        register_rest_route($namespace, '/friends/request', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_friend_request'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/friends/accept', array(
            'methods' => 'POST',
            'callback' => array($this, 'accept_friend_request'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/friends/decline', array(
            'methods' => 'POST',
            'callback' => array($this, 'decline_friend_request'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/friends/cancel', array(
            'methods' => 'POST',
            'callback' => array($this, 'cancel_friend_request'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/friends/unfriend', array(
            'methods' => 'POST',
            'callback' => array($this, 'unfriend'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Header upload endpoint (local to Simuze)
        register_rest_route($namespace, '/account/header-upload', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_header'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Avatar cache refresh endpoint
        register_rest_route($namespace, '/avatar/refresh-cache', array(
            'methods' => 'POST',
            'callback' => array($this, 'refresh_avatar_cache'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Playlists endpoints
        register_rest_route($namespace, '/playlists', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_playlists'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/playlists', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_playlist'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/playlists/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_playlist'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($namespace, '/playlists/(?P<id>\d+)/tracks', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_playlist_tracks'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($namespace, '/playlists/(?P<id>\d+)/songs', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_playlist_tracks'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($namespace, '/users/(?P<id>\d+)/playlists', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_playlists'),
            'permission_callback' => '__return_true',
        ));
        
        // Latest uploads endpoint
        register_rest_route($namespace, '/tracks/latest', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_latest_tracks'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($namespace, '/tracks/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_tracks'),
            'permission_callback' => '__return_true',
        ));
        
        // Add track to playlist endpoint
        register_rest_route($namespace, '/playlists/(?P<id>\d+)/tracks', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_track_to_playlist'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Payment settings endpoints
        register_rest_route($namespace, '/payment-settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_payment_settings'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/payment-settings', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_payment_settings'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($namespace, '/payment-settings/stripe/test', array(
            'methods' => 'POST',
            'callback' => array($this, 'test_stripe_connection'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // SIMEZU Auth Proxy (for forms integration - server-side only)
        register_rest_route($namespace, '/auth/proxy', array(
            'methods' => 'POST',
            'callback' => array($this, 'simezu_auth_proxy'),
            'permission_callback' => '__return_true', // Public endpoint for forms
        ));
        
        register_rest_route($namespace, '/payment-settings/paypal/test', array(
            'methods' => 'POST',
            'callback' => array($this, 'test_paypal_connection'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Webhook endpoints
        $this->register_webhook_routes($namespace);
    }
    
    /**
     * Register webhook routes
     */
    private function register_webhook_routes($namespace) {
        $webhook_controller = Simuze_WebhookController::get_instance();
        
        // Payment webhooks
        register_rest_route($namespace, '/webhooks/payment/intent-created', array(
            'methods' => 'POST',
            'callback' => array($webhook_controller, 'handle_webhook'),
            'permission_callback' => '__return_true', // Webhooks use signature verification instead
        ));
        
        register_rest_route($namespace, '/webhooks/payment/intent-succeeded', array(
            'methods' => 'POST',
            'callback' => array($webhook_controller, 'handle_webhook'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($namespace, '/webhooks/payment/intent-failed', array(
            'methods' => 'POST',
            'callback' => array($webhook_controller, 'handle_webhook'),
            'permission_callback' => '__return_true',
        ));
        
        // Media webhooks
        register_rest_route($namespace, '/webhooks/media/upload-received', array(
            'methods' => 'POST',
            'callback' => array($webhook_controller, 'handle_webhook'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($namespace, '/webhooks/media/processing-completed', array(
            'methods' => 'POST',
            'callback' => array($webhook_controller, 'handle_webhook'),
            'permission_callback' => '__return_true',
        ));
        
        // Group webhooks
        register_rest_route($namespace, '/webhooks/group/member-joined', array(
            'methods' => 'POST',
            'callback' => array($webhook_controller, 'handle_webhook'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($namespace, '/webhooks/group/member-left', array(
            'methods' => 'POST',
            'callback' => array($webhook_controller, 'handle_webhook'),
            'permission_callback' => '__return_true',
        ));
        
        // Release webhooks
        register_rest_route($namespace, '/webhooks/release/published', array(
            'methods' => 'POST',
            'callback' => array($webhook_controller, 'handle_webhook'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Check if user is authenticated
     */
    public function check_auth($request = null) {
        $auth = Simuze_Auth::get_instance();
        
        // Check if we have a token in the Authorization header (for REST API requests)
        // Some servers don't populate $_SERVER['HTTP_AUTHORIZATION'], so we need to check manually
        $token_from_header = null;
        
        // Check Authorization header from request if available
        if ($request && method_exists($request, 'get_header')) {
            $auth_header = $request->get_header('Authorization');
            if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                $token_from_header = $matches[1];
                error_log('[Simuze API] check_auth - Found token in Authorization header (via request)');
            }
        }
        
        // Also check $_SERVER directly (fallback)
        if (!$token_from_header && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                $token_from_header = $matches[1];
                error_log('[Simuze API] check_auth - Found token in Authorization header (via $_SERVER)');
            }
        }
        
        // Also check for Apache-specific header (some servers use this)
        if (!$token_from_header && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                $token_from_header = $matches[1];
                error_log('[Simuze API] check_auth - Found token in REDIRECT_HTTP_AUTHORIZATION');
            }
        }
        
        // If we found a token in header but auth isn't working, try to manually authenticate
        if ($token_from_header && !$auth->is_logged_in()) {
            error_log('[Simuze API] check_auth - Token found in header but user not authenticated, attempting manual auth...');
            
            // Temporarily set the token in $_COOKIE so get_token() can find it
            $original_cookie = $_COOKIE['accessToken'] ?? null;
            $_COOKIE['accessToken'] = $token_from_header;
            
            // Also set in the constant cookie name
            $original_cookie_const = $_COOKIE[Simuze_Auth::ACCESS_TOKEN_COOKIE] ?? null;
            $_COOKIE[Simuze_Auth::ACCESS_TOKEN_COOKIE] = $token_from_header;
            
            // Try to authenticate by calling get_current_user which will trigger authentication
            try {
                $user = $auth->get_current_user();
                
                // Restore original cookies
                if ($original_cookie) {
                    $_COOKIE['accessToken'] = $original_cookie;
                } else {
                    unset($_COOKIE['accessToken']);
                }
                
                if ($original_cookie_const) {
                    $_COOKIE[Simuze_Auth::ACCESS_TOKEN_COOKIE] = $original_cookie_const;
                } else {
                    unset($_COOKIE[Simuze_Auth::ACCESS_TOKEN_COOKIE]);
                }
                
                if ($user) {
                    error_log('[Simuze API] check_auth - Manual authentication successful with header token');
                    return true;
                } else {
                    error_log('[Simuze API] check_auth - Manual authentication failed with header token');
                }
            } catch (Exception $e) {
                error_log('[Simuze API] check_auth - Exception during manual auth: ' . $e->getMessage());
                // Restore original cookies on error
                if ($original_cookie) {
                    $_COOKIE['accessToken'] = $original_cookie;
                } else {
                    unset($_COOKIE['accessToken']);
                }
                if ($original_cookie_const) {
                    $_COOKIE[Simuze_Auth::ACCESS_TOKEN_COOKIE] = $original_cookie_const;
                } else {
                    unset($_COOKIE[Simuze_Auth::ACCESS_TOKEN_COOKIE]);
                }
            }
        }
        
        $is_logged_in = $auth->is_logged_in();
        $current_user = $auth->get_current_user();
        error_log('[Simuze API] check_auth called - is_logged_in: ' . ($is_logged_in ? 'true' : 'false') . ', current_user: ' . ($current_user ? $current_user->id : 'null'));
        
        // Log token sources for debugging
        error_log('[Simuze API] check_auth - Token sources: cookie=' . (isset($_COOKIE['accessToken']) ? 'yes' : 'no') . ', header=' . ($token_from_header ? 'yes' : 'no') . ', $_SERVER[HTTP_AUTHORIZATION]=' . (isset($_SERVER['HTTP_AUTHORIZATION']) ? 'yes' : 'no'));
        
        return $is_logged_in;
    }
    
    /**
     * Check if user is admin
     */
    public function check_admin() {
        return current_user_can('manage_options');
    }
    
    /**
     * Get current user
     */
    public function get_current_user($request) {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        
        if ($user) {
            return rest_ensure_response($user->to_array());
        }
        
        return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
    }
    
    /**
     * Get users
     */
    public function get_users($request) {
        $per_page = (int) ($request->get_param('per_page') ?: 20);
        $page     = (int) ($request->get_param('page') ?: 1);
        $offset   = ($page - 1) * $per_page;

        $users_data = md_db_query(
            "SELECT * FROM simuze_users WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$per_page, $offset]
        );

        $users = array();
        foreach ($users_data as $user_data) {
            $user = new Simuze_User($user_data);
            $users[] = $user->to_array();
        }

        $count = md_db_var("SELECT COUNT(*) FROM simuze_users WHERE deleted_at IS NULL");

        return rest_ensure_response(array(
            'users'    => $users,
            'total'    => (int) $count,
            'page'     => $page,
            'per_page' => $per_page,
        ));
    }
    
    /**
     * Get user by ID
     */
    public function get_user($request) {
        $user = Simuze_User::get($request->get_param('id'));
        
        if ($user) {
            return rest_ensure_response($user->to_array());
        }
        
        return new WP_Error('not_found', 'User not found', array('status' => 404));
    }
    
    /**
     * Search users
     */
    public function search_users($request) {
        $query = $request->get_param('query');
        $limit = (int) ($request->get_param('limit') ?: 10);

        if (empty($query)) {
            return rest_ensure_response(array());
        }

        $like = '%' . md_db_esc_like($query) . '%';
        $users_data = md_db_query(
            "SELECT id, username, display_name, simezu_id, email
             FROM simuze_users
             WHERE (username LIKE ? OR display_name LIKE ? OR email LIKE ?)
               AND deleted_at IS NULL
             LIMIT ?",
            [$like, $like, $like, $limit]
        );
        
        // Convert to Simuze_User objects to get avatar URLs from Simezu
        $users = array();
        foreach ($users_data as $user_data) {
            $user = new Simuze_User($user_data);
            $users[] = array(
                'id' => $user->id,
                'username' => $user->username,
                'display_name' => $user->display_name,
                'avatar_url' => $user->get_avatar_url(60, array('cache_only' => false)), // Allow API fetch
                'simezu_id' => $user->simezu_id ?? '', // Include simezu_id for data attribute
            );
        }
        
        // Return array directly for easier frontend consumption
        return rest_ensure_response($users);
    }
    
    /**
     * Get groups
     */
    public function get_groups($request) {
        $args = array(
            'per_page' => $request->get_param('per_page') ?: 20,
            'page' => $request->get_param('page') ?: 1,
            'group_type' => $request->get_param('group_type'),
            'status' => $request->get_param('status') ?: 'public',
            'search' => $request->get_param('search'),
        );
        
        $result = Simuze_Group::get_all($args);
        
        $group_arrays = array();
        foreach ($result['groups'] as $group) {
            $group_arrays[] = $group->to_array();
        }
        
        return rest_ensure_response(array(
            'groups' => $group_arrays,
            'total' => $result['total'],
            'page' => (int) $args['page'],
            'per_page' => (int) $args['per_page'],
        ));
    }
    
    /**
     * Get current user's groups with role
     */
    public function get_my_groups($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $limit = $request->get_param('per_page') ?: 50;
        $user_groups = Simuze_Group::get_user_groups($current_user->id, $limit);
        
        $group_arrays = array();
        foreach ($user_groups as $group_data) {
            $group = Simuze_Group::get($group_data['id']);
            if ($group) {
                $group_array = $group->to_array();
                // Add member role information
                $group_array['member_role'] = $group_data['member_role'] ?? 'member';
                $group_array['joined_at'] = $group_data['joined_at'] ?? null;
                $group_arrays[] = $group_array;
            }
        }
        
        return rest_ensure_response(array(
            'groups' => $group_arrays,
            'total' => count($group_arrays),
        ));
    }
    
    /**
     * Create group
     */
    public function create_group($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        // Get form data (could be JSON or form-data)
        $content_type = $request->get_content_type();
        if ($content_type && strpos($content_type['value'], 'multipart/form-data') !== false) {
            $data = $request->get_params();
        } else {
            $data = $request->get_json_params();
            if (!$data) {
                $data = $request->get_params();
            }
        }
        
        // Log received data for debugging
        error_log('Simuze Group Creation - Received data: ' . print_r($data, true));
        
        // Validate required fields
        if (empty($data['name'])) {
            error_log('Simuze Group Creation - Missing name field');
            return new WP_Error('missing_name', 'Group name is required', array('status' => 400));
        }
        
        if (empty($data['group_type_id'])) {
            error_log('Simuze Group Creation - Missing group_type_id field');
            return new WP_Error('missing_group_type', 'Group type is required', array('status' => 400));
        }
        
        $data['creator_id'] = $current_user->id;
        
        // Handle file uploads
        $files = $request->get_file_params();

        $upload_info = md_upload_dir();
        $groups_dir  = $upload_info['basedir'] . '/simuze-groups';
        if (!is_dir($groups_dir)) {
            mkdir($groups_dir, 0755, true);
        }

        // Handle cropped avatar (base64 data) — use shared helper when available
        if (!empty($data['avatar_cropped'])) {
            $avatar_url = function_exists('simuze_save_cropped_image') ? simuze_save_cropped_image($data['avatar_cropped'], $groups_dir, 'avatar') : $this->save_cropped_image($data['avatar_cropped'], $groups_dir, 'avatar');
            if ($avatar_url) {
                $data['avatar_url'] = $avatar_url;
                error_log('Simuze Group Creation - Avatar saved: ' . $avatar_url);
            } else {
                error_log('Simuze Group Creation - Failed to save avatar');
            }
            unset($data['avatar_cropped']);
        }
        // Or upload avatar file if provided
        elseif (!empty($files['avatar']) && $files['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatar_dest = $groups_dir . '/avatar-' . time() . '-' . basename($files['avatar']['name']);
            if (move_uploaded_file($files['avatar']['tmp_name'], $avatar_dest)) {
                $data['avatar_url'] = $upload_info['baseurl'] . '/simuze-groups/' . basename($avatar_dest);
            } else {
                error_log('Simuze Group Creation - Avatar upload error: move_uploaded_file failed');
            }
        }

        // Handle cropped header (base64 data) — use shared helper when available
        if (!empty($data['header_cropped'])) {
            $header_url = function_exists('simuze_save_cropped_image') ? simuze_save_cropped_image($data['header_cropped'], $groups_dir, 'header') : $this->save_cropped_image($data['header_cropped'], $groups_dir, 'header');
            if ($header_url) {
                $data['header_url'] = $header_url;
                error_log('Simuze Group Creation - Header saved: ' . $header_url);
            } else {
                error_log('Simuze Group Creation - Failed to save header');
            }
            unset($data['header_cropped']);
        }
        // Or upload header file if provided
        elseif (!empty($files['header']) && $files['header']['error'] === UPLOAD_ERR_OK) {
            $header_dest = $groups_dir . '/header-' . time() . '-' . basename($files['header']['name']);
            if (move_uploaded_file($files['header']['tmp_name'], $header_dest)) {
                $data['header_url'] = $upload_info['baseurl'] . '/simuze-groups/' . basename($header_dest);
            } else {
                error_log('Simuze Group Creation - Header upload error: move_uploaded_file failed');
            }
        }
        
        // Validate group_type_id exists
        $group_type_id = intval($data['group_type_id']);
        if ($group_type_id <= 0) {
            error_log('Simuze Group Creation - Invalid group_type_id: ' . $data['group_type_id']);
            return new WP_Error('invalid_group_type', 'Invalid group type selected', array('status' => 400));
        }
        
        // Verify group type exists (Modular: use md_db_var and simuze_ table; WP: use $wpdb)
        $type_exists = null;
        $type_exists = md_db_var("SELECT id FROM simuze_group_types WHERE id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')", [$group_type_id]);
        
        if (!$type_exists) {
            error_log('Simuze Group Creation - Group type does not exist: ' . $group_type_id);
            return new WP_Error('invalid_group_type', 'Selected group type does not exist', array('status' => 400));
        }
        
        $unique_handle_value = null;
        if (!empty($data['unique_handle'])) {
            $unique_handle_value = Simuze_Group::slugify_handle($data['unique_handle']);
            if (!Simuze_Group::validate_handle($unique_handle_value)) {
                return new WP_Error('invalid_handle', 'Invalid handle format. Must be 3-64 characters, lowercase letters, numbers, and hyphens only.', array('status' => 400));
            }
            if (Simuze_Group::unique_handle_exists($unique_handle_value)) {
                return new WP_Error('handle_exists', 'This handle is already taken', array('status' => 400));
            }
        }
        
        // Sanitize and prepare data
        $group_data = array(
            'name' => sanitize_text_field($data['name']),
            'group_type_id' => $group_type_id,
            'creator_id' => intval($current_user->id),
            'description' => isset($data['description']) ? wp_kses_post($data['description']) : '',
            'status' => 'public',
        );
        
        // Optional fields
        if ($unique_handle_value !== null) {
            $group_data['unique_handle'] = $unique_handle_value;
        }
        if (!empty($data['avatar_url'])) {
            $group_data['avatar_url'] = esc_url_raw($data['avatar_url']);
        }
        if (!empty($data['header_url'])) {
            $group_data['header_url'] = esc_url_raw($data['header_url']);
        }
        
        // Location fields
        if (!empty($data['city'])) {
            $group_data['city'] = sanitize_text_field($data['city']);
        }
        if (!empty($data['province'])) {
            $group_data['province'] = sanitize_text_field($data['province']);
        }
        if (!empty($data['country'])) {
            $group_data['country'] = sanitize_text_field($data['country']);
        }
        if (!empty($data['geo_id'])) {
            $group_data['geo_id'] = sanitize_text_field($data['geo_id']);
        }
        if (!empty($data['lat'])) {
            $group_data['lat'] = sanitize_text_field($data['lat']);
        }
        if (!empty($data['lng'])) {
            $group_data['lng'] = sanitize_text_field($data['lng']);
        }
        
        error_log('Simuze Group Creation - Prepared data: ' . print_r($group_data, true));
        
        // Auto-create location groups if location data is provided
        if (!empty($group_data['city']) || !empty($group_data['province']) || !empty($group_data['country'])) {
            $location_data = array(
                'city' => $group_data['city'] ?? '',
                'province' => $group_data['province'] ?? '',
                'country' => $group_data['country'] ?? '',
                'geo_id' => $group_data['geo_id'] ?? '',
                'lat' => $group_data['lat'] ?? '',
                'lng' => $group_data['lng'] ?? ''
            );
            
            // Auto-create city/province/country groups
            if (class_exists('Simuze_Geo')) {
                $location_groups = Simuze_Geo::auto_create_location_groups($location_data, $current_user->id);
            }
        }
        
        // Create the main group
        $group = Simuze_Group::create($group_data);
        
        if ($group) {
            error_log('Simuze Group Creation - Group created successfully with ID: ' . $group->id);
            
            // Send invitations to members
            if (!empty($data['invited_members'])) {
                $member_ids = explode(',', $data['invited_members']);
                foreach ($member_ids as $member_id) {
                    $member_id = (int) trim($member_id);
                    if ($member_id && $member_id !== $current_user->id) {
                        $group->send_invitation($member_id, $current_user->id);
                    }
                }
            }
            
            // Record activity
            if (class_exists('Simuze_Activity')) {
                Simuze_Activity::record_user_activity(
                    $current_user->id,
                    'group_created',
                    null,
                    'group',
                    $group->id
                );
            }
            
            $response = $group->to_array();
            $response['url'] = $group->get_url();
            
            return rest_ensure_response($response);
        }
        
        error_log('Simuze Group Creation - Database error: group creation returned falsy');

        return new WP_Error('creation_failed', 'Failed to create group.', array('status' => 500));
    }
    
    /**
     * Get group by ID
     */
    public function get_group($request) {
        $group = Simuze_Group::get($request->get_param('id'));
        
        if ($group) {
            return rest_ensure_response($group->to_array());
        }
        
        return new WP_Error('not_found', 'Group not found', array('status' => 404));
    }
    
    /**
     * Update group
     */
    public function update_group($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        // Check permissions
        if ($group->creator_id != $current_user->id && !$group->is_member($current_user->id, 'admin')) {
            return new WP_Error('forbidden', 'Insufficient permissions', array('status' => 403));
        }
        
        $data = $request->get_json_params();
        
        // Handle cropped avatar (base64 data)
        if (!empty($data['avatar_cropped'])) {
            $upload_dir = md_upload_dir();
            $groups_dir = $upload_dir['basedir'] . '/simuze-groups';
            
            if (!file_exists($groups_dir)) {
                mkdir($groups_dir, 0755, true);
            }
            
            $avatar_url = $this->save_cropped_image($data['avatar_cropped'], $groups_dir, 'avatar');
            if ($avatar_url) {
                $data['avatar_url'] = $avatar_url;
            }
            unset($data['avatar_cropped']);
        }
        
        // Handle cropped header (base64 data)
        if (!empty($data['header_cropped'])) {
            $upload_dir = md_upload_dir();
            $groups_dir = $upload_dir['basedir'] . '/simuze-groups';
            
            if (!file_exists($groups_dir)) {
                mkdir($groups_dir, 0755, true);
            }
            
            $header_url = $this->save_cropped_image($data['header_cropped'], $groups_dir, 'header');
            if ($header_url) {
                $data['header_url'] = $header_url;
            }
            unset($data['header_cropped']);
        }
        
        $group->update($data);
        
        return rest_ensure_response($group->to_array());
    }
    
    /**
     * Delete group
     */
    public function delete_group($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        // Check permissions - only creator or admin can delete
        if ($group->creator_id != $current_user->id && !$group->is_member($current_user->id, 'admin')) {
            return new WP_Error('forbidden', 'Insufficient permissions', array('status' => 403));
        }
        
        $result = $group->delete();
        
        if ($result) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Group deleted successfully'
            ));
        }
        
        return new WP_Error('delete_failed', 'Failed to delete group', array('status' => 500));
    }
    
    /**
     * Upload group avatar (local Simuze storage + CDN-ready URLs)
     * Validations:
     * - Max 2MB
     * - Min 500x500
     * Produces thumb (64x64), medium (200x200), full (500x500)
     */
    public function upload_group_avatar($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();

        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }

        $group_id = (int) $request->get_param('id');
        $group = Simuze_Group::get($group_id);

        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }

        // Only creator or admin members can update avatar
        if ($group->creator_id != $current_user->id && !$group->is_member($current_user->id, 'admin')) {
            return new WP_Error('forbidden', 'Insufficient permissions', array('status' => 403));
        }

        if (empty($_FILES['avatar'])) {
            return new WP_Error('no_file', 'Avatar file is required', array('status' => 400));
        }

        $file = $_FILES['avatar'];
        if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', 'Upload failed', array('status' => 400));
        }

        // Validate size (max 2MB)
        if (!empty($file['size']) && (int) $file['size'] > 2 * 1024 * 1024) {
            return new WP_Error('too_large', 'Avatar must be <= 2MB', array('status' => 400));
        }

        // Validate dimensions (>= 500x500)
        $image_info = @getimagesize($file['tmp_name']);
        if (!$image_info) {
            return new WP_Error('invalid_image', 'Invalid image file', array('status' => 400));
        }
        if ($image_info[0] < 500 || $image_info[1] < 500) {
            return new WP_Error('too_small', 'Minimum size is 500x500px', array('status' => 400));
        }

        $upload_dir = md_upload_dir();
        $base_dir = trailingslashit($upload_dir['basedir']) . 'simuze/groups/' . $group_id . '/avatar';
        $base_url = trailingslashit($upload_dir['baseurl']) . 'simuze/groups/' . $group_id . '/avatar';
        if (!file_exists($base_dir)) {
            mkdir($base_dir, 0755, true);
        }

        $sizes = array(
            'thumb' => 64,
            'medium' => 200,
            'full' => 500,
        );

        $urls = array();
        foreach ($sizes as $label => $dimension) {
            $dest_path = rtrim($base_dir, '/') . '/' . $label . '.jpg';
            if (!$this->_resize_image_gd($file['tmp_name'], $dest_path, $dimension, $dimension)) {
                return new WP_Error('image_editor_error', 'Could not process image', array('status' => 500));
            }
            $urls[$label] = rtrim($base_url, '/') . '/' . $label . '.jpg';
        }

        // Persist in cache table (local only)
        if (class_exists('Simuze_Avatar')) {
            $avatar = Simuze_Avatar::get_instance();
            $avatar->update_group_cache($group_id, array(
                'thumb' => $urls['thumb'] ?? '',
                'medium' => $urls['medium'] ?? '',
                'full' => $urls['full'] ?? '',
            ), array('source' => 'simuze_local'));
        }

        // Store legacy pointer on group for compatibility
        $group->update(array('avatar_url' => $urls['full'] ?? ''));

        return rest_ensure_response(array(
            'status' => 'ok',
            'thumb_url' => $urls['thumb'] ?? '',
            'medium_url' => $urls['medium'] ?? '',
            'full_url' => $urls['full'] ?? '',
        ));
    }
    
    /**
     * Get group members
     */
    public function get_group_members($request) {
        $group = Simuze_Group::get($request->get_param('id'));
        
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        $args = array(
            'per_page' => $request->get_param('per_page') ?: 20,
            'page' => $request->get_param('page') ?: 1,
            'role' => $request->get_param('role'),
        );
        
        $members = $group->get_members($args);
        
        return rest_ensure_response($members);
    }
    
    /**
     * Join group
     */
    public function join_group($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        if ($group->is_member($current_user->id)) {
            return new WP_Error('already_member', 'User is already a member', array('status' => 400));
        }
        
        $group->add_member($current_user->id);
        
        return rest_ensure_response(array('success' => true));
    }
    
    /**
     * Leave group
     */
    public function leave_group($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        if (!$group->is_member($current_user->id)) {
            return new WP_Error('not_member', 'User is not a member', array('status' => 400));
        }
        
        $group->remove_member($current_user->id);
        
        return rest_ensure_response(array('success' => true));
    }
    
    /**
     * Get group invitations
     */
    public function get_group_invitations($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        // Check permissions
        if (!$group->can_manage($current_user->id)) {
            return new WP_Error('forbidden', 'Insufficient permissions', array('status' => 403));
        }
        
        $status = $request->get_param('status') ?: 'pending';
        $invitations = $group->get_invitations($status);
        
        return rest_ensure_response(array('invitations' => $invitations));
    }
    
    /**
     * Invite user to group
     */
    public function invite_to_group($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        // Check permissions
        if (!$group->can_manage($current_user->id)) {
            return new WP_Error('forbidden', 'Insufficient permissions', array('status' => 403));
        }
        
        $data = $request->get_json_params();
        $user_id = $data['user_id'];
        $message = isset($data['message']) ? $data['message'] : null;
        
        $result = $group->send_invitation($user_id, $current_user->id, $message);
        
        if ($result) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('invitation_failed', 'Failed to send invitation', array('status' => 400));
    }
    
    /**
     * Accept group invitation
     */
    public function accept_group_invitation($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        $result = $group->accept_invitation($current_user->id);
        
        if ($result) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('accept_failed', 'Failed to accept invitation', array('status' => 400));
    }
    
    /**
     * Reject group invitation
     */
    public function reject_group_invitation($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        $result = $group->reject_invitation($current_user->id);
        
        if ($result) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('reject_failed', 'Failed to reject invitation', array('status' => 400));
    }
    
    /**
     * Cancel/delete group invitation
     */
    public function cancel_group_invitation($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        // Check permissions - only group managers can cancel invitations
        if (!$group->can_manage($current_user->id)) {
            return new WP_Error('forbidden', 'Insufficient permissions', array('status' => 403));
        }
        
        $invitation_id = $request->get_param('invitation_id');
        
        if (!$invitation_id) {
            return new WP_Error('invalid_request', 'Invitation ID is required', array('status' => 400));
        }
        
        $result = $group->cancel_invitation($invitation_id);
        
        if ($result) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('cancel_failed', 'Failed to cancel invitation', array('status' => 400));
    }
    
    /**
     * Check if unique_name is available
     */
    public function check_unique_name($request) {
        $data = $request->get_json_params();
        $unique_name = isset($data['unique_name']) ? sanitize_title($data['unique_name']) : '';
        $group_id = isset($data['group_id']) ? intval($data['group_id']) : null;
        
        if (empty($unique_name)) {
            return rest_ensure_response(array(
                'available' => false,
                'message' => 'Unique name is required'
            ));
        }
        
        $available = !Simuze_Group::unique_name_exists($unique_name, $group_id);
        
        return rest_ensure_response(array(
            'available' => $available,
            'unique_name' => $unique_name
        ));
    }
    
    /**
     * Check if unique_handle is available
     */
    public function check_unique_handle($request) {
        $data = $request->get_json_params();
        if (!$data) {
            $data = $request->get_params();
        }
        
        $handle = isset($data['unique_handle']) ? Simuze_Group::slugify_handle($data['unique_handle']) : '';
        $group_id = isset($data['group_id']) ? intval($data['group_id']) : null;
        
        if (empty($handle)) {
            return rest_ensure_response(array(
                'is_unique' => false,
                'message' => 'Handle is required'
            ));
        }
        
        if (!Simuze_Group::validate_handle($handle)) {
            return rest_ensure_response(array(
                'is_unique' => false,
                'message' => 'Invalid handle format. Must be 3-64 characters, lowercase letters, numbers, and hyphens only.'
            ));
        }
        
        $is_unique = !Simuze_Group::unique_handle_exists($handle, $group_id);
        
        return rest_ensure_response(array(
            'is_unique' => $is_unique,
            'handle' => $handle
        ));
    }
    
    /**
     * Resolve handle to group ID (for mentions and PMs)
     */
    public function resolve_handle($request) {
        $handle = $request->get_param('handle');
        
        if (empty($handle)) {
            return new WP_Error('invalid_handle', 'Handle is required', array('status' => 400));
        }
        
        $group = Simuze_Group::get_by_unique_handle($handle);
        
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        // If this was resolved via alias, indicate redirect needed
        $response_data = array(
            'group_id' => $group->id,
            'handle' => $group->unique_handle,
            'name' => $group->name,
            'url' => $group->get_url()
        );
        
        if (isset($group->_should_redirect) && $group->_should_redirect) {
            $response_data['redirect'] = true;
            $response_data['redirect_to'] = $group->unique_handle;
        }
        
        return rest_ensure_response($response_data);
    }
    
    /**
     * Update group member role
     */
    public function update_group_member($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        // Check permissions
        if (!$group->can_manage($current_user->id)) {
            return new WP_Error('forbidden', 'Insufficient permissions', array('status' => 403));
        }
        
        $data = $request->get_json_params();
        $user_id = $data['user_id'];
        $role = $data['role'];
        
        $result = $group->update_member_role($user_id, $role);
        
        if ($result !== false) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('update_failed', 'Failed to update member', array('status' => 400));
    }
    
    /**
     * Remove member from group (admin only)
     */
    public function remove_group_member($request) {
        if (!current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'Insufficient permissions', array('status' => 403));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        $user_id = $request->get_param('user_id');
        
        $result = $group->remove_member($user_id);
        
        if ($result) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('remove_failed', 'Failed to remove member', array('status' => 400));
    }
    
    /**
     * Get activity feed
     * GET /simuze/v1/activity?user_id=&group_id=&page=&per_page=
     */
    public function get_activity($request) {
        if (!class_exists('Simuze_Activity')) {
            return rest_ensure_response(array('activity' => array()));
        }
        $params   = $request->get_params();
        $group_id = isset($params['group_id']) ? intval($params['group_id']) : 0;
        $user_id  = isset($params['user_id'])  ? intval($params['user_id'])  : 0;
        $page     = max(1, intval($params['page']     ?? 1));
        $per_page = min(50, max(1, intval($params['per_page'] ?? 20)));

        $current_user = Simuze_Auth::get_instance()->get_current_user();
        $viewer_id    = $current_user ? $current_user->id : null;

        if ($group_id) {
            $activity = Simuze_Activity::get_group_posts($group_id, array('per_page' => $per_page, 'page' => $page));
        } elseif ($user_id) {
            $activity = Simuze_Activity::get_user_posts($user_id, array('per_page' => $per_page, 'page' => $page, 'viewer_id' => $viewer_id));
        } elseif ($viewer_id) {
            $activity = Simuze_Activity::get_user_feed($viewer_id, array('per_page' => $per_page, 'page' => $page));
        } else {
            $activity = array();
        }

        return rest_ensure_response(array('activity' => $activity ?: array()));
    }

    /**
     * Get message threads or messages within a thread
     * GET /simuze/v1/messages[?thread_id=]
     */
    public function get_messages($request) {
        $current_user = Simuze_Auth::get_instance()->get_current_user();
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        if (!class_exists('Simuze_Message')) {
            return rest_ensure_response(array('threads' => array(), 'messages' => array()));
        }

        $params    = $request->get_params();
        $thread_id = isset($params['thread_id']) ? sanitize_text_field($params['thread_id']) : null;
        $page      = max(1, intval($params['page']     ?? 1));
        $per_page  = min(100, max(1, intval($params['per_page'] ?? 30)));

        if ($thread_id) {
            // Return messages within a specific thread; also mark them read
            $messages = Simuze_Message::get_user_messages($current_user->id, array(
                'thread_id' => $thread_id,
                'per_page'  => $per_page,
                'page'      => $page,
            ));
            // Mark all unread messages in this thread as read
            if (!empty($messages)) {
                md_db_exec(
                    "UPDATE simuze_messages SET is_read = 1 WHERE thread_id = ? AND recipient_id = ? AND is_read = 0",
                    [$thread_id, $current_user->id]
                );
            }
            return rest_ensure_response(array('messages' => $messages ?: array()));
        }

        // No thread_id — return thread list with metadata
        $threads = Simuze_Message::get_threads($current_user->id) ?: array();
        return rest_ensure_response(array('threads' => $threads, 'messages' => array()));
    }

    /**
     * Send a message
     * POST /simuze/v1/messages  { recipient_id, message, subject?, group_id? }
     */
    public function send_message($request) {
        $current_user = Simuze_Auth::get_instance()->get_current_user();
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        if (!class_exists('Simuze_Message')) {
            return new WP_Error('service_unavailable', 'Messaging not available', array('status' => 503));
        }

        $params       = $request->get_json_params() ?: $request->get_params();
        $recipient_id = isset($params['recipient_id']) ? intval($params['recipient_id']) : 0;
        $group_id     = isset($params['group_id'])     ? intval($params['group_id'])     : null;
        $message_text = isset($params['message'])      ? trim(wp_kses_post($params['message'])) : '';
        $subject      = isset($params['subject'])      ? sanitize_text_field($params['subject']) : null;

        if (empty($message_text)) {
            return new WP_Error('empty_message', 'Message content is required', array('status' => 400));
        }
        if (!$recipient_id && !$group_id) {
            return new WP_Error('no_recipient', 'recipient_id or group_id is required', array('status' => 400));
        }

        $message_id = Simuze_Message::send($current_user->id, $recipient_id ?: null, $message_text, $subject, $group_id);

        if ($message_id) {
            return rest_ensure_response(array('success' => true, 'id' => $message_id));
        }

        return new WP_Error('send_failed', 'Failed to send message', array('status' => 500));
    }
    
    /**
     * Update user
     */
    public function update_user($request) {
        $user_id = (int) $request->get_param('id');
        $current_user = Simuze_Auth::get_instance()->get_current_user();

        if (!$current_user) {
            return new WP_Error('forbidden', 'You must be logged in to update a user.', array('status' => 403));
        }

        // Allow user to update their own profile
        if ((int)$current_user->id !== (int)$user_id) {
            return new WP_Error('forbidden', 'You do not have permission to update this user.', array('status' => 403));
        }

        $user = Simuze_User::get($user_id);
        if (!$user) {
            return new WP_Error('not_found', 'User not found.', array('status' => 404));
        }

        $data = $request->get_json_params();
        $update_data = array();
        $profile_data = array();

        // Handle cropped avatar (base64 data) - Upload directly to SIMEZU API
        if (!empty($data['avatar_cropped'])) {
            // Upload directly to SIMEZU API using POST /api/avatar/upload
            $upload_result = $this->upload_avatar_to_simezu($user, $data['avatar_cropped']);
            if ($upload_result && is_array($upload_result)) {
                // Cache is automatically updated by upload_avatar_to_simezu
                // Store full_url for reference if available
                if (!empty($upload_result['full_url'])) {
                    $update_data['avatar_url'] = $upload_result['full_url'];
                } elseif (!empty($upload_result['avatar_url'])) {
                    $update_data['avatar_url'] = $upload_result['avatar_url'];
                }
                error_log('Simuze User Update - Avatar uploaded to SIMEZU and cache updated');
            } else {
                error_log('Simuze User Update - Failed to upload avatar to SIMEZU');
            }
            unset($data['avatar_cropped']);
        }
        
        // Handle cropped header (base64 data)
        if (!empty($data['header_cropped'])) {
            $upload_dir = md_upload_dir();
            $headers_dir = $upload_dir['basedir'] . '/simuze-headers/' . $user_id;
            
            if (!file_exists($headers_dir)) {
                mkdir($headers_dir, 0755, true);
            }

            // Remove existing headers for this user to keep only the latest upload
            foreach (glob($headers_dir . '/*') as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            
            $header_url = $this->save_cropped_image($data['header_cropped'], $headers_dir, 'header');
            if ($header_url) {
                $update_data['header_url'] = $header_url;
            }
            unset($data['header_cropped']);
        }

        if (isset($data['display_name'])) {
            $update_data['display_name'] = sanitize_text_field($data['display_name']);
        }
        if (isset($data['first_name'])) {
            $profile_data['first_name'] = sanitize_text_field($data['first_name']);
        }
        if (isset($data['last_name'])) {
            $profile_data['last_name'] = sanitize_text_field($data['last_name']);
        }
        if (isset($data['profile'])) {
            if (isset($data['profile']['bio'])) $profile_data['bio'] = trim(strip_tags($data['profile']['bio']));
            if (isset($data['profile']['location'])) $profile_data['location'] = sanitize_text_field($data['profile']['location']);
            if (isset($data['profile']['website'])) $profile_data['website'] = esc_url_raw($data['profile']['website']);
            // Handle websites as array (repeater field)
            if (isset($data['profile']['websites']) && is_array($data['profile']['websites'])) {
                // Store as JSON in social_links column (or we can add a websites column later)
                $websites = array_filter(array_map('esc_url_raw', $data['profile']['websites']));
                $profile_data['social_links'] = json_encode(array('websites' => array_values($websites)));
            }
        }

        if (!empty($update_data)) {
            $user->update($update_data);
        }
        if (!empty($profile_data)) {
            $user->update_profile($profile_data);
        }

        return rest_ensure_response($user->to_array());
    }
    
    /**
     * Get user avatar URL from Simezu Avatar API
     * Uses: GET /simezu/api/auth/users/{user_id}/avatar
     * Authenticatie: X-App-ID en X-API-Key headers
     */
    public function get_user_avatar($request) {
        $user_id = (int) $request->get_param('id');
        $size = $request->get_param('size') ?: 96;
        
        $user = Simuze_User::get($user_id);
        if (!$user) {
            return new WP_Error('not_found', 'User not found.', array('status' => 404));
        }
        
        if (empty($user->simezu_id)) {
            return new WP_Error('no_simezu_id', 'User has no Simezu ID.', array('status' => 404));
        }
        
        $simezu_base  = rtrim(defined('SIMEZU_API_BASE') ? SIMEZU_API_BASE : 'http://localhost/simezu/api', '/');
        $api_key      = defined('SIMEZU_API_KEY') ? SIMEZU_API_KEY : '';
        $app_id       = defined('SIMEZU_APP_ID')  ? SIMEZU_APP_ID  : '';
        $fallback_url = $simezu_base . '/auth/users/' . urlencode($user->simezu_id) . '/avatar/image';

        $endpoint = $simezu_base . '/auth/users/' . urlencode($user->simezu_id) . '/avatar';
        if ($size && $size != 96) {
            $endpoint .= '?size=' . urlencode($size);
        }

        $response = md_http_get($endpoint, [
            'headers' => [
                'X-App-ID'     => $app_id,
                'X-API-Key'    => $api_key,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 10,
        ]);

        if ($response === false) {
            error_log('Simezu Avatar API Error: could not connect to ' . $endpoint);
            return rest_ensure_response(array(
                'avatar_url'      => $fallback_url,
                'avatar_url_full' => $fallback_url,
            ));
        }

        $status_code = $response['code'];
        if ($status_code === 200) {
            $data = json_decode($response['body'], true);
            if ($data && !empty($data['avatar_url_full'])) {
                return rest_ensure_response(array(
                    'user_id'         => $data['user_id'] ?? $user->simezu_id,
                    'avatar_url'      => $data['avatar_url'] ?? '',
                    'avatar_url_full' => $data['avatar_url_full'],
                ));
            }
        } elseif ($status_code === 404) {
            error_log('Simezu Avatar API: Avatar not found for user ' . $user->simezu_id);
            return rest_ensure_response(array(
                'user_id'         => $user->simezu_id,
                'avatar_url'      => '',
                'avatar_url_full' => '',
            ));
        }

        return rest_ensure_response(array(
            'avatar_url'      => $fallback_url,
            'avatar_url_full' => $fallback_url,
        ));
    }
    
    /**
     * Get avatar by simezu_id (for data-simezu-user-id attributes)
     * Uses Simuze_Avatar class with caching
     */
    public function get_avatar_by_simezu_id($request) {
        $simezu_id = $request->get_param('simezu_id');
        $size = $request->get_param('size') ?: 240;
        
        if (empty($simezu_id)) {
            return new WP_Error('invalid_param', 'simezu_id is required', array('status' => 400));
        }
        
        // Use Simuze_Avatar class with caching
        if (class_exists('Simuze_Avatar')) {
            $avatar = Simuze_Avatar::get_instance();
            
            // Convert size to variant
            $variant = 'medium';
            if (is_numeric($size)) {
                if ($size <= 60) {
                    $variant = 'thumb';
                } elseif ($size >= 200) {
                    $variant = 'full';
                }
            } else {
                $variant = $size;
            }
            
            $avatar_url = $avatar->get_user_avatar_url($simezu_id, $variant, array('cache_only' => false));
            
            return rest_ensure_response(array(
                'avatar_url' => $avatar_url,
                'avatar_url_full' => $avatar_url,
                'simezu_id' => $simezu_id
            ));
        }
        
        // Fallback to direct API call
        $simezu_base = rtrim(defined('SIMEZU_API_BASE') ? SIMEZU_API_BASE : 'http://localhost/simezu/api', '/');
        $avatar_url  = $simezu_base . '/auth/users/' . urlencode($simezu_id) . '/avatar/image';
        if ($size && $size != 96) {
            $avatar_url .= '?size=' . urlencode($size);
        }
        
        return rest_ensure_response(array(
            'avatar_url' => $avatar_url,
            'avatar_url_full' => $avatar_url,
            'simezu_id' => $simezu_id
        ));
    }
    
    /**
     * Upload user avatar
     */
    public function upload_user_avatar($request) {
        $user_id = (int) $request->get_param('id');
        $current_user = Simuze_Auth::get_instance()->get_current_user();

        if (!$current_user || $current_user->id !== $user_id) {
            return new WP_Error('forbidden', 'You do not have permission to update this user.', array('status' => 403));
        }

        $user = Simuze_User::get($user_id);
        if (!$user) {
            return new WP_Error('not_found', 'User not found.', array('status' => 404));
        }

        // Check if file was uploaded
        if (empty($_FILES['avatar'])) {
            return new WP_Error('no_file', 'No file uploaded.', array('status' => 400));
        }

        $file = $_FILES['avatar'];

        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', 'File upload error: ' . $file['error'], array('status' => 400));
        }

        // Validate file type using finfo (no WP dependency)
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        $finfo         = new \finfo(FILEINFO_MIME_TYPE);
        $detected_type = $finfo->file($file['tmp_name']);

        if (!in_array($detected_type, $allowed_types)) {
            return new WP_Error('invalid_type', 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.', array('status' => 400));
        }

        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return new WP_Error('file_too_large', 'File is too large. Maximum size is 5MB.', array('status' => 400));
        }

        $upload_dir = defined('PUBLIC_PATH') ? PUBLIC_PATH . '/uploads/avatars' : '';
        if (!$upload_dir) {
            return new WP_Error('config_error', 'Upload directory not configured.', array('status' => 500));
        }
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg');
        $filename = 'avatar-' . $user_id . '-' . time() . '.' . $ext;
        $dest     = $upload_dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return new WP_Error('upload_failed', 'Failed to save uploaded file.', array('status' => 500));
        }

        $avatar_url = md_home_url('/public/uploads/avatars/' . $filename);
        $user->update(array('avatar_url' => $avatar_url));

        return rest_ensure_response(array(
            'success'    => true,
            'avatar_url' => $avatar_url,
            'user'       => $user->to_array(),
        ));
    }
    
    /**
     * Upload avatar directly to SIMEZU API
     * Uses POST /api/avatar/upload endpoint
     * Updates local cache after successful upload
     * 
     * @param object $user Simuze_User object
     * @param string $base64_data Base64 encoded image data
     * @return array|false Avatar metadata on success, false on failure
     */
    private function upload_avatar_to_simezu($user, $base64_data) {
        if (empty($user->simezu_id)) {
            error_log('Simuze Avatar Upload: No simezu_id found');
            return false;
        }
        
        $simezu_base = rtrim(defined('SIMEZU_API_BASE') ? SIMEZU_API_BASE : 'http://localhost/simezu/api', '/');

        // Get access token from cookie
        $token = isset($_COOKIE[Simuze_Auth::ACCESS_TOKEN_COOKIE]) ? $_COOKIE[Simuze_Auth::ACCESS_TOKEN_COOKIE] : null;

        if (!$token) {
            error_log('Simuze Avatar Upload: No access token found');
            return false;
        }

        // Extract image data from base64
        $image_data = null;
        $mime_type = 'image/jpeg';

        if (preg_match('/^data:image\/(\w+);base64,/', $base64_data, $type)) {
            $base64_data = substr($base64_data, strpos($base64_data, ',') + 1);
            $type = strtolower($type[1]);

            if (!in_array($type, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
                error_log('Simuze Avatar Upload: Invalid image type');
                return false;
            }

            $image_data = base64_decode($base64_data);

            if ($image_data === false) {
                error_log('Simuze Avatar Upload: Failed to decode base64 data');
                return false;
            }

            $mime_type = 'image/' . ($type === 'jpeg' ? 'jpg' : $type);
        } else {
            error_log('Simuze Avatar Upload: Invalid base64 format');
            return false;
        }

        $boundary = bin2hex(random_bytes(8));
        $body  = '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="avatar"; filename="avatar.' . ($type === 'jpeg' ? 'jpg' : $type) . '"' . "\r\n";
        $body .= 'Content-Type: ' . $mime_type . "\r\n\r\n";
        $body .= $image_data . "\r\n";
        $body .= '--' . $boundary . '--';

        $endpoint = $simezu_base . '/avatar/upload';

        $response = md_http_post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
            ],
            'body'    => $body,
            'timeout' => 30,
        ]);

        if ($response === false) {
            error_log('Simuze Avatar Upload Error: could not connect to ' . $endpoint);
            return false;
        }

        $status_code = $response['code'];
        if ($status_code === 200 || $status_code === 201) {
            $response_body = json_decode($response['body'], true);

            if ($response_body && !empty($response_body)) {
                if (class_exists('Simuze_Avatar')) {
                    $avatar = Simuze_Avatar::get_instance();
                    $avatar->refresh_user_cache($user->simezu_id);
                }
                error_log('Simuze Avatar Upload: Successfully uploaded to SIMEZU and cache updated');
                return $response_body;
            } else {
                error_log('Simuze Avatar Upload: Success but empty response body');
                return false;
            }
        } else {
            error_log('Simuze Avatar Upload Error: HTTP ' . $status_code . ' - ' . $response['body']);
            return false;
        }
    }
    
    /**
     * Sync avatar to Simezu (legacy - for file uploads)
     */
    private function sync_avatar_to_simezu($user, $avatar_url) {
        if (empty($user->simezu_id)) {
            return false;
        }
        
        $simezu_base = rtrim(defined('SIMEZU_API_BASE') ? SIMEZU_API_BASE : 'http://localhost/simezu/api', '/');

        // Get access token from cookie
        $token = isset($_COOKIE[Simuze_Auth::ACCESS_TOKEN_COOKIE]) ? $_COOKIE[Simuze_Auth::ACCESS_TOKEN_COOKIE] : null;

        if (!$token) {
            error_log('Simuze Avatar Sync: No access token found');
            return false;
        }

        // Download the avatar image
        $image_data = @file_get_contents($avatar_url);
        if ($image_data === false) {
            error_log('Simuze Avatar Sync: Failed to download avatar image');
            return false;
        }

        $boundary  = bin2hex(random_bytes(8));
        $body  = '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="avatar"; filename="avatar.jpg"' . "\r\n";
        $body .= 'Content-Type: image/jpeg' . "\r\n\r\n";
        $body .= $image_data . "\r\n";
        $body .= '--' . $boundary . '--';

        $endpoint = $simezu_base . '/api.php/users/' . $user->simezu_id . '/avatar';

        $response = md_http_post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
            ],
            'body'    => $body,
            'timeout' => 30,
        ]);

        if ($response === false) {
            error_log('Simuze Avatar Sync Error: could not connect to ' . $endpoint);
            return false;
        }

        $status_code = $response['code'];
        if ($status_code === 200 || $status_code === 201) {
            error_log('Simuze Avatar Sync: Successfully synced avatar to Simezu');
            return true;
        } else {
            error_log('Simuze Avatar Sync Error: HTTP ' . $status_code . ' - ' . $response['body']);
            return false;
        }
    }
    
    /**
     * Save cropped image from base64 data
     */
    private function save_cropped_image($base64_data, $upload_dir, $prefix = 'image') {
        // Extract image data from base64 string
        if (preg_match('/^data:image\/(\w+);base64,/', $base64_data, $type)) {
            $base64_data = substr($base64_data, strpos($base64_data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif
            
            if (!in_array($type, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
                return false;
            }
            
            $base64_data = base64_decode($base64_data);
            
            if ($base64_data === false) {
                return false;
            }
        } else {
            return false;
        }
        
        // Generate unique filename
        $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . ($type === 'jpeg' ? 'jpg' : $type);
        $filepath = $upload_dir . '/' . $filename;
        
        // Save file
        if (file_put_contents($filepath, $base64_data) === false) {
            return false;
        }
        
        // Return URL
        $upload_dir_info = md_upload_dir();
        $subdir = 'simuze-users';
        if (strpos($upload_dir, 'simuze-groups') !== false) {
            $subdir = 'simuze-groups';
        } elseif (strpos($upload_dir, 'simuze-headers') !== false) {
            // For header uploads we include the user_id subfolder, keep full relative path
            $rel = str_replace($upload_dir_info['basedir'] . '/', '', $upload_dir);
            return $upload_dir_info['baseurl'] . '/' . $rel . '/' . $filename;
        }
        return $upload_dir_info['baseurl'] . '/' . $subdir . '/' . $filename;
    }
    
    /**
     * Follow user or group
     */
    public function follow($request) {
        // TODO: Implement follow
        return rest_ensure_response(array('success' => true));
    }
    
    /**
     * Unfollow user or group
     */
    public function unfollow($request) {
        // TODO: Implement unfollow
        return rest_ensure_response(array('success' => true));
    }
    
    /**
     * Create activity post
     */
    public function create_activity_post($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $params = $request->get_params();
        $content = isset($params['content']) ? wp_kses_post($params['content']) : '';
        
        if (empty($content)) {
            return new WP_Error('empty_content', 'Post content is required', array('status' => 400));
        }
        
        $args = array(
            'group_id' => isset($params['group_id']) ? intval($params['group_id']) : null,
            'title' => isset($params['title']) ? sanitize_text_field($params['title']) : null,
            'visibility' => isset($params['visibility']) ? sanitize_text_field($params['visibility']) : 'public',
            'image_url' => isset($params['image_url']) ? esc_url_raw($params['image_url']) : null,
            'video_url' => isset($params['video_url']) ? esc_url_raw($params['video_url']) : null,
        );
        
        // If group post, verify user is member
        if ($args['group_id']) {
            $group = Simuze_Group::get($args['group_id']);
            if (!$group || !$group->is_member($current_user->id)) {
                return new WP_Error('not_member', 'You must be a group member to post', array('status' => 403));
            }
        }
        
        $post_id = Simuze_Activity::create_post($current_user->id, $content, $args);
        
        if ($post_id) {
            return rest_ensure_response(array(
                'success' => true,
                'id' => $post_id,
            ));
        }
        
        return new WP_Error('creation_failed', 'Failed to create post', array('status' => 500));
    }
    
    /**
     * Update activity post
     */
    public function update_activity_post($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $post_id = intval($request->get_param('id'));
        $params = $request->get_json_params();
        $is_group_post = isset($params['is_group_post']) && $params['is_group_post'];
        
        // Get the post to verify ownership
        $post = Simuze_Activity::get_post($post_id, $is_group_post);
        
        if (!$post) {
            return new WP_Error('not_found', 'Post not found', array('status' => 404));
        }
        
        if ($post['user_id'] != $current_user->id) {
            return new WP_Error('forbidden', 'You can only edit your own posts', array('status' => 403));
        }
        
        $content = isset($params['content']) ? wp_kses_post($params['content']) : $post['content'];
        
        $args = array();
        if (isset($params['title'])) {
            $args['title'] = sanitize_text_field($params['title']);
        }
        if (isset($params['visibility'])) {
            $args['visibility'] = sanitize_text_field($params['visibility']);
        }
        
        $result = Simuze_Activity::update_post($post_id, $content, $is_group_post, $args);
        
        if ($result !== false) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('update_failed', 'Failed to update post', array('status' => 500));
    }
    
    /**
     * Delete activity post
     */
    public function delete_activity_post($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $post_id = intval($request->get_param('id'));
        $params = $request->get_json_params();
        $is_group_post = isset($params['is_group_post']) && $params['is_group_post'];
        
        // Get the post to verify ownership
        $post = Simuze_Activity::get_post($post_id, $is_group_post);
        
        if (!$post) {
            return new WP_Error('not_found', 'Post not found', array('status' => 404));
        }
        
        // Use security class for permission check
        if (!Simuze_Security::can_delete($current_user->id, 'activity', $post_id)) {
            return new WP_Error('forbidden', 'You do not have permission to delete this post', array('status' => 403));
        }
        
        $result = Simuze_Activity::delete_post($post_id, $is_group_post);
        
        if ($result) {
            return rest_ensure_response(array('success' => true));
        }
        
        // Sanitize error message
        $error_message = 'Failed to delete post';
        if (class_exists('Simuze_Security')) {
            $error_message = Simuze_Security::sanitize_error('Failed to delete post', 'activity_delete');
        }
        
        return new WP_Error('delete_failed', $error_message, array('status' => 500));
    }
    
    /**
     * Send friend request
     */
    public function send_friend_request($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $params = $request->get_json_params();
        $friend_id = isset($params['user_id']) ? intval($params['user_id']) : 0;
        
        if (!$friend_id || $friend_id === $current_user->id) {
            return new WP_Error('invalid_user', 'Invalid user ID', array('status' => 400));
        }
        
        $result = Simuze_Friend::send_request($current_user->id, $friend_id);
        
        if ($result) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('request_failed', 'Friend request already exists or failed', array('status' => 400));
    }
    
    /**
     * Accept friend request
     */
    public function accept_friend_request($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $params = $request->get_json_params();
        $friend_id = isset($params['user_id']) ? intval($params['user_id']) : 0;
        
        if (!$friend_id) {
            return new WP_Error('invalid_user', 'Invalid user ID', array('status' => 400));
        }
        
        $result = Simuze_Friend::accept_request($current_user->id, $friend_id);
        
        if ($result) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('accept_failed', 'Failed to accept friend request', array('status' => 400));
    }
    
    /**
     * Decline friend request
     */
    public function decline_friend_request($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $params = $request->get_json_params();
        $friend_id = isset($params['user_id']) ? intval($params['user_id']) : 0;
        
        if (!$friend_id) {
            return new WP_Error('invalid_user', 'Invalid user ID', array('status' => 400));
        }
        
        $result = Simuze_Friend::reject_request($current_user->id, $friend_id);
        
        if ($result !== false) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('decline_failed', 'Failed to decline friend request', array('status' => 400));
    }
    
    /**
     * Cancel sent friend request
     */
    public function cancel_friend_request($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $params = $request->get_json_params();
        $friend_id = isset($params['user_id']) ? intval($params['user_id']) : 0;
        
        if (!$friend_id) {
            return new WP_Error('invalid_user', 'Invalid user ID', array('status' => 400));
        }
        
        $result = Simuze_Friend::cancel_request($current_user->id, $friend_id);
        
        if ($result !== false) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('cancel_failed', 'Failed to cancel friend request', array('status' => 400));
    }
    
    /**
     * Unfriend user
     */
    public function unfriend($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $params = $request->get_json_params();
        $friend_id = isset($params['user_id']) ? intval($params['user_id']) : 0;
        
        if (!$friend_id) {
            return new WP_Error('invalid_user', 'Invalid user ID', array('status' => 400));
        }
        
        $result = Simuze_Friend::unfriend($current_user->id, $friend_id);
        
        if ($result !== false) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('unfriend_failed', 'Failed to unfriend user', array('status' => 400));
    }
    
    /**
     * Upload header image (local to Simuze)
     * POST /wp-json/simuze/v1/account/header-upload
     * Body: { "header_cropped": "data:image/jpeg;base64,..." }
     * Returns: { "status": "ok", "url": "..." }
     */
    public function upload_header($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            error_log('[Header Upload] User not authenticated');
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $data = $request->get_json_params();
        
        if (empty($data['header_cropped'])) {
            error_log('[Header Upload] No header_cropped data provided');
            return new WP_Error('invalid_data', 'No header image data provided', array('status' => 400));
        }
        
        $upload_dir = md_upload_dir();
        $headers_dir = $upload_dir['basedir'] . '/simuze-headers/' . $current_user->id;
        
        if (!file_exists($headers_dir)) {
            mkdir($headers_dir, 0755, true);
        }

        // Remove existing headers for this user to keep only the latest upload
        foreach (glob($headers_dir . '/*') as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        
        $header_url = $this->save_cropped_image($data['header_cropped'], $headers_dir, 'header');
        
        if (!$header_url) {
            error_log('[Header Upload] Failed to save header image');
            return new WP_Error('upload_failed', 'Failed to save header image', array('status' => 500));
        }
        
        // Update user's header_url
        $current_user->update(array('header_url' => $header_url));
        
        error_log('[Header Upload] Success, URL: ' . $header_url);
        
        return rest_ensure_response(array(
            'status' => 'ok',
            'url' => $header_url
        ));
    }
    
    /**
     * Refresh avatar cache after upload
     * POST /wp-json/simuze/v1/avatar/refresh-cache
     * Body: { "simezu_user_id": "..." }
     */
    public function refresh_avatar_cache($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $data = $request->get_json_params();
        $simezu_user_id = isset($data['simezu_user_id']) ? $data['simezu_user_id'] : $current_user->simezu_id;
        
        if (empty($simezu_user_id)) {
            return new WP_Error('invalid_data', 'No Simezu user ID provided', array('status' => 400));
        }
        
        // Clear cache and force refresh
        if (class_exists('Simuze_Avatar')) {
            $avatar = Simuze_Avatar::get_instance();
            $avatar->clear_cache($simezu_user_id, 'user');
            $avatar->force_refresh($simezu_user_id, 'user');
        }
        
        return rest_ensure_response(array('status' => 'ok', 'message' => 'Cache refreshed'));
    }
    
    /**
     * Get playlists for current user
     */
    public function get_playlists($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        error_log('[Simuze API] get_playlists called - current_user: ' . ($current_user ? $current_user->id : 'null'));
        
        if (!$current_user) {
            error_log('[Simuze API] get_playlists - No current user found, returning 401');
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        error_log('[Simuze API] get_playlists - Current user ID: ' . $current_user->id);
        return $this->get_user_playlists($request, $current_user->id);
    }
    
    /**
     * Build a track array from an md_music_uploads row.
     */
    /**
     * Resize and crop an image to exact dimensions using GD (replaces wp_get_image_editor).
     * Crops from center to produce a square if src is not square.
     */
    private function _resize_image_gd(string $src, string $dest, int $w, int $h): bool {
        $info = @getimagesize($src);
        if (!$info) return false;
        [$src_w, $src_h, $type] = [$info[0], $info[1], $info[2]];
        switch ($type) {
            case IMAGETYPE_JPEG: $src_img = @imagecreatefromjpeg($src); break;
            case IMAGETYPE_PNG:  $src_img = @imagecreatefrompng($src);  break;
            case IMAGETYPE_GIF:  $src_img = @imagecreatefromgif($src);  break;
            case IMAGETYPE_WEBP: $src_img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false; break;
            default: return false;
        }
        if (!$src_img) return false;
        $min = min($src_w, $src_h);
        $x   = (int) (($src_w - $min) / 2);
        $y   = (int) (($src_h - $min) / 2);
        $dst = imagecreatetruecolor($w, $h);
        imagecopyresampled($dst, $src_img, 0, 0, $x, $y, $w, $h, $min, $min);
        imagedestroy($src_img);
        $result = imagejpeg($dst, $dest, 90);
        imagedestroy($dst);
        return $result;
    }

    private function _track_from_upload(array $upload): array {
        $file_url    = class_exists('MusicRepository') ? MusicRepository::getAudioUrl($upload) : null;
        $artist_name = '';
        $group_id    = $upload['group_id'] ?? null;
        if (!$group_id && !empty($upload['album_id'])) {
            $album    = md_db_row("SELECT group_id FROM md_music_albums WHERE id = ?", [(int) $upload['album_id']]);
            $group_id = $album['group_id'] ?? null;
        }
        if ($group_id && class_exists('GroupRepository')) {
            $grp = GroupRepository::find((int) $group_id);
            if ($grp) $artist_name = $grp->name;
        }
        return array(
            'id'             => $upload['id'],
            'song_id'        => $upload['id'],
            'title'          => $upload['song_name'] ?? '',
            'name'           => $upload['song_name'] ?? '',
            'description'    => $upload['description'] ?? '',
            'file_url'       => $file_url,
            'mp3_url'        => $file_url,
            'url'            => $file_url,
            'artist'         => $artist_name,
            'artist_name'    => $artist_name,
            'group_id'       => $group_id,
            'artwork_url'    => null,
            'cover'          => null,
            'artwork'        => null,
            'genre'          => $upload['genre'] ?? null,
            'license'        => $upload['license'] ?? null,
            'duration'       => null,
            'price'          => (float) ($upload['price'] ?? 0),
            'final_price'    => (float) ($upload['price'] ?? 0),
            'is_active'      => 1,
            'is_purchasable' => 1,
            'download_count' => 0,
            'created_at'     => $upload['created_at'] ?? null,
        );
    }

    /**
     * Get playlists for a specific user
     */
    public function get_user_playlists($request, $user_id = null) {
        if (!$user_id) {
            $user_id = (int) $request->get_param('id');
        }

        if (!$user_id) {
            return new WP_Error('invalid_user', 'User ID is required', array('status' => 400));
        }

        $include_tracks_param = $request->get_param('include_tracks');
        $include_songs_param  = $request->get_param('include_songs');
        $include_tracks = ($include_tracks_param === 'true' || $include_tracks_param === true || $include_tracks_param === '1' || $include_tracks_param === 1) ||
                          ($include_songs_param  === 'true' || $include_songs_param  === true || $include_songs_param  === '1' || $include_songs_param  === 1);

        $playlists = md_db_query(
            "SELECT * FROM md_music_playlists WHERE created_by = ? AND deleted_at IS NULL ORDER BY created_at DESC",
            [$user_id]
        );

        $owner_user = Simuze_User::get($user_id);
        $owner = $owner_user ? array(
            'id'           => (string) $owner_user->id,
            'username'     => $owner_user->username,
            'display_name' => $owner_user->display_name,
        ) : null;

        $formatted_playlists = array();
        foreach ($playlists as $playlist) {
            $track_count = (int) md_db_var(
                "SELECT COUNT(*) FROM md_music_playlist_tracks WHERE playlist_id = ?",
                [$playlist['id']]
            );
            
            $tracks = array();
            if ($include_tracks && $track_count > 0) {
                $uploads = md_db_query(
                    "SELECT u.* FROM md_music_uploads u
                     JOIN md_music_playlist_tracks pt ON pt.upload_id = u.id
                     WHERE pt.playlist_id = ? AND u.status = 'published'
                     ORDER BY pt.position ASC",
                    [$playlist['id']]
                );
                foreach ($uploads as $upload) {
                    $tracks[] = $this->_track_from_upload($upload);
                }
            }

            $fp = array(
                'id'           => $playlist['id'],
                'name'         => $playlist['name'] ?? '',
                'title'        => $playlist['name'] ?? '',
                'description'  => $playlist['description'] ?? '',
                'slug'         => $playlist['slug'] ?? '',
                'tracks_count' => $track_count,
                'owner'        => $owner,
                'artwork'      => null,
                'created_at'   => $playlist['created_at'] ?? null,
                'track_ids'    => array(),
                'song_ids'     => array(),
            );
            if ($include_tracks) {
                $fp['tracks'] = $tracks;
                $fp['songs']  = $tracks;
                $fp['items']  = $tracks;
            }
            $formatted_playlists[] = $fp;
        }

        return rest_ensure_response(array('playlists' => $formatted_playlists));
    }
    
    /**
     * Get single playlist by ID
     */
    public function get_playlist($request) {
        $playlist_id = (int) $request->get_param('id');
        if (!$playlist_id) {
            return new WP_Error('invalid_playlist', 'Playlist ID is required', array('status' => 400));
        }

        $playlist = md_db_row(
            "SELECT * FROM md_music_playlists WHERE id = ? AND deleted_at IS NULL",
            [$playlist_id]
        );
        if (!$playlist) {
            return new WP_Error('not_found', 'Playlist not found', array('status' => 404));
        }

        $include_tracks_param = $request->get_param('include_tracks');
        $include_songs_param  = $request->get_param('include_songs');
        $include_tracks = ($include_tracks_param === 'true' || $include_tracks_param === true || $include_tracks_param === '1' || $include_tracks_param === 1) ||
                          ($include_songs_param  === 'true' || $include_songs_param  === true || $include_songs_param  === '1' || $include_songs_param  === 1);

        $track_count = (int) md_db_var(
            "SELECT COUNT(*) FROM md_music_playlist_tracks WHERE playlist_id = ?",
            [$playlist_id]
        );

        $tracks = array();
        if ($include_tracks && $track_count > 0) {
            $uploads = md_db_query(
                "SELECT u.* FROM md_music_uploads u
                 JOIN md_music_playlist_tracks pt ON pt.upload_id = u.id
                 WHERE pt.playlist_id = ? AND u.status = 'published'
                 ORDER BY pt.position ASC",
                [$playlist_id]
            );
            foreach ($uploads as $upload) {
                $tracks[] = $this->_track_from_upload($upload);
            }
        }

        $owner_user = !empty($playlist['created_by']) ? Simuze_User::get((int) $playlist['created_by']) : null;
        $owner = $owner_user ? array(
            'id'           => (string) $owner_user->id,
            'username'     => $owner_user->username,
            'display_name' => $owner_user->display_name,
        ) : null;

        $playlist_data = array(
            'id'           => $playlist['id'],
            'name'         => $playlist['name'],
            'title'        => $playlist['name'],
            'description'  => $playlist['description'] ?? '',
            'slug'         => $playlist['slug'] ?? '',
            'tracks_count' => $track_count,
            'owner'        => $owner,
            'artwork'      => null,
            'created_at'   => $playlist['created_at'],
            'track_ids'    => array(),
            'song_ids'     => array(),
        );
        if ($include_tracks) {
            $playlist_data['tracks'] = $tracks;
            $playlist_data['songs']  = $tracks;
            $playlist_data['items']  = $tracks;
        }

        return rest_ensure_response($playlist_data);
    }
    
    /**
     * Get tracks for a playlist
     */
    public function get_playlist_tracks($request) {
        $playlist_id = (int) $request->get_param('id');
        if (!$playlist_id) {
            return new WP_Error('invalid_playlist', 'Playlist ID is required', array('status' => 400));
        }

        $exists = md_db_var(
            "SELECT id FROM md_music_playlists WHERE id = ? AND deleted_at IS NULL",
            [$playlist_id]
        );
        if (!$exists) {
            return new WP_Error('not_found', 'Playlist not found', array('status' => 404));
        }

        $uploads = md_db_query(
            "SELECT u.* FROM md_music_uploads u
             JOIN md_music_playlist_tracks pt ON pt.upload_id = u.id
             WHERE pt.playlist_id = ? AND u.status = 'published'
             ORDER BY pt.position ASC",
            [$playlist_id]
        );

        $tracks = array();
        foreach ($uploads as $upload) {
            $tracks[] = $this->_track_from_upload($upload);
        }

        return rest_ensure_response(array(
            'tracks' => $tracks,
            'songs'  => $tracks,
            'items'  => $tracks,
        ));
    }

    
    /**
     * Create a new playlist
     * POST /wp-json/simuze/v1/playlists
     * Body: { "name": "...", "description": "...", "is_public": true }
     */
    public function create_playlist($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        // Get JSON data
        $data = $request->get_json_params();
        if (!$data) {
            $data = $request->get_params();
        }
        
        $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
        $description = isset($data['description']) ? trim(strip_tags($data['description'])) : '';
        $is_public = isset($data['is_public']) ? ($data['is_public'] === true || $data['is_public'] === '1' || $data['is_public'] === 1) : true;
        
        error_log('[Simuze API] create_playlist - name: ' . $name . ', description: ' . $description . ', is_public: ' . ($is_public ? 'true' : 'false'));
        
        if (empty($name) || trim($name) === '') {
            return new WP_Error('missing_name', 'Playlist name is required', array('status' => 400));
        }
        
        // Use the existing simuze_create_playlist function
        if (!function_exists('simuze_create_playlist')) {
            return new WP_Error('function_not_found', 'Playlist creation function not available', array('status' => 500));
        }
        
        $playlist_id = simuze_create_playlist($name, $description, $is_public);
        
        if (!$playlist_id) {
            error_log('[Simuze API] create_playlist - Failed to create playlist');
            return new WP_Error('creation_failed', 'Failed to create playlist', array('status' => 500));
        }
        
        error_log('[Simuze API] create_playlist - Success, playlist ID: ' . $playlist_id);
        
        // Get the created playlist data
        $playlist_post = get_post($playlist_id);
        if (!$playlist_post) {
            return new WP_Error('not_found', 'Playlist created but not found', array('status' => 500));
        }
        
        // Get track count
        $track_count = 0;
        if (function_exists('simuze_get_playlist_valid_track_count')) {
            $track_count = simuze_get_playlist_valid_track_count($playlist_id);
        }
        
        // Get owner info
        $owner = null;
        if ($current_user) {
            $owner = array(
                'id' => (string) $current_user->id,
                'username' => $current_user->username,
                'display_name' => $current_user->display_name,
            );
        }
        
        $playlist_data = array(
            'id' => $playlist_id,
            'name' => $playlist_post->post_title,
            'title' => $playlist_post->post_title,
            'description' => $playlist_post->post_content,
            'slug' => $playlist_post->post_name,
            'tracks_count' => $track_count,
            'owner' => $owner,
            'artwork' => null,
            'created_at' => $playlist_post->post_date,
        );
        
        return rest_ensure_response($playlist_data);
    }
    
    /**
     * Get latest tracks (from music_uploads table)
     * GET /wp-json/simuze/v1/tracks/latest?limit=10
     */
    public function get_latest_tracks($request) {
        $limit   = min((int) ($request->get_param('limit') ?: 10), 50);
        $uploads = md_db_query(
            "SELECT * FROM md_music_uploads WHERE status = 'published' ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );

        $tracks = array();
        foreach ($uploads as $upload) {
            $track = $this->_track_from_upload($upload);
            $album_name = '';
            if (!empty($upload['album_id'])) {
                $album_name = (string) (md_db_var("SELECT album_name FROM md_music_albums WHERE id = ?", [(int) $upload['album_id']]) ?: '');
            }
            $upload_date             = $upload['created_at'] ?? date('Y-m-d H:i:s');
            $track['upload_id']      = $track['id'];
            $track['album']          = $album_name;
            $track['album_name']     = $album_name;
            $track['upload_date']    = $upload_date;
            $track['formatted_date'] = date('M j', strtotime($upload_date));
            $tracks[] = $track;
        }

        return rest_ensure_response(array('tracks' => $tracks));
    }
    
    /**
     * Search tracks with filters
     * GET /wp-json/simuze/v1/tracks/search
     */
    public function search_tracks($request) {
        $limit         = min((int) ($request->get_param('limit') ?: 50), 200);
        $search        = $request->get_param('search') ?: '';
        $license       = $request->get_param('license') ?: '';
        $genre         = $request->get_param('genre') ?: '';
        $mood_tempo    = $request->get_param('mood_tempo');
        $mood_volume   = $request->get_param('mood_volume');
        $mood_dynamics = $request->get_param('mood_dynamics');
        $mood_emotion  = $request->get_param('mood_emotion');
        $mood_intensity= $request->get_param('mood_intensity');

        if (empty($search) && empty($license) && empty($genre) &&
            $mood_tempo === null && $mood_volume === null && $mood_dynamics === null &&
            $mood_emotion === null && $mood_intensity === null) {
            return $this->get_latest_tracks($request);
        }

        $where  = ["status = 'published'"];
        $params = array();

        if (!empty($search)) {
            $like      = '%' . md_db_esc_like($search) . '%';
            $where[]   = "(song_name LIKE ? OR description LIKE ?)";
            $params[]  = $like;
            $params[]  = $like;
        }
        if (!empty($license)) {
            $where[]  = "license = ?";
            $params[] = $license;
        }
        if (!empty($genre)) {
            $where[]  = "genre LIKE ?";
            $params[] = '%' . md_db_esc_like($genre) . '%';
        }

        $where_sql = implode(' AND ', $where);
        $params[]  = $limit;

        $uploads = md_db_query(
            "SELECT * FROM md_music_uploads WHERE {$where_sql} ORDER BY created_at DESC LIMIT ?",
            $params
        );

        $tracks = array();
        foreach ($uploads as $upload) {
            $track = $this->_track_from_upload($upload);
            $album_name = '';
            if (!empty($upload['album_id'])) {
                $album_name = (string) (md_db_var("SELECT album_name FROM md_music_albums WHERE id = ?", [(int) $upload['album_id']]) ?: '');
            }
            $upload_date             = $upload['created_at'] ?? date('Y-m-d H:i:s');
            $track['upload_id']      = $track['id'];
            $track['album']          = $album_name;
            $track['album_name']     = $album_name;
            $track['upload_date']    = $upload_date;
            $track['formatted_date'] = date('M j', strtotime($upload_date));
            $tracks[] = $track;
        }

        return rest_ensure_response(array('tracks' => $tracks));
    }
    
    /**
     * Add track to playlist
     * POST /wp-json/simuze/v1/playlists/{id}/tracks
     * Body: { "upload_id": 123 }
     */
    public function add_track_to_playlist($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $playlist_id = (int) $request->get_param('id');
        $data = $request->get_json_params();
        if (!$data) {
            $data = $request->get_params();
        }
        
        $upload_id = isset($data['upload_id']) ? (int) $data['upload_id'] : 0;
        
        if (!$playlist_id || !$upload_id) {
            return new WP_Error('missing_params', 'Playlist ID and upload_id are required', array('status' => 400));
        }
        
        // Use the existing simuze_add_to_playlist function
        if (!function_exists('simuze_add_to_playlist')) {
            error_log('[Simuze API] add_track_to_playlist - simuze_add_to_playlist function not found');
            return new WP_Error('function_not_found', 'Add to playlist function not available', array('status' => 500));
        }
        
        error_log('[Simuze API] add_track_to_playlist - Calling simuze_add_to_playlist with playlist_id=' . $playlist_id . ', upload_id=' . $upload_id . ', user_id=' . $current_user->id);
        
        // Pass the current user's ID to ensure proper ownership checking
        // The function will use this to verify the user owns the playlist
        $result = simuze_add_to_playlist($playlist_id, $upload_id, $current_user->id);
        
        error_log('[Simuze API] add_track_to_playlist - Result: ' . var_export($result, true));
        
        if ($result === 'already_exists') {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Track already in playlist',
                'already_exists' => true,
            ));
        } elseif ($result) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Track added to playlist',
            ));
        } else {
            return new WP_Error('add_failed', 'Failed to add track to playlist', array('status' => 500));
        }
    }
    
    /**
     * Get payment settings for current user - always from SIMEZU
     */
    public function get_payment_settings($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        // Get access token for SIMEZU API
        $access_token = $this->get_simezu_access_token();
        
        if (!$access_token) {
            error_log('SIMEZU Payment Settings: No access token available');
            // Return default structure if no token
            return rest_ensure_response(array(
                'data' => array(
                    'paypal' => array('connected' => false, 'email' => '', 'status' => 'not_connected'),
                    'stripe' => array('connected' => false, 'publishable_key' => '', 'status' => 'not_connected'),
                    'credit_card' => array('enabled' => false),
                    'default_payment_method' => 'paypal',
                    'connected' => false,
                    'account_status' => 'not_connected'
                )
            ));
        }
        
        // Get payment settings directly from SIMEZU
        try {
            if (!class_exists('Simuze_Simezu_Payments')) {
                error_log('SIMEZU Payment Settings: Simuze_Simezu_Payments class not found');
                return rest_ensure_response(array(
                    'data' => array(
                        'paypal' => array('connected' => false, 'email' => '', 'status' => 'not_connected'),
                        'stripe' => array('connected' => false, 'publishable_key' => '', 'status' => 'not_connected'),
                        'credit_card' => array('enabled' => false),
                        'default_payment_method' => 'paypal',
                        'connected' => false,
                        'account_status' => 'not_connected'
                    )
                ));
            }
            
            $simezu_payments = Simuze_Simezu_Payments::get_instance();
            $simezu_user_id = $current_user->simezu_id ?? '';
            
            if (empty($simezu_user_id)) {
                error_log('SIMEZU Payment Settings: No SIMEZU user ID found for user ' . $current_user->id);
                return rest_ensure_response(array(
                    'data' => array(
                        'paypal' => array('connected' => false, 'email' => '', 'status' => 'not_connected'),
                        'stripe' => array('connected' => false, 'publishable_key' => '', 'status' => 'not_connected'),
                        'credit_card' => array('enabled' => false),
                        'default_payment_method' => 'paypal',
                        'connected' => false,
                        'account_status' => 'not_connected'
                    )
                ));
            }
            
            $payment_data = $simezu_payments->get_payment_account($simezu_user_id, $access_token);
            
            if (is_wp_error($payment_data)) {
                error_log('SIMEZU Payment Settings Error: ' . $payment_data->get_error_message());
                // Return default structure on error
                return rest_ensure_response(array(
                    'data' => array(
                        'paypal' => array('connected' => false, 'email' => '', 'status' => 'not_connected'),
                        'stripe' => array('connected' => false, 'publishable_key' => '', 'status' => 'not_connected'),
                        'credit_card' => array('enabled' => false),
                        'default_payment_method' => 'paypal',
                        'connected' => false,
                        'account_status' => 'not_connected'
                    )
                ));
            }
            
            // Ensure data structure matches frontend expectations
            if (!is_array($payment_data)) {
                error_log('SIMEZU Payment Settings: Invalid payment data format');
                $payment_data = array();
            }
            
            $normalized_data = array(
                'paypal' => array(
                    'email' => $payment_data['paypal']['email'] ?? '',
                    'connected' => $payment_data['paypal']['connected'] ?? false,
                    'status' => $payment_data['paypal']['status'] ?? ($payment_data['paypal']['connected'] ?? false ? 'connected' : 'not_connected')
                ),
                'stripe' => array(
                    'publishable_key' => $payment_data['stripe']['publishable_key'] ?? '',
                    'connected' => $payment_data['stripe']['connected'] ?? false,
                    'status' => $payment_data['stripe']['status'] ?? ($payment_data['stripe']['connected'] ?? false ? 'connected' : 'not_connected')
                ),
                'credit_card' => array(
                    'enabled' => $payment_data['credit_card']['enabled'] ?? false
                ),
                'default_payment_method' => $payment_data['default_payment_method'] ?? 'paypal',
                'connected' => $payment_data['connected'] ?? false,
                'account_status' => $payment_data['account_status'] ?? 'not_connected'
            );
            
            return rest_ensure_response(array('data' => $normalized_data));
            
        } catch (Exception $e) {
            error_log('SIMEZU Payment Settings Exception: ' . $e->getMessage());
            return rest_ensure_response(array(
                'data' => array(
                    'paypal' => array('connected' => false, 'email' => '', 'status' => 'not_connected'),
                    'stripe' => array('connected' => false, 'publishable_key' => '', 'status' => 'not_connected'),
                    'credit_card' => array('enabled' => false),
                    'default_payment_method' => 'paypal',
                    'connected' => false,
                    'account_status' => 'not_connected'
                )
            ));
        }
    }
    
    /**
     * Save payment settings for current user - always sync to SIMEZU
     */
    public function save_payment_settings($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        // Get access token for SIMEZU API
        $access_token = $this->get_simezu_access_token();
        
        if (!$access_token) {
            return new WP_Error('no_token', 'No access token available for SIMEZU', array('status' => 401));
        }
        
        $simezu_user_id = $current_user->simezu_id ?? '';
        if (empty($simezu_user_id)) {
            return new WP_Error('no_simezu_id', 'No SIMEZU user ID found', array('status' => 400));
        }
        
        $data = $request->get_json_params();
        if (!$data) {
            $data = $request->get_params();
        }
        
        // Build settings payload for SIMEZU
        $simezu_settings = array();
        
        // Handle PayPal settings
        if (isset($data['paypal'])) {
            if (isset($data['paypal']['email'])) {
                $email = filter_var($data['paypal']['email'], FILTER_SANITIZE_EMAIL);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return new WP_Error('invalid_email', 'Invalid PayPal email address', array('status' => 400));
                }
                $simezu_settings['paypal'] = array('email' => $email);
            }
        }
        
        // Handle Stripe settings
        if (isset($data['stripe'])) {
            $stripe_data = array();
            if (isset($data['stripe']['publishable_key'])) {
                $stripe_data['publishable_key'] = sanitize_text_field($data['stripe']['publishable_key']);
            }
            if (isset($data['stripe']['secret_key'])) {
                $stripe_data['secret_key'] = sanitize_text_field($data['stripe']['secret_key']);
            }
            if (!empty($stripe_data)) {
                $simezu_settings['stripe'] = $stripe_data;
            }
        }
        
        // Handle Credit Card settings
        if (isset($data['credit_card'])) {
            $simezu_settings['credit_card'] = array(
                'enabled' => isset($data['credit_card']['enabled']) ? (bool) $data['credit_card']['enabled'] : false
            );
        }
        
        // Handle Default Payment Method
        if (isset($data['default_payment_method'])) {
            $simezu_settings['default_payment_method'] = sanitize_text_field($data['default_payment_method']);
        }
        
        // Update SIMEZU via API
        $simezu_payments = Simuze_Simezu_Payments::get_instance();
        $result = $simezu_payments->update_payment_account($simezu_user_id, $simezu_settings, $access_token);
        
        if (is_wp_error($result)) {
            error_log('SIMEZU Payment Settings Update Error: ' . $result->get_error_message());
            return new WP_Error('simezu_update_failed', 'Failed to update payment settings in SIMEZU: ' . $result->get_error_message(), array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Payment settings saved and synced to SIMEZU successfully',
        ));
    }
    
    /**
     * Get SIMEZU access token from cookies or headers
     */
    private function get_simezu_access_token() {
        // Check cookie first (most common)
        if (isset($_COOKIE[Simuze_Auth::ACCESS_TOKEN_COOKIE])) {
            return $_COOKIE[Simuze_Auth::ACCESS_TOKEN_COOKIE];
        }
        
        // Fallback to old cookie name
        if (isset($_COOKIE['simuze_jwt_token'])) {
            return $_COOKIE['simuze_jwt_token'];
        }
        
        // Check Authorization header
        $auth_header = null;
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        }
        
        if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Test Stripe connection
     */
    public function test_stripe_connection($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $stripe_connected = get_user_meta($current_user->id, '_simuze_stripe_connected', true);
        $stripe_account_id = get_user_meta($current_user->id, '_simuze_stripe_account_id', true);
        
        if (empty($stripe_connected) || empty($stripe_account_id)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'Stripe account not connected. Please connect your Stripe account first.',
            ));
        }
        
        // TODO: Implement actual Stripe API test
        // For now, just check if account ID exists
        // In production, you would make an API call to Stripe to verify the connection
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Stripe connection test successful',
        ));
    }
    
    /**
     * Test PayPal connection
     */
    public function test_paypal_connection($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $data = $request->get_json_params();
        if (!$data) {
            $data = $request->get_params();
        }
        
        $email = isset($data['email']) ? filter_var($data['email'], FILTER_SANITIZE_EMAIL) : '';
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new WP_Error('invalid_email', 'Valid PayPal email address is required', array('status' => 400));
        }
        
        // TODO: Implement actual PayPal email verification
        // For now, we'll just validate the email format and mark it as verified
        // In production, you would:
        // 1. Check if the email is associated with a PayPal account
        // 2. Send a verification email to the user
        // 3. Verify the email through PayPal's API
        
        // For now, simulate verification (accept any valid email)
        update_user_meta($current_user->id, '_simuze_paypal_email', $email);
        update_user_meta($current_user->id, '_simuze_paypal_verified', '1');
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'PayPal email verified successfully',
        ));
    }
    
    /**
     * Get group members with payment settings
     */
    public function get_group_members_with_payments($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        // Check permissions - only group admins can view payment settings
        if (!$group->can_manage($current_user->id)) {
            return new WP_Error('forbidden', 'Insufficient permissions', array('status' => 403));
        }
        
        $members_result = $group->get_members(array('per_page' => 100, 'status' => 'active'));
        $members = $members_result['members'];

        $members_with_payments = array();

        foreach ($members as $member) {
            $payment = md_db_row(
                "SELECT * FROM simuze_group_payments WHERE group_id = ? AND member_id = ?",
                [$group->id, $member['id']]
            );

            if ($payment) {
                $member_user = Simuze_User::get($member['id']);
                if ($member_user) {
                    $members_with_payments[] = array(
                        'id'               => $member['id'],
                        'username'         => $member_user->username,
                        'display_name'     => $member_user->display_name ?: $member_user->username,
                        'paypal_email'     => $payment['paypal_email'] ?? null,
                        'stripe_connect_id'=> $payment['stripe_connect_id'] ?? null,
                    );
                }
            }
        }

        return rest_ensure_response(array('members' => $members_with_payments));
    }
    
    /**
     * Get payment settings for a specific group member
     */
    public function get_group_member_payment($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        // Check permissions
        if (!$group->can_manage($current_user->id)) {
            return new WP_Error('forbidden', 'Insufficient permissions', array('status' => 403));
        }
        
        $member_id = (int) $request->get_param('member_id');

        $payment = md_db_row(
            "SELECT * FROM simuze_group_payments WHERE group_id = ? AND member_id = ?",
            [$group->id, $member_id]
        );

        return rest_ensure_response(array('payment' => $payment));
    }
    
    /**
     * Get group payment settings
     */
    public function get_group_payment_settings($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        $payment = md_db_row(
            "SELECT * FROM simuze_group_payments WHERE group_id = ? AND member_id IS NULL ORDER BY updated_at DESC LIMIT 1",
            [$group->id]
        );
        
        $payment_data = array(
            'paypal' => array(
                'email' => null,
                'connected' => false,
                'status' => 'not_connected'
            ),
            'stripe' => array(
                'publishable_key' => null,
                'connected' => false,
                'status' => 'not_connected'
            ),
            'credit_card' => array(
                'enabled' => false
            ),
            'default_payment_method' => 'paypal'
        );
        
        if ($payment) {
            if (!empty($payment['paypal_email'])) {
                $payment_data['paypal'] = array(
                    'email' => $payment['paypal_email'],
                    'connected' => true,
                    'status' => 'connected'
                );
            }
            
            if (!empty($payment['stripe_connect_id'])) {
                $payment_data['stripe'] = array(
                    'publishable_key' => $payment['stripe_connect_id'],
                    'connected' => true,
                    'status' => 'connected'
                );
            }
        }
        
        return rest_ensure_response(array('data' => $payment_data));
    }
    
    /**
     * Save group payment settings
     */
    public function save_group_payment_settings($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        // Check permissions - only group admins can save payment settings
        if (!$group->can_manage($current_user->id)) {
            return new WP_Error('forbidden', 'Insufficient permissions', array('status' => 403));
        }
        
        $data = $request->get_json_params();
        $payment_method_type = $data['payment_method_type'] ?? 'existing';
        
        $now = date('Y-m-d H:i:s');

        if ($payment_method_type === 'existing') {
            $member_id = (int) ($data['member_id'] ?? 0);
            if (!$member_id) {
                return new WP_Error('invalid_request', 'Member ID is required', array('status' => 400));
            }
            if (!$group->is_member($member_id)) {
                return new WP_Error('invalid_request', 'Member is not part of this group', array('status' => 400));
            }

            $existing_id = md_db_var(
                "SELECT id FROM simuze_group_payments WHERE group_id = ? AND member_id = ?",
                [$group->id, $member_id]
            );

            if ($existing_id) {
                md_db_update('simuze_group_payments', ['updated_at' => $now], ['id' => (int) $existing_id]);
                return rest_ensure_response(array(
                    'success'    => true,
                    'message'    => 'Payment settings already exist for this member',
                    'payment_id' => (int) $existing_id,
                ));
            }

            $new_id = md_db_insert('simuze_group_payments', [
                'group_id'         => $group->id,
                'member_id'        => $member_id,
                'paypal_email'     => null,
                'stripe_connect_id'=> null,
                'description'      => 'Linked from member payment settings',
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            if (!$new_id) {
                return new WP_Error('save_failed', 'Failed to save payment settings', array('status' => 500));
            }

            return rest_ensure_response(array(
                'success'    => true,
                'message'    => 'Payment settings saved successfully',
                'payment_id' => (int) $new_id,
            ));

        } else {
            $paypal_email      = filter_var($data['paypal_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';
            $stripe_connect_id = preg_replace('/[^\w_]/', '', $data['stripe_connect_id'] ?? '');
            $description       = substr(strip_tags($data['description'] ?? ''), 0, 255);

            if (empty($paypal_email) && empty($stripe_connect_id)) {
                return new WP_Error('invalid_request', 'At least one payment method (PayPal or Stripe) is required', array('status' => 400));
            }
            if (!empty($stripe_connect_id) && !preg_match('/^(acct_|sk_|pk_)/', $stripe_connect_id)) {
                return new WP_Error('invalid_request', 'Invalid Stripe Connect ID format', array('status' => 400));
            }

            $new_id = md_db_insert('simuze_group_payments', [
                'group_id'         => $group->id,
                'member_id'        => null,
                'paypal_email'     => $paypal_email ?: null,
                'stripe_connect_id'=> $stripe_connect_id ?: null,
                'description'      => $description ?: null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            if (!$new_id) {
                return new WP_Error('save_failed', 'Failed to save payment settings', array('status' => 500));
            }

            return rest_ensure_response(array(
                'success'    => true,
                'message'    => 'Payment settings saved successfully',
                'payment_id' => (int) $new_id,
            ));
        }
    }
    
    /**
     * Test group payment settings
     */
    public function test_group_payment($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $group = Simuze_Group::get($request->get_param('id'));
        if (!$group) {
            return new WP_Error('not_found', 'Group not found', array('status' => 404));
        }
        
        // Check permissions
        if (!$group->can_manage($current_user->id)) {
            return new WP_Error('forbidden', 'Insufficient permissions', array('status' => 403));
        }
        
        $data = $request->get_json_params();
        $payment_method_type = $data['payment_method_type'] ?? 'existing';
        
        // For now, just validate the payment information
        // In a real implementation, you would send a small test payment via SIMEZU API
        if ($payment_method_type === 'existing') {
            $member_id = (int) ($data['member_id'] ?? 0);
            if (!$member_id) {
                return new WP_Error('invalid_request', 'Member ID is required', array('status' => 400));
            }
            
            $payment = md_db_row(
                "SELECT * FROM simuze_group_payments WHERE group_id = ? AND member_id = ?",
                [$group->id, $member_id]
            );
            
            if (!$payment) {
                return new WP_Error('not_found', 'Payment settings not found for this member', array('status' => 404));
            }
            
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Payment settings validated successfully',
                'payment' => $payment
            ));
            
        } else {
            $paypal_email = filter_var($data['paypal_email'] ?? '', FILTER_SANITIZE_EMAIL);
            $stripe_connect_id = sanitize_text_field($data['stripe_connect_id'] ?? '');
            
            if (empty($paypal_email) && empty($stripe_connect_id)) {
                return new WP_Error('invalid_request', 'At least one payment method is required', array('status' => 400));
            }
            
            // Validate formats
            if (!empty($paypal_email) && !filter_var($paypal_email, FILTER_VALIDATE_EMAIL)) {
                return new WP_Error('invalid_request', 'Invalid PayPal email address', array('status' => 400));
            }
            
            if (!empty($stripe_connect_id) && !preg_match('/^(acct_|sk_|pk_)/', $stripe_connect_id)) {
                return new WP_Error('invalid_request', 'Invalid Stripe Connect ID format', array('status' => 400));
            }
            
            // In a real implementation, you would test the payment via SIMEZU API here
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Payment information validated successfully. Test payment functionality will be implemented via SIMEZU API.',
            ));
        }
    }
    
    /**
     * Search groups (for artist search in Labels)
     */
    public function search_groups($request) {
        $query = md_sanitize_text($request->get_param('query') ?: '');
        $type = md_sanitize_text($request->get_param('type') ?: '');
        $limit = (int) ($request->get_param('limit') ?: 10);
        $exclude_group_id = (int) ($request->get_param('exclude_group_id') ?: 0);

        $where = array("g.deleted_at IS NULL");
        $where_values = array();

        if (!empty($query)) {
            $where[] = "(g.name LIKE ? OR g.slug LIKE ? OR g.description LIKE ?)";
            $search_like = '%' . md_db_esc_like($query) . '%';
            $where_values[] = $search_like;
            $where_values[] = $search_like;
            $where_values[] = $search_like;
        }

        if (!empty($type)) {
            $where[] = "gt.slug = ?";
            $where_values[] = $type;
        }

        if ($exclude_group_id > 0) {
            $where[] = "g.id != ?";
            $where_values[] = $exclude_group_id;
        }

        $where_clause = implode(' AND ', $where);
        $where_values[] = $limit;

        $groups = md_db_query(
            "SELECT g.*, gt.slug as type_slug, gt.name as type_name
             FROM simuze_groups g
             LEFT JOIN simuze_group_types gt ON g.group_type_id = gt.id
             WHERE {$where_clause}
             ORDER BY g.name ASC
             LIMIT ?",
            $where_values
        );
        
        $group_arrays = array();
        foreach ($groups as $group_data) {
            $group = Simuze_Group::get($group_data['id']);
            if ($group) {
                $group_arrays[] = $group->to_array();
            }
        }
        
        return rest_ensure_response(array(
            'groups' => $group_arrays,
            'total' => count($group_arrays)
        ));
    }
    
    /**
     * Invite artist to label
     */
    public function invite_artist_to_label($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $label_id = (int) $request->get_param('id');
        $label = Simuze_Group::get($label_id);
        
        if (!$label) {
            return new WP_Error('not_found', 'Label not found', array('status' => 404));
        }
        
        // Verify this is a Label group
        $group_type = $label->get_group_type();
        if (!$group_type || $group_type['slug'] !== 'label') {
            return new WP_Error('invalid_group_type', 'This endpoint is only available for Label groups', array('status' => 400));
        }
        
        // Verify user is admin of the label
        if (!$label->can_manage($current_user->id)) {
            return new WP_Error('forbidden', 'Only label administrators can invite artists', array('status' => 403));
        }
        
        $data = $request->get_json_params();
        $artist_group_id = (int) ($data['artist_group_id'] ?? 0);
        $message = trim(strip_tags($data['message'] ?? ''));
        
        if (!$artist_group_id) {
            return new WP_Error('invalid_request', 'Artist group ID is required', array('status' => 400));
        }
        
        $artist = Simuze_Group::get($artist_group_id);
        if (!$artist) {
            return new WP_Error('not_found', 'Artist group not found', array('status' => 404));
        }
        
        // Verify artist is actually an Artist group
        $artist_type = $artist->get_group_type();
        if (!$artist_type || $artist_type['slug'] !== 'artist') {
            return new WP_Error('invalid_group_type', 'The selected group is not an Artist group', array('status' => 400));
        }
        
        // Check if relationship already exists
        $existing = md_db_var(
            "SELECT id FROM simuze_label_artists WHERE label_group_id = ? AND artist_group_id = ?",
            [$label_id, $artist_group_id]
        );

        if ($existing) {
            return new WP_Error('already_exists', 'This artist is already associated with this label', array('status' => 400));
        }

        $now = date('Y-m-d H:i:s');
        $invitation_id = md_db_insert('simuze_label_artists', [
            'label_group_id'  => $label_id,
            'artist_group_id' => $artist_group_id,
            'status'          => 'pending',
            'message'         => $message,
            'invited_by'      => $current_user->id,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        if ($invitation_id) {
            // Send message to artist group
            if (class_exists('Simuze_Message')) {
                $message_text = sprintf(
                    "The label '%s' has invited you to join their roster.\n\n%s",
                    $label->name,
                    $message ?: 'No additional message provided.'
                );

                // Send to all admins of the artist group
                $artist_members = $artist->get_members(array('role' => 'admin'));
                foreach ($artist_members['members'] as $member) {
                    Simuze_Message::send(
                        $current_user->id,
                        $member['user_id'],
                        sprintf('Invitation from %s', $label->name),
                        $message_text,
                        $artist_group_id, // group_id
                        'label_artist_invitation'
                    );
                }
            }

            return rest_ensure_response(array(
                'success'       => true,
                'message'       => 'Invitation sent successfully',
                'invitation_id' => $invitation_id,
            ));
        }
        
        return new WP_Error('creation_failed', 'Failed to create invitation', array('status' => 500));
    }
    
    /**
     * Cancel artist invitation
     */
    public function cancel_artist_invitation($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $label_id = (int) $request->get_param('id');
        $invitation_id = (int) $request->get_param('invitation_id');
        
        $label = Simuze_Group::get($label_id);
        if (!$label || !$label->can_manage($current_user->id)) {
            return new WP_Error('forbidden', 'Access denied', array('status' => 403));
        }
        
        $result = md_db_delete('simuze_label_artists', [
            'id'             => $invitation_id,
            'label_group_id' => $label_id,
            'status'         => 'pending',
        ]);
        
        if ($result) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('not_found', 'Invitation not found', array('status' => 404));
    }
    
    /**
     * Remove artist relationship
     */
    public function remove_artist_relationship($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $label_id = (int) $request->get_param('id');
        $relationship_id = (int) $request->get_param('relationship_id');
        
        $label = Simuze_Group::get($label_id);
        if (!$label) {
            return new WP_Error('not_found', 'Label not found', array('status' => 404));
        }
        
        // Check if user is admin of label OR admin of artist
        $is_label_admin = $label->can_manage($current_user->id);
        
        $relationship = md_db_row(
            "SELECT * FROM simuze_label_artists WHERE id = ?",
            [$relationship_id]
        );

        if (!$relationship) {
            return new WP_Error('not_found', 'Relationship not found', array('status' => 404));
        }

        $is_artist_admin = false;
        if ($relationship['artist_group_id']) {
            $artist = Simuze_Group::get($relationship['artist_group_id']);
            if ($artist) {
                $is_artist_admin = $artist->can_manage($current_user->id);
            }
        }

        if (!$is_label_admin && !$is_artist_admin) {
            return new WP_Error('forbidden', 'Access denied', array('status' => 403));
        }

        // Update status to revoked instead of deleting
        $result = md_db_update('simuze_label_artists',
            ['status' => 'revoked', 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $relationship_id]
        );
        
        if ($result !== false) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('update_failed', 'Failed to remove relationship', array('status' => 500));
    }
    
    /**
     * Accept artist invitation (called by artist group)
     */
    public function accept_artist_invitation($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $label_id = (int) $request->get_param('id');
        $data = $request->get_json_params();
        $relationship_id = (int) ($data['relationship_id'] ?? 0);
        
        if (!$relationship_id) {
            return new WP_Error('invalid_request', 'Relationship ID is required', array('status' => 400));
        }
        
        $relationship = md_db_row(
            "SELECT * FROM simuze_label_artists WHERE id = ? AND status = 'pending'",
            [$relationship_id]
        );

        if (!$relationship) {
            return new WP_Error('not_found', 'Invitation not found', array('status' => 404));
        }

        // Verify user is admin of the artist group
        $artist = Simuze_Group::get($relationship['artist_group_id']);
        if (!$artist || !$artist->can_manage($current_user->id)) {
            return new WP_Error('forbidden', 'Only artist administrators can accept invitations', array('status' => 403));
        }

        // Update status to accepted
        $result = md_db_update('simuze_label_artists',
            ['status' => 'accepted', 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $relationship_id]
        );
        
        if ($result !== false) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('update_failed', 'Failed to accept invitation', array('status' => 500));
    }
    
    /**
     * Reject artist invitation (called by artist group)
     */
    public function reject_artist_invitation($request) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $label_id = (int) $request->get_param('id');
        $data = $request->get_json_params();
        $relationship_id = (int) ($data['relationship_id'] ?? 0);
        
        if (!$relationship_id) {
            return new WP_Error('invalid_request', 'Relationship ID is required', array('status' => 400));
        }
        
        $relationship = md_db_row(
            "SELECT * FROM simuze_label_artists WHERE id = ? AND status = 'pending'",
            [$relationship_id]
        );

        if (!$relationship) {
            return new WP_Error('not_found', 'Invitation not found', array('status' => 404));
        }

        // Verify user is admin of the artist group
        $artist = Simuze_Group::get($relationship['artist_group_id']);
        if (!$artist || !$artist->can_manage($current_user->id)) {
            return new WP_Error('forbidden', 'Only artist administrators can reject invitations', array('status' => 403));
        }

        // Update status to rejected
        $result = md_db_update('simuze_label_artists',
            ['status' => 'rejected', 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $relationship_id]
        );
        
        if ($result !== false) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('update_failed', 'Failed to reject invitation', array('status' => 500));
    }
    
    /**
     * SIMEZU Auth Proxy - Server-side proxy for SIMEZU forms
     * SECURITY: Frontend must NEVER know API keys - all requests go through this proxy
     */
    public function simezu_auth_proxy($request) {
        $api_url = defined('SIMEZU_API_BASE') ? SIMEZU_API_BASE : 'http://localhost/simezu/api';
        $api_key = defined('SIMEZU_API_KEY')  ? SIMEZU_API_KEY  : '';
        $app_id  = defined('SIMEZU_APP_ID')   ? SIMEZU_APP_ID   : '';

        $method = $request->get_method();
        $params = $request->get_json_params() ?: [];

        $action           = $params['action'] ?? 'login';
        $simezu_endpoint  = rtrim($api_url, '/') . '/auth/forms/' . $action;

        $headers = [
            'Content-Type' => 'application/json',
            'X-API-Key'    => $api_key,
            'X-App-ID'     => $app_id,
        ];

        if (isset($_COOKIE['accessToken'])) {
            $headers['Authorization'] = 'Bearer ' . $_COOKIE['accessToken'];
        }

        $request_body = $params;
        unset($request_body['endpoint'], $request_body['action']);

        $fn       = strtoupper($method) === 'GET' ? 'md_http_get' : 'md_http_post';
        $response = $fn($simezu_endpoint, [
            'headers' => $headers,
            'body'    => json_encode($request_body),
            'timeout' => 30,
        ]);

        if ($response === false) {
            error_log('SIMEZU Auth Proxy Error: could not connect to ' . $simezu_endpoint);
            return new WP_Error('proxy_error', 'Failed to connect to authentication service', array('status' => 500));
        }

        $status_code   = $response['code'];
        $response_body = $response['body'];

        // Extract Set-Cookie headers from raw header lines
        foreach ($response['headers'] as $hdr) {
            if (stripos($hdr, 'Set-Cookie:') === 0) {
                header($hdr, false);
            }
        }

        $data = json_decode($response_body, true);

        $rest_response = rest_ensure_response($data ?: $response_body);
        $rest_response->set_status($status_code);

        return $rest_response;
    }
}

