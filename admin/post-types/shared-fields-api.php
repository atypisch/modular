<?php
/**
 * Shared Fields API — list and delete shared field definitions.
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

if (!is_logged_in()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = $GLOBALS['db'];
$method = $_SERVER['REQUEST_METHOD'];

// ---------------------------------------------------------------------------
// GET — list all shared fields with linked post type info
// ---------------------------------------------------------------------------
if ($method === 'GET') {
    $rows = $db->query(
        "SELECT id, name, type, definition, created_at, updated_at FROM shared_fields WHERE deleted_at IS NULL ORDER BY name"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Build map of shared_id → post type names
    $postTypes = $db->query(
        "SELECT id, name, slug, `schema` FROM post_types WHERE deleted_at IS NULL AND `schema` IS NOT NULL"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['definition'] = json_decode($row['definition'], true);
        $linked = [];
        foreach ($postTypes as $pt) {
            $schema = json_decode($pt['schema'], true);
            if (!is_array($schema) || empty($schema['fields'])) continue;
            foreach ($schema['fields'] as $field) {
                if (isset($field['_shared_id']) && (int)$field['_shared_id'] === (int)$row['id']) {
                    $linked[] = ['id' => (int)$pt['id'], 'name' => $pt['name'], 'slug' => $pt['slug']];
                    break;
                }
            }
        }
        $row['linked_post_types'] = $linked;
    }
    unset($row);

    header('Content-Type: application/json');
    echo json_encode(['data' => $rows]);
    exit;
}

// ---------------------------------------------------------------------------
// DELETE — soft-delete a shared field, detach from all post types
// ---------------------------------------------------------------------------
if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
    if (!function_exists('csrf_verify') || !csrf_verify()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid request.']);
        exit;
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid shared field id']);
        exit;
    }

    // Soft-delete the shared field
    $stmt = $db->prepare("UPDATE shared_fields SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);

    // Remove _shared_id from all post types referencing this shared field
    $postTypes = $db->query(
        "SELECT id, `schema` FROM post_types WHERE deleted_at IS NULL AND `schema` IS NOT NULL"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($postTypes as $pt) {
        $schema = json_decode($pt['schema'], true);
        if (!is_array($schema) || empty($schema['fields'])) continue;
        $changed = false;
        foreach ($schema['fields'] as &$field) {
            if (isset($field['_shared_id']) && (int)$field['_shared_id'] === $id) {
                unset($field['_shared_id']);
                $changed = true;
            }
        }
        unset($field);
        if ($changed) {
            $stmt = $db->prepare("UPDATE post_types SET `schema` = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([json_encode($schema), $pt['id']]);
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
header('Content-Type: application/json');
echo json_encode(['error' => 'Method not allowed']);
