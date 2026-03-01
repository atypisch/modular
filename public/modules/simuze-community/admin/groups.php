<?php
/**
 * Simuze Community – Groups admin (full CRUD)
 * Create, edit, delete groups. List with actions.
 */
$db = $GLOBALS['db'] ?? null;
if (!defined('SIMUZE_DB_PREFIX')) {
    define('SIMUZE_DB_PREFIX', 'simuze_');
}
$prefix = SIMUZE_DB_PREFIX;
$table = $prefix . 'groups';
$baseUrl = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=groups';

if (!$db) {
    echo '<p>Database unavailable.</p>';
    return;
}

// Check table exists
try {
    $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($table));
    if ($stmt->rowCount() === 0) {
        echo '<div class="container"><div class="card"><p>Groups table not found. Activate the Simuze Community module to run migrations.</p></div></div>';
        return;
    }
} catch (Throwable $e) {
    echo '<div class="container"><div class="card"><p>Error: ' . htmlspecialchars($e->getMessage()) . '</p></div></div>';
    return;
}

// Load group types for dropdown
$groupTypes = [];
$typesTable = $prefix . 'group_types';
try {
    $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($typesTable));
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SELECT id, name, slug FROM {$typesTable} WHERE deleted_at IS NULL ORDER BY name ASC");
        $groupTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) { }

// Handle POST (add / edit / delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $redirectMsg = '';
    try {
        if ($action === 'add' && isset($_POST['name'])) {
            $name = trim((string) $_POST['name']);
            $slug = isset($_POST['slug']) ? trim((string) $_POST['slug']) : '';
            if ($slug === '') {
                $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($name));
            } else {
                $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($slug));
            }
            $description = isset($_POST['description']) ? trim((string) $_POST['description']) : null;
            $group_type = isset($_POST['group_type']) ? trim((string) $_POST['group_type']) : 'artist';
            $owner_id = isset($_POST['owner_id']) ? (int) $_POST['owner_id'] : 0;
            $unique_handle = isset($_POST['unique_handle']) ? trim((string) $_POST['unique_handle']) : null;
            $status = isset($_POST['status']) ? trim((string) $_POST['status']) : 'active';
            if ($name !== '' && $slug !== '') {
                $exists = $db->prepare("SELECT id FROM {$table} WHERE slug = ? AND deleted_at IS NULL");
                $exists->execute([$slug]);
                if ($exists->fetchColumn()) {
                    $redirectMsg = 'error=slug';
                } else {
                    $ins = $db->prepare("INSERT INTO {$table} (name, slug, description, group_type, owner_id, unique_handle, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $ins->execute([$name, $slug, $description ?: null, $group_type, $owner_id ?: 0, $unique_handle ?: null, $status]);
                    $redirectMsg = 'message=added';
                }
            } else {
                $redirectMsg = 'error=missing';
            }
        } elseif ($action === 'edit' && isset($_POST['id'], $_POST['name'])) {
            $id = (int) $_POST['id'];
            $name = trim((string) $_POST['name']);
            $slug = isset($_POST['slug']) ? trim((string) $_POST['slug']) : '';
            if ($slug === '') {
                $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($name));
            } else {
                $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($slug));
            }
            $description = isset($_POST['description']) ? trim((string) $_POST['description']) : null;
            $group_type = isset($_POST['group_type']) ? trim((string) $_POST['group_type']) : 'artist';
            $owner_id = isset($_POST['owner_id']) ? (int) $_POST['owner_id'] : 0;
            $unique_handle = isset($_POST['unique_handle']) ? trim((string) $_POST['unique_handle']) : null;
            $status = isset($_POST['status']) ? trim((string) $_POST['status']) : 'active';
            if ($id > 0 && $name !== '' && $slug !== '') {
                $exists = $db->prepare("SELECT id FROM {$table} WHERE slug = ? AND id != ? AND deleted_at IS NULL");
                $exists->execute([$slug, $id]);
                if ($exists->fetchColumn()) {
                    $redirectMsg = 'error=slug';
                } else {
                    $upd = $db->prepare("UPDATE {$table} SET name = ?, slug = ?, description = ?, group_type = ?, owner_id = ?, unique_handle = ?, status = ?, updated_at = NOW() WHERE id = ?");
                    $upd->execute([$name, $slug, $description ?: null, $group_type, $owner_id, $unique_handle ?: null, $status, $id]);
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
        case 'added': $message = 'Group added successfully.'; break;
        case 'updated': $message = 'Group updated successfully.'; break;
        case 'deleted': $message = 'Group deleted successfully.'; break;
        default: $message = ''; break;
    }
}
if (isset($_GET['error'])) {
    $messageType = 'danger';
    switch ($_GET['error']) {
        case 'slug': $message = 'A group with this slug already exists.'; break;
        case 'missing': $message = 'Name is required.'; break;
        case 'failed': $message = 'An error occurred. Please try again.'; break;
        default: $message = 'An error occurred.'; break;
    }
}

