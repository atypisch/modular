<?php
/**
 * Modular admin entry point.
 *
 * The admin is the Atelier Vue SPA only — the legacy server-rendered admin has
 * been removed. This file just requires auth, then serves the SPA. The first-run
 * installer (admin/setup/) and the auth pages (admin/auth/) are the exceptions.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/data/init.php';

// Require authentication.
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . (function_exists('admin_url') ? admin_url('auth/login.php') : (defined('ADMIN_URL') ? ADMIN_URL . '/auth/login.php' : '/modular/admin/auth/login.php')));
    exit;
}

// First-run installer is served by its own files; everything else is the SPA.
$isSetup = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/setup') !== false;
if (!$isSetup) {
    require __DIR__ . '/spa.php';
    exit;
}
if (is_file(__DIR__ . '/setup/index.php')) {
    require __DIR__ . '/setup/index.php';
    exit;
}
http_response_code(404);
echo 'Setup is not available.';
