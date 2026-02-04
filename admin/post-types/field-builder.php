<?php
/**
 * Field Builder — ACF-equivalent schema builder for Post Types.
 * Spec UX: Library (search, accordion) | Canvas (sortable, drop indicators) | Inline settings (tabs: General, Validation, Display, Logic).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/fields/FieldRegistry.php';

use Modular\Fields\FieldRegistry;

FieldRegistry::bootDefaultFields();

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
                <p style="margin: 0.5rem 0 0; color: var(--text-muted);">Drag fields from the library to the canvas, or click a library item to add it. Click a field to edit settings.</p>
            </div>
            <div class="flex gap-2 align-center">
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

    <div class="field-builder-layout field-builder-two-col">
        <!-- LEFT: Field Library -->
        <div class="field-library card">
            <h3 class="field-library-title">Field Library</h3>
            <input type="search" id="library-search" class="form-control" placeholder="Search fields…" aria-label="Search field types">
            <p class="field-library-hint">Drag items to the canvas, or click to add.</p>
            <div id="library-accordion" class="library-accordion">
                <?php foreach ($categoryOrder as $cat): ?>
                    <?php if (empty($fieldLibrary[$cat])) continue; ?>
                    <div class="library-category" data-category="<?php echo htmlspecialchars($cat); ?>">
                        <button type="button" class="library-category-header" aria-expanded="true" aria-controls="library-list-<?php echo htmlspecialchars($cat); ?>" id="library-accordion-<?php echo htmlspecialchars($cat); ?>">
                            <i class="fas fa-chevron-down library-category-icon"></i>
                            <span><?php echo htmlspecialchars($categoryLabels[$cat] ?? ucfirst($cat)); ?></span>
                        </button>
                        <div id="library-list-<?php echo htmlspecialchars($cat); ?>" class="library-category-list" role="region" aria-labelledby="library-accordion-<?php echo htmlspecialchars($cat); ?>">
                            <?php foreach ($fieldLibrary[$cat] as $f): ?>
                                <div class="field-library-item" draggable="true" data-type="<?php echo htmlspecialchars($f['type']); ?>" data-label="<?php echo htmlspecialchars($f['label']); ?>" data-icon="<?php echo htmlspecialchars($f['icon'] ?? 'fa-circle'); ?>">
                                    <span class="field-library-item-drag" aria-hidden="true"><i class="fas fa-grip-vertical"></i></span>
                                    <i class="fas <?php echo htmlspecialchars($f['icon'] ?? 'fa-circle'); ?> field-library-item-icon"></i>
                                    <span class="field-library-item-label"><?php echo htmlspecialchars($f['label']); ?></span>
                                    <span class="field-library-item-type"><?php echo htmlspecialchars($f['type']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- RIGHT: Active Schema (canvas) -->
        <div class="active-schema card">
            <h3 class="active-schema-title">Active fields</h3>
            <div id="active-fields-list" class="active-fields-list" role="region" aria-label="Drop zone for fields">
                <div id="active-fields-empty" class="empty-state">
                    <i class="fas fa-mouse-pointer" aria-hidden="true"></i>
                    <p>Drag fields here or click a library item to add.</p>
                </div>
                <div class="active-fields-drop-hint" aria-hidden="true"><span>Drop here</span></div>
                <ul id="active-fields-ul" class="active-fields-ul" style="list-style: none; margin: 0; padding: 0; display: none;"></ul>
                <div id="active-fields-insert-line" class="active-fields-insert-line" aria-hidden="true"></div>
            </div>
        </div>
    </div>

    <!-- Slide-in settings panel (from left); overlay when open -->
    <div id="field-settings-overlay" class="field-settings-overlay" aria-hidden="true"></div>
    <div id="field-settings-panel" class="field-settings-panel" aria-hidden="true">
        <div class="field-settings-panel-inner">
            <div class="field-settings-header">
                <span id="settings-header-icon" class="settings-icon-wrap"><i class="fas fa-circle"></i></span>
                <div class="field-settings-header-text">
                    <div id="settings-header-label" class="settings-header-label">Field</div>
                    <div id="settings-header-type" class="settings-header-type">text</div>
                </div>
                <button type="button" id="field-settings-close" class="field-settings-close" aria-label="Close">×</button>
            </div>
            <div class="field-settings-tabs">
                <button type="button" class="field-settings-tab active" data-tab="general">General</button>
                <button type="button" class="field-settings-tab" data-tab="validation">Validation</button>
                <button type="button" class="field-settings-tab" data-tab="display">Display</button>
                <button type="button" class="field-settings-tab" data-tab="logic">Logic</button>
            </div>
            <div id="field-settings-tab-content" class="field-settings-tab-content">
                <div id="tab-general" class="settings-tab-pane" role="tabpanel"></div>
                <div id="tab-validation" class="settings-tab-pane" role="tabpanel" style="display: none;"></div>
                <div id="tab-display" class="settings-tab-pane" role="tabpanel" style="display: none;"></div>
                <div id="tab-logic" class="settings-tab-pane" role="tabpanel" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<style>
.field-builder-two-col { display: grid; grid-template-columns: 260px 1fr; gap: var(--spacing-md); min-height: 65vh; }
@media (max-width: 768px) { .field-builder-two-col { grid-template-columns: 1fr; } .field-library { order: 2; } .active-schema { order: 1; min-height: 280px; } }
.field-library { padding: var(--spacing-md); overflow: hidden; display: flex; flex-direction: column; }
.field-library-title { margin: 0 0 var(--spacing-sm); font-size: 1rem; }
.field-library-hint { margin: 0 0 var(--spacing-sm); font-size: 0.8rem; color: var(--text-muted); }
.library-accordion { flex: 1; overflow-y: auto; min-height: 0; }
.active-schema { padding: var(--spacing-md); display: flex; flex-direction: column; min-width: 0; }
.active-schema-title { margin: 0 0 var(--spacing-md); font-size: 1rem; }
.active-fields-list { flex: 1; min-height: 240px; border: 2px dashed var(--border-color); border-radius: var(--radius-md); padding: var(--spacing-md); position: relative; transition: border-color 0.15s, background 0.15s; }
.field-settings-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 1000; opacity: 0; transition: opacity 0.2s; }
.field-settings-overlay.visible { display: block; opacity: 1; }
.field-settings-panel { position: fixed; left: 0; top: 0; bottom: 0; width: min(380px, 100vw); max-width: 100%; background: var(--surface); box-shadow: 2px 0 12px rgba(0,0,0,0.15); z-index: 1001; transform: translateX(-100%); transition: transform 0.25s ease; display: flex; flex-direction: column; overflow: hidden; }
.field-settings-panel.open { transform: translateX(0); }
.field-settings-panel-inner { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.field-settings-header { padding: var(--spacing-md); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0; }
.field-settings-header-text { min-width: 0; flex: 1; }
.field-settings-close { width: 2rem; height: 2rem; padding: 0; border: none; background: transparent; font-size: 1.5rem; line-height: 1; cursor: pointer; color: var(--text-muted); border-radius: var(--radius-sm); flex-shrink: 0; }
.field-settings-close:hover { color: var(--text); background: var(--hover-bg); }
.field-settings-tabs { display: flex; border-bottom: 1px solid var(--border-color); padding: 0 var(--spacing-md); flex-shrink: 0; }
.field-settings-tab-content { flex: 1; overflow-y: auto; padding: var(--spacing-md); min-height: 0; }
@media (max-width: 480px) { .field-settings-panel { width: 100vw; } }
/* Library */
.library-category-header {
    width: 100%;
    padding: 0.5rem 0.75rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.875rem;
    background: var(--surface);
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
    color: var(--text);
}
.library-category-header:hover { background: var(--hover-bg); }
.library-category-icon { transition: transform 0.15s ease; color: var(--text-muted); }
.library-category-header[aria-expanded="false"] .library-category-icon { transform: rotate(-90deg); }
.library-category-list { margin-bottom: var(--spacing-sm); }
.library-category-list.collapsed { display: none; }
.field-library-item {
    padding: 0.4rem 0.6rem;
    margin: 0.2rem 0;
    border-radius: var(--radius-sm);
    cursor: grab;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--surface);
    border: 1px solid var(--border-color);
    font-size: 0.875rem;
}
.field-library-item:hover { background: var(--hover-bg); }
.field-library-item:active { cursor: grabbing; }
.field-library-item.dragging { opacity: 0.65; }
.field-library-item-drag { color: var(--text-muted); cursor: grab; pointer-events: none; }
.field-library-item { -webkit-user-select: none; user-select: none; }
.field-library-item-icon { color: var(--text-muted); width: 1rem; flex-shrink: 0; }
.field-library-item-label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.field-library-item-type {
    font-size: 0.7rem;
    color: var(--text-muted);
    background: var(--background-subtle);
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    flex-shrink: 0;
}
/* Canvas rows */
.active-field-row {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: 0.5rem 0.75rem;
    margin: 0.4rem 0;
    background: var(--surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
}
.active-field-row:hover { background: var(--hover-bg); }
.active-field-row.selected { border-color: var(--primary-color); background: rgba(45, 128, 22, 0.06); }
.active-field-row .drag-handle { cursor: grab; color: var(--text-muted); flex-shrink: 0; }
.active-field-row .field-label { flex: 1; min-width: 0; font-weight: 500; display: flex; align-items: center; gap: 0.35rem; overflow: hidden; }
.active-field-row .field-label i { color: var(--text-muted); flex-shrink: 0; }
.active-field-row .field-type-badge {
    font-size: 0.7rem;
    color: var(--text-muted);
    background: var(--background-subtle);
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    flex-shrink: 0;
}
.active-field-row .required-badge { color: var(--error-text); font-weight: 600; flex-shrink: 0; }
.active-field-row .btn-row { padding: 0.2rem 0.4rem; background: none; border: none; cursor: pointer; color: var(--text-muted); border-radius: var(--radius-sm); }
.active-field-row .btn-row:hover { color: var(--text); background: var(--hover-bg); }
.active-field-row .btn-row.btn-delete:hover { color: var(--error-text); }
#active-fields-list.drop-active {
        border: 3px dashed #2d8016;
        background: rgba(45, 128, 22, 0.08);
    }
