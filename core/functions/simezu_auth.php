<?php
/**
 * Simezu → Modular Auth Integration
 * Provides auth helpers and populates $GLOBALS on every request.
 *
 * Load order:
 *   1. admin/bootstrap.php includes this file (after auth.php, before modules)
 *   2. _simezu_populate_globals_early() runs here – JWT decoded, $GLOBALS set
 *   3. Modules load, do_action('init') fires, Simuze_Auth::get_instance() runs
 *   4. bootstrap.php calls _simezu_populate_globals_from_auth() after do_action('init')
 *      to refresh $GLOBALS with the local DB user record (Simuze_User object)
 */

// Ensure simuze config constants are available (SIMEZU_JWT_SECRET, SIMEZU_EMBED_URL, etc.)
if (!defined('SIMEZU_JWT_SECRET') && defined('CORE_PATH') && is_file(CORE_PATH . '/config/simuze.php')) {
    require_once CORE_PATH . '/config/simuze.php';
}

/**
 * Attempt to refresh the accessToken using the refreshToken cookie.
 * Uses a raw cURL call — no WP or module dependencies required.
 * Returns decoded JWT payload on success, null on failure.
 */
if (!function_exists('_simezu_try_refresh_token')) {
    function _simezu_try_refresh_token() {
        if (empty($_COOKIE['refreshToken'])) {
            return null;
        }

        $apiBase     = defined('SIMEZU_API_BASE') ? rtrim(SIMEZU_API_BASE, '/') : 'http://localhost/simezu/api';
        $refreshUrl  = $apiBase . '/auth/refresh';
        $refreshTok  = $_COOKIE['refreshToken'];

        $ch = curl_init($refreshUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $refreshTok,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['refreshToken' => $refreshTok]));
        $body     = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$body) {
            return null;
        }

        $resp = json_decode($body, true);
        if (empty($resp['accessToken'])) {
            return null;
        }

        $newToken = $resp['accessToken'];
        $expiry   = time() + 86400 * 30;
        setcookie('accessToken', $newToken, $expiry, '/');
        $_COOKIE['accessToken'] = $newToken;

        if (!empty($resp['refreshToken'])) {
            setcookie('refreshToken', $resp['refreshToken'], time() + 86400 * 90, '/');
            $_COOKIE['refreshToken'] = $resp['refreshToken'];
        }

        $parts = explode('.', $newToken);
        if (count($parts) !== 3) {
            return null;
        }
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        return ($payload && isset($payload['sub'])) ? $payload : null;
    }
}

/**
 * Call Simezu's server-side logout endpoint (best-effort, short timeout).
 * Invalidates the session token on the Simezu auth server.
 */
if (!function_exists('_simezu_server_logout')) {
    function _simezu_server_logout() {
        $token = $_COOKIE['accessToken'] ?? null;
        if (!$token) {
            return;
        }
        $apiBase    = defined('SIMEZU_API_BASE') ? rtrim(SIMEZU_API_BASE, '/') : 'http://localhost/simezu/api';
        $logoutUrl  = $apiBase . '/auth/logout';

        $ch = curl_init($logoutUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
        @curl_exec($ch);
        curl_close($ch);
    }
}

/**
 * Decode JWT from request and return payload array, or null if invalid/absent.
 * Uses SIMEZU_JWT_SECRET constant for local verification – no API call required.
 * Falls back to refreshToken cookie when accessToken is expired.
 */
if (!function_exists('simezu_fetch_current_user')) {
    function simezu_fetch_current_user() {
        $token = null;
        if (!empty($_COOKIE['accessToken'])) {
            $token = $_COOKIE['accessToken'];
        } elseif (!empty($_COOKIE['simuze_jwt_token'])) {
            $token = $_COOKIE['simuze_jwt_token'];
        } elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s+(\S+)/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
                $token = $m[1];
            }
        } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s+(\S+)/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $m)) {
                $token = $m[1];
            }
        }

        if (!$token) {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        list($header, $payload, $signature) = $parts;

        $secret    = defined('SIMEZU_JWT_SECRET') ? SIMEZU_JWT_SECRET : 'simezu-auth-secret-key-change-in-production-2024';
        $valid_sig = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$payload", $secret, true)), '+/', '-_'), '=');

        if (!hash_equals($valid_sig, $signature)) {
            return null;
        }

        $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        if (!$data) {
            return null;
        }

        if (isset($data['exp']) && $data['exp'] < time()) {
            // Token expired — attempt refresh before giving up
            return _simezu_try_refresh_token();
        }

        return $data;
    }
}

/**
 * Redirect to the Modular login page if the current user is not authenticated.
 * Passes the current URL as ?redirect= so login can send the user back.
 */
if (!function_exists('simezu_require_login')) {
    function simezu_require_login($redirect_to = null) {
        if (!empty($GLOBALS['is_logged_in'])) {
            return;
        }

        if (!$redirect_to) {
            $scheme      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host        = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $uri         = $_SERVER['REQUEST_URI'] ?? '/';
            $redirect_to = $scheme . '://' . $host . $uri;
        }

        $login_base = defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/login' : '/login';
        header('Location: ' . $login_base . '?redirect=' . rawurlencode($redirect_to), true, 302);
        exit;
    }
}

