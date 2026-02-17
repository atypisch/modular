<?php
/**
 * Save post type field schema and mode (JSON).
 * Ensure no PHP output before JSON (disable display_errors for this endpoint).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
ini_set('display_errors', '0');
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/functions/subscription.php';

if (!is_logged_in()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}
if (!function_exists('csrf_verify') || !csrf_verify()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request. Please try again.']);
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid post type id']);
    exit;
}

$schemaJson = $_POST['schema'] ?? '';
$mode = isset($_POST['mode']) ? trim($_POST['mode']) : 'fixed';
if (!in_array($mode, ['fixed', 'modular'], true)) {
    $mode = 'fixed';
}
// Enforce feature gating: if modular_post_types not available, force fixed
if ($mode === 'modular' && !subscription_has_feature('modular_post_types')) {
    $mode = 'fixed';
}

$schema = null;
if ($schemaJson !== '') {
    $schema = json_decode($schemaJson, true);
    if ($schema === null && json_last_error() !== JSON_ERROR_NONE) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid schema JSON']);
        exit;
    }
    // Normalize to canonical schema (ui, storage, output) per docs/FIELD_DEFINITION_SCHEMA.md
    if (is_array($schema) && !empty($schema['fields'])) {
        try {
            require_once CORE_PATH . '/fields/FieldSchemaMap.php';
            $schema = \Modular\Fields\FieldSchemaMap::normalizeSchema($schema);
        } catch (\Throwable $e) {
            error_log('Field schema normalize error: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Schema normalization failed. Check server log.']);
            exit;
        }
    }
}

$db = $GLOBALS['db'];
$stmt = $db->prepare("SELECT id FROM post_types WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Post type not found']);
    exit;
}

try {
    $schemaEncoded = $schema !== null ? json_encode($schema) : null;
    $stmt = $db->prepare("UPDATE post_types SET mode = ?, `schema` = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$mode, $schemaEncoded, $id]);
} catch (\Throwable $e) {
    error_log('Field schema save error: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to save schema. Check server log.']);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'id' => $id]);