$groups = [];
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
try {
    $stmt = $db->query("SELECT id, name, slug, description, group_type, owner_id, unique_handle, status, created_at FROM {$table} WHERE deleted_at IS NULL ORDER BY created_at DESC");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $groups = [];
}
$editRow = null;
if ($editId > 0) {
    foreach ($groups as $row) {
        if ((int) $row['id'] === $editId) {
            $editRow = $row;
            break;
        }
    }
}

$typeSlugs = array_column($groupTypes, 'slug');
if (empty($typeSlugs)) {
    $typeSlugs = ['artist'];
}
?>
<div class="container">
    <div class="page-header">
        <h1>Groups</h1>
        <p>Create, edit, and delete Simuze Community groups.</p>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <div class="card">
        <div class="card-body">
            <h2 class="h5"><?php echo $editRow ? 'Edit Group' : 'Add New Group'; ?></h2>
            <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>">
                <input type="hidden" name="action" value="<?php echo $editRow ? 'edit' : 'add'; ?>">
                <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo (int) $editRow['id']; ?>"><?php endif; ?>
                <div class="form-group">
                    <label for="g-name">Name</label>
                    <input type="text" id="g-name" name="name" class="form-control" required
                           value="<?php echo $editRow ? htmlspecialchars($editRow['name'] ?? '') : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="g-slug">Slug</label>
                    <input type="text" id="g-slug" name="slug" class="form-control" placeholder="auto from name"
                           value="<?php echo $editRow ? htmlspecialchars($editRow['slug'] ?? '') : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="g-description">Description (optional)</label>
                    <textarea id="g-description" name="description" class="form-control" rows="2"><?php echo $editRow ? htmlspecialchars($editRow['description'] ?? '') : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label for="g-group_type">Group type</label>
                    <select id="g-group_type" name="group_type" class="form-control">
                        <?php foreach ($groupTypes as $gt): ?>
                            <option value="<?php echo htmlspecialchars($gt['slug']); ?>" <?php echo ($editRow && ($editRow['group_type'] ?? '') === $gt['slug']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($gt['name']); ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($groupTypes)): ?>
                            <option value="artist">Artist</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="g-owner_id">Owner ID (user id)</label>
                    <input type="number" id="g-owner_id" name="owner_id" class="form-control" min="0" value="<?php echo $editRow ? (int)($editRow['owner_id'] ?? 0) : 0; ?>">
                </div>
                <div class="form-group">
                    <label for="g-unique_handle">Unique handle (optional)</label>
                    <input type="text" id="g-unique_handle" name="unique_handle" class="form-control"
                           value="<?php echo $editRow ? htmlspecialchars($editRow['unique_handle'] ?? '') : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="g-status">Status</label>
                    <select id="g-status" name="status" class="form-control">
                        <option value="active" <?php echo ($editRow && ($editRow['status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="pending" <?php echo ($editRow && ($editRow['status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="suspended" <?php echo ($editRow && ($editRow['status'] ?? '') === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $editRow ? 'Update Group' : 'Add Group'; ?></button>
                <?php if ($editRow): ?>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="card mt-4">
        <div class="card-body">
            <h2 class="h5">Existing Groups</h2>
            <?php if (empty($groups)): ?>
                <p>No groups yet. Add one above.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Type</th>
                            <th>Owner ID</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $g): ?>
                            <tr>
                                <td><?php echo (int)$g['id']; ?></td>
                                <td><?php echo htmlspecialchars($g['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($g['slug'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($g['group_type'] ?? ''); ?></td>
                                <td><?php echo (int)($g['owner_id'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars($g['status'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($g['created_at'] ?? ''); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($baseUrl . '&edit=' . (int)$g['id']); ?>">Edit</a>
                                    |
                                    <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>" class="d-inline" onsubmit="return confirm('Delete this group?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$g['id']; ?>">
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