/**
 * Clear Simezu auth cookies and optionally redirect to the site home.
 */
if (!function_exists('simezu_logout')) {
    function simezu_logout($redirect = false) {
        // Invalidate token on Simezu server before clearing cookies locally
        _simezu_server_logout();

        $past = time() - 3600;
        foreach (['accessToken', 'refreshToken', 'simuze_jwt_token'] as $name) {
            setcookie($name, '', $past, '/');
            unset($_COOKIE[$name]);
        }
        $GLOBALS['is_logged_in'] = false;
        $GLOBALS['current_user'] = null;
        $GLOBALS['user_role']    = '';

        if ($redirect) {
            $home = defined('SITE_URL') ? SITE_URL : '/';
            header('Location: ' . $home, true, 302);
            exit;
        }
    }
}

/**
 * Require login for a Modular page. Wraps simezu_require_login().
 */
if (!function_exists('modular_require_login')) {
    function modular_require_login($redirect_to = null) {
        if (!empty($GLOBALS['is_logged_in'])) {
            return;
        }
        simezu_require_login($redirect_to);
    }
}

/**
 * Log out from both Modular (session) and Simezu (cookies), then redirect home.
 */
if (!function_exists('modular_logout')) {
    function modular_logout() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        simezu_logout(true);
    }
}

/**
 * Populate $GLOBALS from JWT early – before Simuze_Auth / modules are available.
 * Sets is_logged_in, user_role, current_user (lightweight stub) and syncs $_SESSION.
 * The stub is replaced by the full Simuze_User object in _simezu_populate_globals_from_auth().
 */
if (!function_exists('_simezu_populate_globals_early')) {
    function _simezu_populate_globals_early() {
        $data = simezu_fetch_current_user();

        if ($data && !empty($data['sub'])) {
            $roles = isset($data['roles']) ? (array) $data['roles'] : [];
            $role  = in_array('admin',  $roles, true) ? 'admin'
                   : (in_array('artist', $roles, true) ? 'artist' : 'user');

            $GLOBALS['is_logged_in'] = true;
            $GLOBALS['user_role']    = $role;

            if (!isset($GLOBALS['current_user'])) {
                $GLOBALS['current_user'] = (object) [
                    'id'                => $data['sub'],
                    'simezu_id'         => $data['sub'],
                    'email'             => $data['email'] ?? '',
                    'username'          => $data['username'] ?? ($data['email'] ?? ''),
                    'unique_identifier' => $data['unique_identifier'] ?? ($data['unique_id'] ?? null),
                    'display_name'      => $data['display_name'] ?? ($data['username'] ?? ''),
                    'avatar_url'        => $data['avatar_url'] ?? null,
                    'roles'             => $roles,
                ];
            }

            // Keep Modular session in sync so admin auth checks pass
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION['user_id']    = $data['sub'];
            $_SESSION['user_email'] = $data['email'] ?? '';
            $_SESSION['user_role']  = $role;
        } else {
            $GLOBALS['is_logged_in'] = false;
            $GLOBALS['user_role']    = '';
            if (!array_key_exists('current_user', $GLOBALS)) {
                $GLOBALS['current_user'] = null;
            }
        }
    }
}

/**
 * Refresh $GLOBALS with the full Simuze_User DB record after modules have loaded.
 * Called from admin/bootstrap.php after do_action('init').
 */
if (!function_exists('_simezu_populate_globals_from_auth')) {
    function _simezu_populate_globals_from_auth() {
        if (!class_exists('Simuze_Auth')) {
            return;
        }
        try {
            $auth = Simuze_Auth::get_instance();
            $user = $auth->get_current_user();

            if ($user && is_object($user)) {
                $GLOBALS['current_user'] = $user;
                $GLOBALS['is_logged_in'] = true;

                // Derive role from JWT (most authoritative)
                $role  = 'user';
                $token = $_COOKIE['accessToken'] ?? ($_COOKIE['simuze_jwt_token'] ?? null);
                if ($token) {
                    $parts = explode('.', $token);
                    if (count($parts) === 3) {
                        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                        if ($payload) {
                            $roles = isset($payload['roles']) ? (array) $payload['roles'] : [];
                            $role  = in_array('admin',  $roles, true) ? 'admin'
                                   : (in_array('artist', $roles, true) ? 'artist' : 'user');
                        }
                    }
                }
                $GLOBALS['user_role'] = $role;

                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
                $_SESSION['user_id']    = $user->id ?? ($user->simezu_id ?? '');
                $_SESSION['user_email'] = $user->email ?? '';
                $_SESSION['username']   = $user->username ?? '';
                $_SESSION['user_role']  = $role;
            } else {
                $GLOBALS['is_logged_in'] = false;
                $GLOBALS['current_user'] = null;
                $GLOBALS['user_role']    = '';
            }
        } catch (\Throwable $e) {
            // Simuze_Auth not ready – keep existing $GLOBALS
        }
    }
}

// Run early population immediately on load
_simezu_populate_globals_early();
