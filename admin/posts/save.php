<?php
/**
 * Posts Save Handler
 * Supports schema-based field_data (saves to data/layout) and legacy component_data JSON.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/data/init.php';
require_once CORE_PATH . '/functions/auth.php';

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /modular/admin/posts');
    exit;
}
if (!csrf_verify()) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header('Location: /modular/admin/posts');
    exit;
}

$db = $GLOBALS['db'];
$userId = $_SESSION['user_id'] ?? 1;

$useSchemaForm = !empty($_POST['use_schema_form']);
$postTypeId = filter_input(INPUT_POST, 'post_type_id', FILTER_SANITIZE_NUMBER_INT);
$title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING) ?? '';
$slug = filter_input(INPUT_POST, 'slug', FILTER_SANITIZE_STRING) ?? '';
$postId = isset($_POST['id']) ? filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT) : null;
// Excerpt is no longer a default field; preserve existing on update when not in form
$excerpt = '';
if ($postId && array_key_exists('excerpt', $_POST)) {
    $excerpt = filter_input(INPUT_POST, 'excerpt', FILTER_SANITIZE_STRING) ?? '';
} elseif ($postId) {
    $stmt = $db->prepare("SELECT excerpt FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    $excerpt = $row['excerpt'] ?? '';
} else {
    $excerpt = filter_input(INPUT_POST, 'excerpt', FILTER_SANITIZE_STRING) ?? '';
}
$status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING) ?? 'draft';

// Validate required
if (empty($title) || empty($postTypeId)) {
    $_SESSION['error'] = 'Title and Post Type are required.';
    header('Location: /modular/admin/posts/edit.php' . (isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
    exit;
}

// Clean slug
if (empty($slug)) {
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', $title)));
} else {
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', $slug)));
}

$dataJson = null;
$layoutJson = null;
$componentData = '[]';

if ($useSchemaForm) {
    require_once CORE_PATH . '/fields/FieldInterface.php';
    require_once CORE_PATH . '/fields/LayoutFieldInterface.php';
    require_once CORE_PATH . '/fields/FieldRegistry.php';
    require_once CORE_PATH . '/fields/TextField.php';
    require_once CORE_PATH . '/fields/TextareaField.php';
    require_once CORE_PATH . '/fields/NumberField.php';
    require_once CORE_PATH . '/fields/EmailField.php';
require_once CORE_PATH . '/fields/URLField.php';
require_once CORE_PATH . '/fields/PasswordField.php';
require_once CORE_PATH . '/fields/RangeField.php';
require_once CORE_PATH . '/fields/SearchField.php';
require_once CORE_PATH . '/fields/HiddenField.php';
    require_once CORE_PATH . '/fields/WysiwygField.php';
    require_once CORE_PATH . '/fields/CodeField.php';
    require_once CORE_PATH . '/fields/MarkdownField.php';
    require_once CORE_PATH . '/fields/SelectField.php';
    require_once CORE_PATH . '/fields/CheckboxField.php';
    require_once CORE_PATH . '/fields/RadioField.php';
    require_once CORE_PATH . '/fields/ButtonGroupField.php';
    require_once CORE_PATH . '/fields/TrueFalseField.php';
    require_once CORE_PATH . '/fields/PostObjectField.php';
    require_once CORE_PATH . '/fields/RelationField.php';
    require_once CORE_PATH . '/fields/TaxonomyField.php';
    require_once CORE_PATH . '/fields/UserField.php';
    require_once CORE_PATH . '/fields/ImageField.php';
    require_once CORE_PATH . '/fields/FileField.php';
    require_once CORE_PATH . '/fields/GalleryField.php';
    require_once CORE_PATH . '/fields/AudioField.php';
    require_once CORE_PATH . '/fields/VideoField.php';
    require_once CORE_PATH . '/fields/EmbedField.php';
    require_once CORE_PATH . '/fields/DatePickerField.php';
    require_once CORE_PATH . '/fields/TimePickerField.php';
    require_once CORE_PATH . '/fields/DateTimePickerField.php';
    require_once CORE_PATH . '/fields/GroupField.php';
    require_once CORE_PATH . '/fields/RepeaterField.php';
    require_once CORE_PATH . '/fields/FlexibleContentField.php';
    require_once CORE_PATH . '/fields/MessageField.php';
    require_once CORE_PATH . '/fields/AccordionField.php';
    require_once CORE_PATH . '/fields/TabField.php';
    require_once CORE_PATH . '/fields/DividerField.php';
    require_once CORE_PATH . '/functions/field-save.php';
    $stmt = $db->prepare("SELECT mode, `schema` FROM post_types WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$postTypeId]);
    $ptRow = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$ptRow || empty($ptRow['schema'])) {
        $_SESSION['error'] = 'Post type schema not found. Save without schema form or configure fields first.';
        header('Location: /modular/admin/posts/edit.php' . ($postId ? '?id=' . $postId : '?post_type_id=' . $postTypeId));
        exit;
    }
    $schema = is_string($ptRow['schema']) ? json_decode($ptRow['schema'], true) : $ptRow['schema'];
    $schemaFields = $schema['fields'] ?? [];
    if (isset($_POST['field_data_json']) && $_POST['field_data_json'] !== '') {
        $decoded = json_decode($_POST['field_data_json'], true);
        $rawFieldData = is_array($decoded) ? $decoded : [];
    } else {
        $rawFieldData = isset($_POST['field_data']) && is_array($_POST['field_data']) ? $_POST['field_data'] : [];
    }

    $layoutOrder = [];
    $layoutJson = null;

    // New format: top section + layout blocks (Add layout → 1/2/3 columns)
    if (isset($rawFieldData['__layout_top']) && array_key_exists('__layout_blocks', $rawFieldData) && is_array($rawFieldData['__layout_blocks'])) {
        $valuesByInstance = isset($rawFieldData['__values_by_instance']) && is_array($rawFieldData['__values_by_instance'])
            ? $rawFieldData['__values_by_instance']
            : [];
        $layoutTop = $rawFieldData['__layout_top'];
        $layoutBlocks = $rawFieldData['__layout_blocks'];
        $layoutOrderIds = isset($rawFieldData['__layout_order']) && is_array($rawFieldData['__layout_order'])
            ? array_values($rawFieldData['__layout_order'])
            : null;
        unset($rawFieldData['__layout_top'], $rawFieldData['__layout_blocks'], $rawFieldData['__values_by_instance'], $rawFieldData['__layout_order']);

        $groupedByField = [];
        foreach (is_array($layoutTop) ? $layoutTop : [] as $inst) {
            if (!is_array($inst)) continue;
            $id = isset($inst['id']) ? (string)$inst['id'] : null;
            $name = isset($inst['name']) ? (string)$inst['name'] : null;
            if (!$id || !$name) continue;
            $value = $valuesByInstance[$id] ?? null;
            if (!array_key_exists($name, $groupedByField)) $groupedByField[$name] = [];
            $groupedByField[$name][] = $value;
        }
        foreach ($layoutBlocks as $block) {
            $cols = isset($block['columns']) && is_array($block['columns']) ? $block['columns'] : [];
            foreach ($cols as $colList) {
                if (!is_array($colList)) continue;
                foreach ($colList as $inst) {
                    if (!is_array($inst)) continue;
                    $id = isset($inst['id']) ? (string)$inst['id'] : null;
                    $name = isset($inst['name']) ? (string)$inst['name'] : null;
                    if (!$id || !$name) continue;
                    $value = $valuesByInstance[$id] ?? null;
                    if (!array_key_exists($name, $groupedByField)) $groupedByField[$name] = [];
                    $groupedByField[$name][] = $value;
                }
            }
        }
        $rawFieldData = $groupedByField;
        foreach ($rawFieldData as $key => $val) {
            if (is_array($val) && count($val) === 1) {
                $rawFieldData[$key] = $val[0];
            }
        }
        $layoutOrder = [ 'top' => is_array($layoutTop) ? $layoutTop : [], 'blocks' => $layoutBlocks ];
        if ($layoutOrderIds !== null) {
            $layoutOrder['order'] = $layoutOrderIds;
        }
        $layoutJson = json_encode($layoutOrder);
    }
    // Legacy: __layout_type + __layout_columns
    elseif (isset($rawFieldData['__layout_type']) && isset($rawFieldData['__layout_columns']) && is_array($rawFieldData['__layout_columns'])) {
        $valuesByInstance = isset($rawFieldData['__values_by_instance']) && is_array($rawFieldData['__values_by_instance'])
            ? $rawFieldData['__values_by_instance']
            : [];
        $layoutType = $rawFieldData['__layout_type'] === '2col' || $rawFieldData['__layout_type'] === '3col' ? $rawFieldData['__layout_type'] : '1col';
        $layoutColumns = $rawFieldData['__layout_columns'];
        unset($rawFieldData['__layout_type'], $rawFieldData['__layout_columns'], $rawFieldData['__values_by_instance']);

        $groupedByField = [];
        foreach ($layoutColumns as $colList) {
            if (!is_array($colList)) continue;
            foreach ($colList as $inst) {
                if (!is_array($inst)) continue;
                $id = isset($inst['id']) ? (string)$inst['id'] : null;
                $name = isset($inst['name']) ? (string)$inst['name'] : null;
                if (!$id || !$name) continue;
                $value = $valuesByInstance[$id] ?? null;
                if (!array_key_exists($name, $groupedByField)) $groupedByField[$name] = [];
                $groupedByField[$name][] = $value;
            }
        }
        $rawFieldData = $groupedByField;
        $layoutOrder = [ 'type' => $layoutType, 'columns' => $layoutColumns ];
        $layoutJson = json_encode($layoutOrder);
    }
    // Legacy: __layout_instances (flat list) + __values_by_instance
    elseif (isset($rawFieldData['__layout_instances']) && is_array($rawFieldData['__layout_instances'])) {
        $instances = $rawFieldData['__layout_instances'];
        $valuesByInstance = isset($rawFieldData['__values_by_instance']) && is_array($rawFieldData['__values_by_instance'])
            ? $rawFieldData['__values_by_instance']
            : [];
        unset($rawFieldData['__layout_instances'], $rawFieldData['__values_by_instance']);

        $layoutOrder = [];
        $groupedByField = [];
        foreach ($instances as $inst) {
            if (!is_array($inst)) continue;
            $id = isset($inst['id']) ? (string)$inst['id'] : null;
            $name = isset($inst['name']) ? (string)$inst['name'] : null;
            if (!$id || !$name) continue;
            $layoutOrder[] = ['id' => $id, 'name' => $name];
            $value = $valuesByInstance[$id] ?? null;
            if (!array_key_exists($name, $groupedByField)) $groupedByField[$name] = [];
            $groupedByField[$name][] = $value;
        }
        $rawFieldData = $groupedByField;
        $layoutJson = !empty($layoutOrder) ? json_encode($layoutOrder) : null;
    } elseif (isset($rawFieldData['__layout']) && is_array($rawFieldData['__layout'])) {
        $layoutOrder = array_values(array_filter($rawFieldData['__layout'], 'strlen'));
        unset($rawFieldData['__layout']);
        $layoutJson = !empty($layoutOrder) ? json_encode($layoutOrder) : null;
    }

    $data = sanitize_field_data_from_schema($schemaFields, $rawFieldData);
    $dataJson = json_encode($data);
    if ($dataJson === 'null' || $dataJson === false) {
        $dataJson = '{}';
    }
    if ($layoutJson === null) {
        $layoutJson = json_encode(build_layout_order_from_schema($schemaFields));
    }
    $componentData = json_encode(['data' => $data]);
} else {
    $rawComponent = filter_input(INPUT_POST, 'component_data', FILTER_UNSAFE_RAW) ?? '[]';
    $decoded = json_decode($rawComponent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION['error'] = 'Invalid JSON in component data.';
        header('Location: /modular/admin/posts/edit.php' . ($postId ? '?id=' . $postId : ''));
        exit;
    }
    $componentData = json_encode($decoded);
}

try {
    // Check if post type exists
    $stmt = $db->prepare("SELECT id FROM post_types WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$postTypeId]);
    if (!$stmt->fetch()) {
        throw new Exception("Post type not found");
    }

    // Check slug uniqueness
    $checkStmt = $db->prepare("SELECT id FROM posts WHERE slug = ? AND post_type_id = ?" . ($postId ? " AND id != ?" : ""));
    $checkParams = [$slug, $postTypeId];
    if ($postId) {
        $checkParams[] = $postId;
    }
    $checkStmt->execute($checkParams);
    if ($checkStmt->fetch()) {
        throw new Exception("A post with this slug already exists for this post type");
    }

    $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;

    if ($postId) {
        $stmt = $db->prepare("
            UPDATE posts 
            SET post_type_id = ?, slug = ?, title = ?, excerpt = ?, status = ?, 
                component_data = ?, data = ?, layout = ?, published_at = ?, updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $postTypeId, $slug, $title, $excerpt, $status,
            $componentData, $dataJson, $layoutJson, $publishedAt, $userId, $postId
        ]);
        $_SESSION['success'] = 'Post updated successfully.';
        header('Location: /modular/admin/posts/edit.php?id=' . (int)$postId);
        exit;
    } else {
        $stmt = $db->prepare("
            INSERT INTO posts 
            (post_type_id, slug, title, excerpt, status, component_data, data, layout,
             author_id, created_by, updated_by, published_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $postTypeId, $slug, $title, $excerpt, $status, $componentData, $dataJson, $layoutJson,
            $userId, $userId, $userId, $publishedAt
        ]);
        $newId = (int)$db->lastInsertId();
        $_SESSION['success'] = 'Post created successfully.';
        header('Location: /modular/admin/posts/edit.php?id=' . $newId);
        exit;
    }

} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
    error_log("Post save error: " . $e->getMessage());
    header('Location: /modular/admin/posts/edit.php' . ($postId ? '?id=' . $postId : '?post_type_id=' . $postTypeId));
    exit;
}
