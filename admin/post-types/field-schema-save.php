<?php
/**
 * Save post type field schema and mode (JSON).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';

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

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid post type id']);
    exit;
}

$schemaJson = $_POST['schema'] ?? '';
$mode = isset($_POST['mode']) ? trim($_POST['mode']) : 'fixed';
if (!in_array($mode, ['fixed', 'modular'], true)) {
    $mode = 'fixed';
}

$schema = null;
if ($schemaJson !== '') {
    $schema = json_decode($schemaJson, true);
    if ($schema === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid schema JSON']);
        exit;
    }
}

$db = $GLOBALS['db'];
$stmt = $db->prepare("SELECT id FROM post_types WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Post type not found']);
    exit;
}

$schemaEncoded = $schema !== null ? json_encode($schema) : null;
$stmt = $db->prepare("UPDATE post_types SET mode = ?, `schema` = ?, updated_at = NOW() WHERE id = ?");
$stmt->execute([$mode, $schemaEncoded, $id]);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'id' => $id]);
