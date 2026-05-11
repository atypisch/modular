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
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/posts');
    exit;
}
if (!csrf_verify()) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/posts');
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
$template = isset($_POST['template']) ? trim((string) $_POST['template']) : null;
$publishedAtRaw = isset($_POST['published_at']) ? trim((string) $_POST['published_at']) : '';
$visibility = in_array($_POST['visibility'] ?? '', ['public', 'private', 'password'], true) ? $_POST['visibility'] : 'public';
$postPassword = isset($_POST['post_password']) ? trim((string) $_POST['post_password']) : null;

// Publication date: accept datetime-local (YYYY-MM-DDTHH:MM) or empty
$publishedAt = null;
if ($publishedAtRaw !== '') {
    $ts = strtotime($publishedAtRaw);
    if ($ts !== false) {
        $publishedAt = date('Y-m-d H:i:s', $ts);
    }
}
// If user chose Published but set a future date, treat as scheduled
if ($status === 'published' && $publishedAt !== null && strtotime($publishedAt) > time()) {
    $status = 'scheduled';
}
// When saving as draft/archived with no date submitted, preserve existing published_at
if ($status !== 'published' && $status !== 'scheduled' && $publishedAt === null && $postId) {
    try {
        $stmt = $db->prepare("SELECT published_at FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row && !empty($row['published_at'])) {
            $publishedAt = $row['published_at'];
        }
    } catch (\Throwable $e) { /* ignore */ }
}
if ($status === 'published' && $publishedAt === null) {
    $publishedAt = date('Y-m-d H:i:s');
}
$passwordCol = ($visibility === 'password') ? $postPassword : null;

// Validate required
if (empty($title) || empty($postTypeId)) {
    $_SESSION['error'] = 'Title and Post Type are required.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/posts/edit.php' . (isset($_POST['id']) ? '?id=' . $_POST['id'] : ''));
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

// Parent/hiërarchie validatie voor hiërarchische post types
$parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
if ($parentId !== null && $parentId <= 0) {
    $parentId = null;
}
$postTypeRow = null;
try {
    $stmtPt = $db->prepare("SELECT id, hierarchical FROM post_types WHERE id = ? AND deleted_at IS NULL");
    $stmtPt->execute([$postTypeId]);
    $postTypeRow = $stmtPt->fetch(\PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    $postTypeRow = null;
}
if (!$postTypeRow) {
    $_SESSION['error'] = 'Post type not found.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/posts/edit.php' . ($postId ? '?id=' . $postId : ''));
    exit;
}
$isHierarchical = !empty($postTypeRow['hierarchical']);
if (!$isHierarchical) {
    $parentId = null;
} elseif ($parentId !== null) {
    // Parent moet bestaan en zelfde post_type_id hebben
    try {
        $stmtParent = $db->prepare("SELECT id, post_type_id, parent_id FROM posts WHERE id = ? AND deleted_at IS NULL");
        $stmtParent->execute([$parentId]);
        $parentRow = $stmtParent->fetch(\PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        $parentRow = false;
    }
    if (!$parentRow || (int)$parentRow['post_type_id'] !== (int)$postTypeId) {
        $_SESSION['error'] = 'Invalid parent selected.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/posts/edit.php' . ($postId ? '?id=' . $postId : ''));
        exit;
    }
    // Maximaal 3 niveaus diep
    $depth = 1;
    $cursorParentId = $parentRow['parent_id'] !== null ? (int)$parentRow['parent_id'] : null;
    while ($cursorParentId !== null) {
        $depth++;
        if ($depth > 3) {
            break;
        }
        $stmtDepth = $db->prepare("SELECT parent_id FROM posts WHERE id = ? AND deleted_at IS NULL");
        $stmtDepth->execute([$cursorParentId]);
        $rowDepth = $stmtDepth->fetch(\PDO::FETCH_ASSOC);
        $cursorParentId = $rowDepth && $rowDepth['parent_id'] !== null ? (int)$rowDepth['parent_id'] : null;
    }
    if ($depth >= 3) {
        $_SESSION['error'] = 'Maximum hiërarchische diepte (3 niveaus) overschreden.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/posts/edit.php' . ($postId ? '?id=' . $postId : ''));
        exit;
    }
    // Cirkels voorkomen bij update: parent mag geen descendant zijn
    if ($postId) {
        $checkId = $parentId;
        while ($checkId !== null) {
            if ((int)$checkId === (int)$postId) {
                $_SESSION['error'] = 'Een bericht kan geen child van zichzelf (of eigen subpagina) zijn.';
                header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/posts/edit.php?id=' . (int)$postId);
                exit;
            }
            $stmtChain = $db->prepare("SELECT parent_id FROM posts WHERE id = ? AND deleted_at IS NULL");
            $stmtChain->execute([$checkId]);
            $rowChain = $stmtChain->fetch(\PDO::FETCH_ASSOC);
            $checkId = $rowChain && $rowChain['parent_id'] !== null ? (int)$rowChain['parent_id'] : null;
        }
    }
}

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
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/posts/edit.php' . ($postId ? '?id=' . $postId : '?post_type_id=' . $postTypeId));
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
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/posts/edit.php' . ($postId ? '?id=' . $postId : ''));
        exit;
    }
    $componentData = json_encode($decoded);
}

try {
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

    if ($postId) {
        $stmt = $db->prepare("
            UPDATE posts
            SET post_type_id = ?, parent_id = ?, slug = ?, title = ?, excerpt = ?, status = ?,
                component_data = ?, data = ?, layout = ?, published_at = ?, template = ?, visibility = ?, password = ?,
                updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $postTypeId, $parentId, $slug, $title, $excerpt, $status,
            $componentData, $dataJson, $layoutJson, $publishedAt, $template, $visibility, $passwordCol,
            $userId, $postId
        ]);
        $savedPostId = (int) $postId;
    } else {
        $stmt = $db->prepare("
            INSERT INTO posts
            (post_type_id, parent_id, slug, title, excerpt, status, component_data, data, layout,
             author_id, created_by, updated_by, published_at, template, visibility, password, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $postTypeId, $parentId, $slug, $title, $excerpt, $status, $componentData, $dataJson, $layoutJson,
            $userId, $userId, $userId, $publishedAt, $template, $visibility, $passwordCol
        ]);
        $savedPostId = (int) $db->lastInsertId();
    }

    if (function_exists('do_action')) {
        do_action('post_saved', $savedPostId, $slug, $_POST);
    }

    if ($postId) {
        $_SESSION['success'] = 'Post updated successfully.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/posts/edit.php?id=' . (int)$postId);
        exit;
    } else {
        $_SESSION['success'] = 'Post created successfully.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/posts/edit.php?id=' . $savedPostId);
        exit;
    }

} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
    error_log("Post save error: " . $e->getMessage());
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/posts/edit.php' . ($postId ? '?id=' . $postId : '?post_type_id=' . $postTypeId));
    exit;
}
