<?php
/**
 * Field Builder — ACF-equivalent schema builder for Post Types.
 * Two columns: Field Library (left), Active Schema (right). Config modal for each field.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/fields/FieldInterface.php';
require_once CORE_PATH . '/fields/LayoutFieldInterface.php';
require_once CORE_PATH . '/fields/FieldRegistry.php';
require_once CORE_PATH . '/fields/TextField.php';
require_once CORE_PATH . '/fields/TextareaField.php';
require_once CORE_PATH . '/fields/NumberField.php';
require_once CORE_PATH . '/fields/EmailField.php';
require_once CORE_PATH . '/fields/URLField.php';
require_once CORE_PATH . '/fields/PasswordField.php';
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

use Modular\Fields\FieldRegistry;

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['error'] = 'Invalid post type.';
    header('Location: /modular/admin/post-types');
    exit;
}

$db = $GLOBALS['db'];
$stmt = $db->prepare("SELECT id, name, slug, mode, `schema` FROM post_types WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$postType = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$postType) {
    $_SESSION['error'] = 'Post type not found.';
    header('Location: /modular/admin/post-types');
    exit;
}

FieldRegistry::bootDefaultFields();
$fieldLibrary = FieldRegistry::listForAdminGrouped();
$categoryOrder = ['basic', 'content', 'choice', 'relational', 'media', 'date_time', 'layout', 'utility'];
$categoryLabels = [
    'basic' => 'Basic',
    'content' => 'Content',
    'choice' => 'Choice',
    'relational' => 'Relational',
    'media' => 'Media',
    'date_time' => 'Date & Time',
    'layout' => 'Layout',
    'utility' => 'Utility',
];

$schema = $postType['schema'];
if (is_string($schema)) {
    $schema = json_decode($schema, true);
}
$schema = is_array($schema) ? $schema : ['fields' => []];
$mode = $postType['mode'] ?? 'fixed';

$page_title = 'Field Builder: ' . htmlspecialchars($postType['name']);
$post_types_active = true;
require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header" style="margin-bottom: var(--spacing-lg);">
        <div class="flex justify-between items-center">
            <div>
                <a href="/modular/admin/post-types/edit.php?id=<?php echo (int)$id; ?>" class="btn btn-secondary btn-sm" style="margin-bottom: 0.5rem;">
                    <i class="fas fa-arrow-left"></i> Back to Post Type
                </a>
                <h1 style="margin: 0;"><?php echo $page_title; ?></h1>
                <p style="margin: 0.5rem 0 0; color: var(--text-muted);">Define the field schema for this post type.</p>
            </div>
            <div class="flex gap-2">
                <label class="flex align-center gap-2">
                    <span>Mode:</span>
                    <select id="schema-mode" class="form-control" style="width: auto;">
                        <option value="fixed" <?php echo $mode === 'fixed' ? 'selected' : ''; ?>>Fixed</option>
                        <option value="modular" <?php echo $mode === 'modular' ? 'selected' : ''; ?>>Modular</option>
                    </select>
                </label>
                <button type="button" id="save-schema-btn" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save schema
                </button>
            </div>
        </div>
    </div>

    <div class="field-builder-layout" style="display: grid; grid-template-columns: 280px 1fr; gap: var(--spacing-lg); min-height: 70vh;">
        <!-- LEFT: Field Library -->
        <div class="field-library card" style="padding: var(--spacing-md); overflow-y: auto;">
            <h3 style="margin: 0 0 var(--spacing-md); font-size: 1rem;">Field Library</h3>
            <p class="help-text" style="margin-bottom: var(--spacing-md);">Drag fields into the active schema.</p>
            <?php foreach ($categoryOrder as $cat): ?>
                <?php if (empty($fieldLibrary[$cat])) continue; ?>
                <div class="field-category" data-category="<?php echo htmlspecialchars($cat); ?>">
                    <h4 class="field-category-title" style="font-size: 0.9rem; margin: var(--spacing-sm) 0; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-chevron-down toggle-icon"></i>
                        <?php echo htmlspecialchars($categoryLabels[$cat] ?? ucfirst($cat)); ?>
                    </h4>
                    <div class="field-category-list">
                        <?php foreach ($fieldLibrary[$cat] as $f): ?>
                            <div class="field-library-item" draggable="true" data-type="<?php echo htmlspecialchars($f['type']); ?>" data-label="<?php echo htmlspecialchars($f['label']); ?>" data-icon="<?php echo htmlspecialchars($f['icon'] ?? 'fa-circle'); ?>">
                                <i class="fas <?php echo htmlspecialchars($f['icon'] ?? 'fa-circle'); ?>"></i>
                                <span><?php echo htmlspecialchars($f['label']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- RIGHT: Active Schema -->
        <div class="active-schema card" style="padding: var(--spacing-md); display: flex; flex-direction: column;">
            <h3 style="margin: 0 0 var(--spacing-md); font-size: 1rem;">Active fields</h3>
            <div id="active-fields-list" class="active-fields-list" style="flex: 1; min-height: 200px; border: 2px dashed var(--border-color); border-radius: var(--radius-md); padding: var(--spacing-md);">
                <!-- placeholder when empty -->
                <div id="active-fields-empty" class="empty-state" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                    <i class="fas fa-mouse-pointer" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    <p>Drag fields here or add from the library.</p>
                </div>
                <ul id="active-fields-ul" class="active-fields-ul" style="list-style: none; margin: 0; padding: 0; display: none;"></ul>
            </div>
        </div>
    </div>
</div>

<!-- Field config modal -->
<div id="field-config-modal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-content card" style="max-width: 520px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div class="flex justify-between items-center" style="margin-bottom: var(--spacing-md);">
            <h3 style="margin: 0;">Field settings</h3>
            <button type="button" id="modal-close" class="btn btn-secondary btn-sm">&times;</button>
        </div>
        <form id="field-config-form">
            <input type="hidden" id="config-field-index" value="">
            <div class="form-group">
                <label for="config-label">Label <span class="required">*</span></label>
                <input type="text" id="config-label" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="config-name">Name (key)</label>
                <input type="text" id="config-name" class="form-control" placeholder="snake_case">
                <div class="help-text">Unique key for this field. Auto-generated from label if empty.</div>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="config-required" value="1"> Required
                </label>
            </div>
            <div class="form-group">
                <label for="config-instructions">Instructions / Help text</label>
                <textarea id="config-instructions" class="form-control" rows="2"></textarea>
            </div>
            <div id="config-extra" class="form-group">
                <!-- Field-specific options rendered by JS -->
            </div>
            <div class="form-actions" style="margin-top: var(--spacing-lg);">
                <button type="button" id="modal-cancel" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save field</button>
            </div>
        </form>
    </div>
</div>

<style>
.field-library-item { padding: 0.5rem 0.75rem; margin: 0.25rem 0; border-radius: var(--radius-sm); cursor: grab; display: flex; align-items: center; gap: 0.5rem; background: var(--surface); border: 1px solid var(--border-color); }
.field-library-item:hover { background: var(--hover-bg); }
.field-library-item.dragging { opacity: 0.6; }
.field-library-item i { color: var(--text-muted); width: 1.25rem; }
.active-field-row { display: flex; align-items: center; gap: var(--spacing-sm); padding: 0.5rem 0.75rem; margin: 0.25rem 0; background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-sm); }
.active-field-row .drag-handle { cursor: grab; color: var(--text-muted); }
.active-field-row .field-label { flex: 1; font-weight: 500; }
.active-field-row .field-type { font-size: 0.75rem; color: var(--text-muted); }
.active-field-row .required-badge { font-size: 0.7rem; color: var(--error-text); margin-left: 0.25rem; }
.active-field-row .btn-icon { padding: 0.25rem 0.5rem; background: none; border: none; cursor: pointer; color: var(--text-muted); }
.active-field-row .btn-icon:hover { color: var(--text); }
.field-category-list { margin-left: 1rem; }
.field-category-list.collapsed { display: none; }
#active-fields-list.drop-active { border-color: var(--primary-color); background: rgba(45, 80, 22, 0.04); }
</style>

<script>
(function() {
    function run() {
    const postTypeId = <?php echo (int)$id; ?>;
    const fieldLibrary = <?php echo json_encode($fieldLibrary); ?>;
    const categoryOrder = <?php echo json_encode($categoryOrder); ?>;

    let activeFields = <?php echo json_encode($schema['fields'] ?? []); ?>;

    const emptyEl = document.getElementById('active-fields-empty');
    const listEl = document.getElementById('active-fields-ul');
    const dropZone = document.getElementById('active-fields-list');
    if (!dropZone || !listEl) return;
    const modal = document.getElementById('field-config-modal');
    const form = document.getElementById('field-config-form');
    const LIBRARY_DROP_TYPE = 'application/x-modular-field';

    function slugify(s) {
        return String(s).toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '') || 'field';
    }

    function findFieldDef(type) {
        for (const cat of categoryOrder) {
            const list = fieldLibrary[cat];
            if (!list) continue;
            const f = list.find(x => x.type === type);
            if (f) return f;
        }
        return null;
    }

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function renderActiveFields() {
        listEl.innerHTML = '';
        if (activeFields.length === 0) {
            emptyEl.style.display = 'block';
            listEl.style.display = 'none';
            return;
        }
        emptyEl.style.display = 'none';
        listEl.style.display = 'block';
        activeFields.forEach((field, index) => {
            const li = document.createElement('li');
            li.className = 'active-field-row';
            li.dataset.index = String(index);
            const def = findFieldDef(field.type);
            const icon = (def && def.icon) ? def.icon : 'fa-circle';
            const label = field.label || field.name || field.type;
            li.innerHTML = [
                '<span class="drag-handle" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></span>',
                '<span class="field-label"><i class="fas ' + icon + '"></i> ' + escapeHtml(label) + (field.required ? '<span class="required-badge">*</span>' : '') + '</span>',
                '<span class="field-type">' + escapeHtml(field.type) + '</span>',
                '<button type="button" class="btn-icon edit-field" title="Edit"><i class="fas fa-cog"></i></button>',
                '<button type="button" class="btn-icon duplicate-field" title="Duplicate"><i class="fas fa-copy"></i></button>',
                '<button type="button" class="btn-icon delete-field" title="Delete"><i class="fas fa-trash-alt"></i></button>'
            ].join('');
            listEl.appendChild(li);
        });
    }

    function openModal(index) {
        document.getElementById('config-field-index').value = String(index);
        const field = index >= 0 && index < activeFields.length ? activeFields[index] : null;
        document.getElementById('config-label').value = field ? (field.label || '') : '';
        document.getElementById('config-name').value = field ? (field.name || '') : '';
        document.getElementById('config-required').checked = field ? !!field.required : false;
        document.getElementById('config-instructions').value = field ? (field.instructions || '') : '';
        document.getElementById('config-extra').innerHTML = '';
        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    // —— Single delegation: active list (edit, duplicate, delete, reorder) ——
    listEl.addEventListener('click', function(e) {
        const row = e.target.closest('li.active-field-row');
        if (!row) return;
        const idx = parseInt(row.dataset.index, 10);
        if (e.target.closest('.edit-field')) {
            e.preventDefault();
            openModal(idx);
            return;
        }
        if (e.target.closest('.duplicate-field')) {
            e.preventDefault();
            const copy = JSON.parse(JSON.stringify(activeFields[idx]));
            copy.name = (copy.name || copy.label || 'field') + '_copy';
            copy.label = (copy.label || 'Field') + ' (copy)';
            activeFields.splice(idx + 1, 0, copy);
            renderActiveFields();
            return;
        }
        if (e.target.closest('.delete-field')) {
            e.preventDefault();
            activeFields.splice(idx, 1);
            renderActiveFields();
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const index = parseInt(document.getElementById('config-field-index').value, 10);
        const label = document.getElementById('config-label').value.trim();
        const name = document.getElementById('config-name').value.trim() || slugify(label);
        const required = document.getElementById('config-required').checked;
        const instructions = document.getElementById('config-instructions').value.trim();
        const field = (index >= 0 && index < activeFields.length) ? activeFields[index] : { type: 'text' };
        field.label = label;
        field.name = name;
        field.required = required;
        field.instructions = instructions || undefined;
        if (index < 0) {
            activeFields.push(field);
        } else {
            activeFields[index] = field;
        }
        renderActiveFields();
        closeModal();
    });

    document.getElementById('modal-close').addEventListener('click', closeModal);
    document.getElementById('modal-cancel').addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });

    document.getElementById('save-schema-btn').addEventListener('click', function() {
        const mode = document.getElementById('schema-mode').value;
        const payload = new FormData();
        payload.append('id', postTypeId);
        payload.append('mode', mode);
        payload.append('schema', JSON.stringify({ fields: activeFields }));
        fetch('/modular/admin/post-types/field-schema-save.php', {
            method: 'POST',
            body: payload,
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) alert('Error: ' + data.error);
            else alert('Schema saved.');
        })
        .catch(function() { alert('Request failed.'); });
    });

    // —— Library: drag + category collapse (single container, delegated) ——
    const libraryEl = document.querySelector('.field-library');
    if (libraryEl) {
        libraryEl.addEventListener('dragstart', function(e) {
            const item = e.target.closest('.field-library-item');
            if (!item) return;
            var payload = JSON.stringify({
                type: item.dataset.type,
                label: item.dataset.label || item.dataset.type,
                icon: item.dataset.icon || 'fa-circle'
            });
            e.dataTransfer.setData(LIBRARY_DROP_TYPE, payload);
            e.dataTransfer.setData('text/plain', payload);
            e.dataTransfer.effectAllowed = 'copy';
            item.classList.add('dragging');
        });
        libraryEl.addEventListener('dragend', function(e) {
            const item = e.target.closest('.field-library-item');
            if (item) item.classList.remove('dragging');
        });
        libraryEl.addEventListener('click', function(e) {
            const title = e.target.closest('.field-category-title');
            if (!title) return;
            const list = title.nextElementSibling;
            if (!list) return;
            list.classList.toggle('collapsed');
            const icon = title.querySelector('.toggle-icon');
            if (icon) {
                icon.classList.toggle('fa-chevron-down');
                icon.classList.toggle('fa-chevron-right');
            }
        });
    }

    // —— Drop zone: must preventDefault on dragover so drop fires ——
    function onDragover(e) {
        e.preventDefault();
        e.stopPropagation();
        e.dataTransfer.dropEffect = 'copy';
        dropZone.classList.add('drop-active');
    }

    function onDragleave(e) {
        if (!dropZone.contains(e.relatedTarget)) {
            dropZone.classList.remove('drop-active');
        }
    }

    function onDragEnter(e) {
        e.preventDefault();
        dropZone.classList.add('drop-active');
    }

    dropZone.addEventListener('dragenter', onDragEnter, false);
    dropZone.addEventListener('dragover', onDragover, false);
    dropZone.addEventListener('dragleave', onDragleave, false);
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('drop-active');
        var raw = e.dataTransfer.getData(LIBRARY_DROP_TYPE) || e.dataTransfer.getData('text/plain');
        if (!raw) return;
        var data;
        try {
            data = JSON.parse(raw);
        } catch (err) {
            return;
        }
        activeFields.push({
            type: data.type,
            label: data.label || data.type,
            name: slugify(data.label || data.type)
        });
        renderActiveFields();
    }, false);

    renderActiveFields();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
</script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
