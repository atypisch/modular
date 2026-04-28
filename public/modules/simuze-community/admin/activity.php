<?php
/**
 * Simuze Community – Activity stream admin (full CRUD)
 * Monitor profile/group updates. Create, edit, delete activity items.
 */
$db = $GLOBALS['db'] ?? null;
if (!defined('SIMUZE_DB_PREFIX')) {
    define('SIMUZE_DB_PREFIX', 'simuze_');
}
$prefix = SIMUZE_DB_PREFIX;
$table = $prefix . 'activity';
$baseUrl = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=activity';

if (!$db) {
    echo '<p>Database unavailable.</p>';
    return;
}

// Check table exists (migrations run only on module activation / new install)
try {
    $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($table));
    if ($stmt->rowCount() === 0) {
        echo '<div class="container"><div class="card"><p>Activity table not found. Deactivate and reactivate the Simuze Community module to run migrations.</p></div></div>';
        return;
    }
} catch (Throwable $e) {
    echo '<div class="container"><div class="card"><p>Error: ' . htmlspecialchars($e->getMessage()) . '</p></div></div>';
    return;
}

// Handle POST (add / edit / delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $redirectMsg = '';
    try {
        if ($action === 'add') {
            $author_type = isset($_POST['author_type']) && in_array($_POST['author_type'], ['user', 'group'], true) ? $_POST['author_type'] : 'user';
            $author_id = isset($_POST['author_id']) ? (int) $_POST['author_id'] : 0;
            $content = isset($_POST['content']) ? trim((string) $_POST['content']) : '';
            $extra = isset($_POST['extra']) ? trim((string) $_POST['extra']) : null;
            if ($content !== '') {
                $ins = $db->prepare("INSERT INTO {$table} (author_type, author_id, content, extra, created_at) VALUES (?, ?, ?, ?, NOW())");
                $ins->execute([$author_type, $author_id, $content, $extra ?: null]);
                $redirectMsg = 'message=added';
            } else {
                $redirectMsg = 'error=missing';
            }
        } elseif ($action === 'edit' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
            $author_type = isset($_POST['author_type']) && in_array($_POST['author_type'], ['user', 'group'], true) ? $_POST['author_type'] : 'user';
            $author_id = isset($_POST['author_id']) ? (int) $_POST['author_id'] : 0;
            $content = isset($_POST['content']) ? trim((string) $_POST['content']) : '';
            $extra = isset($_POST['extra']) ? trim((string) $_POST['extra']) : null;
            if ($id > 0 && $content !== '') {
                $upd = $db->prepare("UPDATE {$table} SET author_type = ?, author_id = ?, content = ?, extra = ? WHERE id = ?");
                $upd->execute([$author_type, $author_id, $content, $extra ?: null, $id]);
                $redirectMsg = 'message=updated';
            } else {
                $redirectMsg = 'error=missing';
            }
        } elseif ($action === 'delete' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
            if ($id > 0) {
                $del = $db->prepare("DELETE FROM {$table} WHERE id = ?");
                $del->execute([$id]);
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
        case 'added': $message = 'Activity item added.'; break;
        case 'updated': $message = 'Activity item updated.'; break;
        case 'deleted': $message = 'Activity item deleted.'; break;
        default: $message = ''; break;
    }
}
if (isset($_GET['error'])) {
    $messageType = 'danger';
    switch ($_GET['error']) {
        case 'missing': $message = 'Content is required.'; break;
        case 'failed': $message = 'An error occurred.'; break;
        default: $message = 'An error occurred.'; break;
    }
}

$items = [];
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
try {
    $stmt = $db->query("SELECT id, author_type, author_id, content, extra, created_at FROM {$table} ORDER BY created_at DESC LIMIT 300");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { }
$editRow = null;
if ($editId > 0) {
    foreach ($items as $row) {
        if ((int) $row['id'] === $editId) {
            $editRow = $row;
            break;
        }
    }
}
?>
<div class="container">
    <div class="page-header">
        <h1>Activity Stream</h1>
        <p>Profile and group updates. Users and groups can add updates; monitor and moderate here.</p>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <div class="card">
        <div class="card-body">
            <h2 class="h5"><?php echo $editRow ? 'Edit Activity' : 'Add Activity'; ?></h2>
            <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>">
                <input type="hidden" name="action" value="<?php echo $editRow ? 'edit' : 'add'; ?>">
                <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo (int) $editRow['id']; ?>"><?php endif; ?>
                <div class="form-group">
                    <label>Author type</label>
                    <select name="author_type" class="form-control">
                        <option value="user" <?php echo ($editRow && ($editRow['author_type'] ?? '') === 'user') ? 'selected' : ''; ?>>User</option>
                        <option value="group" <?php echo ($editRow && ($editRow['author_type'] ?? '') === 'group') ? 'selected' : ''; ?>>Group</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Author ID (user id or group id)</label>
                    <input type="number" name="author_id" class="form-control" min="0" value="<?php echo $editRow ? (int)($editRow['author_id'] ?? 0) : 0; ?>">
                </div>
                <div class="form-group">
                    <label>Content</label>
                    <textarea name="content" class="form-control" rows="3" required><?php echo $editRow ? htmlspecialchars($editRow['content'] ?? '') : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label>Extra (optional JSON/text)</label>
                    <input type="text" name="extra" class="form-control" value="<?php echo $editRow ? htmlspecialchars($editRow['extra'] ?? '') : ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $editRow ? 'Update' : 'Add'; ?></button>
                <?php if ($editRow): ?>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="card mt-4">
        <div class="card-body">
            <h2 class="h5">Activity stream</h2>
            <?php if (empty($items)): ?>
                <p>No activity yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Author</th>
                            <th>Content</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $a): ?>
                            <tr>
                                <td><?php echo (int) $a['id']; ?></td>
                                <td><?php echo htmlspecialchars($a['author_type'] ?? 'user'); ?> #<?php echo (int)($a['author_id'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars(mb_substr($a['content'] ?? '', 0, 100)); ?><?php echo mb_strlen($a['content'] ?? '') > 100 ? '…' : ''; ?></td>
                                <td><?php echo htmlspecialchars($a['created_at'] ?? ''); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($baseUrl . '&edit=' . (int) $a['id']); ?>">Edit</a>
                                    |
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this item?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $a['id']; ?>">
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
