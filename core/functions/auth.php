<?php
/**
 * Authentication helper functions
 */

if (!function_exists('start_session_safe')) {
    /**
     * Start session safely (only if not already started)
     */
    function start_session_safe() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('require_auth')) {
    /**
     * Check if user is logged in and is admin
     * Redirect to login page if not authenticated
     */
    function require_auth() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: /modular/admin/auth/login.php');
            exit;
        }
        
        // For admin-only pages
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = 'You do not have permission to access this page.';
            header('Location: /modular/admin/index.php');
            exit;
        }
    }
}

if (!function_exists('is_logged_in')) {
    /**
     * Check if user is logged in
     */
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('is_admin')) {
    /**
     * Check if logged in user is an admin
     */
    function is_admin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

if (!function_exists('get_current_user')) {
    /**
     * Get current user data
     */
    function get_current_user() {
        if (!is_logged_in()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'] ?? '',
            'name' => $_SESSION['user_name'] ?? '',
            'role' => $_SESSION['user_role'] ?? ''
        ];
    }
}