.active-fields-drop-hint {
    display: none;
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    z-index: 5;
    color: #2d8016;
    font-size: 0.9rem;
    font-weight: 500;
}
#active-fields-list.drop-active .active-fields-drop-hint { display: flex; }
.active-fields-drop-hint span { background: var(--surface); padding: 0.5rem 1rem; border-radius: var(--radius-sm); border: 2px dashed #2d8016; }
.active-fields-insert-line {
    display: none;
    position: absolute;
    left: var(--spacing-md);
    right: var(--spacing-md);
    height: 0;
    border-top: 3px dashed #2d8016;
    margin: 0;
    pointer-events: none;
    z-index: 10;
    box-sizing: border-box;
}
.active-fields-insert-line.visible { display: block; }
.sortable-ghost {
    position: relative !important;
    display: block !important;
    width: 100% !important;
    height: 4px !important;
    min-height: 4px !important;
    padding: 0 !important;
    margin: 0.5rem 0 !important;
    border: none !important;
    background: transparent !important;
    overflow: visible !important;
    box-sizing: border-box !important;
}
.sortable-ghost::after {
    content: "" !important;
    position: absolute !important;
    left: 0 !important;
    right: 0 !important;
    top: 0 !important;
    width: 100% !important;
    border-top: 3px dashed #2d8016;
    box-sizing: border-box !important;
}
.sortable-ghost > * { display: none !important; }
.sortable-chosen { opacity: 0 !important; }
.sortable-drag, .sortable-fallback {
    opacity: 0.65 !important;
    min-height: 2.5rem !important;
    padding: 0.5rem 0.75rem !important;
    box-sizing: border-box !important;
    min-width: 180px !important;
}
/* Settings panel tabs */
.field-settings-tab {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    cursor: pointer;
    color: var(--text-muted);
}
.field-settings-tab:hover { color: var(--text); }
.field-settings-tab.active { color: var(--primary-color); border-bottom-color: var(--primary-color); font-weight: 500; }
.settings-tab-pane .form-group { margin-bottom: var(--spacing-md); }
.settings-tab-pane .form-group label { display: block; margin-bottom: 0.25rem; font-size: 0.875rem; }
.settings-icon-wrap {
    width: 36px;
    height: 36px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--background-subtle);
    color: var(--text-muted);
    flex-shrink: 0;
}
.settings-header-label { font-weight: 600; font-size: 0.95rem; }
.settings-header-type { font-size: 0.75rem; color: var(--text-muted); }
</style>

