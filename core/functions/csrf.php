<?php
/**
 * CSRF protection: token generation, field helper, verification.
 * Use on all state-changing requests (POST/PUT/DELETE).
 */

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /** Output hidden input for forms. */
    function csrf_field(): string {
        $name = '_csrf_token';
        $value = csrf_token();
        return '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '">';
    }
}

if (!function_exists('csrf_verify')) {
    /** Verify token from POST or header. Returns true if valid, false otherwise. */
    function csrf_verify(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION['_csrf_token'] ?? '';
        return $expected !== '' && hash_equals($expected, $token);
    }
}

// ---------------------------------------------------------------------------
// Modular-native CSRF aliases (md_ prefix). Use these in new code instead of
// wp_create_nonce / wp_verify_nonce / wp_nonce_field.
// ---------------------------------------------------------------------------
if (!function_exists('md_csrf_token')) {
    function md_csrf_token(): string {
        return csrf_token();
    }
}

if (!function_exists('md_csrf_field')) {
    /** Render hidden input for use in HTML forms. */
    function md_csrf_field(): string {
        return csrf_field();
    }
}

if (!function_exists('md_verify_csrf')) {
    /** Returns true when the submitted CSRF token is valid. */
    function md_verify_csrf(): bool {
        return csrf_verify();
    }
}
