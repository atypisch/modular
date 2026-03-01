<?php
/**
 * Simuze Community – Reports admin (full CRUD)
 * Create, edit, delete user reports on content. Review and resolve reports.
 */
$db = $GLOBALS['db'] ?? null;
if (!defined('SIMUZE_DB_PREFIX')) {
    define('SIMUZE_DB_PREFIX', 'simuze_');
}
$prefix = SIMUZE_DB_PREFIX;
$table = $prefix . 'reports';
$baseUrl = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=reports';

if (!$db) {
    echo '<p>Database unavailable.</p>';
    return;
}

try {
    $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($table));
    if ($stmt->rowCount() === 0) {
        echo '<div class="container"><div class="card"><p>Reports table not found. Activate the Simuze Community module to run migrations.</p></div></div>';
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
            $reporter_id = isset($_POST['reporter_id']) ? (int) $_POST['reporter_id'] : 0;
            $content_type = isset($_POST['content_type']) ? trim((string) $_POST['content_type']) : '';
            $content_id = isset($_POST['content_id']) ? (int) $_POST['content_id'] : 0;
            $report_type = isset($_POST['report_type']) ? trim((string) $_POST['report_type']) : 'other';
            $comment = isset($_POST['comment']) ? trim((string) $_POST['comment']) : null;
            $status = isset($_POST['status']) ? trim((string) $_POST['status']) : 'pending';
            $notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : null;
            if ($content_type !== '' && $report_type !== '') {
                $ins = $db->prepare("INSERT INTO {$table} (reporter_id, content_type, content_id, report_type, comment, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $ins->execute([$reporter_id, $content_type, $content_id, $report_type, $comment, $status, $notes]);
                $redirectMsg = 'message=added';
            } else {
                $redirectMsg = 'error=missing';
            }
        } elseif ($action === 'edit' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
            $reporter_id = isset($_POST['reporter_id']) ? (int) $_POST['reporter_id'] : 0;
            $content_type = isset($_POST['content_type']) ? trim((string) $_POST['content_type']) : '';
            $content_id = isset($_POST['content_id']) ? (int) $_POST['content_id'] : 0;
            $report_type = isset($_POST['report_type']) ? trim((string) $_POST['report_type']) : 'other';
            $comment = isset($_POST['comment']) ? trim((string) $_POST['comment']) : null;
            $status = isset($_POST['status']) ? trim((string) $_POST['status']) : 'pending';
            $reviewed_by = isset($_POST['reviewed_by']) ? (int) $_POST['reviewed_by'] : null;
            $notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : null;
            if ($id > 0 && $content_type !== '' && $report_type !== '') {
                $reviewed_at = $reviewed_by ? 'NOW()' : 'NULL';
                $upd = $db->prepare("UPDATE {$table} SET reporter_id = ?, content_type = ?, content_id = ?, report_type = ?, comment = ?, status = ?, reviewed_by = ?, reviewed_at = {$reviewed_at}, notes = ?, updated_at = NOW() WHERE id = ?");
                $upd->execute([$reporter_id, $content_type, $content_id, $report_type, $comment, $status, $reviewed_by ?: null, $notes, $id]);
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
        case 'added': $message = 'Report added successfully.'; break;
        case 'updated': $message = 'Report updated successfully.'; break;
        case 'deleted': $message = 'Report deleted successfully.'; break;
        default: $message = ''; break;
    }
}
if (isset($_GET['error'])) {
    $messageType = 'danger';
    switch ($_GET['error']) {
        case 'missing': $message = 'Content type and report type are required.'; break;
        case 'failed': $message = 'An error occurred. Please try again.'; break;
        default: $message = 'An error occurred.'; break;
    }
}

$reports = [];
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
try {
    $stmt = $db->query("SELECT id, reporter_id, content_type, content_id, report_type, comment, status, reviewed_by, reviewed_at, notes, created_at, updated_at FROM {$table} ORDER BY created_at DESC LIMIT 200");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $reports = [];
}
$editRow = null;
if ($editId > 0) {
    foreach ($reports as $row) {
        if ((int) $row['id'] === $editId) {
            $editRow = $row;
            break;
        }
    }
}
?>
<div class="container">
    <div class="page-header">
        <h1>Reports</h1>
        <p>User-reported content. Create, edit, and resolve reports.</p>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <div class="card">
        <div class="card-body">
            <h2 class="h5"><?php echo $editRow ? 'Edit Report' : 'Add New Report'; ?></h2>
            <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>">
                <input type="hidden" name="action" value="<?php echo $editRow ? 'edit' : 'add'; ?>">
                <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo (int) $editRow['id']; ?>"><?php endif; ?>
                <div class="form-group">
                    <label for="rp-reporter_id">Reporter ID (user id)</label>
                    <input type="number" id="rp-reporter_id" name="reporter_id" class="form-control" min="0" value="<?php echo $editRow ? (int)($editRow['reporter_id'] ?? 0) : 0; ?>">
                </div>
                <div class="form-group">
                    <label for="rp-content_type">Content type</label>
                    <input type="text" id="rp-content_type" name="content_type" class="form-control" required placeholder="e.g. group, post, message"
                           value="<?php echo $editRow ? htmlspecialchars($editRow['content_type'] ?? '') : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="rp-content_id">Content ID</label>
                    <input type="number" id="rp-content_id" name="content_id" class="form-control" min="0" value="<?php echo $editRow ? (int)($editRow['content_id'] ?? 0) : 0; ?>">
                </div>
                <div class="form-group">
                    <label for="rp-report_type">Report type</label>
                    <select id="rp-report_type" name="report_type" class="form-control">
                        <option value="spam" <?php echo ($editRow && ($editRow['report_type'] ?? '') === 'spam') ? 'selected' : ''; ?>>Spam</option>
                        <option value="harassment" <?php echo ($editRow && ($editRow['report_type'] ?? '') === 'harassment') ? 'selected' : ''; ?>>Harassment</option>
                        <option value="inappropriate" <?php echo ($editRow && ($editRow['report_type'] ?? '') === 'inappropriate') ? 'selected' : ''; ?>>Inappropriate</option>
                        <option value="other" <?php echo ($editRow && ($editRow['report_type'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="rp-comment">Comment (optional)</label>
                    <textarea id="rp-comment" name="comment" class="form-control" rows="2"><?php echo $editRow ? htmlspecialchars($editRow['comment'] ?? '') : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label for="rp-status">Status</label>
                    <select id="rp-status" name="status" class="form-control">
                        <option value="pending" <?php echo ($editRow && ($editRow['status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="resolved" <?php echo ($editRow && ($editRow['status'] ?? '') === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                        <option value="dismissed" <?php echo ($editRow && ($editRow['status'] ?? '') === 'dismissed') ? 'selected' : ''; ?>>Dismissed</option>
                    </select>
                </div>
                <?php if ($editRow): ?>
                <div class="form-group">
                    <label for="rp-reviewed_by">Reviewed by (user id, optional)</label>
                    <input type="number" id="rp-reviewed_by" name="reviewed_by" class="form-control" min="0" value="<?php echo $editRow && !empty($editRow['reviewed_by']) ? (int) $editRow['reviewed_by'] : ''; ?>" placeholder="Leave empty if not reviewed">
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="rp-notes">Admin notes (optional)</label>
                    <textarea id="rp-notes" name="notes" class="form-control" rows="2"><?php echo $editRow ? htmlspecialchars($editRow['notes'] ?? '') : ''; ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $editRow ? 'Update Report' : 'Add Report'; ?></button>
                <?php if ($editRow): ?>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="card mt-4">
        <div class="card-body">
            <h2 class="h5">Existing Reports</h2>
            <?php if (empty($reports)): ?>
                <p>No reports yet. Add one above.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Reporter</th>
                            <th>Content type</th>
                            <th>Content ID</th>
                            <th>Report type</th>
                            <th>Status</th>
                            <th>Reviewed by</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $r): ?>
                            <tr>
                                <td><?php echo (int) $r['id']; ?></td>
                                <td><?php echo (int) ($r['reporter_id'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars($r['content_type'] ?? ''); ?></td>
                                <td><?php echo (int) ($r['content_id'] ?? 0); ?></td>
                                <td><?php echo htmlspecialchars($r['report_type'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                                <td><?php echo !empty($r['reviewed_by']) ? (int) $r['reviewed_by'] : '—'; ?></td>
                                <td><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($baseUrl . '&edit=' . (int) $r['id']); ?>">Edit</a>
                                    |
                                    <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>" class="d-inline" onsubmit="return confirm('Delete this report?');">
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
