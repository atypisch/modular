<?php
// Backwards-compatible redirect: Module Management now lives at /admin/modules.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$target = (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/modules';
header('Location: ' . $target, true, 301);
exit;
