<?php
/**
 * Field Builder (Vue 3 + PrimeVue)
 * Replaces admin/post-types/field-builder.php with same UX: Library | Canvas | Settings panel.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/functions/subscription.php';
require_once CORE_PATH . '/fields/FieldRegistry.php';

use Modular\Fields\FieldRegistry;

FieldRegistry::bootDefaultFields();

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['error'] = 'Invalid post type.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/post-types');
    exit;
}

$db = $GLOBALS['db'];
$stmt = $db->prepare("SELECT id, name, slug, mode, `schema` FROM post_types WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$postType = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$postType) {
    $_SESSION['error'] = 'Post type not found.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/post-types');
    exit;
}

$schema = $postType['schema'];
if (is_string($schema)) {
    $schema = json_decode($schema, true);
}
if (!is_array($schema)) {
    $schema = ['fields' => []];
} elseif (!isset($schema['fields']) || !is_array($schema['fields'])) {
    $isList = array_keys($schema) === array_keys(array_values($schema));
    $schema = $isList ? ['fields' => $schema] : ['fields' => []];
}
$mode = $postType['mode'] ?? 'fixed';
$modularAllowed = function_exists('subscription_has_feature') && subscription_has_feature('modular_post_types');

$fieldLibrary = FieldRegistry::listForAdminGrouped();
$categoryOrder = ['basic', 'content', 'choice', 'relational', 'media', 'date_time', 'layout', 'event', 'utility'];
$categoryLabels = [
    'basic' => 'Basic',
    'content' => 'Content',
    'choice' => 'Choice',
    'relational' => 'Relational',
    'media' => 'Media',
    'date_time' => 'Date & Time',
    'layout' => 'Layout',
    'event' => 'Event',
    'utility' => 'Utility',
];

$availablePostTypes = [];
$availableTaxonomies = [];
try {
    $res = $db->query("SELECT id, name, slug FROM post_types WHERE deleted_at IS NULL ORDER BY name");
    if ($res) {
        $availablePostTypes = $res->fetchAll(PDO::FETCH_ASSOC);
    }
    $res = $db->query("SELECT id, name, slug FROM taxonomies WHERE deleted_at IS NULL ORDER BY name");
    if ($res) {
        $availableTaxonomies = $res->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
}

// Load shared fields data for sharing UI
$sharedFieldsRaw = $db->query(
    "SELECT id, name, type, definition FROM shared_fields WHERE deleted_at IS NULL"
)->fetchAll(PDO::FETCH_ASSOC);

// Build map: shared_id => list of post type ids that use it
$sharedFieldPostTypes = [];
foreach ($availablePostTypes as $pt) {
    if ((int)$pt['id'] === $id) continue;
    $ptStmt = $db->prepare("SELECT `schema` FROM post_types WHERE id = ? AND deleted_at IS NULL");
    $ptStmt->execute([$pt['id']]);
    $ptRow = $ptStmt->fetch(PDO::FETCH_ASSOC);
    if (!$ptRow || !$ptRow['schema']) continue;
    $ptSchema = json_decode($ptRow['schema'], true);
    if (!is_array($ptSchema) || empty($ptSchema['fields'])) continue;
    foreach ($ptSchema['fields'] as $f) {
        if (isset($f['_shared_id'])) {
            $sid = (int)$f['_shared_id'];
            if (!isset($sharedFieldPostTypes[$sid])) $sharedFieldPostTypes[$sid] = [];
            $sharedFieldPostTypes[$sid][] = (int)$pt['id'];
        }
    }
}

$page_title = 'Field Builder: ' . htmlspecialchars($postType['name']);
$post_types_active = true;

$sharedFieldAttachDefaults = [];
foreach ($sharedFieldsRaw as $sfRow) {
    $defRow = json_decode($sfRow['definition'] ?? '{}', true);
    $sharedFieldAttachDefaults[(int)$sfRow['id']] = is_array($defRow) && !empty($defRow['attach_to_new_post_types']);
}

$config = [
    'postTypeId' => $id,
    'postTypeName' => $postType['name'],
    'fieldLibrary' => $fieldLibrary,
    'categoryOrder' => $categoryOrder,
    'categoryLabels' => $categoryLabels,
    'availablePostTypes' => $availablePostTypes,
    'availableTaxonomies' => $availableTaxonomies,
    'initialFields' => $schema['fields'] ?? [],
    'mode' => $mode,
    'modularAllowed' => $modularAllowed,
    'adminUrl' => defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin',
    'saveUrl' => (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/post-types/field-schema-save.php',
    'csrfToken' => function_exists('csrf_token') ? csrf_token() : '',
    'sharedFields' => array_map(function ($sf) {
        return ['id' => (int)$sf['id'], 'name' => $sf['name'], 'type' => $sf['type']];
    }, $sharedFieldsRaw),
    'sharedFieldPostTypes' => $sharedFieldPostTypes,
    'sharedFieldAttachDefaults' => $sharedFieldAttachDefaults,
    'otherPostTypes' => array_values(array_filter($availablePostTypes, function ($pt) use ($id) {
        return (int)$pt['id'] !== $id;
    })),
];

require_once ADMIN_PATH . '/templates/header.php';
?>
<div class="container" id="field-builder-vue-app">
    <div class="page-header" style="margin-bottom: var(--spacing-lg);">
        <div class="flex justify-between items-start" style="gap: 1rem;">
            <div>
                <a :href="config.adminUrl + '/post-types/edit.php?id=' + config.postTypeId" class="btn btn-secondary btn-sm" style="margin-bottom: 0.5rem;">
                    <i class="fas fa-arrow-left"></i> Back to Post Type
                </a>
                <h1 style="margin: 0;"><?php echo htmlspecialchars($page_title); ?></h1>
                <p style="margin: 0.5rem 0 0; color: var(--text-muted);">Configure which fields editors see. Drag from the library to the canvas or click to add. Click a field to edit settings.</p>
                <div class="flex gap-2 align-center" style="margin-top: 0.75rem;">
                    <label class="flex align-center gap-2">
                        <span>Mode:</span>
                        <select v-model="config.mode" class="form-control" style="width: auto;" :disabled="!config.modularAllowed">
                            <option value="fixed">Fixed</option>
                            <option value="modular">Modular</option>
                        </select>
                    </label>
                    <span v-if="!config.modularAllowed" class="help-text" style="margin-left: 0.5rem;">Modular mode is available in Pro and Agency tiers.</span>
                </div>
            </div>
            <div class="flex-shrink-0">
                <button type="button" @click="saveSchema" class="btn btn-primary" :disabled="saving">
                    <i class="fas fa-save"></i> {{ saving ? 'Saving…' : 'Save schema' }}
                </button>
            </div>
        </div>
    </div>

    <div class="field-builder-two-col" style="display: grid; grid-template-columns: 260px 1fr; gap: var(--spacing-md); min-height: 65vh;">
        <!-- Library -->
        <div class="card field-library" style="padding: var(--spacing-md); overflow: hidden; display: flex; flex-direction: column;">
            <h3 class="field-library-title" style="margin: 0 0 var(--spacing-sm); font-size: 1rem;">Field Library</h3>
            <input type="search" v-model="librarySearch" class="form-control" placeholder="Search fields…" aria-label="Search field types" style="margin-bottom: 0.5rem;">
            <p class="field-library-hint" style="margin: 0 0 var(--spacing-sm); font-size: 0.8rem; color: var(--text-muted);">Drag items to the canvas, or click to add.</p>
            <div class="library-accordion" style="flex: 1; overflow-y: auto; min-height: 0;">
                <div v-for="cat in visibleCategories" :key="cat" class="library-category">
                    <button type="button" class="library-category-header" style="width:100%;padding:0.5rem 0.75rem;text-align:left;font-weight:600;font-size:0.875rem;background:var(--surface);border:none;border-radius:var(--radius-sm);cursor:pointer;display:flex;align-items:center;gap:0.5rem;margin-bottom:0.25rem;color:var(--text);"
                        @click="toggleCategory(cat)"
                        :aria-expanded="expandedCategories.includes(cat)">
                        <i class="fas fa-chevron-down" style="transition: transform 0.15s;" :style="{ transform: expandedCategories.includes(cat) ? 'rotate(0)' : 'rotate(-90deg)' }"></i>
                        <span>{{ config.categoryLabels[cat] || cat }}</span>
                    </button>
                    <div v-show="expandedCategories.includes(cat)" class="library-category-list" style="margin-bottom: var(--spacing-sm);">
                        <div v-for="f in filteredLibraryByCategory(cat)" :key="f.type"
                            class="field-library-item"
                            :title="f.description || f.label"
                            style="padding:0.4rem 0.6rem;margin:0.2rem 0;border-radius:var(--radius-sm);cursor:grab;display:flex;align-items:center;gap:0.5rem;background:var(--surface);border:1px solid var(--border-color);font-size:0.875rem;"
                            draggable="true"
                            :data-type="f.type" :data-label="f.label" :data-icon="f.icon || 'fa-circle'"
                            @click="addField(f)"
                            @dragstart="onLibraryDragStart($event, f)">
                            <span style="color:var(--text-muted);"><i class="fas fa-grip-vertical"></i></span>
                            <i class="fas" :class="f.icon || 'fa-circle'" style="color:var(--text-muted);width:1rem;"></i>
                            <span class="field-library-item-label" style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ f.label }}</span>
                            <span style="font-size:0.7rem;color:var(--text-muted);background:var(--background-subtle);padding:0.15rem 0.4rem;border-radius:4px;">{{ f.type }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Canvas + Modular fields config -->
        <div class="active-schema-wrapper" style="display:flex;flex-direction:column;gap:var(--spacing-md);min-width:0;">
            <!-- Active fields (always visible when creating a post) -->
            <div class="card active-schema" style="padding: var(--spacing-md); display: flex; flex-direction: column; min-width: 0;">
                <div class="flex justify-between align-center" style="margin: 0 0 var(--spacing-md); gap: 0.75rem; flex-wrap: wrap;">
                    <h3 class="active-schema-title" style="margin: 0; font-size: 1rem;">Active fields</h3>
                    <button type="button" class="btn btn-secondary btn-sm" style="flex-shrink:0;" title="Adds a Field Group marker so fields below belong to this block until the next marker" @click="addFieldGroupSection">
                        <i class="fas fa-layer-group"></i> Field Group
                    </button>
                </div>
                <p class="field-library-hint" style="margin: 0 0 0.5rem 0; font-size: 0.8rem; color: var(--text-muted);">
                    Fields used by this post type. On the canvas, <strong>Field Group</strong> markers split the schema into sections (the first section is usually &quot;General&quot;); order matches the editor.
                </p>
                <?php
                $ptSchemaPeek = isset($currentSchema) && is_array($currentSchema) ? $currentSchema : null;
                if ($ptSchemaPeek === null && isset($postType['schema'])) {
                    $ptSchemaPeek = is_string($postType['schema']) ? json_decode($postType['schema'], true) : (is_array($postType['schema']) ? $postType['schema'] : null);
                }
                $fixedFgList = (is_array($ptSchemaPeek) && isset($ptSchemaPeek['field_groups']) && is_array($ptSchemaPeek['field_groups'])) ? $ptSchemaPeek['field_groups'] : [];
                if (!empty($fixedFgList)):
                ?>
                <details class="field-builder-fixed-groups" style="margin:0 0 0.5rem 0;padding:0.5rem 0.75rem;border:1px dashed var(--border-color, #e2e8f0);border-radius:6px;background:var(--background-subtle, #f8fafc);">
                    <summary style="font-size:0.8rem;color:var(--text-muted);cursor:pointer;">
                        <i class="fas fa-lock" style="margin-right:0.25rem;opacity:0.7;"></i>
                        Schema-level Field Groups (<?php echo count($fixedFgList); ?>) — managed from the post item editor.
                    </summary>
                    <ul style="margin:0.5rem 0 0;padding:0 0 0 1.25rem;list-style:disc;font-size:0.8rem;color:var(--text-muted);">
                        <?php foreach ($fixedFgList as $fg):
                            if (!is_array($fg)) continue;
                            $labelTxt = $fg['label'] ?? 'Field Group';
                            $shared = !empty($fg['shared_id']);
                        ?>
                            <li><strong><?php echo htmlspecialchars($labelTxt, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php echo $shared ? '<span class="field-type-badge" style="font-size:0.65rem;margin-left:0.25rem;">shared</span>' : '<span class="field-type-badge" style="font-size:0.65rem;margin-left:0.25rem;">fixed</span>'; ?>
                                · <?php echo count(isset($fg['items']) && is_array($fg['items']) ? $fg['items'] : []); ?> fields
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p style="margin:0.5rem 0 0;font-size:0.78rem;color:var(--text-muted);">
                        Field Groups marked <strong>Fixed</strong> or <strong>Shared</strong> appear locked on every item of this post type. Create or edit them from any post item editor via <em>Add field group</em>.
                    </p>
                </details>
                <?php endif; ?>
                <div v-if="config.mode === 'modular'" class="show-on-creation-controls" style="margin:0 0 0.5rem 0;font-size:0.8rem;color:var(--text-muted);display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                    <span style="margin-left:1.25rem;"></span>
                    <label class="flex align-center gap-2">
                        <input type="checkbox" v-model="allShowOnCreationSelected">
                        <span>Alle velden tonen bij nieuw bericht</span>
                    </label>
                    <i class="fas fa-info-circle" style="color:var(--text-muted);cursor:help;" title="Gaat over het aanmaken van een nieuw bericht (content), niet over nieuwe post types. Uitvinken verbergt het veld op het eerste scherm; editors kunnen het alsnog via &quot;Velden toevoegen&quot; kiezen." aria-label="Help"></i>
                </div>
                <div ref="dropZoneRef"
                    class="active-fields-list"
                    style="flex:1;min-height:240px;border:2px dashed var(--border-color);border-radius:var(--radius-md);padding:var(--spacing-md);position:relative;transition: border-color 0.15s, background 0.15s;"
                    :class="{ 'drop-active': isDropActive }"
                    @dragover.prevent="onCanvasDragOver"
                    @dragleave="onCanvasDragLeave"
                    @drop.prevent="onCanvasDrop">
                    <div v-if="activeFields.length === 0" class="empty-state" style="text-align:center;padding:2rem;color:var(--text-muted);">
                        <i class="fas fa-mouse-pointer" style="font-size:2rem;margin-bottom:0.5rem;"></i>
                        <p>No fields yet. Drag from the library or click a field type to add.</p>
                    </div>
                    <template v-if="activeFields.length > 0">
                    <ul ref="canvasListRef" v-if="!canvasHasGroupedLayout" :key="'canvas-flat-' + canvasListKey" class="active-fields-ul" style="list-style:none;margin:0;padding:0;">
                        <li v-for="(field, index) in activeFields" :key="field._uid || ('f-' + index)"
                            class="active-field-row"
                            :class="{ selected: selectedIndex === index }"
                            style="display:flex;align-items:center;gap:var(--spacing-sm);padding:0.5rem 0.75rem;margin:0.4rem 0;background:var(--surface);border:1px solid var(--border-color);border-radius:var(--radius-sm);cursor:pointer;"
                            :data-index="index"
                            @click="selectField(index)">
                            <label v-if="config.mode === 'modular'" class="show-on-creation-toggle" style="display:flex;align-items:center;flex-shrink:0;" @click.stop title="Tonen bij nieuw bericht (content), niet bij nieuw post type">
                                <input type="checkbox" v-model="field.show_on_creation">
                            </label>
                            <span class="drag-handle" title="Drag to reorder" style="cursor:grab;color:var(--text-muted);"><i class="fas fa-grip-vertical"></i></span>
                            <button type="button" class="field-row-collapse" style="width:1.25rem;height:1.25rem;padding:0;margin:0;border:none;background:none;cursor:pointer;color:var(--text-muted);" @click.stop="toggleSettings(index)" :aria-label="selectedIndex === index ? 'Close settings' : 'Open settings'">
                                <i class="fas" :class="selectedIndex === index ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                            </button>
                            <span class="field-label" style="flex:1;min-width:0;font-weight:500;display:flex;align-items:center;gap:0.35rem;">
                                <i class="fas" :class="getFieldIcon(field)" style="color:var(--text-muted);"></i>
                                {{ field.label || field.name || field.type }}
                                <span v-if="field.required" class="required-badge" style="color:var(--error-text);font-weight:600;">*</span>
                            </span>
                            <span v-if="field._shared_id" class="shared-badge" title="Gedeeld veld" style="color:var(--primary-color);font-size:0.75rem;"><i class="fas fa-link"></i></span>
                            <span class="field-type-badge" style="font-size:0.7rem;color:var(--text-muted);background:var(--background-subtle);padding:0.2rem 0.5rem;border-radius:4px;">{{ field.type }}</span>
                            <button type="button" class="btn-row" title="Edit settings" @click.stop="selectField(index)"><i class="fas fa-cog"></i></button>
                            <button type="button" class="btn-row" title="Duplicate" @click.stop="duplicateField(index)"><i class="fas fa-copy"></i></button>
                            <button type="button" class="btn-row btn-delete" title="Delete" @click.stop="deleteField(index)"><i class="fas fa-trash-alt"></i></button>
                        </li>
                    </ul>
                    <div ref="canvasGroupsRef" v-else :key="'canvas-grouped-' + canvasListKey" class="field-builder-canvas-grouped" style="margin:0;padding:0;">
                        <div v-for="(seg, si) in builderSegments" :key="'seg-' + si + '-' + (seg.tabIdx !== null ? seg.tabIdx : 'lead')"
                             class="field-builder-section"
                             :data-segment-leading="seg.tabIdx === null ? '1' : '0'"
                             :data-tab-uid="seg.tabIdx !== null && activeFields[seg.tabIdx] ? activeFields[seg.tabIdx]._uid : null">
                            <div class="field-builder-section-header">
                                <span class="field-builder-section-handle" title="Drag the whole Field Group section to reorder">
                                    <i class="fas fa-grip-vertical" style="color:var(--text-muted);cursor:grab;"></i>
                                </span>
                                <span class="field-builder-section-title" style="font-weight:600;font-size:0.9rem;">
                                    {{ seg.tabIdx === null ? 'General' : ((activeFields[seg.tabIdx] && activeFields[seg.tabIdx].label) || 'Field Group') }}
                                </span>
                                <span v-if="seg.tabIdx !== null" class="field-type-badge" style="font-size:0.65rem;margin-left:0.25rem;">field group</span>
                                <button v-if="seg.tabIdx !== null" type="button" class="btn btn-secondary btn-sm" style="margin-left:0.35rem;padding:0.15rem 0.45rem;font-size:0.75rem;" @click.stop="selectField(seg.tabIdx)">Settings</button>
                            </div>
                            <ul class="field-builder-group-inner active-fields-ul" style="list-style:none;margin:0;padding:0.35rem 0 0.75rem 1.25rem;">
                                <li v-for="fi in seg.fieldIndices" :key="activeFields[fi]._uid || ('row-' + fi)"
                                    class="active-field-row"
                                    :class="{ selected: selectedIndex === fi }"
                                    style="display:flex;align-items:center;gap:var(--spacing-sm);padding:0.5rem 0.75rem;margin:0.4rem 0;background:var(--surface);border:1px solid var(--border-color);border-radius:var(--radius-sm);cursor:pointer;"
                                    :data-flat-index="fi"
                                    :data-af-uid="activeFields[fi]._uid"
                                    @click="selectField(fi)">
                                    <label v-if="config.mode === 'modular'" class="show-on-creation-toggle" style="display:flex;align-items:center;flex-shrink:0;" @click.stop title="Tonen bij nieuw bericht (content), niet bij nieuw post type">
                                        <input type="checkbox" v-model="activeFields[fi].show_on_creation">
                                    </label>
                                    <span class="drag-handle" title="Drag to reorder" style="cursor:grab;color:var(--text-muted);"><i class="fas fa-grip-vertical"></i></span>
                                    <button type="button" class="field-row-collapse" style="width:1.25rem;height:1.25rem;padding:0;margin:0;border:none;background:none;cursor:pointer;color:var(--text-muted);" @click.stop="toggleSettings(fi)" :aria-label="selectedIndex === fi ? 'Close settings' : 'Open settings'">
                                        <i class="fas" :class="selectedIndex === fi ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                    </button>
                                    <span class="field-label" style="flex:1;min-width:0;font-weight:500;display:flex;align-items:center;gap:0.35rem;">
                                        <i class="fas" :class="getFieldIcon(activeFields[fi])" style="color:var(--text-muted);"></i>
                                        {{ activeFields[fi].label || activeFields[fi].name || activeFields[fi].type }}
                                        <span v-if="activeFields[fi].required" class="required-badge" style="color:var(--error-text);font-weight:600;">*</span>
                                    </span>
                                    <span v-if="activeFields[fi]._shared_id" class="shared-badge" title="Gedeeld veld" style="color:var(--primary-color);font-size:0.75rem;"><i class="fas fa-link"></i></span>
                                    <span class="field-type-badge" style="font-size:0.7rem;color:var(--text-muted);background:var(--background-subtle);padding:0.2rem 0.5rem;border-radius:4px;">{{ activeFields[fi].type }}</span>
                                    <button type="button" class="btn-row" title="Edit settings" @click.stop="selectField(fi)"><i class="fas fa-cog"></i></button>
                                    <button type="button" class="btn-row" title="Duplicate" @click.stop="duplicateField(fi)"><i class="fas fa-copy"></i></button>
                                    <button type="button" class="btn-row btn-delete" title="Delete" @click.stop="deleteField(fi)"><i class="fas fa-trash-alt"></i></button>
                                </li>
                            </ul>
                        </div>
                    </div>
                    </template>
                    <div v-show="isDropActive && libraryInsertIndex >= 0" class="active-fields-insert-line visible" style="position:absolute;left:var(--spacing-md);right:var(--spacing-md);height:0;border-top:3px dashed #2d8016;pointer-events:none;z-index:10;" :style="{ top: insertLineTop + 'px' }"></div>
                </div>
            </div>

            <!-- Modular fields card removed: active fields live in canvas -->
        </div>
    </div>

    <!-- Settings panel (slide-in) -->
    <div v-show="settingsVisible" class="field-settings-overlay visible" style="position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:1000;" @click="closeSettings" aria-hidden="true"></div>
    <div v-show="settingsVisible" class="field-settings-panel open" style="position:fixed;right:0;top:0;bottom:0;width:min(480px,95vw);background:#fff;box-shadow:-2px 0 16px rgba(0,0,0,0.12);z-index:1001;display:flex;flex-direction:column;overflow:hidden;">
        <div class="field-settings-panel-inner" style="display:flex;flex-direction:column;height:100%;overflow:hidden;">
            <div class="field-settings-header" style="padding:var(--spacing-md);border-bottom:1px solid var(--border-color);display:flex;align-items:center;gap:0.75rem;flex-shrink:0;">
                <span class="settings-icon-wrap" style="width:36px;height:36px;border-radius:999px;display:flex;align-items:center;justify-content:center;background:var(--background-subtle);color:var(--text-muted);">
                    <i class="fas" :class="getFieldIcon(selectedField)"></i>
                </span>
                <div style="min-width:0;flex:1;">
                    <div class="settings-header-label" style="font-weight:600;font-size:0.95rem;">{{ selectedField ? (selectedField.label || selectedField.name || selectedField.type) : 'Field' }}</div>
                    <div class="settings-header-type" style="font-size:0.75rem;color:var(--text-muted);">{{ selectedField ? selectedField.type : '' }}</div>
                </div>
                <button type="button" class="field-settings-close" style="width:2rem;height:2rem;padding:0;border:none;background:transparent;font-size:1.5rem;line-height:1;cursor:pointer;color:var(--text-muted);" @click="closeSettings" aria-label="Close">×</button>
            </div>
            <div class="field-settings-tabs" style="display:flex;border-bottom:1px solid var(--border-color);padding:0 var(--spacing-md);flex-shrink:0;">
                <button v-for="t in ['general','validation','display','logic']" :key="t" type="button" class="field-settings-tab" :class="{ active: settingsTab === t }" style="padding:0.5rem 0.75rem;font-size:0.875rem;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-1px;cursor:pointer;" :style="settingsTab === t ? 'color:var(--primary-color);border-bottom-color:var(--primary-color);font-weight:500;' : 'color:var(--text-muted);'" @click="settingsTab = t">{{ t.charAt(0).toUpperCase() + t.slice(1) }}</button>
            </div>
            <div class="field-settings-tab-content" style="flex:1;overflow-y:auto;padding:var(--spacing-md);min-height:0;">
                <template v-for="item in currentTabFormItems" :key="item.key">
                    <div class="form-group" style="margin-bottom:var(--spacing-md);">
                        <label style="display:block;margin-bottom:0.25rem;font-size:0.875rem;">{{ item.label }}</label>
                        <input v-if="item.inputType === 'boolean'" type="checkbox" v-model="selectedField[item.key]" />
                        <input v-else-if="item.inputType === 'number'" type="number" class="form-control" v-model.number="selectedField[item.key]" :step="item.meta && item.meta.type === 'number' ? 'any' : '1'" />
                        <textarea v-else-if="item.inputType === 'choices'" class="form-control" rows="5" :value="getChoicesText(item.key)" @input="setChoicesText(item.key, $event.target.value)" placeholder="value : Label"></textarea>
                        <textarea v-else-if="item.inputType === 'json'" class="form-control" rows="6" style="font-family:monospace;font-size:0.8rem;" :value="getJsonText(item.key)" @input="setJsonText(item.key, $event.target.value)"></textarea>
                        <div v-else-if="item.inputType === 'post_type'" class="multi-select-list" style="display:flex;flex-direction:column;gap:0.35rem;max-height:12rem;overflow-y:auto;">
                            <label v-for="pt in config.availablePostTypes" :key="pt.slug || pt.id" class="checkbox-label" style="display:flex;align-items:center;gap:0.5rem;font-weight:normal;">
                                <input type="checkbox" :value="pt.slug || pt.id" :checked="(selectedField[item.key] || []).indexOf(pt.slug || pt.id) >= 0" @change="togglePostType(item.key, pt.slug || pt.id, $event.target.checked)" />
                                {{ pt.name || pt.slug }}
                            </label>
                        </div>
                        <div v-else-if="item.inputType === 'taxonomy'" class="multi-select-list" style="display:flex;flex-direction:column;gap:0.35rem;max-height:12rem;overflow-y:auto;">
                            <label v-for="tax in config.availableTaxonomies" :key="tax.slug || tax.id" class="checkbox-label" style="display:flex;align-items:center;gap:0.5rem;font-weight:normal;">
                                <input type="checkbox" :value="tax.slug || tax.id" :checked="(selectedField[item.key] || []).indexOf(tax.slug || tax.id) >= 0" @change="toggleTaxonomy(item.key, tax.slug || tax.id, $event.target.checked)" />
                                {{ tax.name || tax.slug }}
                            </label>
                        </div>
                        <select v-else-if="item.inputType === 'select'" class="form-control" v-model="selectedField[item.key]">
                            <option v-for="opt in (item.options || [])" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <textarea v-else-if="item.rows > 1" class="form-control" :rows="item.rows" v-model="selectedField[item.key]"></textarea>
                        <input v-else type="text" class="form-control" v-model="selectedField[item.key]" />
                    </div>
                </template>
                <!-- Sharing section (General tab only) -->
                <div v-if="settingsTab === 'general' && selectedField && selectedField.type" class="field-sharing-section" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border-color);">
                    <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;">
                        <i class="fas fa-link" style="margin-right:0.25rem;"></i> Delen
                    </label>
                    <!-- Already shared -->
                    <template v-if="selectedField._shared_id">
                        <p style="font-size:0.85rem;color:var(--text-muted);margin:0 0 0.5rem;">
                            <i class="fas fa-link" style="color:var(--primary-color);"></i>
                            Dit veld is gedeeld met {{ getSharedPostTypeNames(selectedField._shared_id) }}.
                        </p>
                        <label class="flex align-center gap-2" style="margin:0.5rem 0;font-size:0.85rem;font-weight:normal;cursor:pointer;">
                            <input type="checkbox" v-model="selectedField.attach_to_new_post_types" />
                            <span>Bij nieuw post type automatisch toevoegen</span>
                        </label>
                        <p class="help-text" style="font-size:0.75rem;color:var(--text-muted);margin:0 0 0.75rem;">Voegt dit gedeelde veld toe aan het schema wanneer je een nieuw post type aanmaakt (los van de vinkjes &quot;bij nieuw bericht&quot; hierboven).</p>
                        <button type="button" class="btn btn-secondary btn-sm" @click="detachSharedField(selectedIndex)">
                            <i class="fas fa-unlink"></i> Lostrekken
                        </button>
                    </template>
                    <!-- Not shared yet: offer to share -->
                    <template v-else>
                        <p style="font-size:0.8rem;color:var(--text-muted);margin:0 0 0.5rem;">Deel dit veld met andere post types:</p>
                        <div style="display:flex;flex-direction:column;gap:0.35rem;max-height:10rem;overflow-y:auto;">
                            <label v-for="pt in config.otherPostTypes" :key="pt.id" style="display:flex;align-items:center;gap:0.5rem;font-weight:normal;font-size:0.85rem;">
                                <input type="checkbox"
                                    :value="pt.id"
                                    :checked="(selectedField._share_with_post_types || []).indexOf(pt.id) >= 0"
                                    @change="toggleShareWith(selectedIndex, pt.id, $event.target.checked)" />
                                {{ pt.name }}
                            </label>
                        </div>
                    </template>
                </div>

                <div v-if="selectedField && selectedField.type === 'group'" class="field-builder-group-subpanel" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border-color);">
                    <label style="display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.35rem;">Fields inside this group</label>
                    <p class="help-text" style="font-size:0.8rem;color:var(--text-muted);margin-bottom:0.75rem;">Manage sub-fields here (not drag from canvas). Add <strong>Field Group</strong> (utilities) between sub-fields for segments. Under <em>Display → Inner tabs layout</em>, choose stacked, top tabs, or sidebar (ACF-style).</p>
                    <ul v-if="(selectedField.sub_fields || []).length" style="list-style:none;margin:0;padding:0;">
                        <li v-for="(sub, si) in selectedField.sub_fields" :key="sub._uid || ('grp-sub-' + si)" style="display:flex;align-items:center;gap:0.45rem;padding:0.4rem 0.5rem;margin-bottom:0.35rem;background:var(--background-subtle);border:1px solid var(--border-color);border-radius:var(--radius-sm);font-size:0.85rem;">
                            <span style="flex:1;min-width:0;">
                                <i class="fas" :class="getFieldIcon(sub)" style="margin-right:0.35rem;color:var(--text-muted);"></i>{{ sub.label || sub.name }}
                                <span style="color:var(--text-muted);">({{ sub.type }})</span>
                            </span>
                            <button type="button" class="btn btn-secondary btn-sm" style="padding:0.15rem 0.4rem;font-size:0.7rem;" :disabled="si === 0" @click="moveGroupSubField(si, -1)" title="Omhoog"><i class="fas fa-arrow-up"></i></button>
                            <button type="button" class="btn btn-secondary btn-sm" style="padding:0.15rem 0.4rem;font-size:0.7rem;" :disabled="si >= selectedField.sub_fields.length - 1" @click="moveGroupSubField(si, 1)" title="Omlaag"><i class="fas fa-arrow-down"></i></button>
                            <button type="button" class="btn btn-secondary btn-sm" style="padding:0.15rem 0.4rem;font-size:0.7rem;color:var(--error-text);" title="Verwijderen" @click="removeGroupSubField(si)"><i class="fas fa-trash-alt"></i></button>
                        </li>
                    </ul>
                    <div style="margin-top:0.65rem;display:flex;align-items:center;gap:0.5rem;">
                        <select class="form-control" style="flex:1;font-size:0.875rem;" @change="onGroupSubFieldAddSelect($event)">
                            <option value="">Add field…</option>
                            <optgroup v-for="cat in config.categoryOrder" :key="'grp-add-' + cat" :label="cat">
                                <option v-for="lf in filteredLibraryByCategory(cat)" :key="lf.type + '-' + cat" :value="lf.type">{{ lf.label }} — {{ lf.type }}</option>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#field-builder-vue-app .active-field-row.selected { border-color: var(--primary-color); background: rgba(45, 128, 22, 0.06); }
#field-builder-vue-app .active-field-row .btn-row { padding: 0.2rem 0.4rem; background: none; border: none; cursor: pointer; color: var(--text-muted); border-radius: var(--radius-sm); }
#field-builder-vue-app .active-field-row .btn-row:hover { color: var(--text); background: var(--hover-bg); }
#field-builder-vue-app .active-field-row .btn-row.btn-delete:hover { color: var(--error-text); }
#field-builder-vue-app .active-fields-list.drop-active { border-color: #2d8016; background: rgba(45, 128, 22, 0.08); }
#field-builder-vue-app .sortable-ghost { height: 4px; min-height: 4px; padding: 0; margin: 0.5rem 0; border: none; background: transparent; }
#field-builder-vue-app .sortable-ghost::after { content: ""; display: block; border-top: 3px dashed #2d8016; }
#field-builder-vue-app .sortable-ghost > * { display: none !important; }
/* Ensure field rows stay visible after drag; Sortable may leave chosen/drag class on dropped node */
#field-builder-vue-app .active-fields-ul .active-field-row { opacity: 1 !important; visibility: visible !important; }
#field-builder-vue-app .active-fields-ul .sortable-chosen.active-field-row,
#field-builder-vue-app .active-fields-ul .sortable-drag.active-field-row { opacity: 1 !important; visibility: visible !important; }
#field-builder-vue-app .field-builder-canvas-grouped {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}
#field-builder-vue-app .field-builder-section {
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    background: rgba(0, 0, 0, 0.03);
}
#field-builder-vue-app .field-builder-group-subpanel .btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
#field-builder-vue-app .field-builder-section-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 0.6rem;
    border-bottom: 1px solid var(--border-color);
    background: rgba(45, 128, 22, 0.06);
    border-radius: var(--radius-md) var(--radius-md) 0 0;
}
#field-builder-vue-app .field-builder-section-handle {
    cursor: grab;
    display: inline-flex;
    align-items: center;
    padding-right: 0.15rem;
}
#field-builder-vue-app .field-builder-section-handle:active {
    cursor: grabbing;
}
</style>
<script>
window.MODULAR_FIELD_BUILDER_CONFIG = <?php echo json_encode($config); ?>;
</script>
<script src="<?php echo htmlspecialchars($config['adminUrl']); ?>/assets/js/sortable.js"></script>
<script src="<?php echo htmlspecialchars($config['adminUrl']); ?>/assets/vendor/vue/vue.global.prod.js"></script>
<script src="<?php echo htmlspecialchars($config['adminUrl']); ?>/assets/vendor/primevue/primevue.min.js"></script>
<link rel="stylesheet" href="<?php echo htmlspecialchars($config['adminUrl']); ?>/assets/vendor/primevue/primevue.min.css">
<script src="<?php echo htmlspecialchars($config['adminUrl']); ?>/field-builder/field-builder.js"></script>
<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
