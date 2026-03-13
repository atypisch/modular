<?php
/**
 * Simuze Community – Group Types admin (CRUD)
 * Aligned with WP plugin: add/edit/delete, list with ID, Name, Slug, Icon, Actions.
 */
$db = $GLOBALS['db'] ?? null;
if (!defined('SIMUZE_DB_PREFIX')) {
    define('SIMUZE_DB_PREFIX', 'simuze_');
}
$prefix = SIMUZE_DB_PREFIX;
$table = $prefix . 'group_types';
$baseUrl = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=group-types';

// If table is missing, run module migrations (e.g. module was activated before migrations existed)
if ($db instanceof PDO) {
    $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($table));
    if ($stmt && $stmt->rowCount() === 0) {
        $migrationsDir = dirname(__DIR__) . '/migrations';
        if (is_dir($migrationsDir)) {
            $files = glob($migrationsDir . '/*.php');
            sort($files);
            foreach ($files as $f) {
                require_once $f;
            }
            header('Location: ' . $baseUrl . '&message=migrated');
            exit;
        }
    }
}

// Handle POST (add / edit / delete) before any output
if ($db && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $redirectMsg = '';
    try {
        $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($table));
        if ($stmt->rowCount() === 0) {
            $redirectMsg = 'error=table';
        } else {
            if ($action === 'add' && isset($_POST['name'], $_POST['slug'])) {
                $name = trim((string) $_POST['name']);
                $slug = trim((string) $_POST['slug']);
                $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($slug));
                $icon = isset($_POST['icon']) ? trim((string) $_POST['icon']) : null;
                if ($name !== '' && $slug !== '') {
                    $exists = $db->prepare("SELECT id FROM {$table} WHERE slug = ? AND deleted_at IS NULL");
                    $exists->execute([$slug]);
                    if ($exists->fetchColumn()) {
                        $redirectMsg = 'error=slug';
                    } else {
                        $ins = $db->prepare("INSERT INTO {$table} (name, slug, icon, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                        $ins->execute([$name, $slug, $icon ?: null]);
                        $redirectMsg = 'message=added';
                    }
                } else {
                    $redirectMsg = 'error=missing';
                }
            } elseif ($action === 'edit' && isset($_POST['id'], $_POST['name'], $_POST['slug'])) {
                $id = (int) $_POST['id'];
                $name = trim((string) $_POST['name']);
                $slug = trim((string) $_POST['slug']);
                $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($slug));
                $icon = isset($_POST['icon']) ? trim((string) $_POST['icon']) : null;
                if ($id > 0 && $name !== '' && $slug !== '') {
                    $exists = $db->prepare("SELECT id FROM {$table} WHERE slug = ? AND id != ? AND deleted_at IS NULL");
                    $exists->execute([$slug, $id]);
                    if ($exists->fetchColumn()) {
                        $redirectMsg = 'error=slug';
                    } else {
                        $upd = $db->prepare("UPDATE {$table} SET name = ?, slug = ?, icon = ?, updated_at = NOW() WHERE id = ?");
                        $upd->execute([$name, $slug, $icon ?: null, $id]);
                        $redirectMsg = 'message=updated';
                    }
                } else {
                    $redirectMsg = 'error=missing';
                }
            } elseif ($action === 'delete' && isset($_POST['id'])) {
                $id = (int) $_POST['id'];
                if ($id > 0) {
                    $upd = $db->prepare("UPDATE {$table} SET deleted_at = NOW() WHERE id = ?");
                    $upd->execute([$id]);
                    $redirectMsg = 'message=deleted';
                }
            }
        }
    } catch (Throwable $e) {
        $redirectMsg = 'error=failed';
    }
    if ($redirectMsg !== '') {
        header('Location: ' . $baseUrl . '&' . $redirectMsg);
        exit;
    }
}

$message = '';
$messageType = 'success';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'added': $message = 'Group type added successfully.'; break;
        case 'updated': $message = 'Group type updated successfully.'; break;
        case 'deleted': $message = 'Group type deleted successfully.'; break;
        case 'migrated': $message = 'Database tables created. You can add group types now.'; break;
        default: $message = ''; break;
    }
}
if (isset($_GET['error'])) {
    $messageType = 'danger';
    switch ($_GET['error']) {
        case 'table': $message = 'Group types table not found. Activate the module to run migrations.'; break;
        case 'slug': $message = 'A group type with this slug already exists.'; break;
        case 'missing': $message = 'Name and slug are required.'; break;
        case 'failed': $message = 'An error occurred. Please try again.'; break;
        default: $message = 'An error occurred.'; break;
    }
}

$groupTypes = [];
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
if ($db) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($table));
        if ($stmt->rowCount() > 0) {
            $stmt = $db->query("SELECT id, name, slug, icon FROM {$table} WHERE deleted_at IS NULL ORDER BY name ASC");
            $groupTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) { }
}
$editRow = null;
if ($editId > 0) {
    foreach ($groupTypes as $row) {
        if ((int) $row['id'] === $editId) {
            $editRow = $row;
            break;
        }
    }
}
?>
<div class="container">
    <div class="page-header">
        <h1>Group Types</h1>
        <p>Manage group types (e.g. Artist, Label, City). Add new types or edit existing ones.</p>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <div class="card">
        <div class="card-body">
            <h2 class="h5"><?php echo $editRow ? 'Edit Group Type' : 'Add New Group Type'; ?></h2>
            <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>">
                <input type="hidden" name="action" value="<?php echo $editRow ? 'edit' : 'add'; ?>">
                <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo (int) $editRow['id']; ?>"><?php endif; ?>
                <div class="form-group">
                    <label for="gt-name">Name</label>
                    <input type="text" id="gt-name" name="name" class="form-control" required
                           value="<?php echo $editRow ? htmlspecialchars($editRow['name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="gt-slug">Slug</label>
                    <input type="text" id="gt-slug" name="slug" class="form-control" required
                           value="<?php echo $editRow ? htmlspecialchars($editRow['slug']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="gt-icon">Icon (optional)</label>
                    <input type="text" id="gt-icon" name="icon" class="form-control" placeholder="dashicons-admin-users"
                           value="<?php echo $editRow ? htmlspecialchars($editRow['icon'] ?? '') : ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $editRow ? 'Update Group Type' : 'Add Group Type'; ?></button>
                <?php if ($editRow): ?>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="card mt-4">
        <div class="card-body">
            <h2 class="h5">Existing Group Types</h2>
            <?php if (empty($groupTypes)): ?>
                <p>No group types yet. Add one above.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Icon</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupTypes as $t): ?>
                            <tr>
                                <td><?php echo (int) $t['id']; ?></td>
                                <td><?php echo htmlspecialchars($t['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($t['slug'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($t['icon'] ?? ''); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($baseUrl . '&edit=' . (int) $t['id']); ?>">Edit</a>
                                    |
                                    <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>" class="d-inline" onsubmit="return confirm('Delete this group type?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">
                                        <button type="submit" class="btn btn-link p-0 text-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
