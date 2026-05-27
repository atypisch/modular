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
    if (is_array($schema) && (!isset($schema['fields']) || !is_array($schema['fields']))) {
        $isList = array_keys($schema) === array_keys(array_values($schema));
        if ($isList) {
            $schema = ['fields' => $schema];
        }
    }
    // Normalize to canonical schema (ui, storage, output) per docs/FIELD_DEFINITION_SCHEMA.md
    if (is_array($schema) && !empty($schema['fields'])) {
        try {
            require_once CORE_PATH . '/fields/FieldSchemaMap.php';
            $incomingFieldGroups = isset($schema['field_groups']) && is_array($schema['field_groups']) ? $schema['field_groups'] : null;
            $schema = \Modular\Fields\FieldSchemaMap::normalizeSchema($schema);
            if ($incomingFieldGroups !== null) {
                $schema['field_groups'] = $incomingFieldGroups;
            }
        } catch (\Throwable $e) {
            error_log('Field schema normalize error: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Schema normalization failed. Check server log.']);
            exit;
        }
    }
}

$db = $GLOBALS['db'];
$stmt = $db->prepare("SELECT id, `schema` FROM post_types WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$existingRow = $stmt->fetch(\PDO::FETCH_ASSOC);
if (!$existingRow) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Post type not found']);
    exit;
}

// Preserve previously-stored field_groups when the Field Builder did not send them
if (is_array($schema)) {
    $existingSchema = isset($existingRow['schema']) && $existingRow['schema'] !== '' ? json_decode((string) $existingRow['schema'], true) : null;
    if (is_array($existingSchema) && isset($existingSchema['field_groups']) && is_array($existingSchema['field_groups'])) {
        if (!isset($schema['field_groups']) || !is_array($schema['field_groups'])) {
            $schema['field_groups'] = $existingSchema['field_groups'];
        }
    }
}

// ---- Shared fields handling ------------------------------------------------
$sharedActions = [];
if (!empty($_POST['shared_actions'])) {
    $sharedActions = json_decode($_POST['shared_actions'], true);
    if (!is_array($sharedActions)) $sharedActions = [];
}

$userId = $_SESSION['user_id'] ?? 1;

if (is_array($schema) && !empty($schema['fields'])) {
    // Index shared_actions by _shared_id for quick lookup
    $actionMap = [];
    foreach ($sharedActions as $sa) {
        if (isset($sa['_shared_id'])) {
            $actionMap[(int)$sa['_shared_id']] = $sa['action'] ?? '';
        }
    }

    // Helper: get field definition without internal keys
    $cleanDef = function ($field) {
        $copy = $field;
        unset($copy['_shared_id'], $copy['_share_with_post_types'], $copy['_uid']);
        return $copy;
    };

    foreach ($schema['fields'] as &$field) {
        // --- Existing shared field: propagate or detach ---
        if (isset($field['_shared_id'])) {
            $sid = (int)$field['_shared_id'];
            $action = $actionMap[$sid] ?? '';

            if ($action === 'detach') {
                unset($field['_shared_id']);
            } elseif ($action === 'propagate') {
                $def = $cleanDef($field);
                // Update shared_fields canonical definition
                $stmt = $db->prepare("UPDATE shared_fields SET definition = ?, updated_by = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
                $stmt->execute([json_encode($def), $userId, $sid]);

                // Propagate to all other post types using this shared field
                $allPts = $db->query("SELECT id, `schema` FROM post_types WHERE deleted_at IS NULL AND `schema` IS NOT NULL AND id != " . (int)$id)->fetchAll(PDO::FETCH_ASSOC);
                foreach ($allPts as $pt) {
                    $ptSchema = json_decode($pt['schema'], true);
                    if (!is_array($ptSchema) || empty($ptSchema['fields'])) continue;
                    $changed = false;
                    foreach ($ptSchema['fields'] as &$ptField) {
                        if (isset($ptField['_shared_id']) && (int)$ptField['_shared_id'] === $sid) {
                            $ptField = array_merge($def, ['_shared_id' => $sid]);
                            $changed = true;
                        }
                    }
                    unset($ptField);
                    if ($changed) {
                        $stmt = $db->prepare("UPDATE post_types SET `schema` = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([json_encode($ptSchema), $pt['id']]);
                    }
                }
            }
        }

        // --- New sharing: field has _share_with_post_types ---
        if (!empty($field['_share_with_post_types']) && !isset($field['_shared_id'])) {
            $targetPtIds = $field['_share_with_post_types'];
            $def = $cleanDef($field);
            $sfType = ($field['type'] === 'tab') ? 'field_group' : 'field';
            $sfName = $field['label'] ?? $field['name'] ?? 'Shared field';

            // Insert into shared_fields
            $stmt = $db->prepare("INSERT INTO shared_fields (name, type, definition, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$sfName, $sfType, json_encode($def), $userId, $userId]);
            $newSharedId = (int)$db->lastInsertId();

            // Tag current field
            $field['_shared_id'] = $newSharedId;

            // Add field to target post types
            foreach ($targetPtIds as $ptId) {
                $ptId = (int)$ptId;
                if ($ptId === (int)$id) continue;
                $stmt = $db->prepare("SELECT `schema` FROM post_types WHERE id = ? AND deleted_at IS NULL");
                $stmt->execute([$ptId]);
                $ptRow = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$ptRow) continue;
                $ptSchema = $ptRow['schema'] ? json_decode($ptRow['schema'], true) : ['fields' => []];
                if (!is_array($ptSchema)) $ptSchema = ['fields' => []];
                if (!isset($ptSchema['fields'])) $ptSchema['fields'] = [];
                $sharedField = array_merge($def, ['_shared_id' => $newSharedId]);
                $ptSchema['fields'][] = $sharedField;
                $stmt = $db->prepare("UPDATE post_types SET `schema` = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([json_encode($ptSchema), $ptId]);
            }
        }

        // Clean up the temporary _share_with_post_types key before saving
        unset($field['_share_with_post_types']);
    }
    unset($field);

    foreach ($schema['fields'] as $field) {
        if (!isset($field['_shared_id'])) {
            continue;
        }
        $sid = (int)$field['_shared_id'];
        $stmt = $db->prepare("SELECT definition FROM shared_fields WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$sid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            continue;
        }
        $def = json_decode($row['definition'] ?? '[]', true);
        if (!is_array($def)) {
            $def = [];
        }
        $def['attach_to_new_post_types'] = !empty($field['attach_to_new_post_types']);
        $stmt = $db->prepare("UPDATE shared_fields SET definition = ?, updated_by = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([json_encode($def), $userId, $sid]);
    }
}
// ---- End shared fields handling --------------------------------------------

try {
    $schemaEncoded = $schema !== null ? json_encode($schema, JSON_THROW_ON_ERROR) : null;
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
