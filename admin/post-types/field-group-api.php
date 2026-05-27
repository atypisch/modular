<?php
/**
 * Field Group API — manage post-type field groups (fixed + shared).
 *
 * GET  ?post_type_id=<id>             list { fixed: [...], shared: [...] }
 * POST action=create_local_fixed      append a new fixed-for-PT group to post_types.schema.field_groups
 * POST action=create_shared           insert a shared_fields row (type=field_group) and attach to PT
 * POST action=attach_shared           attach an existing shared group to a post type
 * POST action=update_definition       update label / items of a fixed/shared group
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

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = $GLOBALS['db'];
$method = $_SERVER['REQUEST_METHOD'];

function fg_load_schema(PDO $db, int $postTypeId): ?array
{
    $stmt = $db->prepare("SELECT `schema` FROM post_types WHERE id = ? AND deleted_at IS NULL LIMIT 1");
    $stmt->execute([$postTypeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    $schema = isset($row['schema']) && $row['schema'] !== '' ? json_decode((string) $row['schema'], true) : [];
    if (!is_array($schema)) {
        $schema = [];
    }
    if (!isset($schema['fields']) || !is_array($schema['fields'])) {
        $schema['fields'] = [];
    }
    if (!isset($schema['field_groups']) || !is_array($schema['field_groups'])) {
        $schema['field_groups'] = [];
    }
    return $schema;
}

function fg_save_schema(PDO $db, int $postTypeId, array $schema): void
{
    $stmt = $db->prepare("UPDATE post_types SET `schema` = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([json_encode($schema, JSON_UNESCAPED_UNICODE), $postTypeId]);
}

function fg_make_id(string $prefix = 'fg'): string
{
    return $prefix . '-' . bin2hex(random_bytes(6));
}

function fg_clean_items($items): array
{
    if (!is_array($items)) return [];
    $out = [];
    foreach ($items as $it) {
        if (!is_array($it)) continue;
        if (isset($it['type']) && $it['type'] === 'tab') {
            $out[] = [
                'id' => (string) ($it['id'] ?? fg_make_id('fg-tab')),
                'type' => 'tab',
                'label' => (string) ($it['label'] ?? 'Tab'),
            ];
            continue;
        }
        if (!isset($it['id'], $it['name'])) continue;
        $out[] = [
            'id' => (string) $it['id'],
            'name' => (string) $it['name'],
        ];
    }
    return $out;
}

// ---------------------------------------------------------------------------
// GET — list fixed (per PT) + shared field groups
// ---------------------------------------------------------------------------
if ($method === 'GET') {
    $postTypeId = isset($_GET['post_type_id']) ? (int) $_GET['post_type_id'] : 0;
    $fixed = [];
    if ($postTypeId > 0) {
        $schema = fg_load_schema($db, $postTypeId);
        if ($schema && !empty($schema['field_groups'])) {
            foreach ($schema['field_groups'] as $g) {
                if (!is_array($g)) continue;
                $fixed[] = [
                    'id' => (string) ($g['id'] ?? ''),
                    'label' => (string) ($g['label'] ?? 'Field Group'),
                    'items' => isset($g['items']) && is_array($g['items']) ? array_values($g['items']) : [],
                    'shared_id' => isset($g['shared_id']) ? (int) $g['shared_id'] : null,
                    'locked' => isset($g['locked']) ? (bool) $g['locked'] : true,
                ];
            }
        }
    }
    $shared = [];
    $rows = $db->query("SELECT id, name, definition FROM shared_fields WHERE type = 'field_group' AND deleted_at IS NULL ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $def = json_decode((string) ($row['definition'] ?? '[]'), true);
        $items = (is_array($def) && isset($def['items']) && is_array($def['items'])) ? array_values($def['items']) : [];
        $label = (is_array($def) && isset($def['label'])) ? (string) $def['label'] : (string) ($row['name'] ?? 'Field Group');
        $shared[] = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'label' => $label,
            'items' => $items,
        ];
    }
    echo json_encode(['fixed' => $fixed, 'shared' => $shared]);
    exit;
}

// ---------------------------------------------------------------------------
// POST — write actions (CSRF protected)
// ---------------------------------------------------------------------------
if ($method === 'POST') {
    if (!function_exists('csrf_verify') || !csrf_verify()) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid request.']);
        exit;
    }
    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
    $postTypeId = isset($_POST['post_type_id']) ? (int) $_POST['post_type_id'] : 0;
    $label = trim((string) ($_POST['label'] ?? ''));
    if ($label === '') $label = 'Field Group';

    if ($action === 'create_local_fixed') {
        if ($postTypeId <= 0) {
            echo json_encode(['error' => 'Missing post_type_id.']);
            exit;
        }
        $schema = fg_load_schema($db, $postTypeId);
        if ($schema === null) {
            echo json_encode(['error' => 'Post type not found.']);
            exit;
        }
        $gid = fg_make_id('fg');
        $items = isset($_POST['items']) ? json_decode((string) $_POST['items'], true) : [];
        $items = fg_clean_items(is_array($items) ? $items : []);
        $entry = [
            'id' => $gid,
            'label' => $label,
            'items' => $items,
            'shared_id' => null,
            'locked' => true,
        ];
        $schema['field_groups'][] = $entry;
        fg_save_schema($db, $postTypeId, $schema);
        echo json_encode(['success' => true, 'id' => $gid, 'label' => $label, 'items' => $items]);
        exit;
    }

    if ($action === 'create_shared') {
        if ($postTypeId <= 0) {
            echo json_encode(['error' => 'Missing post_type_id.']);
            exit;
        }
        $schema = fg_load_schema($db, $postTypeId);
        if ($schema === null) {
            echo json_encode(['error' => 'Post type not found.']);
            exit;
        }
        $items = isset($_POST['items']) ? json_decode((string) $_POST['items'], true) : [];
        $items = fg_clean_items(is_array($items) ? $items : []);
        $name = $label;
        $userId = $_SESSION['user_id'] ?? 0;
        $definition = json_encode(['label' => $label, 'items' => $items], JSON_UNESCAPED_UNICODE);
        $stmt = $db->prepare("INSERT INTO shared_fields (name, type, definition, created_by, updated_by, created_at, updated_at) VALUES (?, 'field_group', ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$name, $definition, $userId, $userId]);
        $sharedId = (int) $db->lastInsertId();
        $schema['field_groups'][] = [
            'id' => fg_make_id('fg'),
            'label' => $label,
            'items' => $items,
            'shared_id' => $sharedId,
            'locked' => true,
        ];
        fg_save_schema($db, $postTypeId, $schema);
        echo json_encode(['success' => true, 'id' => $sharedId, 'name' => $name, 'label' => $label, 'items' => $items]);
        exit;
    }

    if ($action === 'attach_shared') {
        if ($postTypeId <= 0) {
            echo json_encode(['error' => 'Missing post_type_id.']);
            exit;
        }
        $sharedId = (int) ($_POST['shared_id'] ?? 0);
        if ($sharedId <= 0) {
            echo json_encode(['error' => 'Missing shared_id.']);
            exit;
        }
        $stmt = $db->prepare("SELECT name, definition FROM shared_fields WHERE id = ? AND type = 'field_group' AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$sharedId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo json_encode(['error' => 'Shared field group not found.']);
            exit;
        }
        $def = json_decode((string) ($row['definition'] ?? '[]'), true);
        $items = (is_array($def) && isset($def['items']) && is_array($def['items'])) ? array_values($def['items']) : [];
        $sLabel = (is_array($def) && isset($def['label'])) ? (string) $def['label'] : (string) ($row['name'] ?? 'Field Group');
        $schema = fg_load_schema($db, $postTypeId);
        if ($schema === null) {
            echo json_encode(['error' => 'Post type not found.']);
            exit;
        }
        $alreadyAttached = false;
        foreach ($schema['field_groups'] as $g) {
            if (is_array($g) && isset($g['shared_id']) && (int) $g['shared_id'] === $sharedId) {
                $alreadyAttached = true;
                break;
            }
        }
        if (!$alreadyAttached) {
            $schema['field_groups'][] = [
                'id' => fg_make_id('fg'),
                'label' => $sLabel,
                'items' => $items,
                'shared_id' => $sharedId,
                'locked' => true,
            ];
            fg_save_schema($db, $postTypeId, $schema);
        }
        echo json_encode(['success' => true, 'id' => $sharedId, 'label' => $sLabel, 'items' => $items]);
        exit;
    }

    if ($action === 'update_definition') {
        $gid = (string) ($_POST['group_id'] ?? '');
        $sharedId = (int) ($_POST['shared_id'] ?? 0);
        $items = isset($_POST['items']) ? json_decode((string) $_POST['items'], true) : null;
        $cleaned = fg_clean_items(is_array($items) ? $items : []);
        $newLabel = trim((string) ($_POST['label'] ?? ''));
        if ($sharedId > 0) {
            $stmt = $db->prepare("SELECT definition FROM shared_fields WHERE id = ? AND type = 'field_group' AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$sharedId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $def = $row ? (json_decode((string) ($row['definition'] ?? '[]'), true) ?: []) : [];
            if (!is_array($def)) $def = [];
            if ($newLabel !== '') $def['label'] = $newLabel;
            $def['items'] = $cleaned;
            $stmt = $db->prepare("UPDATE shared_fields SET definition = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([json_encode($def, JSON_UNESCAPED_UNICODE), $sharedId]);
            // Propagate to every PT that links this shared group
            $rows = $db->query("SELECT id, `schema` FROM post_types WHERE deleted_at IS NULL AND `schema` IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $pt) {
                $sch = json_decode((string) ($pt['schema'] ?? '[]'), true);
                if (!is_array($sch) || empty($sch['field_groups']) || !is_array($sch['field_groups'])) continue;
                $changed = false;
                foreach ($sch['field_groups'] as &$g) {
                    if (is_array($g) && isset($g['shared_id']) && (int) $g['shared_id'] === $sharedId) {
                        if ($newLabel !== '') $g['label'] = $newLabel;
                        $g['items'] = $cleaned;
                        $changed = true;
                    }
                }
                unset($g);
                if ($changed) {
                    fg_save_schema($db, (int) $pt['id'], $sch);
                }
            }
            echo json_encode(['success' => true, 'shared_id' => $sharedId]);
            exit;
        }
        if ($postTypeId > 0 && $gid !== '') {
            $schema = fg_load_schema($db, $postTypeId);
            if ($schema === null) {
                echo json_encode(['error' => 'Post type not found.']);
                exit;
            }
            $found = false;
            foreach ($schema['field_groups'] as &$g) {
                if (is_array($g) && (string) ($g['id'] ?? '') === $gid) {
                    if ($newLabel !== '') $g['label'] = $newLabel;
                    $g['items'] = $cleaned;
                    $found = true;
                }
            }
            unset($g);
            if ($found) {
                fg_save_schema($db, $postTypeId, $schema);
                echo json_encode(['success' => true, 'group_id' => $gid]);
                exit;
            }
            echo json_encode(['error' => 'Group not found in this post type.']);
            exit;
        }
        echo json_encode(['error' => 'Missing identifiers (need shared_id or post_type_id + group_id).']);
        exit;
    }

    echo json_encode(['error' => 'Unknown action.']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
