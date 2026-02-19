<?php
/**
 * Simuze Community – Reports admin (list)
 * User reports on content. Aligned with WP plugin.
 */
$db = $GLOBALS['db'] ?? null;
$prefix = defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_';
$table = $prefix . 'reports';
$reports = [];
if ($db) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($table));
        if ($stmt->rowCount() > 0) {
            $stmt = $db->query("SELECT id, reporter_id, content_type, content_id, report_type, status, reviewed_by, reviewed_at, created_at FROM {$table} ORDER BY created_at DESC LIMIT 200");
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) { }
}
?>
<div class="container">
    <div class="page-header">
        <h1>Reports</h1>
        <p>User-reported content. Review and resolve reports here.</p>
    </div>
    <div class="card">
        <div class="card-body">
            <?php if (empty($reports)): ?>
                <p>No reports yet.</p>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $r): ?>
                            <tr>
                                <td><?php echo (int) $r['id']; ?></td>
                                <td><?php echo (int) $r['reporter_id']; ?></td>
                                <td><?php echo htmlspecialchars($r['content_type'] ?? ''); ?></td>
                                <td><?php echo (int) $r['content_id']; ?></td>
                                <td><?php echo htmlspecialchars($r['report_type'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                                <td><?php echo $r['reviewed_by'] ? (int) $r['reviewed_by'] : '—'; ?></td>
                                <td><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
