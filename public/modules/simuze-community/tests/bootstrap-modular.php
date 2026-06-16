<?php
/**
 * Bootstrap for Simuze Community module tests (Modular context).
 * Run from project root: php public/modules/simuze-community/tests/bootstrap-modular.php
 * Or from browser: /modules/simuze-community/tests/bootstrap-modular.php
 */
$projectRoot = dirname(dirname(dirname(dirname(__DIR__))));
if (!defined('MODULAR_PATH')) {
    define('MODULAR_PATH', $projectRoot);
}
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
require_once $projectRoot . '/admin/config/config.php';
require_once $projectRoot . '/admin/bootstrap.php';
if (is_file($projectRoot . '/core/config/simuze.php')) {
    require_once $projectRoot . '/core/config/simuze.php';
}
define('SIMUZE_MODULAR_RUN', true);
define('SIMUZE_COMMUNITY_VERSION', '1.0.0');
define('SIMUZE_COMMUNITY_PLUGIN_DIR', dirname(__DIR__) . '/');
define('SIMUZE_DB_PREFIX', 'simuze_');
define('ABSPATH', $projectRoot . '/');
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/modular-runtime.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-database.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-auth.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-user.php';
require_once SIMUZE_COMMUNITY_PLUGIN_DIR . 'includes/class-group.php';

$errors = [];
if (!isset($GLOBALS['db']) || !$GLOBALS['db'] instanceof PDO) {
    $errors[] = 'Database not available';
}
if (!class_exists('Simuze_Database')) {
    $errors[] = 'Simuze_Database not loaded';
} else {
    $db = Simuze_Database::get_instance();
    if (!$db) {
        $errors[] = 'Simuze_Database::get_instance() failed';
    }
}
if (!class_exists('Simuze_Auth')) {
    $errors[] = 'Simuze_Auth not loaded';
}
if (!class_exists('Simuze_User')) {
    $errors[] = 'Simuze_User not loaded';
}
if (!class_exists('Simuze_Group')) {
    $errors[] = 'Simuze_Group not loaded';
}
if (empty($errors) && isset($GLOBALS['db'])) {
    try {
        $stmt = $GLOBALS['db']->query("SELECT COUNT(*) FROM simuze_users WHERE deleted_at IS NULL");
        $count = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($count === 0) {
            $errors[] = 'No simuze_users rows (seed demo data manually)';
        }
    } catch (Throwable $e) {
        $errors[] = 'simuze_users table check: ' . $e->getMessage();
    }
}
if (empty($errors) && class_exists('Simuze_Group')) {
    $group = Simuze_Group::get_by_unique_handle('demo-artist');
    if (!$group && isset($GLOBALS['db'])) {
        $stmt = $GLOBALS['db']->query("SELECT COUNT(*) FROM simuze_groups WHERE deleted_at IS NULL");
        if ($stmt && (int) $stmt->fetchColumn() > 0) {
            $errors[] = 'Simuze_Group::get_by_unique_handle returned null (check group has unique_handle)';
        }
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => empty($errors),
    'errors' => $errors,
    'checks' => [
        'database' => isset($GLOBALS['db']),
        'Simuze_Database' => class_exists('Simuze_Database'),
        'Simuze_Auth' => class_exists('Simuze_Auth'),
        'Simuze_User' => class_exists('Simuze_User'),
        'Simuze_Group' => class_exists('Simuze_Group'),
    ],
], JSON_PRETTY_PRINT);
