<?php
/**
 * Posts Edit Page - Japanese Style
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

// Theme path and active theme for template discovery (WordPress-style Template Name)
if (!defined('THEMES_PATH')) {
    define('THEMES_PATH', defined('PUBLIC_PATH') ? PUBLIC_PATH . '/themes' : dirname(__DIR__, 2) . '/public/themes');
}
if (!defined('ACTIVE_THEME')) {
    $activeThemeSlug = 'default';
    if (isset($GLOBALS['db'])) {
        try {
            $st = $GLOBALS['db']->query("SELECT option_value FROM settings WHERE option_name = 'active_theme' LIMIT 1");
            $r = $st ? $st->fetch(\PDO::FETCH_ASSOC) : null;
            if ($r && !empty(trim((string) $r['option_value']))) {
                $activeThemeSlug = trim($r['option_value']);
            }
        } catch (\Throwable $e) { /* ignore */ }
    }
    define('ACTIVE_THEME', $activeThemeSlug);
}
if (is_file(CORE_PATH . '/functions/template-helpers.php')) {
    require_once CORE_PATH . '/functions/template-helpers.php';
}

if (!is_logged_in()) {
    header('Location: ' . admin_url('auth/login.php'));
    exit;
}

$page_title = isset($_GET['id']) ? 'Edit Post' : 'Add New Post';
$posts_active = true;

$db = $GLOBALS['db'];
$postId = $_GET['id'] ?? null;
$postTypeId = $_GET['post_type_id'] ?? null;