<script src="/modular/admin/assets/js/sortable.js"></script>
<script>
(function() {
    const postTypeId = <?php echo (int)$id; ?>;
    const fieldLibrary = <?php echo json_encode($fieldLibrary); ?>;
    const categoryOrder = <?php echo json_encode($categoryOrder); ?>;
    const TAB_KEYS = {
        general: ['label', 'name', 'key', 'instructions', 'default_value', 'sub_fields', 'layouts', 'button_label'],
        validation: ['required', 'min', 'max', 'step', 'maxlength', 'pattern', 'allow_null', 'multiple'],
        display: ['placeholder', 'prepend', 'append', 'rows', 'wrapper_class', 'input_class', 'ui', 'return_format', 'choices', 'layout'],
        logic: ['conditional_logic']
    };

    let activeFields = <?php echo json_encode($schema['fields'] ?? []); ?>;
    let selectedIndex = -1;
    let activeListSortable = null;
    let draggingFromLibrary = false;
    let libraryInsertIndex = 0;
    let justDraggedLibrary = false;
    let lastDragPayload = null;

    const emptyEl = document.getElementById('active-fields-empty');
    const listEl = document.getElementById('active-fields-ul');
    const dropZone = document.getElementById('active-fields-list');
    const insertLineEl = document.getElementById('active-fields-insert-line');
    const overlayEl = document.getElementById('field-settings-overlay');
    const panelEl = document.getElementById('field-settings-panel');
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
        div.textContent = s == null ? '' : s;
        return div.innerHTML;
    }

    function getTabForKey(key) {
        for (const [tab, keys] of Object.entries(TAB_KEYS)) {
            if (keys.includes(key)) return tab;
        }
        return 'general';
    }

    function choicesToLines(obj) {
        if (!obj || typeof obj !== 'object') return '';
        return Object.entries(obj).map(function(e) { return e[0] + ' : ' + e[1]; }).join('\n');
    }
    function linesToChoices(text) {
        const obj = {};
        if (!text || typeof text !== 'string') return obj;
        text.split('\n').forEach(function(line) {
            line = line.trim();
            if (!line) return;
            const i = line.indexOf(' : ');
            if (i > 0) {
                obj[line.slice(0, i).trim()] = line.slice(i + 3).trim();
            } else {
                obj[line] = line;
            }
        });
        return obj;
    }

    function renderActiveFields() {
        listEl.innerHTML = '';
        if (activeFields.length === 0) {
            emptyEl.style.display = 'block';
            listEl.style.display = 'none';
            selectedIndex = -1;
            closeSettingsPanel();
            reinitSortable();
            return;
        }
        emptyEl.style.display = 'none';
        listEl.style.display = 'block';
        activeFields.forEach((field, index) => {
            const li = document.createElement('li');
            li.className = 'active-field-row' + (index === selectedIndex ? ' selected' : '');
            li.dataset.index = String(index);
            const def = findFieldDef(field.type);
            const icon = (def && def.icon) ? def.icon : 'fa-circle';
            const label = field.label || field.name || field.type;
            const expanded = index === selectedIndex;
            li.innerHTML = [
                '<span class="drag-handle" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></span>',
                '<button type="button" class="field-row-collapse" title="' + (expanded ? 'Close settings' : 'Open settings') + '" aria-label="' + (expanded ? 'Close settings' : 'Open settings') + '"><i class="fas fa-chevron-' + (expanded ? 'down' : 'right') + '"></i></button>',
                '<span class="field-label"><i class="fas ' + icon + '"></i> ' + escapeHtml(label) + (field.required ? '<span class="required-badge" aria-label="Required">*</span>' : '') + '</span>',
                '<span class="field-type-badge">' + escapeHtml(field.type) + '</span>',
                '<button type="button" class="btn-row edit-field" title="Edit settings"><i class="fas fa-cog"></i></button>',
                '<button type="button" class="btn-row duplicate-field" title="Duplicate"><i class="fas fa-copy"></i></button>',
                '<button type="button" class="btn-row btn-delete delete-field" title="Delete"><i class="fas fa-trash-alt"></i></button>'
            ].join('');
            listEl.appendChild(li);
        });
        reinitSortable();
    }

    function reinitSortable() {
        if (activeListSortable) {
            activeListSortable.destroy();
            activeListSortable = null;
        }
        if (activeFields.length === 0) return;
        if (typeof window.ModularSortable === 'undefined' || !window.ModularSortable.create) return;
        activeListSortable = window.ModularSortable.create(listEl, {
            animation: 150,
            forceFallback: true,
            fallbackClass: 'sortable-fallback',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function(evt) {
                const rows = listEl.querySelectorAll('li.active-field-row');
                const reordered = Array.from(rows).map(li => activeFields[parseInt(li.dataset.index, 10)]);
                activeFields = reordered;
                selectedIndex = activeFields.length ? 0 : -1;
                renderActiveFields();
                if (selectedIndex >= 0) fillSettingsForm(selectedIndex);
            }
        });
    }

    function closeSettingsPanel() {
        if (overlayEl) overlayEl.classList.remove('visible');
        if (panelEl) { panelEl.classList.remove('open'); panelEl.setAttribute('aria-hidden', 'true'); }
    }

    function openSettingsPanel(index) {
        if (overlayEl) overlayEl.classList.add('visible');
        if (panelEl) { panelEl.classList.add('open'); panelEl.setAttribute('aria-hidden', 'false'); }
        fillSettingsForm(index);
    }

    function fillSettingsForm(index) {
        selectedIndex = index;
        const field = index >= 0 && index < activeFields.length ? activeFields[index] : null;
        if (!field) {
            closeSettingsPanel();
            renderActiveFields();
            return;
        }
        const def = findFieldDef(field.type);
        const iconClass = def && def.icon ? def.icon : 'fa-circle';
        document.getElementById('settings-header-icon').querySelector('i').className = 'fas ' + iconClass;
        document.getElementById('settings-header-label').textContent = field.label || field.name || field.type;
        document.getElementById('settings-header-type').textContent = field.type;

        const schema = (def && def.config_schema) ? def.config_schema : {};
        const panes = { general: document.getElementById('tab-general'), validation: document.getElementById('tab-validation'), display: document.getElementById('tab-display'), logic: document.getElementById('tab-logic') };
        Object.keys(panes).forEach(t => { panes[t].innerHTML = ''; });

        for (const [key, meta] of Object.entries(schema)) {
            const tab = getTabForKey(key);
            const pane = panes[tab];
            if (!pane) continue;
            const val = field[key];
            const type = meta.type || 'string';
            const required = meta.required === true;
            const label = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            let input = '';
            const id = 'cfg-' + key + '-' + index;
            if (type === 'boolean') {
                const checked = val === true || val === '1' || (meta.default && val === undefined);
                input = '<label class="checkbox-label"><input type="checkbox" id="' + id + '" data-key="' + escapeHtml(key) + '" ' + (checked ? 'checked' : '') + '> ' + escapeHtml(label) + '</label>';
            } else if (type === 'integer' || type === 'number') {
                const v = val !== undefined && val !== '' ? val : (meta.default !== undefined ? meta.default : '');
                input = '<input type="number" id="' + id + '" class="form-control" data-key="' + escapeHtml(key) + '" value="' + escapeHtml(String(v)) + '" step="' + (type === 'number' ? 'any' : '1') + '">';
            } else if (key === 'choices') {
                const v = choicesToLines(val);
                input = '<textarea id="' + id + '" class="form-control" data-key="' + escapeHtml(key) + '" rows="6" placeholder="red : Red&#10;green : Green">' + escapeHtml(v) + '</textarea><p class="help-text" style="margin-top: 0.25rem; font-size: 0.75rem;">One per line: <code>value : Label</code></p>';
            } else if (key === 'sub_fields') {
                const v = typeof val === 'object' && val !== null ? JSON.stringify(val, null, 2) : (Array.isArray(val) ? JSON.stringify(val, null, 2) : '[]');
                input = '<textarea id="' + id + '" class="form-control" data-key="' + escapeHtml(key) + '" rows="8" style="font-family: monospace; font-size: 0.8rem;">' + escapeHtml(v) + '</textarea><p class="help-text" style="margin-top: 0.25rem; font-size: 0.75rem;">Array of field objects: <code>[{"type":"text","name":"title","label":"Title"}, ...]</code></p>';
            } else if (key === 'layouts') {
                const v = typeof val === 'object' && val !== null ? JSON.stringify(val, null, 2) : (Array.isArray(val) ? JSON.stringify(val, null, 2) : '[]');
                input = '<textarea id="' + id + '" class="form-control" data-key="' + escapeHtml(key) + '" rows="8" style="font-family: monospace; font-size: 0.8rem;">' + escapeHtml(v) + '</textarea><p class="help-text" style="margin-top: 0.25rem; font-size: 0.75rem;">Allowed layouts: array of <code>{"name":"...","label":"...","sub_fields":[...]}</code></p>';
            } else if (type === 'object' || type === 'array') {
                const v = typeof val === 'object' && val !== null ? JSON.stringify(val, null, 2) : (val || '');
                input = '<textarea id="' + id + '" class="form-control" data-key="' + escapeHtml(key) + '" rows="4" style="font-family: monospace; font-size: 0.8rem;">' + escapeHtml(typeof v === 'string' ? v : JSON.stringify(v)) + '</textarea>';
            } else {
                const v = val !== undefined && val !== null ? val : (meta.default !== undefined ? meta.default : '');
                const rows = key === 'instructions' || key === 'description' ? 2 : 1;
                if (rows > 1) {
                    input = '<textarea id="' + id + '" class="form-control" data-key="' + escapeHtml(key) + '" rows="' + rows + '">' + escapeHtml(String(v)) + '</textarea>';
                } else {
                    input = '<input type="text" id="' + id + '" class="form-control" data-key="' + escapeHtml(key) + '" value="' + escapeHtml(String(v)) + '">';
                }
            }
            pane.innerHTML += '<div class="form-group"><label for="' + id + '">' + escapeHtml(label) + (required ? ' <span class="required">*</span>' : '') + '</label>' + input + '</div>';
        }

        // Ensure label/name/required exist in General if not in schema
        if (!schema.label && panes.general) {
            const lid = 'cfg-label-' + index;
            if (!document.getElementById(lid)) {
                panes.general.insertAdjacentHTML('afterbegin', '<div class="form-group"><label for="' + lid + '">Label</label><input type="text" id="' + lid + '" class="form-control" data-key="label" value="' + escapeHtml(field.label || '') + '"></div>');
            }
        }
        if (!schema.name && panes.general) {
            const nid = 'cfg-name-' + index;
            if (!document.getElementById(nid)) {
                panes.general.insertAdjacentHTML('afterbegin', '<div class="form-group"><label for="' + nid + '">Name (key)</label><input type="text" id="' + nid + '" class="form-control" data-key="name" value="' + escapeHtml(field.name || '') + '"></div>');
            }
        }
        if (!schema.required && panes.validation) {
            const rid = 'cfg-required-' + index;
            if (!document.getElementById(rid)) {
                const checked = field.required === true;
                panes.validation.insertAdjacentHTML('afterbegin', '<div class="form-group"><label class="checkbox-label"><input type="checkbox" id="' + rid + '" data-key="required" ' + (checked ? 'checked' : '') + '> Required</label></div>');
            }
        }
        if (!schema.instructions && panes.general) {
            const iid = 'cfg-instructions-' + index;
            if (!document.getElementById(iid)) {
                panes.general.insertAdjacentHTML('beforeend', '<div class="form-group"><label for="' + iid + '">Instructions</label><textarea id="' + iid + '" class="form-control" data-key="instructions" rows="2">' + escapeHtml(field.instructions || '') + '</textarea></div>');
            }
        }

        document.querySelectorAll('.field-settings-tab').forEach(t => t.classList.remove('active'));
        document.querySelector('.field-settings-tab[data-tab="general"]').classList.add('active');
        document.querySelectorAll('.settings-tab-pane').forEach(p => { p.style.display = 'none'; });
        document.getElementById('tab-general').style.display = 'block';

        renderActiveFields();
    }

    function collectSettingsFromPanel() {
        if (selectedIndex < 0 || selectedIndex >= activeFields.length) return;
        const field = activeFields[selectedIndex];
        panelEl.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (!key) return;
            let val;
            if (el.type === 'checkbox') {
                val = el.checked;
            } else if (el.tagName === 'TEXTAREA') {
                val = el.value;
                if (key === 'choices') {
                    val = linesToChoices(el.value);
                } else if (key === 'sub_fields' || key === 'conditional_logic' || key === 'post_type' || key === 'taxonomy' || key === 'filters' || key === 'elements' || key === 'layouts') {
                    try { val = JSON.parse(el.value || (key === 'sub_fields' ? '[]' : '[]')); } catch (e) { val = key === 'sub_fields' ? [] : []; }
                }
            } else {
                val = el.value;
                if (el.type === 'number') val = el.value === '' ? undefined : (el.step === 'any' ? parseFloat(val) : parseInt(val, 10));
            }
            field[key] = val;
        });
        if (field.label && !field.name) field.name = slugify(field.label);
    }

    listEl.addEventListener('click', function(e) {
        const row = e.target.closest('li.active-field-row');
        if (!row) return;
        e.preventDefault();
        const idx = parseInt(row.dataset.index, 10);
        if (e.target.closest('.delete-field')) {
            const label = activeFields[idx].label || activeFields[idx].name || activeFields[idx].type;
            if (!confirm('Delete field “‘ + label + '”? This cannot be undone.')) return;
            collectSettingsFromPanel();
            activeFields.splice(idx, 1);
            selectedIndex = idx >= activeFields.length ? activeFields.length - 1 : idx;
            if (activeFields.length === 0) {
                closeSettingsPanel();
                selectedIndex = -1;
            } else {
                fillSettingsForm(selectedIndex >= 0 ? selectedIndex : 0);
            }
            renderActiveFields();
            return;
        }
        if (e.target.closest('.duplicate-field')) {
            collectSettingsFromPanel();
            const copy = JSON.parse(JSON.stringify(activeFields[idx]));
            copy.name = (copy.name || copy.label || 'field') + '_copy';
            copy.label = (copy.label || 'Field') + ' (copy)';
            activeFields.splice(idx + 1, 0, copy);
            selectedIndex = idx + 1;
            renderActiveFields();
            fillSettingsForm(selectedIndex);
            return;
        }
        if (e.target.closest('.edit-field') || e.target.closest('.field-label') || row === e.target.closest('.active-field-row')) {
            if (e.target.closest('.drag-handle')) return;
            collectSettingsFromPanel();
            selectedIndex = idx;
            openSettingsPanel(idx);
            return;
        }
        if (e.target.closest('.field-row-collapse')) {
            if (selectedIndex === idx) {
                closeSettingsPanel();
                selectedIndex = -1;
            } else {
                openSettingsPanel(idx);
            }
            renderActiveFields();
        }
    });

    document.querySelectorAll('.field-settings-tab').forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.getAttribute('data-tab');
            document.querySelectorAll('.field-settings-tab').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.settings-tab-pane').forEach(p => { p.style.display = 'none'; });
            const pane = document.getElementById('tab-' + tab);
            if (pane) pane.style.display = 'block';
        });
    });
    var closeBtn = document.getElementById('field-settings-close');
    if (closeBtn) closeBtn.addEventListener('click', closeSettingsPanel);
    if (overlayEl) overlayEl.addEventListener('click', closeSettingsPanel);

    document.getElementById('save-schema-btn').addEventListener('click', function() {
        collectSettingsFromPanel();
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
        .then(r => r.json())
        .then(data => {
            if (data.error) alert('Error: ' + data.error);
            else alert('Schema saved.');
        })
        .catch(() => alert('Request failed.'));
    });

    function updateLibraryInsertLine(clientY) {
        if (!insertLineEl || !dropZone) return;
        const zoneRect = dropZone.getBoundingClientRect();
        const rows = listEl.querySelectorAll('li.active-field-row');
        if (rows.length === 0) {
            libraryInsertIndex = 0;
            insertLineEl.style.top = '16px';
            insertLineEl.classList.add('visible');
            return;
        }
        let i = 0;
        for (; i < rows.length; i++) {
            const r = rows[i].getBoundingClientRect();
            if (clientY < r.top + r.height / 2) break;
        }
        libraryInsertIndex = i;
        let topPx;
        if (i === 0) {
            topPx = rows[0].getBoundingClientRect().top - zoneRect.top - 2;
        } else {
            topPx = rows[i - 1].getBoundingClientRect().bottom - zoneRect.top + 2;
        }
        insertLineEl.style.top = Math.max(4, topPx) + 'px';
        insertLineEl.classList.add('visible');
    }

    function hideLibraryInsertLine() {
        if (insertLineEl) insertLineEl.classList.remove('visible');
    }

    const libraryEl = document.querySelector('.field-library');
    if (libraryEl) {
        libraryEl.addEventListener('dragstart', function(e) {
            const item = e.target.closest('.field-library-item');
            if (!item) return;
            draggingFromLibrary = true;
            const data = {
                type: item.dataset.type,
                label: item.dataset.label || item.dataset.type,
                icon: item.dataset.icon || 'fa-circle'
            };
            const payload = JSON.stringify(data);
            lastDragPayload = data;
            e.dataTransfer.setData(LIBRARY_DROP_TYPE, payload);
            e.dataTransfer.setData('text/plain', payload);
            e.dataTransfer.effectAllowed = 'copy';
            item.classList.add('dragging');
            try {
                const clone = item.cloneNode(true);
                clone.style.cssText = 'position:absolute;top:-9999px;left:0;padding:0.4rem 0.6rem;min-height:2rem;display:flex;align-items:center;gap:0.5rem;background:var(--surface);border:1px solid var(--border-color);border-radius:var(--radius-sm);white-space:nowrap;font-size:0.875rem;';
                document.body.appendChild(clone);
                e.dataTransfer.setDragImage(clone, 0, 0);
                setTimeout(function() { if (clone.parentNode) clone.parentNode.removeChild(clone); }, 0);
            } catch (err) {}
        });
        libraryEl.addEventListener('dragend', function(e) {
            const item = e.target.closest('.field-library-item');
            if (item) item.classList.remove('dragging');
            draggingFromLibrary = false;
            lastDragPayload = null;
            justDraggedLibrary = true;
            setTimeout(function() { justDraggedLibrary = false; }, 0);
            hideLibraryInsertLine();
        });
        libraryEl.querySelectorAll('.library-category-header').forEach(btn => {
            btn.addEventListener('click', function() {
                const expanded = this.getAttribute('aria-expanded') !== 'true';
                this.setAttribute('aria-expanded', expanded);
                const list = this.nextElementSibling;
                if (list) list.classList.toggle('collapsed', !expanded);
            });
        });
        libraryEl.addEventListener('click', function(e) {
            const item = e.target.closest('.field-library-item');
            if (!item || justDraggedLibrary) return;
            e.preventDefault();
            const type = item.dataset.type;
            const label = item.dataset.label || type;
            const def = findFieldDef(type);
            const newField = { type: type, label: label, name: slugify(label) };
            if (def && def.config_schema) {
                for (var k in def.config_schema) {
                    if (def.config_schema[k].default !== undefined && newField[k] === undefined) newField[k] = def.config_schema[k].default;
                }
            }
            activeFields.push(newField);
            selectedIndex = activeFields.length - 1;
            renderActiveFields();
            openSettingsPanel(selectedIndex);
        });
    }

    const searchEl = document.getElementById('library-search');
    if (searchEl) {
        searchEl.addEventListener('input', function() {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('.field-library-item').forEach(item => {
                const label = (item.dataset.label || '').toLowerCase();
                const type = (item.dataset.type || '').toLowerCase();
                const match = !q || label.includes(q) || type.includes(q);
                item.style.display = match ? '' : 'none';
            });
            document.querySelectorAll('.library-category').forEach(cat => {
                const list = cat.querySelector('.library-category-list');
                const visible = list && list.querySelectorAll('.field-library-item[style=""], .field-library-item:not([style*="display: none"])').length;
                cat.style.display = (visible === 0 && q) ? 'none' : '';
            });
        });
    }

    function handleDragEnter(e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.add('drop-active');
        if (draggingFromLibrary) updateLibraryInsertLine(e.clientY);
    }
    function handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        e.dataTransfer.dropEffect = 'copy';
        dropZone.classList.add('drop-active');
        if (draggingFromLibrary) updateLibraryInsertLine(e.clientY);
    }
    function handleDragLeave(e) {
        if (!dropZone.contains(e.relatedTarget)) {
            dropZone.classList.remove('drop-active');
            hideLibraryInsertLine();
        }
    }
    function handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('drop-active');
        hideLibraryInsertLine();
        var data = null;
        var raw = e.dataTransfer.getData(LIBRARY_DROP_TYPE) || e.dataTransfer.getData('text/plain');
        if (raw) {
            try { data = JSON.parse(raw); } catch (err) {}
        }
        if (!data && lastDragPayload) data = lastDragPayload;
        if (!data || !data.type) return;
        var newField = {
            type: data.type,
            label: data.label || data.type,
            name: slugify(data.label || data.type)
        };
        var def = findFieldDef(data.type);
        if (def && def.config_schema) {
            for (var k in def.config_schema) {
                if (def.config_schema[k].default !== undefined && newField[k] === undefined) newField[k] = def.config_schema[k].default;
            }
        }
        var idx = typeof libraryInsertIndex !== 'undefined' ? libraryInsertIndex : activeFields.length;
        activeFields.splice(idx, 0, newField);
        selectedIndex = idx;
        renderActiveFields();
        openSettingsPanel(idx);
    }
    dropZone.addEventListener('dragenter', handleDragEnter, true);
    dropZone.addEventListener('dragover', handleDragOver, true);
    dropZone.addEventListener('dragleave', handleDragLeave, true);
    dropZone.addEventListener('drop', handleDrop, true);

    renderActiveFields();
})();
</script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
