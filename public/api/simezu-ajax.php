<?php
/**
 * Simuze Community AJAX endpoint (Modular replacement for admin-ajax.php).
 * Dispatches to wp_ajax_* / wp_ajax_nopriv_* handlers registered by the module.
 */
if (!defined('MODULAR_PATH')) {
    define('MODULAR_PATH', dirname(dirname(__DIR__)));
}
require_once MODULAR_PATH . '/admin/config/config.php';
require_once MODULAR_PATH . '/admin/bootstrap.php';
if (is_file(MODULAR_PATH . '/core/config/simuze.php')) {
    require_once MODULAR_PATH . '/core/config/simuze.php';
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$action = isset($_REQUEST['action']) ? trim((string) $_REQUEST['action']) : '';
if ($action === '') {
    echo json_encode(['success' => false, 'message' => 'Missing action']);
    exit;
}

ob_start();
$logged_in = false;
if (class_exists('Simuze_Auth')) {
    try {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        $logged_in = $user !== null;
    } catch (Throwable $e) {
        $logged_in = false;
    }
}

if (function_exists('do_action')) {
    if ($logged_in) {
        do_action('wp_ajax_' . $action);
    } else {
        do_action('wp_ajax_nopriv_' . $action);
    }
}

$out = ob_get_clean();
if ($out !== '' && $out !== false) {
    echo $out;
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action or no handler']);
}
