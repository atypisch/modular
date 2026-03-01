<?php
/**
 * Simuze Community – Reviews admin (full CRUD)
 * Create, edit, delete reviews (content moderation / review queue).
 */
$db = $GLOBALS['db'] ?? null;
if (!defined('SIMUZE_DB_PREFIX')) {
    define('SIMUZE_DB_PREFIX', 'simuze_');
}
$prefix = SIMUZE_DB_PREFIX;
$table = $prefix . 'reviews';
$baseUrl = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=reviews';

if (!$db) {
    echo '<p>Database unavailable.</p>';
    return;
}

try {
    $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($table));
    if ($stmt->rowCount() === 0) {
        echo '<div class="container"><div class="card"><p>Reviews table not found. Activate the Simuze Community module to run migrations.</p></div></div>';
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
            $reviewer_id = isset($_POST['reviewer_id']) ? (int) $_POST['reviewer_id'] : 0;
            $content_type = isset($_POST['content_type']) ? trim((string) $_POST['content_type']) : '';
            $content_id = isset($_POST['content_id']) ? (int) $_POST['content_id'] : 0;
            $status = isset($_POST['status']) ? trim((string) $_POST['status']) : 'pending';
            $notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : null;
            if ($content_type !== '') {
                $ins = $db->prepare("INSERT INTO {$table} (reviewer_id, content_type, content_id, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                $ins->execute([$reviewer_id, $content_type, $content_id, $status, $notes]);
                $redirectMsg = 'message=added';
            } else {
                $redirectMsg = 'error=missing';
            }
        } elseif ($action === 'edit' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
            $reviewer_id = isset($_POST['reviewer_id']) ? (int) $_POST['reviewer_id'] : 0;
            $content_type = isset($_POST['content_type']) ? trim((string) $_POST['content_type']) : '';
            $content_id = isset($_POST['content_id']) ? (int) $_POST['content_id'] : 0;
            $status = isset($_POST['status']) ? trim((string) $_POST['status']) : 'pending';
            $notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : null;
            if ($id > 0 && $content_type !== '') {
                $upd = $db->prepare("UPDATE {$table} SET reviewer_id = ?, content_type = ?, content_id = ?, status = ?, notes = ?, updated_at = NOW() WHERE id = ?");
                $upd->execute([$reviewer_id, $content_type, $content_id, $status, $notes, $id]);
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
        case 'added': $message = 'Review added successfully.'; break;
        case 'updated': $message = 'Review updated successfully.'; break;
        case 'deleted': $message = 'Review deleted successfully.'; break;
        default: $message = ''; break;
    }
}
if (isset($_GET['error'])) {
    $messageType = 'danger';
    switch ($_GET['error']) {
        case 'missing': $message = 'Content type is required.'; break;
        case 'failed': $message = 'An error occurred. Please try again.'; break;
        default: $message = 'An error occurred.'; break;
    }
}

$reviews = [];
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
try {
    $stmt = $db->query("SELECT id, reviewer_id, content_type, content_id, status, notes, created_at, updated_at FROM {$table} ORDER BY created_at DESC LIMIT 200");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $reviews = [];
}
$editRow = null;
if ($editId > 0) {
    foreach ($reviews as $row) {
        if ((int) $row['id'] === $editId) {
            $editRow = $row;
            break;
        }
    }
}
?>
<div class="container">
    <div class="page-header">
        <h1>Reviews</h1>
        <p>Content moderation and review queue. Create, edit, and delete reviews.</p>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <div class="card">
        <div class="card-body">
            <h2 class="h5"><?php echo $editRow ? 'Edit Review' : 'Add New Review'; ?></h2>
            <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>">
                <input type="hidden" name="action" value="<?php echo $editRow ? 'edit' : 'add'; ?>">
                <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo (int) $editRow['id']; ?>"><?php endif; ?>
                <div class="form-group">
                    <label for="r-reviewer_id">Reviewer ID (user id)</label>
                    <input type="number" id="r-reviewer_id" name="reviewer_id" class="form-control" min="0" value="<?php echo $editRow ? (int)($editRow['reviewer_id'] ?? 0) : 0; ?>">
                </div>
                <div class="form-group">
                    <label for="r-content_type">Content type</label>
                    <input type="text" id="r-content_type" name="content_type" class="form-control" required placeholder="e.g. group, post, profile"
                           value="<?php echo $editRow ? htmlspecialchars($editRow['content_type'] ?? '') : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="r-content_id">Content ID</label>
                    <input type="number" id="r-content_id" name="content_id" class="form-control" min="0" value="<?php echo $editRow ? (int)($editRow['content_id'] ?? 0) : 0; ?>">
                </div>
                <div class="form-group">
                    <label for="r-status">Status</label>
                    <select id="r-status" name="status" class="form-control">
                        <option value="pending" <?php echo ($editRow && ($editRow['status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo ($editRow && ($editRow['status'] ?? '') === 'approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo ($editRow && ($editRow['status'] ?? '') === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="r-notes">Notes (optional)</label>
                    <textarea id="r-notes" name="notes" class="form-control" rows="2"><?php echo $editRow ? htmlspecialchars($editRow['notes'] ?? '') : ''; ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $editRow ? 'Update Review' : 'Add Review'; ?></button>
                <?php if ($editRow): ?>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="card mt-4">
        <div class="card-body">
            <h2 class="h5">Existing Reviews</h2>
            <?php if (empty($reviews)): ?>
                <p>No reviews yet. Add one above.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Reviewer</th>
                            <th>Content type</th>
                            <th>Content ID</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $r): ?>
                            <tr>
                                <td><?php echo (int) $r['id']; ?></td>
                                <td><?php echo (int) ($r['reviewer_id'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars($r['content_type'] ?? ''); ?></td>
                                <td><?php echo (int) ($r['content_id'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($baseUrl . '&edit=' . (int) $r['id']); ?>">Edit</a>
                                    |
                                    <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>" class="d-inline" onsubmit="return confirm('Delete this review?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
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
