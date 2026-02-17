<?php
/**
 * Simuze Community – Groups admin (list)
 * Included by admin/module-page.php when module=simuze-community&page=groups
 */
$db = $GLOBALS['db'] ?? null;
if (!$db) {
    echo '<p>Database unavailable.</p>';
    return;
}
$prefix = defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_';
$table = $prefix . 'groups';

// Check table exists
try {
    $stmt = $db->query("SHOW TABLES LIKE '" . $prefix . "groups'");
    if ($stmt->rowCount() === 0) {
        echo '<div class="container"><div class="card"><p>Groups table not found. Activate the Simuze Community module to run migrations.</p></div></div>';
        return;
    }
} catch (Throwable $e) {
    echo '<div class="container"><div class="card"><p>Error: ' . htmlspecialchars($e->getMessage()) . '</p></div></div>';
    return;
}

$groups = [];
try {
    $stmt = $db->query("SELECT id, name, slug, group_type, owner_id, status, created_at FROM {$table} WHERE deleted_at IS NULL ORDER BY created_at DESC");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $groups = [];
}
?>
<div class="container">
    <div class="page-header">
        <h1>Groups</h1>
        <p>Manage Simuze Community groups.</p>
    </div>
    <div class="card">
        <div class="card-body">
            <?php if (empty($groups)): ?>
                <p>No groups yet. Create groups via the public site or add an API for group creation.</p>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $g): ?>
                            <tr>
                                <td><?php echo (int)$g['id']; ?></td>
                                <td><?php echo htmlspecialchars($g['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($g['slug'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($g['group_type'] ?? ''); ?></td>
                                <td><?php echo (int)$g['owner_id']; ?></td>
                                <td><?php echo htmlspecialchars($g['status'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($g['created_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
