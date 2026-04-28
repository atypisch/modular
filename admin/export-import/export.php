<?php
/**
 * Export handler — POST: build bundle, stream as download.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/functions/csrf.php';
require_once CORE_PATH . '/classes/Exporter.php';

if (!is_logged_in() || (function_exists('is_admin') && !is_admin())) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/admin') . '/export-import?tab=export');
    exit;
}
if (!md_verify_csrf()) {
    http_response_code(403);
    echo 'Invalid CSRF token';
    exit;
}

$includeMedia = !empty($_POST['include_media']);

try {
    @set_time_limit(0);
    $exporter = new \Modular\Exporter($includeMedia);
    $exporter->streamDownload();
} catch (\Throwable $e) {
    error_log('Export failed: ' . $e->getMessage());
    $_SESSION['_export_import_flash'] = [
        'type'    => 'error',
        'message' => 'Export failed: ' . htmlspecialchars($e->getMessage()),
    ];
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/admin') . '/export-import?tab=export');
    exit;
}
