<?php
/**
 * Simuze Community – Reviews admin (list)
 * Content moderation / review queue. Aligned with WP plugin.
 */
$db = $GLOBALS['db'] ?? null;
$prefix = defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_';
$table = $prefix . 'reviews';
$reviews = [];
if ($db) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($table));
        if ($stmt->rowCount() > 0) {
            $stmt = $db->query("SELECT id, reviewer_id, content_type, content_id, status, notes, created_at FROM {$table} ORDER BY created_at DESC LIMIT 200");
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) { }
}
?>
<div class="container">
    <div class="page-header">
        <h1>Reviews</h1>
        <p>Content moderation and review queue. Items submitted for review appear here.</p>
    </div>
    <div class="card">
        <div class="card-body">
            <?php if (empty($reviews)): ?>
                <p>No reviews yet. Reviews are created when content is submitted for moderation.</p>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $r): ?>
                            <tr>
                                <td><?php echo (int) $r['id']; ?></td>
                                <td><?php echo (int) $r['reviewer_id']; ?></td>
                                <td><?php echo htmlspecialchars($r['content_type'] ?? ''); ?></td>
                                <td><?php echo (int) $r['content_id']; ?></td>
                                <td><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
