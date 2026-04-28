<?php
/**
 * External Authentication System
 * Handles JWT authentication via Simezu API
 * 
 * Simezu is the central auth server running on MAMP (localhost/simezu)
 * Users are stored in Simezu, we only keep a local link table
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Auth {
    
    private static $instance = null;
    private $api_url;
    private $current_user = null;
    private $jwt_secret;
    
    // Cookie names - shared with Simezu
    const ACCESS_TOKEN_COOKIE = 'accessToken';
    const REFRESH_TOKEN_COOKIE = 'refreshToken';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Default to localhost Simezu API
        // Use /api endpoint (not /api.php/auth) for consistency
        $this->api_url = get_option('simuze_auth_api_url', 'http://localhost/simezu/api');
        $this->jwt_secret = get_option('simuze_jwt_secret', 'simezu-auth-secret-key-change-in-production-2024');
        
        // Check for JWT token in cookies or headers
        add_action('init', array($this, 'authenticate_user'), 5);
        
        // Add login/logout handlers
        add_action('init', array($this, 'handle_auth_actions'), 6);
        
        // Redirect to profile after login
        add_action('template_redirect', array($this, 'maybe_redirect_after_login'));
        
        // Add auth scripts to footer
        add_action('wp_footer', array($this, 'output_auth_scripts'));
    }
    
    /**
     * Handle auth actions (login redirect, logout)
     */
    public function handle_auth_actions() {
        // Handle logout
        if (isset($_GET['simuze_logout']) && $_GET['simuze_logout'] === '1') {
            $this->logout();
            wp_safe_redirect(home_url('/'));
            exit;
        }
        
        // Handle login callback from Simezu
        if (isset($_GET['simuze_auth_callback'])) {
            $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : null;
            if ($token) {
                $this->login($token);
                $redirect = isset($_GET['redirect']) ? esc_url_raw($_GET['redirect']) : home_url('/profile/');
                wp_safe_redirect($redirect);
                exit;
            }
        }
    }
    
    /**
     * Redirect to profile after successful login
     */
    public function maybe_redirect_after_login() {
        // If user just logged in (has token but no redirect happened yet)
        if (isset($_GET['logged_in']) && $_GET['logged_in'] === '1') {
            $user = $this->get_current_user();
            if ($user) {
                wp_safe_redirect(home_url('/profile/'));
                exit;
            }
        }
    }
    
    /**
     * Output auth scripts in footer
     */
    public function output_auth_scripts() {
        $simezu_login_url = get_option('simuze_login_url', 'http://localhost/simezu/login.php');
        $current_user = $this->get_current_user();
        ?>
        <script>
        window.SimuzeAuth = {
            isLoggedIn: <?php echo $current_user ? 'true' : 'false'; ?>,
            currentUser: <?php echo $current_user ? wp_json_encode($current_user->to_array()) : 'null'; ?>,
            loginUrl: <?php echo wp_json_encode($simezu_login_url . '?redirect=' . urlencode(home_url('/profile/?logged_in=1'))); ?>,
            logoutUrl: <?php echo wp_json_encode(home_url('/?simuze_logout=1')); ?>,
            profileUrl: <?php echo wp_json_encode(home_url('/profile/')); ?>,
            
            login: function() {
                window.location.href = this.loginUrl;
            },
            
            logout: function() {
                window.location.href = this.logoutUrl;
            },
            
            goToProfile: function() {
                window.location.href = this.profileUrl;
            }
        };
        </script>
        <?php
    }
    
    /**
     * Authenticate user from JWT token
     */
    public function authenticate_user() {
        $token = $this->get_token();
        
        if (!$token) {
            return false;
        }
        
        // Verify token with external API
        $user_data = $this->verify_token($token);
        
        if ($user_data && !is_wp_error($user_data) && !empty($user_data['id'])) {
            // Generate username from email if not provided
            $username = $user_data['username'] ?? null;
            if (empty($username) && !empty($user_data['email'])) {
                $username = $this->generate_username_from_email($user_data['email']);
            }
            
            // Generate display name
            $display_name = $user_data['display_name'] ?? null;
            if (empty($display_name)) {
                if (!empty($user_data['first_name']) || !empty($user_data['last_name'])) {
                    $display_name = trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''));
                } else {
                    $display_name = $username;
                }
            }
            
            // Get or create user in our database
            $user = Simuze_User::get_by_simezu_id($user_data['id']);
            
            // Fallback: if not found by simezu_id, try by email (user might have been created with different simezu_id)
            if (!$user && !empty($user_data['email'])) {
                $user = Simuze_User::get_by_email($user_data['email']);
                if ($user) {
                    // Update simezu_id if it's different
                    if ($user->simezu_id !== $user_data['id']) {
                        error_log('[Simuze Auth] authenticate_user - Updating simezu_id for existing user: ' . $user->id . ' (old: ' . $user->simezu_id . ', new: ' . $user_data['id'] . ')');
                        $user->update(array('simezu_id' => $user_data['id']));
                    }
                }
            }
            
            if (!$user) {
                // Create new user
                $user = Simuze_User::create(array(
                    'simezu_id' => $user_data['id'],
                    'username' => $username,
                    'email' => $user_data['email'],
                    'display_name' => $display_name,
                    'avatar_url' => $user_data['avatar_url'] ?? null,
                ));
                
                // If creation failed (duplicate email), try to find existing user
                if (!$user && !empty($user_data['email'])) {
                    error_log('[Simuze Auth] authenticate_user - User creation failed, trying to find by email: ' . $user_data['email']);
                    $user = Simuze_User::get_by_email($user_data['email']);
                    if ($user) {
                        // Update simezu_id and other fields
                        $update_data = array(
                            'simezu_id' => $user_data['id'],
                            'display_name' => $display_name,
                        );
                        $user->update($update_data);
                        error_log('[Simuze Auth] authenticate_user - Found existing user by email and updated: ' . $user->id);
                    }
                }
            } else {
                // Update user data (sync from Simezu)
                $update_data = array(
                    'email' => $user_data['email'],
                    'display_name' => $display_name,
                );
                
                // Don't update avatar_url from Simezu - we always get it from Simezu API
                // The avatar_url field is kept for backwards compatibility but not used for display
                
                $user->update($update_data);
            }
            
            // Only set current_user if we successfully got a user
            if ($user) {
                $this->current_user = $user;
            } else {
                error_log('[Simuze Auth] authenticate_user - Failed to get or create user for simezu_id: ' . $user_data['id']);
                return false;
            }
            
            // IMPORTANT: Preserve JWT tokens in cookies for other applications
            // Don't modify or clear tokens - they're shared across applications
            if ($token && !isset($_COOKIE[self::ACCESS_TOKEN_COOKIE])) {
                // Only set cookie if not already present (don't overwrite existing tokens)
                $expiry = time() + (86400 * 30); // 30 days
                $secure = is_ssl();
                $httponly = true;
                $path = '/';
                setcookie(self::ACCESS_TOKEN_COOKIE, $token, $expiry, $path, '', $secure, $httponly);
                $_COOKIE[self::ACCESS_TOKEN_COOKIE] = $token;
            }
            
            error_log('[Simuze Auth] authenticate_user - User authenticated successfully: ' . $user->id);
            return $user;
        }
        
        error_log('[Simuze Auth] authenticate_user - Token verification failed or no user ID');
        return false;
    }
    
    /**
     * Generate username from email
     * Creates a clean, URL-safe username (no email addresses)
     */
    private function generate_username_from_email($email) {
        // Get the part before @
        $username = strtolower(explode('@', $email)[0]);
        
        // Remove special characters, only allow lowercase letters, numbers, and hyphens (NO underscores, NO special chars)
        $username = preg_replace('/[^a-z0-9-]/', '', $username); // Only allow lowercase, numbers, and hyphens
        
        // Ensure it's not empty
        if (empty($username)) {
            $username = 'user' . time();
        }
        
        // Ensure minimum length of 3 characters
        if (strlen($username) < 3) {
            $username = $username . '123';
        }
        
        // Check if username exists, append number if needed
        $base_username = $username;
        $counter = 1;
        
        while (Simuze_User::get_by_username($username)) {
            $username = $base_username . '-' . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Get JWT token from request
     */
    private function get_token() {
        // Check Simezu access token cookie first (shared with Simezu)
        if (isset($_COOKIE[self::ACCESS_TOKEN_COOKIE])) {
            error_log('[Simuze Auth] Token found in cookie: accessToken');
            return $_COOKIE[self::ACCESS_TOKEN_COOKIE];
        }
        
        // Fallback to old cookie name
        if (isset($_COOKIE['simuze_jwt_token'])) {
            error_log('[Simuze Auth] Token found in cookie: simuze_jwt_token');
            return $_COOKIE['simuze_jwt_token'];
        }
        
        // Check Authorization header (multiple ways, as some servers handle this differently)
        $auth_header = null;
        
        // Standard way
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
            error_log('[Simuze Auth] Found HTTP_AUTHORIZATION header');
        }
        // Apache-specific (some servers use this)
        elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            error_log('[Simuze Auth] Found REDIRECT_HTTP_AUTHORIZATION header');
        }
        // Some servers pass it via getallheaders() function
        elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $auth_header = $headers['Authorization'];
                error_log('[Simuze Auth] Found Authorization header via getallheaders()');
            } elseif (isset($headers['authorization'])) {
                $auth_header = $headers['authorization'];
                error_log('[Simuze Auth] Found authorization header via getallheaders() (lowercase)');
            }
        }
        
        if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            error_log('[Simuze Auth] Token extracted from Authorization header');
            return $matches[1];
        }
        
        // Check query parameter (for testing)
        if (isset($_GET['token'])) {
            error_log('[Simuze Auth] Token found in query parameter');
            return sanitize_text_field($_GET['token']);
        }
        
        error_log('[Simuze Auth] No token found in any source');
        return null;
    }
    
    /**
     * Verify JWT token locally (same algorithm as Simezu)
     * This avoids API calls for every request
     */
    private function verify_token($token) {
        // First try local verification
        $payload = $this->verify_jwt_locally($token);
        
        if ($payload) {
            return $payload;
        }
        
        // Fallback to API verification if local fails
        return $this->verify_token_via_api($token);
    }
    
    /**
     * Verify JWT locally using the shared secret
     */
    private function verify_jwt_locally($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            error_log('[Simuze Auth] verify_jwt_locally - Invalid token format (not 3 parts)');
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        // Verify signature
        $valid_signature = hash_hmac('sha256', "$header.$payload", $this->jwt_secret, true);
        $valid_signature = $this->base64_url_encode($valid_signature);
        
        if (!hash_equals($valid_signature, $signature)) {
            error_log('[Simuze Auth] verify_jwt_locally - Signature verification failed');
            error_log('[Simuze Auth] verify_jwt_locally - Expected: ' . substr($valid_signature, 0, 20) . '..., Got: ' . substr($signature, 0, 20) . '...');
            return false;
        }
        
        // Decode payload
        $data = json_decode($this->base64_url_decode($payload), true);
        
        if (!$data) {
            error_log('[Simuze Auth] verify_jwt_locally - Failed to decode payload');
            return false;
        }
        
        // Check expiration
        if (isset($data['exp']) && $data['exp'] < time()) {
            $expired_seconds = time() - $data['exp'];
            error_log('[Simuze Auth] verify_jwt_locally - Token expired (exp: ' . $data['exp'] . ', now: ' . time() . ', expired ' . $expired_seconds . ' seconds ago)');
            // NOTE: Don't clear expired tokens from cookies - other applications may handle refresh
            // Only clear from $_COOKIE superglobal to prevent local use, but keep actual cookies intact
            if (isset($_COOKIE[self::ACCESS_TOKEN_COOKIE])) {
                unset($_COOKIE[self::ACCESS_TOKEN_COOKIE]);
            }
            if (isset($_COOKIE['simuze_jwt_token'])) {
                unset($_COOKIE['simuze_jwt_token']);
            }
            // Try to refresh token via API if refreshToken is available
            if (isset($_COOKIE[self::REFRESH_TOKEN_COOKIE])) {
                error_log('[Simuze Auth] verify_jwt_locally - Attempting token refresh via API');
                return $this->refresh_token_via_api();
            }
            return false;
        }
        
        error_log('[Simuze Auth] verify_jwt_locally - Token verified successfully, user ID: ' . ($data['sub'] ?? 'null'));
        
        // Map Simezu payload to our format
        return array(
            'id' => $data['sub'] ?? null,
            'email' => $data['email'] ?? null,
            'username' => $data['email'] ?? null, // Use email as username if no username
            'display_name' => $data['display_name'] ?? $data['email'] ?? null,
            'roles' => $data['roles'] ?? array(),
            'avatar_url' => $data['avatar_url'] ?? null,
        );
    }
    
    /**
     * Verify token via Simezu API (fallback)
     */
    private function verify_token_via_api($token) {
        // Build correct API URL - ensure we're using localhost, not api.simezu.com
        $api_url = $this->api_url;
        
        // If api_url contains api.simezu.com, replace with localhost
        if (strpos($api_url, 'api.simezu.com') !== false) {
            $api_url = str_replace('api.simezu.com', 'localhost/simezu', $api_url);
            error_log('[Simuze Auth] Fixed API URL from api.simezu.com to localhost/simezu');
        }
        
        // Ensure we have the correct endpoint
        $endpoint = '/me';
        if (strpos($api_url, '/api') === false && strpos($api_url, '/api.php') === false) {
            // If URL doesn't contain /api, add it
            $api_url = rtrim($api_url, '/') . '/api';
        }
        
        $full_url = rtrim($api_url, '/') . $endpoint;
        error_log('[Simuze Auth] verify_token_via_api - Using URL: ' . $full_url);
        
        $response = wp_remote_get($full_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 10,
            'sslverify' => false, // For localhost
        ));
        
        if (is_wp_error($response)) {
            error_log('Simuze Auth API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200 && $data) {
            // Build display name from available fields
            $display_name = null;
            if (!empty($data['firstName']) || !empty($data['lastName'])) {
                $display_name = trim(($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? ''));
            } elseif (!empty($data['first_name']) || !empty($data['last_name'])) {
                $display_name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
            }
            
            // Map API response to our format
            return array(
                'id' => $data['id'] ?? $data['sub'] ?? null,
                'email' => $data['email'] ?? null,
                'username' => $data['username'] ?? null,
                'first_name' => $data['firstName'] ?? $data['first_name'] ?? null,
                'last_name' => $data['lastName'] ?? $data['last_name'] ?? null,
                'display_name' => $display_name,
                'roles' => $data['roles'] ?? array(),
                'avatar_url' => $data['avatarUrl'] ?? $data['avatar_url'] ?? null,
            );
        }
        
        // Log failed auth attempts for debugging
        if ($status_code !== 200) {
            error_log('Simuze Auth API returned status ' . $status_code . ': ' . $body);
        }
        
        return false;
    }
    
    /**
     * Refresh JWT token using refresh token
     * Preserves tokens for other applications
     */
    private function refresh_token_via_api() {
        if (!isset($_COOKIE[self::REFRESH_TOKEN_COOKIE])) {
            return false;
        }
        
        $refresh_token = $_COOKIE[self::REFRESH_TOKEN_COOKIE];
        $api_url = $this->api_url;
        
        // Ensure we have the correct endpoint
        if (strpos($api_url, '/api') === false && strpos($api_url, '/api.php') === false) {
            $api_url = rtrim($api_url, '/') . '/api';
        }
        
        $endpoint = '/auth/refresh';
        $full_url = rtrim($api_url, '/') . $endpoint;
        
        error_log('[Simuze Auth] refresh_token_via_api - Attempting refresh at: ' . $full_url);
        
        $response = wp_remote_post($full_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $refresh_token,
            ),
            'body' => json_encode(array('refreshToken' => $refresh_token)),
            'timeout' => 10,
            'sslverify' => false,
        ));
        
        if (is_wp_error($response)) {
            error_log('[Simuze Auth] refresh_token_via_api - Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log('[Simuze Auth] refresh_token_via_api - Status: ' . $status_code);
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || empty($data['accessToken'])) {
            error_log('[Simuze Auth] refresh_token_via_api - No accessToken in response');
            return false;
        }
        
        // Update access token cookie (preserve for other applications)
        $new_token = $data['accessToken'];
        $expiry = time() + (86400 * 30); // 30 days
        $secure = is_ssl();
        $httponly = true;
        $path = '/';
        
        setcookie(self::ACCESS_TOKEN_COOKIE, $new_token, $expiry, $path, '', $secure, $httponly);
        $_COOKIE[self::ACCESS_TOKEN_COOKIE] = $new_token;
        
        // Update refresh token if provided
        if (!empty($data['refreshToken'])) {
            setcookie(self::REFRESH_TOKEN_COOKIE, $data['refreshToken'], time() + (86400 * 90), $path, '', $secure, $httponly);
            $_COOKIE[self::REFRESH_TOKEN_COOKIE] = $data['refreshToken'];
        }
        
        error_log('[Simuze Auth] refresh_token_via_api - Token refreshed successfully');
        
        // Retry authentication with new token
        return $this->verify_jwt_locally($new_token);
    }
    
    /**
     * Base64 URL encode
     */
    private function base64_url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private function base64_url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Get current authenticated user
     */
    public function get_current_user() {
        if ($this->current_user === null) {
            $this->authenticate_user();
        }
        return $this->current_user;
    }
    
    /**
     * Check if user is logged in
     */
    public function is_logged_in() {
        $user = $this->get_current_user();
        // Make sure we actually have a user object, not just a non-false value
        return $user !== false && $user !== null && is_object($user);
    }
    
    /**
     * Login user (sets cookie)
     */
    public function login($token) {
        // Set both cookie names for compatibility
        $expiry = time() + (86400 * 30); // 30 days
        $secure = is_ssl();
        $httponly = true;
        $path = '/';
        
        setcookie(self::ACCESS_TOKEN_COOKIE, $token, $expiry, $path, '', $secure, $httponly);
        setcookie('simuze_jwt_token', $token, $expiry, $path, '', $secure, $httponly);
        
        $_COOKIE[self::ACCESS_TOKEN_COOKIE] = $token;
        $_COOKIE['simuze_jwt_token'] = $token;
        
        $this->authenticate_user();
    }
    
    /**
     * Logout user
     */
    public function logout() {
        $past = time() - 3600;
        $path = '/';
        
        // Clear all auth cookies
        setcookie(self::ACCESS_TOKEN_COOKIE, '', $past, $path);
        setcookie(self::REFRESH_TOKEN_COOKIE, '', $past, $path);
        setcookie('simuze_jwt_token', '', $past, $path);
        
        unset($_COOKIE[self::ACCESS_TOKEN_COOKIE]);
        unset($_COOKIE[self::REFRESH_TOKEN_COOKIE]);
        unset($_COOKIE['simuze_jwt_token']);
        
        $this->current_user = null;
    }
    
    /**
     * Get Simezu login URL
     */
    public function get_login_url($redirect = null) {
        $login_url = get_option('simuze_login_url', 'http://localhost/simezu/login.php');
        
        if (!$redirect) {
            $redirect = home_url('/profile/?logged_in=1');
        }
        
        return $login_url . '?redirect=' . urlencode($redirect);
    }
    
    /**
     * Get Simezu register URL
     */
    public function get_register_url($redirect = null) {
        $register_url = get_option('simuze_register_url', 'http://localhost/simezu/register.php');
        
        if (!$redirect) {
            $redirect = home_url('/profile/?logged_in=1');
        }
        
        return $register_url . '?redirect=' . urlencode($redirect);
    }
    
    /**
     * Check if user has specific role
     */
    public function has_role($role) {
        $user = $this->get_current_user();
        if (!$user) {
            return false;
        }
        
        // Get roles from user data
        $token = $this->get_token();
        if ($token) {
            $payload = $this->verify_jwt_locally($token);
            if ($payload && isset($payload['roles'])) {
                return in_array($role, $payload['roles']);
            }
        }
        
        return false;
    }
    
    /**
     * Check if user is admin
     */
    public function is_admin() {
        // Check JWT roles first
        if ($this->has_role('admin') || $this->has_role('superadmin') || $this->has_role('Super Admin')) {
            return true;
        }
        
        // Also check WordPress admin capability as fallback
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Check if user has is_admin flag in their Simuze_User record
        $user = $this->get_current_user();
        if ($user && isset($user->is_admin) && $user->is_admin) {
            return true;
        }
        
        // Check if user's email is in the super admin list
        if ($user && isset($user->email)) {
            $super_admin_emails = apply_filters('simuze_super_admin_emails', array(
                'marten@atypisch.nl',
                'admin@simuze.com',
            ));
            if (in_array(strtolower($user->email), array_map('strtolower', $super_admin_emails))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if current user is a super admin (alias for is_admin)
     */
    public function is_super_admin() {
        return $this->is_admin();
    }
}

