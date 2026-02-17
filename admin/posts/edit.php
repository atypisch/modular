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

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$page_title = isset($_GET['id']) ? 'Edit Post' : 'Add New Post';
$posts_active = true;

$db = $GLOBALS['db'];
$postId = $_GET['id'] ?? null;
$postTypeId = $_GET['post_type_id'] ?? null;

// Post type slug/name for URL preview and heading (loaded when we have postTypeId)
$postTypeSlug = '';
$postTypeName = '';

// Load existing post if editing
$postData = [
    'post_type_id' => $postTypeId ?? '',
    'title' => '',
    'slug' => '',
    'status' => 'draft',
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
            $postData['layout'] = isset($post['layout']) && $post['layout'] !== '' ? json_decode($post['layout'], true) : null;
            $postTypeId = $post['post_type_id'];
            $postTypeName = $post['post_type_name'] ?? '';
            $postTypeSlug = $post['post_type_slug'] ?? '';
        } else {
            $_SESSION['error'] = 'Post not found.';
            header('Location: /modular/admin/posts');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error loading post: ' . $e->getMessage();
        header('Location: /modular/admin/posts');
        exit;
    }
}

// When adding new, post type must be in URL
if (!$postId && !$postTypeId) {
    $_SESSION['error'] = 'Post type is required.';
    header('Location: /modular/admin/posts');
    exit;
}

// Load current post type schema (for selected post type) to decide schema form vs legacy JSON
$postTypeSchema = null;
$postTypeMode = 'fixed';
$fieldData = [];
if ($postTypeId) {
    // Use schema from post row when we already have it (edit), else fetch
    if (!empty($postData['post_type_schema'])) {
        $postTypeSchema = json_decode($postData['post_type_schema'], true);
        $postTypeMode = $postData['post_type_mode'] ?? 'fixed';
    }
    if ($postTypeSchema === null) {
        try {
            $stmt = $db->prepare("SELECT name, slug, mode, `schema` FROM post_types WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$postTypeId]);
            $ptRow = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($ptRow) {
                $postTypeName = $ptRow['name'] ?? '';
                $postTypeSlug = $ptRow['slug'] ?? '';
                $postTypeMode = $ptRow['mode'] ?? 'fixed';
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

$schemaFields = ($postTypeSchema && isset($postTypeSchema['fields']) && is_array($postTypeSchema['fields'])) ? $postTypeSchema['fields'] : [];
$useSchemaForm = !empty($schemaFields);

// Normalize to canonical schema (ui, storage, output) per docs/FIELD_DEFINITION_SCHEMA.md
if ($useSchemaForm) {
    require_once CORE_PATH . '/fields/FieldSchemaMap.php';
    $normalizedSchema = \Modular\Fields\FieldSchemaMap::normalizeSchema(['fields' => $schemaFields]);
    $schemaFields = $normalizedSchema['fields'];
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
?>

<link rel="stylesheet" href="/modular/admin/assets/css/post-edit.css">

<div class="post-edit-wrap">
    <div class="post-edit-header">
        <a href="/modular/admin/posts<?php echo $postTypeId ? '?post_type_id=' . (int)$postTypeId : ''; ?>" class="btn btn-secondary btn-sm back-link">
            <i class="fas fa-arrow-left"></i> Back to Posts
        </a>
        <h1 class="post-edit-title-label"><?php echo $postId ? 'Edit post' : 'Add new'; ?></h1>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form action="/modular/admin/posts/save.php" method="POST" id="post-edit-form">
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

    <div class="post-edit-body">
        <main class="post-edit-main">
            <div class="card">
                <?php if ($useSchemaForm): ?>
                    <div class="post-edit-fields-head">
                        <h3 style="margin: 0;">Fields</h3>
                        <?php if ($user_is_admin): ?>
                        <p class="help-text" style="margin: 0.25rem 0 0;">These fields are defined in <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/field-builder/?id=<?php echo (int)$postTypeId; ?>">Configure fields</a> for this post type.</p>
                        <?php endif; ?>
                        <?php if ($user_is_admin && $postTypeMode === 'modular'): ?>
                        <button type="button" class="btn btn-secondary btn-sm" id="add-fields-btn" style="margin-top: 0.5rem;">
                            <i class="fas fa-plus"></i> Add fields
                        </button>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="use_schema_form" value="1">
                    <input type="hidden" name="field_data_json" id="field_data_json" value="">
                    <div class="post-edit-fields-area <?php echo $postTypeMode === 'modular' ? 'post-edit-fields-draggable' : ''; ?>" id="post-edit-fields-area" data-fixed="<?php echo $postTypeMode === 'fixed' ? '1' : '0'; ?>">
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
                    window.MODULAR_FIELD_SCHEMA = <?php echo json_encode(['fields' => $schemaFields]); ?>;
                    window.MODULAR_FIELD_DATA = <?php echo json_encode($fieldData); ?>;
                    window.MODULAR_FIELD_OPTIONS = {};
                    </script>
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
            <div class="card">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="draft" <?php echo ($postData['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($postData['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="archived" <?php echo ($postData['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                <div class="form-actions post-edit-sidebar-actions">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> Save post
                    </button>
                    <a href="/modular/admin/posts<?php echo $postTypeId ? '?post_type_id=' . (int)$postTypeId : ''; ?>" class="btn btn-secondary" style="width: 100%; text-align: center;">Cancel</a>
                </div>
            </div>
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
            <p class="help-text">To add new field types to this post type, define them in Configure fields. Existing fields below can be reordered when this post type is in modular mode.</p>
            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/field-builder/?id=<?php echo (int)$postTypeId; ?>" class="btn btn-primary btn-sm btn-inline" style="margin-top: 0.5rem;">
                <i class="fas fa-cogs"></i> Configure fields
            </a>
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
})();
</script>

<?php
$additional_scripts = $useSchemaForm
    ? '<script src="/modular/admin/assets/js/field-renderer/resolveFieldComponent.js?v=1"></script><script src="/modular/admin/assets/js/field-renderer/field-renderer-app.js?v=1"></script>'
    : '<script src="/modular/admin/assets/js/primevue-fields.js?v=3"></script>';
require_once ADMIN_PATH . '/templates/footer.php';
?>
