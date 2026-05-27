<?php
/**
 * Settings > Navigation
 * Create and edit navigations; add items from post types via drag & drop.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/functions/permalinks.php';

if (!is_logged_in()) {
    header('Location: ' . ADMIN_URL . '/auth/login.php');
    exit;
}

$page_title = 'Navigation';
$settings_active = true;
$db = $GLOBALS['db'];

$base_path = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
$option_navigations = 'navigations';
$default_navigations = [['slug' => 'main', 'name' => 'Main Navigation']];

// Load navigations list
$navigations = $default_navigations;
try {
    $stmt = $db->prepare("SELECT option_value FROM settings WHERE option_name = ? LIMIT 1");
    $stmt->execute([$option_navigations]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['option_value'] !== '') {
        $dec = json_decode($row['option_value'], true);
        if (is_array($dec) && !empty($dec)) {
            $navigations = $dec;
        }
    }
} catch (Exception $e) {
    // keep default
}

// Save navigations list (add new)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_navigation') {
    if (!function_exists('csrf_verify') || !csrf_verify()) {
        $_SESSION['error'] = 'Invalid request.';
    } else {
        $name = trim($_POST['nav_name'] ?? '');
        $slug = trim($_POST['nav_slug'] ?? '');
        if ($name !== '' && $slug !== '') {
            $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($slug));
            if ($slug === '') $slug = 'menu-' . count($navigations);
            $exists = false;
            foreach ($navigations as $n) {
                if (($n['slug'] ?? '') === $slug) { $exists = true; break; }
            }
            if (!$exists) {
                $navigations[] = ['slug' => $slug, 'name' => $name];
                $json = json_encode($navigations);
                $stmt = $db->prepare("INSERT INTO settings (option_name, option_value, autoload, created_at, updated_at) VALUES (?, ?, 0, NOW(), NOW()) ON DUPLICATE KEY UPDATE option_value = ?, updated_at = NOW()");
                $stmt->execute([$option_navigations, $json, $json]);
                $_SESSION['success'] = 'Navigation added.';
            } else {
                $_SESSION['error'] = 'A navigation with that slug already exists.';
            }
        } else {
            $_SESSION['error'] = 'Name and slug are required.';
        }
        header('Location: ' . ADMIN_URL . '/settings/navigation.php');
        exit;
    }
}

// Delete navigation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_navigation') {
    if (!function_exists('csrf_verify') || !csrf_verify()) {
        $_SESSION['error'] = 'Invalid request.';
    } else {
        $slug = trim($_POST['nav_slug'] ?? '');
        if ($slug !== '' && $slug !== 'main') {
            $navigations = array_values(array_filter($navigations, function ($n) use ($slug) { return ($n['slug'] ?? '') !== $slug; }));
            $stmt = $db->prepare("INSERT INTO settings (option_name, option_value, autoload, created_at, updated_at) VALUES (?, ?, 0, NOW(), NOW()) ON DUPLICATE KEY UPDATE option_value = ?, updated_at = NOW()");
            $stmt->execute([$option_navigations, json_encode($navigations), json_encode($navigations)]);
            $db->prepare("DELETE FROM settings WHERE option_name = ?")->execute(['site_menu_' . $slug]);
            $_SESSION['success'] = 'Navigation removed.';
        }
        header('Location: ' . ADMIN_URL . '/settings/navigation.php');
        exit;
    }
}

$edit_slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;

// Load menu items for edit view
$menu_items = [];
if ($edit_slug !== null && $edit_slug !== '') {
    try {
        $stmt = $db->prepare("SELECT option_value FROM settings WHERE option_name = ? LIMIT 1");
        $stmt->execute(['site_menu_' . $edit_slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['option_value'] !== '') {
            $dec = json_decode($row['option_value'], true);
            if (is_array($dec)) $menu_items = $dec;
        }
    } catch (Exception $e) {
        // empty
    }
}

// Save menu items (edit view)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $edit_slug !== null && isset($_POST['action']) && $_POST['action'] === 'save_menu') {
    if (!function_exists('csrf_verify') || !csrf_verify()) {
        $_SESSION['error'] = 'Invalid request.';
    } else {
        $raw = $_POST['menu_items'] ?? '[]';
        $items = json_decode($raw, true);
        if (!is_array($items)) $items = [];
        $normalized = [];
        foreach ($items as $it) {
            $label = isset($it['label']) ? trim($it['label']) : '';
            $url = isset($it['url']) ? trim($it['url']) : '#';
            $children = isset($it['children']) && is_array($it['children']) ? $it['children'] : [];
            $entry = ['label' => $label, 'url' => $url, 'children' => $children];
            if (!empty($it['type']) && $it['type'] === 'post' && !empty($it['post_id'])) {
                $entry['type'] = 'post';
                $entry['post_id'] = (int) $it['post_id'];
                $entry['post_type'] = isset($it['post_type']) ? trim($it['post_type']) : '';
            } else {
                $entry['type'] = 'custom';
            }
            $normalized[] = $entry;
        }
        $stmt = $db->prepare("INSERT INTO settings (option_name, option_value, autoload, created_at, updated_at) VALUES (?, ?, 0, NOW(), NOW()) ON DUPLICATE KEY UPDATE option_value = ?, updated_at = NOW()");
        $stmt->execute(['site_menu_' . $edit_slug, json_encode($normalized), json_encode($normalized)]);
        $_SESSION['success'] = 'Menu saved.';
        header('Location: ' . ADMIN_URL . '/settings/navigation.php?slug=' . urlencode($edit_slug));
        exit;
    }
}

// Load all post types and their published posts (for "available" panel – overview per type).
// Posts are flattened depth-first so parents appear before children, with depth carried along.
$available_by_type = [];
try {
    $stmt = $db->query("SELECT id, name, slug FROM post_types WHERE deleted_at IS NULL ORDER BY name");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($types as $pt) {
        $stmt = $db->prepare("
            SELECT p.id, p.parent_id, p.title, p.slug FROM posts p
            WHERE p.post_type_id = ? AND p.deleted_at IS NULL AND p.status = 'published'
            ORDER BY p.title ASC
        ");
        $stmt->execute([$pt['id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $byParent = [];
        $ownIds = [];
        foreach ($rows as $r) {
            $ownIds[(int) $r['id']] = true;
        }
        foreach ($rows as $r) {
            $pid = $r['parent_id'] !== null ? (int) $r['parent_id'] : 0;
            // Treat parents that are not in this post type's published set as roots
            if ($pid !== 0 && empty($ownIds[$pid])) {
                $pid = 0;
            }
            $byParent[$pid][] = $r;
        }

        $flat = [];
        $walk = function ($parentId, $depth) use (&$walk, &$byParent, &$flat, $db) {
            $children = $byParent[$parentId] ?? [];
            foreach ($children as $row) {
                $path = get_hierarchical_post_path((int) $row['id'], $db);
                $row['depth'] = $depth;
                $row['url_path'] = $path !== '' ? $path : (string) $row['slug'];
                $flat[] = $row;
                $walk((int) $row['id'], $depth + 1);
            }
        };
        $walk(0, 0);

        $available_by_type[] = [
            'post_type' => $pt,
            'posts' => $flat,
        ];
    }
} catch (Exception $e) {
    // leave empty
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Navigation</h1>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if ($edit_slug === null || $edit_slug === ''): ?>
        <!-- List navigations -->
        <div class="card">
            <h2>Navigations</h2>
            <p class="text-muted">Create menus and assign items from post types. The <strong>Main Navigation</strong> is used in the default theme header.</p>
            <ul class="nav-list-settings">
                <?php foreach ($navigations as $nav): ?>
                    <?php $s = $nav['slug'] ?? ''; $n = $nav['name'] ?? $s; ?>
                    <li>
                        <a href="<?php echo ADMIN_URL; ?>/settings/navigation.php?slug=<?php echo urlencode($s); ?>"><?php echo htmlspecialchars($n); ?></a>
                        <?php if ($s !== 'main'): ?>
                            <form method="post" class="inline-form">
                                <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>
                                <input type="hidden" name="action" value="delete_navigation">
                                <input type="hidden" name="nav_slug" value="<?php echo htmlspecialchars($s); ?>">
                                <button type="submit" class="btn btn-sm btn-ghost" data-confirm="Weet u zeker dat u deze navigatie wilt verwijderen?">Remove</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <form method="post" class="form-inline">
                <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>
                <input type="hidden" name="action" value="add_navigation">
                <input type="text" name="nav_name" placeholder="Menu name" required>
                <input type="text" name="nav_slug" placeholder="slug (e.g. footer)" pattern="[a-z0-9_-]+" title="Lowercase letters, numbers, underscore, hyphen">
                <button type="submit" class="btn btn-primary">Add navigation</button>
            </form>
        </div>
    <?php else: ?>
        <!-- Edit one navigation: drag & drop -->
        <?php
        $current_name = 'Main Navigation';
        foreach ($navigations as $n) {
            if (($n['slug'] ?? '') === $edit_slug) {
                $current_name = $n['name'] ?? $edit_slug;
                break;
            }
        }
        ?>
        <div class="card nav-editor-header">
            <p><a href="<?php echo ADMIN_URL; ?>/settings/navigation.php">&larr; Back to navigations</a></p>
            <h2><?php echo htmlspecialchars($current_name); ?></h2>
            <p class="text-muted">Drag items from the left into the menu on the right. Reorder by dragging. Add custom links below.</p>
        </div>

        <div class="nav-editor">
            <div class="nav-editor-available card">
                <h3>Available items</h3>
                <p class="text-muted small">Drag a link into the dropzone on the right to add it to this navigation.</p>
                <?php foreach ($available_by_type as $group): ?>
                    <?php $ptSlug = $group['post_type']['slug'] ?? ''; ?>
                    <div class="nav-group" data-pt-slug="<?php echo htmlspecialchars($ptSlug); ?>">
                        <button type="button" class="nav-group-toggle" aria-expanded="true">
                            <i class="fas fa-chevron-down nav-group-caret" aria-hidden="true"></i>
                            <strong><?php echo htmlspecialchars($group['post_type']['name']); ?></strong>
                            <span class="nav-group-count"><?php echo count($group['posts']); ?></span>
                        </button>
                        <div class="nav-group-body">
                            <ul class="nav-available-list" data-base="<?php echo htmlspecialchars($base_path); ?>">
                                <?php foreach ($group['posts'] as $post): ?>
                                    <?php
                                    $depth = isset($post['depth']) ? (int) $post['depth'] : 0;
                                    $urlPath = isset($post['url_path']) && $post['url_path'] !== '' ? $post['url_path'] : (string) $post['slug'];
                                    $fullUrl = $base_path . '/' . ltrim($urlPath, '/');
                                    $prefix = $depth > 0 ? str_repeat('-', $depth) . ' ' : '';
                                    ?>
                                    <li class="nav-available-item" draggable="true"
                                        data-type="post"
                                        data-post-id="<?php echo (int)$post['id']; ?>"
                                        data-post-type="<?php echo htmlspecialchars($ptSlug); ?>"
                                        data-label="<?php echo htmlspecialchars($post['title']); ?>"
                                        data-url="<?php echo htmlspecialchars($fullUrl); ?>"
                                        data-depth="<?php echo $depth; ?>"
                                        style="<?php echo $depth > 0 ? 'padding-left:' . (0.75 + $depth * 1.0) . 'rem;' : ''; ?>">
                                        <span class="nav-available-prefix" aria-hidden="true"><?php echo htmlspecialchars($prefix); ?></span><?php echo htmlspecialchars($post['title']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (empty($group['posts'])): ?>
                                <p class="nav-empty-hint text-muted">No published items</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($available_by_type)): ?>
                    <p class="text-muted">No post types found. Add items below as custom links.</p>
                <?php endif; ?>
            </div>
            <aside class="nav-editor-menu">
                <h3>Dropzone – <?php echo htmlspecialchars($current_name); ?></h3>
                <p class="text-muted small">Drop items here to build the menu. Reorder by dragging.</p>
                <form method="post" id="nav-menu-form">
                    <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>
                    <input type="hidden" name="action" value="save_menu">
                    <input type="hidden" name="menu_items" id="menu_items_input" value="<?php echo htmlspecialchars(json_encode($menu_items)); ?>">
                    <div class="nav-dropzone" id="nav-dropzone">
                        <ul class="nav-menu-list" id="nav-menu-list">
                            <?php foreach ($menu_items as $item): ?>
                                <?php
                                $isPost = isset($item['type']) && $item['type'] === 'post' && !empty($item['post_id']);
                                $dataType = $isPost ? 'post' : 'custom';
                                $dataPostId = $isPost ? (int)$item['post_id'] : '';
                                $dataPostType = $isPost ? ($item['post_type'] ?? '') : '';
                                ?>
                                <li class="nav-menu-item" data-type="<?php echo htmlspecialchars($dataType); ?>" data-post-id="<?php echo $dataPostId; ?>" data-post-type="<?php echo htmlspecialchars($dataPostType); ?>" data-label="<?php echo htmlspecialchars($item['label'] ?? ''); ?>" data-url="<?php echo htmlspecialchars($item['url'] ?? '#'); ?>">
                                    <span class="nav-item-label"><?php echo htmlspecialchars($item['label'] ?? ''); ?></span>
                                    <span class="nav-item-url text-muted"><?php echo htmlspecialchars($item['url'] ?? '#'); ?></span>
                                    <button type="button" class="nav-remove" aria-label="Remove">×</button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="nav-dropzone-placeholder" id="nav-dropzone-placeholder"<?php echo !empty($menu_items) ? ' style="display:none"' : ''; ?>>Drag items here or add a custom link below</p>
                    </div>
                    <div class="nav-custom">
                        <input type="text" id="custom_label" placeholder="Label">
                        <input type="text" id="custom_url" placeholder="URL (e.g. /contact)">
                        <button type="button" id="nav-add-custom" class="btn btn-secondary">Add custom link</button>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save menu</button>
                    </div>
                </form>
            </aside>
        </div>
    <?php endif; ?>
</div>

<style>
.nav-list-settings { list-style: none; margin: 1rem 0; }
.nav-list-settings li { display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem; }
.nav-list-settings a { font-weight: 500; }
.form-inline { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 1rem; }
.form-inline input[type="text"] { min-width: 160px; }
/* Grid is a direct child of .container (no .card wrapper) so sticky inside engages against .main-content. */
.nav-editor {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-top: 1rem;
    align-items: start;
}
.nav-editor-available { min-width: 0; }
.nav-editor-menu {
    min-width: 0;
    align-self: start;
    position: sticky;
    top: 0.75rem;
    max-height: calc(100vh - var(--toolbar-height) - 2.5rem);
    overflow-x: hidden;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: var(--spacing-md);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
@media (max-width: 768px) {
    .nav-editor { grid-template-columns: 1fr; }
    .nav-editor-menu {
        position: static;
        max-height: none;
        overflow: visible;
    }
}
.nav-editor-available h3, .nav-editor-menu h3 { margin-bottom: 0.5rem; font-size: 1rem; }
.nav-editor-available .small, .nav-editor-menu .small { font-size: 0.875rem; margin-bottom: 0.75rem; }
.nav-group { margin-bottom: 1rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; overflow: hidden; }
.nav-group-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.6rem 0.75rem;
    background: #f8fafc;
    border: none;
    border-bottom: 1px solid #e2e8f0;
    cursor: pointer;
    text-align: left;
    font: inherit;
    color: inherit;
}
.nav-group-toggle:hover { background: #f1f5f9; }
.nav-group-toggle strong { font-weight: 600; flex: 1; }
.nav-group-caret { font-size: 0.75rem; color: #64748b; transition: transform 0.15s ease; }
.nav-group.is-collapsed .nav-group-caret { transform: rotate(-90deg); }
.nav-group.is-collapsed .nav-group-toggle { border-bottom-color: transparent; }
.nav-group.is-collapsed .nav-group-body { display: none; }
.nav-group-count {
    font-size: 0.75rem;
    color: #64748b;
    background: #e2e8f0;
    border-radius: 999px;
    padding: 0.05rem 0.5rem;
    min-width: 1.5rem;
    text-align: center;
}
.nav-group-body { padding: 0.5rem 0.75rem; }
.nav-empty-hint { font-size: 0.85rem; margin: 0.25rem 0 0 0; }
.nav-available-list { list-style: none; padding: 0; margin: 0; }
.nav-available-item, .nav-menu-item { padding: 0.5rem 0.75rem; margin-bottom: 4px; border-radius: 6px; cursor: grab; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.nav-available-item:active { cursor: grabbing; }
.nav-available-item { background: #f0f4ff; border: 1px dashed #c7d2fe; }
.nav-available-item:hover { background: #e0e7ff; }
.nav-available-prefix { color: #64748b; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: 0.04em; }
.nav-available-item[data-depth="0"] .nav-available-prefix { display: none; }
.nav-dropzone { min-height: 120px; border: 2px dashed #cbd5e1; border-radius: 8px; padding: 0.75rem; background: #f8fafc; transition: background 0.15s, border-color 0.15s; }
.nav-dropzone.drag-over { background: #f1f5f9; border-color: #2563eb; }
.nav-dropzone-placeholder { margin: 1.5rem 0; text-align: center; color: #64748b; font-size: 0.9rem; }
.nav-menu-list { list-style: none; padding: 0; margin: 0; min-height: 44px; }
.nav-menu-item { background: #fff; border: 1px solid #e2e8f0; cursor: grab; }
.nav-menu-item:active { cursor: grabbing; }
.nav-menu-item .nav-item-label { flex: 1; }
.nav-menu-item .nav-item-url { font-size: 0.85em; max-width: 180px; overflow: hidden; text-overflow: ellipsis; }
.nav-remove { background: none; border: none; cursor: pointer; font-size: 1.2rem; line-height: 1; color: #64748b; padding: 0 4px; }
.nav-remove:hover { color: #dc2626; }
.nav-custom { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 1rem; }
.nav-custom input { flex: 1; min-width: 120px; }
.form-actions { margin-top: 1rem; }
</style>

<script>
(function() {
    var list = document.getElementById('nav-menu-list');
    var input = document.getElementById('menu_items_input');
    var dropzone = document.getElementById('nav-dropzone');
    var placeholder = document.getElementById('nav-dropzone-placeholder');
    if (!list || !input) return;

    function serialize() {
        var items = [];
        list.querySelectorAll('.nav-menu-item').forEach(function(li) {
            var type = li.getAttribute('data-type') || 'custom';
            var o = {
                label: li.getAttribute('data-label') || '',
                url: li.getAttribute('data-url') || '#',
                children: []
            };
            if (type === 'post' && li.getAttribute('data-post-id')) {
                o.type = 'post';
                o.post_id = parseInt(li.getAttribute('data-post-id'), 10);
                o.post_type = li.getAttribute('data-post-type') || '';
            } else {
                o.type = 'custom';
            }
            items.push(o);
        });
        input.value = JSON.stringify(items);
        if (placeholder) placeholder.style.display = list.querySelectorAll('.nav-menu-item').length ? 'none' : 'block';
    }

    function addItem(opts) {
        var label = opts.label || '';
        var url = opts.url || '#';
        var type = opts.type || 'custom';
        var postId = opts.post_id ? String(opts.post_id) : '';
        var postType = opts.post_type || '';
        var li = document.createElement('li');
        li.className = 'nav-menu-item';
        li.setAttribute('data-type', type);
        li.setAttribute('data-post-id', postId);
        li.setAttribute('data-post-type', postType);
        li.setAttribute('data-label', label);
        li.setAttribute('data-url', url);
        li.draggable = true;
        li.innerHTML = '<span class="nav-item-label">' + escapeHtml(label) + '</span><span class="nav-item-url text-muted">' + escapeHtml(url) + '</span><button type="button" class="nav-remove" aria-label="Remove">×</button>';
        list.appendChild(li);
        bindItem(li);
        serialize();
    }

    list.addEventListener('dragstart', function(e) {
        if (e.target.classList.contains('nav-menu-item')) {
            e.target.classList.add('dragging');
            e.dataTransfer.setData('text/plain', 'reorder');
            e.dataTransfer.effectAllowed = 'move';
        }
    });
    list.addEventListener('dragend', function(e) {
        if (e.target.classList) e.target.classList.remove('dragging');
    });
    list.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        if (dropzone) dropzone.classList.add('drag-over');
        var after = getDragAfter(list, e.clientY);
        var dragging = list.querySelector('.dragging');
        if (dragging && after !== dragging) {
            if (after == null) list.appendChild(dragging);
            else list.insertBefore(dragging, after);
        }
    });
    list.addEventListener('drop', function(e) {
        e.preventDefault();
        if (dropzone) dropzone.classList.remove('drag-over');
        var label = e.dataTransfer.getData('label');
        var url = e.dataTransfer.getData('url');
        var type = e.dataTransfer.getData('type') || 'custom';
        var postId = e.dataTransfer.getData('post_id') || '';
        var postType = e.dataTransfer.getData('post_type') || '';
        if (label !== undefined && url !== undefined) {
            addItem({ label: label, url: url, type: type, post_id: postId ? parseInt(postId, 10) : null, post_type: postType });
        }
    });

    if (dropzone) {
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            dropzone.classList.add('drag-over');
        });
        dropzone.addEventListener('dragleave', function(e) {
            if (!dropzone.contains(e.relatedTarget)) dropzone.classList.remove('drag-over');
        });
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
            if (e.target.closest('#nav-menu-list')) return;
            var label = e.dataTransfer.getData('label');
            var url = e.dataTransfer.getData('url');
            var type = e.dataTransfer.getData('type') || 'custom';
            var postId = e.dataTransfer.getData('post_id') || '';
            var postType = e.dataTransfer.getData('post_type') || '';
            if (label !== undefined && url !== undefined) addItem({ label: label, url: url, type: type, post_id: postId ? parseInt(postId, 10) : null, post_type: postType });
        });
    }

    function getDragAfter(container, y) {
        var items = [].slice.call(container.querySelectorAll('.nav-menu-item:not(.dragging)'));
        for (var i = 0; i < items.length; i++) {
            var box = items[i].getBoundingClientRect();
            if (y < box.top + box.height / 2) return items[i];
        }
        return null;
    }
    function escapeHtml(s) {
        var div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }
    function bindItem(li) {
        var btn = li.querySelector('.nav-remove');
        if (btn) btn.addEventListener('click', function() { li.remove(); serialize(); });
    }
    list.querySelectorAll('.nav-menu-item').forEach(bindItem);

    document.querySelectorAll('.nav-available-item').forEach(function(li) {
        li.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('label', li.getAttribute('data-label') || '');
            e.dataTransfer.setData('url', li.getAttribute('data-url') || '#');
            e.dataTransfer.setData('type', li.getAttribute('data-type') || 'custom');
            e.dataTransfer.setData('post_id', li.getAttribute('data-post-id') || '');
            e.dataTransfer.setData('post_type', li.getAttribute('data-post-type') || '');
            e.dataTransfer.effectAllowed = 'copy';
        });
    });

    var customLabel = document.getElementById('custom_label');
    var customUrl = document.getElementById('custom_url');
    document.getElementById('nav-add-custom') && document.getElementById('nav-add-custom').addEventListener('click', function() {
        var label = (customLabel && customLabel.value.trim()) || 'Link';
        var url = (customUrl && customUrl.value.trim()) || '#';
        addItem({ label: label, url: url, type: 'custom' });
        if (customLabel) customLabel.value = '';
        if (customUrl) customUrl.value = '';
    });

    document.getElementById('nav-menu-form').addEventListener('submit', function() { serialize(); });

    // Per-post-type collapse with localStorage persistence
    var COLLAPSE_KEY = 'mdlr_nav_collapsed_pt';
    function readCollapsed() {
        try {
            var raw = localStorage.getItem(COLLAPSE_KEY);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }
    function writeCollapsed(arr) {
        try { localStorage.setItem(COLLAPSE_KEY, JSON.stringify(arr)); } catch (e) {}
    }
    var collapsed = readCollapsed();
    document.querySelectorAll('.nav-group').forEach(function(group) {
        var slug = group.getAttribute('data-pt-slug') || '';
        var toggle = group.querySelector('.nav-group-toggle');
        if (!toggle) return;
        if (slug && collapsed.indexOf(slug) !== -1) {
            group.classList.add('is-collapsed');
            toggle.setAttribute('aria-expanded', 'false');
        }
        toggle.addEventListener('click', function() {
            var nowCollapsed = !group.classList.contains('is-collapsed');
            group.classList.toggle('is-collapsed', nowCollapsed);
            toggle.setAttribute('aria-expanded', nowCollapsed ? 'false' : 'true');
            var current = readCollapsed();
            var idx = current.indexOf(slug);
            if (nowCollapsed && idx === -1) current.push(slug);
            if (!nowCollapsed && idx !== -1) current.splice(idx, 1);
            writeCollapsed(current);
        });
    });
})();
</script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
