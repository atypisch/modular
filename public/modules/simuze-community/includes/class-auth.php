<?php
/**
 * External Authentication System
 * Handles JWT authentication via Simezu API
 * 
 * Simezu is the central auth server running on MAMP (localhost/simezu)
 * Users are stored in Simezu, we only keep a local link table
 */

if (!defined('ABSPATH') && !defined('SIMUZE_MODULAR_RUN')) {
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
        // Configure API base and JWT secret differently for Modular vs WordPress runtimes
        if (defined('SIMUZE_MODULAR_RUN')) {
            // Modular app: always use core/config/simuze.php constants
            $this->api_url   = defined('SIMEZU_API_BASE') ? SIMEZU_API_BASE : 'http://localhost/simezu/api';
            $this->jwt_secret = defined('SIMEZU_JWT_SECRET') ? SIMEZU_JWT_SECRET : 'simezu-auth-secret-key-change-in-production-2024';

            // In Modular there is only one early init call from admin/bootstrap.php.
            // When this class is first instantiated during that init, we must run
            // authentication immediately rather than waiting for another init hook.
            $this->authenticate_user();
            $this->handle_auth_actions();
        } else {
            // WordPress plugin: fall back to options API with sensible defaults
            $this->api_url = defined('SIMEZU_API_BASE')
                ? SIMEZU_API_BASE
                : get_option('simuze_auth_api_url', 'http://localhost/simezu/api');
            $this->jwt_secret = defined('SIMEZU_JWT_SECRET')
                ? SIMEZU_JWT_SECRET
                : get_option('simuze_jwt_secret', 'simezu-auth-secret-key-change-in-production-2024');

            // WordPress behaviour: hook into init / template_redirect / wp_footer
            add_action('init', array($this, 'authenticate_user'), 5);
            add_action('init', array($this, 'handle_auth_actions'), 6);
            add_action('template_redirect', array($this, 'maybe_redirect_after_login'));
            add_action('wp_footer', array($this, 'output_auth_scripts'));
        }
    }
    
    /**
     * Handle auth actions (login redirect, logout)
     *
     * Follows Simezu SSO rules: we never require the access token
     * to be present in the URL for normal operation. Authentication
     * is based on the shared cookies/headers managed by Simezu.
     */
    public function handle_auth_actions() {
        // Modular runtime: use Modular helpers / redirects instead of WP functions
        if (defined('SIMUZE_MODULAR_RUN')) {
            if (isset($_GET['simuze_logout']) && $_GET['simuze_logout'] === '1') {
                $this->logout();
                $target = defined('SITE_URL') ? SITE_URL : '/';
                header('Location: ' . $target, true, 302);
                exit;
            }

            // Legacy login callback is not used in Modular; rely on shared cookies/JWT instead.
            return;
        }

        // Handle logout
        if (isset($_GET['simuze_logout']) && $_GET['simuze_logout'] === '1') {
            $this->logout();
            wp_safe_redirect(home_url('/'));
            exit;
        }
        
        // Handle login callback from Simezu (legacy flow that may still be used)
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
        if (defined('SIMUZE_MODULAR_RUN')) {
            return;
        }
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
        if (defined('SIMUZE_MODULAR_RUN')) {
            return;
        }
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
            // No token available – treat as not logged in.
            // Leave $this->current_user as null so get_current_user()
            // can still fall back to the JWT stub in $GLOBALS['current_user'].
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
        
        error_log('[Simuze Auth] No token found in any source');
        return null;
    }
    
    /**
     * Verify JWT token.
     *
     * Modular note:
     * - In the Modular runtime we rely solely on local JWT verification.
     * - We deliberately skip remote /me verification to avoid slow/blocking
     *   HTTP calls when Simezu is unreachable or misconfigured.
     */
    private function verify_token($token) {
        // First try local verification
        $payload = $this->verify_jwt_locally($token);
        
        if ($payload) {
            return $payload;
        }
        
        // In Modular, treat invalid/expired tokens as unauthenticated
        if (defined('SIMUZE_MODULAR_RUN') && SIMUZE_MODULAR_RUN) {
            return false;
        }
        
        // WordPress plugin: fall back to API verification if local fails
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
            // In Modular we skip remote refresh calls to avoid long-running HTTP.
            if (defined('SIMUZE_MODULAR_RUN') && SIMUZE_MODULAR_RUN) {
                return false;
            }
            // Try to refresh token via API if refreshToken is available (WP plugin context)
            if (isset($_COOKIE[self::REFRESH_TOKEN_COOKIE])) {
                error_log('[Simuze Auth] verify_jwt_locally - Attempting token refresh via API');
                return $this->refresh_token_via_api();
            }
            return false;
        }
        
        error_log('[Simuze Auth] verify_jwt_locally - Token verified successfully, user ID: ' . ($data['sub'] ?? 'null'));
        
        // Map Simezu payload to our format.
        // Prefer explicit username from JWT when available; otherwise fall back to email.
        return array(
            'id' => $data['sub'] ?? null,
            'email' => $data['email'] ?? null,
            'username' => $data['username'] ?? ($data['email'] ?? null),
            'display_name' => $data['display_name'] ?? $data['username'] ?? null,
            'roles' => $data['roles'] ?? array(),
            'avatar_url' => $data['avatar_url'] ?? null,
        );
    }
    
    /**
     * Verify token via Simezu API (fallback)
     */
    private function verify_token_via_api($token) {
        // In the Modular app we never want to block on remote Simezu /me calls.
        // Auth is derived purely from the locally-verifiable JWT (see verify_jwt_locally()).
        if (defined('SIMUZE_MODULAR_RUN') && SIMUZE_MODULAR_RUN) {
            error_log('[Simuze Auth] verify_token_via_api skipped in Modular context');
            return false;
        }
        // In Modular we use a proxy endpoint (e.g. http://localhost/simuze-mdlr/api/auth)
        // or a direct Simezu API base (http://localhost/simezu/api). Both already point
        // at the correct /auth stack; we only need to append /me.
        $api_url = rtrim($this->api_url, '/');
        $full_url = $api_url . '/me';
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
        // In Modular we avoid background refresh calls to prevent long-running HTTP requests.
        if (defined('SIMUZE_MODULAR_RUN') && SIMUZE_MODULAR_RUN) {
            error_log('[Simuze Auth] refresh_token_via_api skipped in Modular context');
            return false;
        }
        if (!isset($_COOKIE[self::REFRESH_TOKEN_COOKIE])) {
            return false;
        }
        
        $refresh_token = $_COOKIE[self::REFRESH_TOKEN_COOKIE];
        $api_url = rtrim($this->api_url, '/');
        $full_url = $api_url . '/refresh';
        
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
            // Primary path: use Simezu token verification + local Simuze_User mapping
            $user = $this->authenticate_user();
            if ($user && is_object($user)) {
                $this->current_user = $user;
            }
        }

        // Fallback path in Modular: if authenticate_user() did not yield a local
        // Simuze_User, but the Simezu SSO layer already decoded a valid JWT into
        // $GLOBALS['current_user'], map that stub to a Simuze_User by simezu_id/email.
        if ($this->current_user === null && isset($GLOBALS['current_user']) && is_object($GLOBALS['current_user'])) {
            $jwt_user    = $GLOBALS['current_user'];
            $jwt_simezu  = $jwt_user->simezu_id ?? ($jwt_user->id ?? null);
            $jwt_email   = $jwt_user->email ?? null;
            $local_user  = null;

            if (!empty($jwt_simezu) && class_exists('Simuze_User')) {
                $local_user = Simuze_User::get_by_simezu_id($jwt_simezu);
            }
            if (!$local_user && !empty($jwt_email) && class_exists('Simuze_User')) {
                $local_user = Simuze_User::get_by_email($jwt_email);
            }

            if ($local_user && is_object($local_user)) {
                $this->current_user = $local_user;
            }
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
        if (defined('SIMUZE_MODULAR_RUN')) {
            $login_base = defined('SIMEZU_LOGIN_URL') ? SIMEZU_LOGIN_URL : 'http://localhost/simezu/login';
            if (!$redirect) {
                $redirect = defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/profile/?logged_in=1' : '/profile/?logged_in=1';
            }
            return rtrim($login_base, '?') . (strpos($login_base, '?') === false ? '?' : '&') . 'redirect=' . urlencode($redirect);
        }

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
        if (defined('SIMUZE_MODULAR_RUN')) {
            $register_base = defined('SIMEZU_ACCOUNT_URL') ? SIMEZU_ACCOUNT_URL : 'http://localhost/simezu/register';
            if (!$redirect) {
                $redirect = defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/profile/?logged_in=1' : '/profile/?logged_in=1';
            }
            return rtrim($register_base, '?') . (strpos($register_base, '?') === false ? '?' : '&') . 'redirect=' . urlencode($redirect);
        }

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
        if (defined('SIMUZE_MODULAR_RUN')) {
            // In Modular we derive roles purely from JWT / Simuze_User flags
            if ($this->has_role('admin') || $this->has_role('superadmin') || $this->has_role('Super Admin')) {
                return true;
            }

            $user = $this->get_current_user();
            if ($user && isset($user->is_admin) && $user->is_admin) {
                return true;
            }

            if ($user && isset($user->email)) {
                $super_admin_emails = [
                    'marten@atypisch.nl',
                    'admin@simuze.com',
                ];
                if (in_array(strtolower($user->email), array_map('strtolower', $super_admin_emails), true)) {
                    return true;
                }
            }

            return false;
        }

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