// 'show' post type is managed via simuze_events — redirect to dedicated admin
$_checkTypeId = $postTypeId;
if (!$_checkTypeId && $postId) {
    try {
        $stmtPT = $db->prepare("SELECT pt.slug FROM posts p INNER JOIN post_types pt ON pt.id = p.post_type_id WHERE p.id = ? LIMIT 1");
        $stmtPT->execute([$postId]);
        $ptSlugCheck = $stmtPT->fetchColumn();
        if ($ptSlugCheck === 'show') {
            $showsAdminUrl = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=shows';
            header('Location: ' . $showsAdminUrl);
            exit;
        }
    } catch (\Throwable $e) { /* ignore */ }
}
if ($_checkTypeId) {
    try {
        $stmtSlug2 = $db->prepare("SELECT slug FROM post_types WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmtSlug2->execute([$_checkTypeId]);
        $ptSlugRow2 = $stmtSlug2->fetch(\PDO::FETCH_ASSOC);
        if ($ptSlugRow2 && ($ptSlugRow2['slug'] ?? '') === 'show') {
            $showsAdminUrl = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=shows&action=new';
            header('Location: ' . $showsAdminUrl);
            exit;
        }
    } catch (\Throwable $e) { /* ignore */ }
}

// Post type slug/name for URL preview and heading (loaded when we have postTypeId)
$postTypeSlug = '';
$postTypeName = '';

// Load existing post if editing
$postData = [
    'post_type_id' => $postTypeId ?? '',
    'title' => '',
    'slug' => '',
    'status' => 'draft',
    'template' => '',
    'published_at' => null,
    'visibility' => 'public',
    'password' => '',
    'component_data' => []
];

if ($postId) {
    try {
        $stmt = $db->prepare("
            SELECT p.*, pt.name as post_type_name, pt.slug as post_type_slug, pt.mode as post_type_mode, pt.`schema` as post_type_schema
            FROM posts p
            INNER JOIN post_types pt ON p.post_type_id = pt.id
            WHERE p.id = ? AND p.deleted_at IS NULL
        ");
        $stmt->execute([$postId]);
        $post = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($post) {
            $postData = $post;
            $postData['component_data'] = json_decode($post['component_data'] ?? '[]', true);
            $postData['data'] = isset($post['data']) && $post['data'] !== '' ? json_decode($post['data'], true) : null;
            $postData['meta_data'] = isset($post['meta_data']) && $post['meta_data'] !== '' ? json_decode($post['meta_data'], true) : null;
            $postData['layout'] = isset($post['layout']) && $post['layout'] !== '' ? json_decode($post['layout'], true) : null;
            $postTypeId = $post['post_type_id'];
            $postTypeName = $post['post_type_name'] ?? '';
            $postTypeSlug = $post['post_type_slug'] ?? '';
        } else {
            $_SESSION['error'] = 'Post not found.';
            header('Location: ' . admin_posts_index_url(admin_posts_resolve_post_type_id(null, $_GET['post_type_id'] ?? null)));
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error loading post: ' . $e->getMessage();
        header('Location: ' . admin_posts_index_url(admin_posts_resolve_post_type_id(null, $_GET['post_type_id'] ?? null)));
        exit;
    }
}

// When adding new, post type must be in URL
if (!$postId && !$postTypeId) {
    $_SESSION['error'] = 'Post type is required.';
    header('Location: ' . admin_posts_index_url());
    exit;
}

if ($postTypeId) {
    admin_posts_filter_remember((int) $postTypeId);
}

// Load current post type schema (for selected post type) to decide schema form vs legacy JSON
$postTypeSchema = null;
$postTypeMode = 'fixed';
$fieldData = [];
$isHierarchicalPostType = false;
if ($postTypeId) {
    // Altijd post type metadata laden (incl. hierarchical) zodat sidebar parent-keuze correct zichtbaar is
    try {
        $stmtMeta = $db->prepare("SELECT name, slug, mode, hierarchical FROM post_types WHERE id = ? AND deleted_at IS NULL");
        $stmtMeta->execute([$postTypeId]);
        $ptMeta = $stmtMeta->fetch(\PDO::FETCH_ASSOC);
        if ($ptMeta) {
            $postTypeName = $ptMeta['name'] ?? $postTypeName;
            $postTypeSlug = $ptMeta['slug'] ?? $postTypeSlug;
            $postTypeMode = $ptMeta['mode'] ?? $postTypeMode;
            $isHierarchicalPostType = !empty($ptMeta['hierarchical']);
        }
    } catch (\Throwable $e) {
        error_log("Error loading post type metadata: " . $e->getMessage());
    }

    // Use schema from post row when we already have it (edit), else fetch
    if (!empty($postData['post_type_schema'])) {
        $postTypeSchema = json_decode($postData['post_type_schema'], true);
        $postTypeMode = $postData['post_type_mode'] ?? 'fixed';
    }
    if ($postTypeSchema === null) {
        try {
            $stmt = $db->prepare("SELECT name, slug, mode, hierarchical, `schema` FROM post_types WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$postTypeId]);
            $ptRow = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($ptRow) {
                $postTypeName = $ptRow['name'] ?? '';
                $postTypeSlug = $ptRow['slug'] ?? '';
                $postTypeMode = $ptRow['mode'] ?? 'fixed';
                $isHierarchicalPostType = !empty($ptRow['hierarchical']);
                $postTypeSchema = isset($ptRow['schema']) && $ptRow['schema'] !== '' && $ptRow['schema'] !== null
                    ? json_decode($ptRow['schema'], true)
                    : null;
            }
        } catch (Exception $e) {
            error_log("Error loading post type schema: " . $e->getMessage());
        }
    }
    if ($postTypeName === '' && !empty($postData['post_type_name'])) {
        $postTypeName = $postData['post_type_name'];
    }
    if ($postTypeSlug === '' && $postTypeId) {
        $stmt = $db->prepare("SELECT slug FROM post_types WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$postTypeId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) $postTypeSlug = $row['slug'] ?? '';
    }
    // Effective field data: prefer data column, else component_data
    if (!empty($postData['data']) && is_array($postData['data'])) {
        $fieldData = $postData['data'];
    } else {
        $legacy = $postData['component_data'] ?? [];
        $fieldData = isset($legacy['data']) && is_array($legacy['data']) ? $legacy['data'] : (is_array($legacy) ? $legacy : []);
    }
}

$schemaFields = [];
if (is_array($postTypeSchema)) {
    if (isset($postTypeSchema['fields']) && is_array($postTypeSchema['fields'])) {
        $schemaFields = $postTypeSchema['fields'];
    } else {
        $isList = array_keys($postTypeSchema) === array_keys(array_values($postTypeSchema));
        if ($isList) {
            $schemaFields = $postTypeSchema;
            $postTypeSchema = ['fields' => $schemaFields];
        }
    }
}
$useSchemaForm = !empty($schemaFields);

// Current layout (array of instances or field names) if stored
$currentLayout = [];
if (!empty($postData['layout']) && is_array($postData['layout'])) {
    $currentLayout = $postData['layout'];
}

// Normalize to canonical schema (ui, storage, output) per docs/FIELD_DEFINITION_SCHEMA.md
if ($useSchemaForm) {
    require_once CORE_PATH . '/fields/FieldSchemaMap.php';
    $normalizedSchema = \Modular\Fields\FieldSchemaMap::normalizeSchema(['fields' => $schemaFields]);
    $schemaFields = $normalizedSchema['fields'];

    $metaData = isset($postData['meta_data']) && is_array($postData['meta_data']) ? $postData['meta_data'] : [];
    $hydrateLegacyField = static function (array $fieldDef, array $metaData) {
        $fieldName = (string) ($fieldDef['name'] ?? $fieldDef['key'] ?? '');
        if ($fieldName === '') {
            return null;
        }
        $fieldType = (string) ($fieldDef['type'] ?? '');
        if ($fieldType === 'repeater') {
            $subFields = isset($fieldDef['sub_fields']) && is_array($fieldDef['sub_fields']) ? $fieldDef['sub_fields'] : [];
            if (empty($subFields)) {
                return array_key_exists($fieldName, $metaData) ? $metaData[$fieldName] : null;
            }
            $rowCount = 0;
            if (isset($metaData[$fieldName]) && is_scalar($metaData[$fieldName])) {
                $rowCount = max(0, (int) $metaData[$fieldName]);
            }
            if ($rowCount <= 0) {
                foreach ($metaData as $k => $v) {
                    if (preg_match('/^' . preg_quote($fieldName, '/') . '_(\d+)_/i', (string) $k, $m)) {
                        $rowCount = max($rowCount, ((int) $m[1]) + 1);
                    }
                }
            }
            if ($rowCount <= 0) {
                return null;
            }
            $rows = [];
            for ($i = 0; $i < $rowCount; $i++) {
                $row = [];
                foreach ($subFields as $subField) {
                    $subName = (string) ($subField['name'] ?? $subField['key'] ?? '');
                    if ($subName === '') {
                        continue;
                    }
                    $metaKey = $fieldName . '_' . $i . '_' . $subName;
                    if (array_key_exists($metaKey, $metaData)) {
                        $row[$subName] = $metaData[$metaKey];
                    }
                }
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }
            return !empty($rows) ? $rows : null;
        }
        if ($fieldType === 'flexible_content') {
            $layoutsRaw = $metaData[$fieldName] ?? null;
            if (is_string($layoutsRaw)) {
                $decoded = json_decode($layoutsRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $layoutsRaw = $decoded;
                }
            }
            if (!is_array($layoutsRaw) || empty($layoutsRaw)) {
                return null;
            }
            $rows = [];
            foreach (array_values($layoutsRaw) as $i => $layoutName) {
                $layoutKey = is_scalar($layoutName) ? (string) $layoutName : '';
                if ($layoutKey === '') {
                    continue;
                }
                $row = ['acf_fc_layout' => $layoutKey];
                $prefix = $fieldName . '_' . $i . '_';
                foreach ($metaData as $k => $v) {
                    if (strpos((string) $k, $prefix) !== 0) {
                        continue;
                    }
                    $subKey = substr((string) $k, strlen($prefix));
                    if ($subKey !== '') {
                        $row[$subKey] = $v;
                    }
                }
                $rows[] = $row;
            }
            return !empty($rows) ? $rows : null;
        }
        return array_key_exists($fieldName, $metaData) ? $metaData[$fieldName] : null;
    };

    $isSchemaValueEmpty = static function (array $fieldDef, $value): bool {
        if ($value === null || $value === '') {
            return true;
        }
        $type = (string) ($fieldDef['type'] ?? '');
        if ($type === 'flexible_content') {
            if (!is_array($value)) {
                return true;
            }
            if (isset($value['blocks']) && is_array($value['blocks'])) {
                return count($value['blocks']) === 0;
            }
            if ($value === []) {
                return true;
            }

            return false;
        }
        if ($type === 'repeater' || $type === 'gallery') {
            return !is_array($value) || count($value) === 0;
        }
        if ($type === 'group') {
            if (!is_array($value)) {
                return true;
            }
            foreach ($value as $v) {
                if ($v !== null && $v !== '' && !(is_array($v) && $v === [])) {
                    return false;
                }
            }

            return true;
        }
        if ($type === 'true_false') {
            return false;
        }

        return false;
    };

    if (!empty($metaData)) {
        foreach ($schemaFields as $f) {
            $fieldName = (string) ($f['name'] ?? $f['key'] ?? '');
            if ($fieldName === '') {
                continue;
            }
            $hydrated = $hydrateLegacyField($f, $metaData);
            if ($hydrated === null) {
                continue;
            }
            if (!array_key_exists($fieldName, $fieldData) || $isSchemaValueEmpty($f, $fieldData[$fieldName])) {
                $fieldData[$fieldName] = $hydrated;
            }
        }
    }

    // Modular layout: \"show_on_creation\" applies to NEW posts (first editor screen), not
    // to the admin when editing an existing post — editors must see every schema field.
    if (empty($currentLayout) && $postTypeMode === 'modular') {
        $showOnCreationNames = [];
        $allNames = [];
        foreach ($schemaFields as $f) {
            $name = $f['name'] ?? ($f['key'] ?? null);
            if (!$name) {
                continue;
            }
            $allNames[] = $name;
            if (!array_key_exists('show_on_creation', $f) || $f['show_on_creation']) {
                $showOnCreationNames[] = $name;
            }
        }
        if ($postId) {
            $baseNames = $allNames;
        } else {
            $baseNames = !empty($showOnCreationNames) ? $showOnCreationNames : $allNames;
        }

        $currentLayout = [];
        $i = 0;
        foreach ($baseNames as $n) {
            $i++;
            $currentLayout[] = [
                'id' => 'fi-init-' . $i . '-' . $n,
                'name' => $n,
            ];
        }
    } elseif ($postTypeMode === 'modular' && $postId && !empty($currentLayout) && is_array($currentLayout)) {
        $collectLayoutNames = static function (array $layout): array {
            $names = [];
            $walk = static function ($inst) use (&$names) {
                if (is_array($inst) && !empty($inst['name'])) {
                    $names[$inst['name']] = true;
                }
            };
            if (isset($layout['blocks']) && is_array($layout['blocks'])) {
                if (isset($layout['top']) && is_array($layout['top'])) {
                    foreach ($layout['top'] as $inst) {
                        $walk($inst);
                    }
                }
                foreach ($layout['blocks'] as $block) {
                    if (!is_array($block) || empty($block['columns']) || !is_array($block['columns'])) {
                        continue;
                    }
                    foreach ($block['columns'] as $col) {
                        if (!is_array($col)) {
                            continue;
                        }
                        foreach ($col as $inst) {
                            $walk($inst);
                        }
                    }
                }
                return $names;
            }
            if (isset($layout['columns']) && is_array($layout['columns'])) {
                foreach ($layout['columns'] as $col) {
                    if (!is_array($col)) {
                        continue;
                    }
                    foreach ($col as $inst) {
                        $walk($inst);
                    }
                }
                return $names;
            }
            $keys = array_keys($layout);
            if ($keys === range(0, count($layout) - 1)) {
                foreach ($layout as $inst) {
                    $walk($inst);
                }
            }
            return $names;
        };
        $present = $collectLayoutNames($currentLayout);
        $missing = [];
        foreach ($schemaFields as $f) {
            $name = $f['name'] ?? ($f['key'] ?? null);
            if ($name && empty($present[$name])) {
                $missing[] = $name;
            }
        }
        if ($missing !== []) {
            if (isset($currentLayout['blocks']) && is_array($currentLayout['blocks'])) {
                if (!isset($currentLayout['blocks'][0]) || !is_array($currentLayout['blocks'][0])) {
                    array_unshift($currentLayout['blocks'], [
                        'id' => 'block-admin-' . str_replace('.', '', uniqid('', true)),
                        'numCols' => 1,
                        'label' => 'Layout 1 Column',
                        'collapsed' => false,
                        'columns' => [[], [], []],
                    ]);
                }
                $b0 = &$currentLayout['blocks'][0];
                if (!isset($b0['columns']) || !is_array($b0['columns'])) {
                    $b0['columns'] = [[], [], []];
                }
                if (!isset($b0['columns'][0]) || !is_array($b0['columns'][0])) {
                    $b0['columns'][0] = [];
                }
                $mi = 0;
                foreach ($missing as $name) {
                    $mi++;
                    $b0['columns'][0][] = [
                        'id' => 'fi-admin-merge-' . $mi . '-' . $name,
                        'name' => $name,
                    ];
                }
                unset($b0);
            } elseif (isset($currentLayout['columns']) && is_array($currentLayout['columns']) && array_key_exists(0, $currentLayout['columns'])) {
                if (!is_array($currentLayout['columns'][0])) {
                    $currentLayout['columns'][0] = [];
                }
                $mi = 0;
                foreach ($missing as $name) {
                    $mi++;
                    $currentLayout['columns'][0][] = [
                        'id' => 'fi-admin-merge-' . $mi . '-' . $name,
                        'name' => $name,
                    ];
                }
            } else {
                $keys = array_keys($currentLayout);
                if ($keys === range(0, count($currentLayout) - 1)) {
                    $mi = 0;
                    foreach ($missing as $name) {
                        $mi++;
                        $currentLayout[] = [
                            'id' => 'fi-admin-merge-' . $mi . '-' . $name,
                            'name' => $name,
                        ];
                    }
                }
            }
        }
    }
}

// Load field system so schema form can render (FieldRegistry + field classes)
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
}
require_once CORE_PATH . '/functions/field-form.php';
if ($useSchemaForm) {
    foreach ($schemaFields as &$f) {
        $t = $f['type'] ?? '';
        if (in_array($t, ['post_object', 'relationship', 'relation'], true)) {
            $posts = get_posts_for_relational_field($f);
            $f['options'] = array_map(function ($p) {
                $label = ($p['title'] ?? '') . (isset($p['post_type_slug']) && $p['post_type_slug'] ? ' (' . $p['post_type_slug'] . ')' : '');
                return ['label' => $label, 'value' => (int)($p['id'] ?? 0)];
            }, $posts);
        }
    }
    unset($f);
}
$user_is_admin = function_exists('is_admin') && is_admin();
require_once ADMIN_PATH . '/templates/header.php';

$base_url = rtrim(SITE_URL ?? '', '/') . '/public/';
$preview_slug = $postData['slug'] ?? '';
$post_view_url = '';
$post_preview_url = '';
if ($postId && trim((string) ($postData['slug'] ?? '')) !== '') {
    $post_view_url = function_exists('get_permalink') ? get_permalink((int) $postId) : '';
    $post_preview_url = function_exists('get_post_preview_url') ? get_post_preview_url((int) $postId) : '';
}
// Build parent options for hierarchical post types
$parentOptions = [];
if ($postTypeId && $isHierarchicalPostType) {
    try {
        $stmtParents = $db->prepare("
            SELECT id, parent_id, title, slug, `order`
            FROM posts
            WHERE post_type_id = ? AND deleted_at IS NULL
            ORDER BY `order`, title
        ");
        $stmtParents->execute([$postTypeId]);
        $allPosts = $stmtParents->fetchAll(\PDO::FETCH_ASSOC);
        $byParent = [];
        foreach ($allPosts as $row) {
            if ($postId && (int)$row['id'] === (int)$postId) {
                continue;
            }
            $parentIdKey = $row['parent_id'] !== null ? (int)$row['parent_id'] : 0;
            if (!isset($byParent[$parentIdKey])) {
                $byParent[$parentIdKey] = [];
            }
            $byParent[$parentIdKey][] = $row;
        }
        $buildOptions = function ($parentId, $depth) use (&$buildOptions, $byParent, &$parentOptions) {
            if ($depth > 5) {
                return;
            }
            $bucketKey = $parentId !== null ? (int)$parentId : 0;
            if (empty($byParent[$bucketKey])) {
                return;
            }
            foreach ($byParent[$bucketKey] as $child) {
                $prefix = str_repeat('— ', max(0, $depth - 1));
                $label = $child['title'] !== '' ? $child['title'] : ($child['slug'] ?? '');
                $parentOptions[] = [
                    'id' => (int)$child['id'],
                    'label' => $prefix . $label,
                ];
                if ($depth < 3) {
                    $buildOptions($child['id'], $depth + 1);
                }
            }
        };
        $buildOptions(0, 1);
    } catch (\Throwable $e) {
        error_log('Error building parent options: ' . $e->getMessage());
        $parentOptions = [];
    }
}
?>

<link rel="stylesheet" href="<?php echo admin_url('assets/css/post-edit.css'); ?>">

<div class="post-edit-wrap">
    <div class="post-edit-header">
        <a href="<?php echo admin_url('posts') . ($postTypeId ? '?post_type_id=' . (int)$postTypeId : ''); ?>" class="btn btn-secondary btn-sm back-link">
            <i class="fas fa-arrow-left"></i> Back to Posts
        </a>
        <h1 class="post-edit-title-label"><?php echo $postId ? 'Edit post' : 'Add new'; ?></h1>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <form action="<?php echo admin_url('posts/save.php'); ?>" method="POST" id="post-edit-form">
        <?php echo csrf_field(); ?>
        <?php if ($postId): ?>
        <input type="hidden" name="id" value="<?php echo (int)$postId; ?>">
        <?php endif; ?>
        <input type="hidden" name="post_type_id" value="<?php echo (int)$postTypeId; ?>">
        <input type="hidden" name="slug" id="slug" value="<?php echo htmlspecialchars($preview_slug); ?>">

        <!-- Title: large editable, outside card -->
        <div class="post-edit-title-block">
            <input type="text" name="title" id="title" class="post-edit-title-input" value="<?php echo htmlspecialchars($postData['title'] ?? ''); ?>" placeholder="Enter title" required>
        </div>

        <!-- URL preview + slug mini editor -->
        <div class="post-edit-url-preview">
            <span class="post-edit-url-base"><?php echo htmlspecialchars($base_url); ?><?php echo htmlspecialchars($postTypeSlug ? $postTypeSlug . '/' : ''); ?></span>
            <span class="post-edit-url-slug" id="url-slug-display"><?php echo htmlspecialchars($preview_slug ?: 'post-slug'); ?></span>
            <button type="button" class="post-edit-slug-edit" id="slug-edit-btn" aria-label="Edit slug"><i class="fas fa-pencil-alt"></i></button>
            <span class="post-edit-slug-inline" id="slug-inline-wrap" style="display: none;">
                <input type="text" id="slug-inline-input" class="form-control post-edit-slug-input" value="<?php echo htmlspecialchars($preview_slug); ?>">
                <button type="button" id="slug-inline-done" class="btn btn-primary btn-sm">OK</button>
            </span>
        </div>
        <?php if ($postId && $post_view_url !== '' && $post_preview_url !== ''): ?>
        <div class="post-edit-public-links">
            <a href="<?php echo htmlspecialchars($post_view_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">View</a>
            <span class="post-edit-public-links-sep" aria-hidden="true">|</span>
            <a href="<?php echo htmlspecialchars($post_preview_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Preview</a>
        </div>
        <?php endif; ?>

    <div class="post-edit-body">
        <main class="post-edit-main">
            <div class="card" id="post-edit">
                <?php if ($useSchemaForm): ?>
                    <div class="post-edit-fields-head">
                        <h3 style="margin: 0;">Fields</h3>
                        <?php if ($user_is_admin): ?>
                        <p class="help-text" style="margin: 0.25rem 0 0;">These fields are defined in <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/field-builder/?id=<?php echo (int)$postTypeId; ?>">Configure fields</a> for this post type.</p>
                        <?php endif; ?>
                        <?php if ($postTypeMode === 'modular'): ?>
                        <div class="post-edit-fields-head-actions" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; flex-wrap: wrap;">
                            <button type="button" class="btn btn-secondary btn-sm" id="add-fields-btn">
                                <i class="fas fa-plus"></i> Add fields
                            </button>
                            <div class="add-layout-wrap" style="position: relative;">
                                <button type="button" class="btn btn-secondary btn-sm" id="add-layout-btn" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-columns"></i> Add layout
                                </button>
                                <div id="add-layout-menu" class="add-layout-dropdown" style="display: none; position: absolute; left: 0; top: 100%; margin-top: 2px; min-width: 10rem; background: var(--surface, #fff); border: 1px solid var(--border-color, #ddd); border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 100; padding: 4px;">
                                    <button type="button" class="add-layout-opt" data-cols="1">1 column</button>
                                    <button type="button" class="add-layout-opt" data-cols="2">2 columns</button>
                                    <button type="button" class="add-layout-opt" data-cols="3">3 columns</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" id="add-field-group-btn">
                                <i class="fas fa-layer-group"></i> Add field group
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="use_schema_form" value="1">
                    <input type="hidden" name="field_data_json" id="field_data_json" value="">
                    <?php
                    $fixedFieldGroups = [];
                    if (is_array($postTypeSchema) && isset($postTypeSchema['field_groups']) && is_array($postTypeSchema['field_groups'])) {
                        foreach ($postTypeSchema['field_groups'] as $fg) {
                            if (!is_array($fg)) continue;
                            $gid = isset($fg['id']) ? (string) $fg['id'] : '';
                            if ($gid === '') continue;
                            $fixedFieldGroups[] = [
                                'id' => $gid,
                                'label' => isset($fg['label']) ? (string) $fg['label'] : 'Field Group',
                                'items' => isset($fg['items']) && is_array($fg['items']) ? array_values($fg['items']) : [],
                                'shared_id' => isset($fg['shared_id']) ? (int) $fg['shared_id'] : null,
                                'locked' => isset($fg['locked']) ? (bool) $fg['locked'] : true,
                            ];
                        }
                    }
                    $sharedFieldGroupsIndex = [];
                    try {
                        $stmtShared = $db->query("SELECT id, name, definition FROM shared_fields WHERE type = 'field_group' AND deleted_at IS NULL ORDER BY name");
                        if ($stmtShared) {
                            foreach ($stmtShared->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                                $def = json_decode((string) ($row['definition'] ?? '[]'), true);
                                $items = (is_array($def) && isset($def['items']) && is_array($def['items'])) ? $def['items'] : [];
                                $label = (is_array($def) && isset($def['label'])) ? (string) $def['label'] : (string) ($row['name'] ?? 'Field Group');
                                $sharedFieldGroupsIndex[] = [
                                    'id' => (int) $row['id'],
                                    'name' => (string) $row['name'],
                                    'label' => $label,
                                    'items' => $items,
                                ];
                            }
                        }
                    } catch (\Throwable $e) { /* ignore */ }
                    ?>
                    <div class="post-edit-fields-area <?php echo $postTypeMode === 'modular' ? 'post-edit-fields-draggable' : ''; ?>" id="post-edit-fields-area" data-fixed="<?php echo $postTypeMode === 'fixed' ? '1' : '0'; ?>">
                        <script type="application/json" id="modular-field-schema-json"><?php echo json_encode(['fields' => $schemaFields]); ?></script>
                        <script type="application/json" id="modular-field-data-json"><?php echo json_encode($fieldData); ?></script>
                        <script type="application/json" id="modular-field-layout-json"><?php echo json_encode($currentLayout); ?></script>
                        <script type="application/json" id="modular-fixed-field-groups-json"><?php echo json_encode($fixedFieldGroups); ?></script>
                        <script type="application/json" id="modular-shared-field-groups-json"><?php echo json_encode($sharedFieldGroupsIndex); ?></script>
                        <div id="post-edit-fields-vue-app" class="modular-field-renderer-container">
                            <noscript>
                                <p class="help-text">JavaScript is required for the field editor. Please enable it and refresh.</p>
                            </noscript>
                        </div>
                        <div id="post-edit-fields-php-fallback" style="display: none;">
                            <?php render_schema_fields_form($schemaFields, $fieldData, 'field_data'); ?>
                        </div>
                    </div>
                    <script>
                    (function(){
                        function parseJsonScript(id, fallback) {
                            var el = document.getElementById(id);
                            if (!el || !el.textContent) return fallback;
                            try { return JSON.parse(el.textContent.trim()); } catch (e) { return fallback; }
                        }
                        window.MODULAR_FIELD_SCHEMA = parseJsonScript('modular-field-schema-json', { fields: [] });
                        window.MODULAR_FIELD_DATA = parseJsonScript('modular-field-data-json', {});
                        window.MODULAR_FIELD_LAYOUT = parseJsonScript('modular-field-layout-json', null);
                        window.MODULAR_FIXED_GROUPS = parseJsonScript('modular-fixed-field-groups-json', []);
                        window.MODULAR_SHARED_GROUPS_INDEX = parseJsonScript('modular-shared-field-groups-json', []);
                        window.MODULAR_BASE_URL = '<?php echo defined("BASE_PATH") ? BASE_PATH : "/modular"; ?>';
                        window.MODULAR_TINYMCE_BASE_URL = '<?php echo defined("ADMIN_URL") ? rtrim(ADMIN_URL, "/") . "/assets/vendor/tinymce" : "/modular/admin/assets/vendor/tinymce"; ?>';
                        window.MODULAR_CSRF_TOKEN = '<?php echo defined("BASE_PATH") && function_exists("csrf_token") ? csrf_token() : ""; ?>';
                        window.MODULAR_POST_ID = <?php echo (int)$postId; ?>;
                        window.MODULAR_POST_TYPE_ID = <?php echo (int)$postTypeId; ?>;
                    })();
                    </script>
                    <script>
                    (function(){
                        setTimeout(function checkMounted() {
                            var root = document.getElementById('post-edit-fields-vue-app');
                            if (!root) return;
                            if (root.querySelector('.modular-field-renderer-root')) return;
                            var fallback = document.getElementById('modular-fields-load-fallback');
                            if (fallback) fallback.style.display = 'block';
                        }, 2500);
                    })();
                    </script>
                    <script>
                    (function(){
                        var form = document.getElementById('post-edit-form');
                        if (!form) return;
                        form.addEventListener('submit', function(e) {
                            if (!window.ModularFieldRendererApp || typeof window.ModularFieldRendererApp._serialize !== 'function') return;
                            var jsonInput = document.getElementById('field_data_json');
                            if (!jsonInput) return;
                            window.ModularFieldRendererApp._serialize();
                            if (!jsonInput.value || jsonInput.value === '') {
                                e.preventDefault();
                                setTimeout(function() {
                                    window.ModularFieldRendererApp._serialize();
                                    if (jsonInput.value) form.submit();
                                }, 50);
                            }
                        }, true);
                    })();
                    </script>
                    <div id="modular-fields-load-fallback" style="display: none; padding: 1rem; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; color: #92400e; margin-top: 0.5rem;">
                        <p><strong>Velden zijn niet geladen.</strong> Vernieuw de pagina. Als het probleem blijft: controleer de browserconsole op foutmeldingen.</p>
                    </div>
                <?php else: ?>
                    <h3 style="margin-top: 0;">Component Data</h3>
                    <div class="form-group">
                        <label for="component_data">Component Data (JSON)</label>
                        <textarea name="component_data" id="component_data" class="form-control" rows="8" style="font-family: monospace; font-size: 13px;"><?php echo htmlspecialchars(json_encode($postData['component_data'] ?? [], JSON_PRETTY_PRINT)); ?></textarea>
                        <div class="help-text">Component data in JSON format.</div>
                    </div>
                <?php endif; ?>

            </div>
        </main>

        <aside class="post-edit-sidebar">
            <?php
            $availableTemplates = function_exists('get_theme_templates') ? get_theme_templates() : [ '' => 'Default' ];
            $templateSelectedValue = isset($postData['template']) ? trim((string) $postData['template']) : '';
            if (
                $templateSelectedValue === ''
                && ($postTypeSlug ?? '') === 'page'
                && !empty($postData['slug'])
                && function_exists('resolve_page_template_key_for_slug')
            ) {
                $slugTemplate = resolve_page_template_key_for_slug((string) $postData['slug']);
                if ($slugTemplate !== '') {
                    $templateSelectedValue = $slugTemplate;
                    if (!array_key_exists($slugTemplate, $availableTemplates)) {
                        $availableTemplates[$slugTemplate] = ucfirst(str_replace(['_', '-'], ' ', $slugTemplate));
                    }
                }
            }
            $pubDate = $postData['published_at'] ?? null;
            $pubDateLocal = $pubDate ? date('Y-m-d\TH:i', strtotime($pubDate)) : '';
            $vis = $postData['visibility'] ?? 'public';
            $postPassword = $postData['password'] ?? '';
            $currentParentId = isset($postData['parent_id']) ? (int)$postData['parent_id'] : 0;
            ?>
            <div class="card">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control post-edit-status-select" aria-label="Post status">
                        <option value="draft" <?php echo ($postData['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($postData['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="scheduled" <?php echo ($postData['status'] ?? '') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="archived" <?php echo ($postData['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="published_at">Publication date</label>
                    <input type="datetime-local" name="published_at" id="published_at" class="form-control" value="<?php echo htmlspecialchars($pubDateLocal); ?>" aria-label="Publication date and time">
                    <div class="help-text">Past or future. Future + Published will be treated as scheduled.</div>
                </div>
                <div class="form-group">
                    <label for="template">Template</label>
                    <select name="template" id="template" class="form-control" aria-label="Page template">
                        <?php foreach ($availableTemplates as $tplKey => $tplLabel): ?>
                        <option value="<?php echo htmlspecialchars($tplKey); ?>" <?php echo ((string) $templateSelectedValue === (string) $tplKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tplLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="help-text">Templates are theme PHP files with <code>Template Name: …</code> in a comment block.</div>
                </div>
                <?php if ($isHierarchicalPostType): ?>
                <div class="form-group">
                    <label for="parent_id">Parent</label>
                    <select name="parent_id" id="parent_id" class="form-control" aria-label="Parent page">
                        <option value="0">(No parent)</option>
                        <?php foreach ($parentOptions as $opt): ?>
                            <option value="<?php echo (int)$opt['id']; ?>" <?php echo $currentParentId === (int)$opt['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="help-text">Kies een parent voor hiërarchische navigatie. Maximaal 3 niveaus diep.</div>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="visibility">Visibility</label>
                    <select name="visibility" id="visibility" class="form-control" aria-label="Post visibility">
                        <option value="public" <?php echo $vis === 'public' ? 'selected' : ''; ?>>Public</option>
                        <option value="private" <?php echo $vis === 'private' ? 'selected' : ''; ?>>Private</option>
                        <option value="password" <?php echo $vis === 'password' ? 'selected' : ''; ?>>Password protected</option>
                    </select>
                </div>
                <div class="form-group post-edit-password-wrap" id="post-edit-password-wrap" style="<?php echo $vis === 'password' ? '' : 'display:none;'; ?>">
                    <label for="post_password">Post password</label>
                    <input type="text" name="post_password" id="post_password" class="form-control" value="<?php echo htmlspecialchars($postPassword); ?>" autocomplete="off" placeholder="Enter password to view">
                </div>
                <div class="form-actions post-edit-sidebar-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save post
                    </button>
                    <?php if (!empty($postId)): ?>
                    <a href="<?php echo admin_url('posts/delete.php?id=' . (int) $postId . ($postTypeId ? '&post_type_id=' . (int) $postTypeId : '')); ?>" class="btn btn-secondary post-edit-remove-btn" data-confirm="Move this post to trash?">
                        <i class="fas fa-trash-alt"></i> Remove post
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (function_exists('do_action')) { do_action('post_edit_sidebar', $postId, (int)$postTypeId); } ?>
        </aside>
        </div>
        </form>
    </div>

    <!-- Add fields modal (modular + admin only) -->
<div id="add-fields-modal" class="post-edit-modal-overlay" style="display: none;" aria-hidden="true">
    <div class="post-edit-modal-panel">
        <div class="post-edit-modal-header">
            <h3>Add fields</h3>
            <button type="button" class="btn btn-secondary btn-sm" id="add-fields-modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="post-edit-modal-body">
            <p class="help-text">Click a field below to add it to this post. You can reorder fields by dragging them in the main editor.</p>
            <div class="post-edit-modal-library">
                <?php foreach ($schemaFields as $f): ?>
                    <?php
                    $name = $f['name'] ?? ($f['key'] ?? '');
                    if (!$name) continue;
                    $inLayout = in_array($name, $currentLayout ?? [], true);
                    ?>
                    <button type="button"
                            class="post-edit-modal-library-item"
                            data-add-field-name="<?php echo htmlspecialchars($name); ?>">
                        <span class="field-library-drag-handle" aria-hidden="true">
                            <i class="fas fa-grip-vertical"></i>
                        </span>
                        <strong><?php echo htmlspecialchars($f['label'] ?? $name); ?></strong>
                        <span class="field-type-label">
                            <?php echo htmlspecialchars($f['type'] ?? ''); ?>
                            <?php if ($inLayout): ?> · In use<?php endif; ?>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
            <p class="help-text" style="margin-top:0.75rem;">
                To change which field types are available for this post type, use Configure fields in the Post Type editor.
            </p>
        </div>
    </div>
</div>

<!-- Add field group modal -->
<div id="add-field-group-modal" class="post-edit-modal-overlay" style="display: none;" aria-hidden="true">
    <div class="post-edit-modal-panel">
        <div class="post-edit-modal-header">
            <h3>Add field group</h3>
            <button type="button" class="btn btn-secondary btn-sm" id="add-field-group-modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="post-edit-modal-body">
            <div class="field-group-modal-tabs" role="tablist">
                <button type="button" class="field-group-modal-tab is-active" data-fg-tab="create" role="tab" aria-selected="true">Create new</button>
                <button type="button" class="field-group-modal-tab" data-fg-tab="attach" role="tab" aria-selected="false">Attach existing</button>
            </div>

            <div class="field-group-modal-pane is-active" data-fg-pane="create">
                <div class="form-group">
                    <label for="fg-create-label">Label</label>
                    <input type="text" id="fg-create-label" class="form-control" placeholder="e.g. Brand &amp; status">
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-primary btn-sm" id="fg-create-submit">
                        <i class="fas fa-plus"></i> Create field group
                    </button>
                    <p class="help-text" style="margin-top: 0.5rem;">
                        The new group starts <strong>local</strong> (only on this post). Drag existing fields in and add tabs to organize.
                        Use the <i class="fas fa-lock" style="font-size:0.85em;"></i> Promote menu on the group header to make it
                        <strong>Fixed for this post type</strong> or <strong>Share across post types</strong>.
                    </p>
                </div>
            </div>

            <div class="field-group-modal-pane" data-fg-pane="attach" style="display: none;">
                <p class="help-text">Field groups already defined for this post type or shared across the studio.</p>
                <div class="field-group-modal-list" id="fg-attach-list">
                    <p class="help-text" style="opacity: 0.7;">Loading…</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirm before removing a field that has content -->
<div id="modular-remove-confirm-modal" class="modular-confirm-overlay" style="display: none;" aria-hidden="true">
    <div class="modular-confirm-dialog">
        <p class="modular-confirm-text">Dit veld bevat inhoud. Weet je zeker dat je het wilt verwijderen?</p>
        <div class="modular-confirm-actions">
            <button type="button" class="btn btn-secondary" id="modular-remove-cancel">Annuleren</button>
            <button type="button" class="btn btn-primary btn-danger" id="modular-remove-confirm">Verwijderen</button>
        </div>
    </div>
</div>

<script>
(function() {
    var titleEl = document.getElementById('title');
    var slugHidden = document.getElementById('slug');
    var slugDisplay = document.getElementById('url-slug-display');
    var slugEditBtn = document.getElementById('slug-edit-btn');
    var slugInlineWrap = document.getElementById('slug-inline-wrap');
    var slugInlineInput = document.getElementById('slug-inline-input');
    var slugInlineDone = document.getElementById('slug-inline-done');

    function slugify(s) {
        return String(s).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'post';
    }

    function updateSlugFromTitle() {
        if (!slugHidden.value || slugHidden.value === slugify(titleEl.value)) {
            slugHidden.value = slugify(titleEl.value);
            slugDisplay.textContent = slugHidden.value || 'post-slug';
            if (slugInlineWrap.style.display !== 'none') slugInlineInput.value = slugHidden.value;
        }
    }

    titleEl.addEventListener('input', updateSlugFromTitle);

    slugEditBtn.addEventListener('click', function() {
        slugInlineInput.value = slugHidden.value;
        slugDisplay.style.display = 'none';
        slugEditBtn.style.display = 'none';
        slugInlineWrap.style.display = 'inline-flex';
        slugInlineInput.focus();
    });

    function closeSlugInline() {
        var v = slugify(slugInlineInput.value) || 'post';
        slugHidden.value = v;
        slugDisplay.textContent = v;
        slugDisplay.style.display = '';
        slugEditBtn.style.display = '';
        slugInlineWrap.style.display = 'none';
    }

    slugInlineDone.addEventListener('click', closeSlugInline);
    slugInlineInput.addEventListener('blur', closeSlugInline);
    slugInlineInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); closeSlugInline(); }
    });

    // Add fields modal
    var addBtn = document.getElementById('add-fields-btn');
    var modal = document.getElementById('add-fields-modal');
    var modalClose = document.getElementById('add-fields-modal-close');
    if (addBtn && modal) {
        addBtn.addEventListener('click', function() {
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
        });
        function closeModal() {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }
        if (modalClose) modalClose.addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }

    // Visibility: show/hide post password field
    var visibilitySelect = document.getElementById('visibility');
    var passwordWrap = document.getElementById('post-edit-password-wrap');
    if (visibilitySelect && passwordWrap) {
        function togglePasswordWrap() {
            passwordWrap.style.display = visibilitySelect.value === 'password' ? '' : 'none';
        }
        visibilitySelect.addEventListener('change', togglePasswordWrap);
    }
})();
</script>

<?php
$admin_assets = rtrim(admin_url(), '/') . '/assets';
$tinymce_script = $admin_assets . '/vendor/tinymce/tinymce.min.js';
$additional_scripts = $useSchemaForm
    ? '<link rel="stylesheet" href="' . htmlspecialchars($admin_assets) . '/js/wysiwyg-field-bundle.css?v=1">'
    . '<script src="' . htmlspecialchars($tinymce_script) . '"></script>'
    . '<script src="' . htmlspecialchars($admin_assets) . '/js/wysiwyg-field-bundle.js?v=1"></script>'
    . '<script src="' . htmlspecialchars($admin_assets) . '/js/field-renderer/resolveFieldComponent.js?v=1"></script>'
    . '<script src="' . htmlspecialchars($admin_assets) . '/js/field-renderer/drag-manager.js?v=1"></script>'
    . '<script src="' . htmlspecialchars($admin_assets) . '/js/field-renderer/field-renderer-app.js?v=7"></script>'
    . '<script src="' . htmlspecialchars($admin_assets) . '/js/post-edit-fields-draggable.js?v=4"></script>'
    : '<script src="' . htmlspecialchars($admin_assets) . '/js/primevue-fields.js?v=3"></script>';
require_once ADMIN_PATH . '/templates/footer.php';
?>
