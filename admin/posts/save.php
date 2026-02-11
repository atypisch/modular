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
    $data = sanitize_field_data_from_schema($schemaFields, $rawFieldData);
    $dataJson = json_encode($data);
    $layoutJson = json_encode(build_layout_order_from_schema($schemaFields));
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
        $_SESSION['success'] = 'Post created successfully.';
    }

    header('Location: /modular/admin/posts?post_type_id=' . $postTypeId);
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
    error_log("Post save error: " . $e->getMessage());
    header('Location: /modular/admin/posts/edit.php' . ($postId ? '?id=' . $postId : '?post_type_id=' . $postTypeId));
    exit;
}
