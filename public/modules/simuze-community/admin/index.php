<?php
/**
 * Simuze Community – Overview (dashboard)
 * First submenu item under Community; aligned with WP “Simuze” dashboard.
 */

$db = $GLOBALS['db'] ?? null;
$prefix = defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_';

$stats = ['groups' => 0, 'group_types' => 0, 'reviews' => 0, 'reports' => 0, 'conversations' => 0, 'activity' => 0];
if ($db) {
    try {
        $t = $prefix . 'groups';
        if (trim($db->query("SHOW TABLES LIKE '" . str_replace("'", "''", $t) . "'")->fetchColumn(0) ?? '') === $t) {
            $stmt = $db->query("SELECT COUNT(*) FROM {$t} WHERE deleted_at IS NULL");
            $stats['groups'] = (int) $stmt->fetchColumn();
        }
    } catch (Throwable $e) { }
    try {
        $t = $prefix . 'group_types';
        if (trim($db->query("SHOW TABLES LIKE '" . str_replace("'", "''", $t) . "'")->fetchColumn(0) ?? '') === $t) {
            $stmt = $db->query("SELECT COUNT(*) FROM {$t} WHERE deleted_at IS NULL");
            $stats['group_types'] = (int) $stmt->fetchColumn();
        }
    } catch (Throwable $e) { }
    try {
        $t = $prefix . 'reviews';
        if (trim($db->query("SHOW TABLES LIKE '" . str_replace("'", "''", $t) . "'")->fetchColumn(0) ?? '') === $t) {
            $stmt = $db->query("SELECT COUNT(*) FROM {$t}");
            $stats['reviews'] = (int) $stmt->fetchColumn();
        }
    } catch (Throwable $e) { }
    try {
        $t = $prefix . 'reports';
        if (trim($db->query("SHOW TABLES LIKE '" . str_replace("'", "''", $t) . "'")->fetchColumn(0) ?? '') === $t) {
            $stmt = $db->query("SELECT COUNT(*) FROM {$t}");
            $stats['reports'] = (int) $stmt->fetchColumn();
        }
    } catch (Throwable $e) { }
    try {
        $t = $prefix . 'conversations';
        if (trim($db->query("SHOW TABLES LIKE '" . str_replace("'", "''", $t) . "'")->fetchColumn(0) ?? '') === $t) {
            $stmt = $db->query("SELECT COUNT(*) FROM {$t}");
            $stats['conversations'] = (int) $stmt->fetchColumn();
        }
    } catch (Throwable $e) { }
    try {
        $t = $prefix . 'activity';
        if (trim($db->query("SHOW TABLES LIKE '" . str_replace("'", "''", $t) . "'")->fetchColumn(0) ?? '') === $t) {
            $stmt = $db->query("SELECT COUNT(*) FROM {$t}");
            $stats['activity'] = (int) $stmt->fetchColumn();
        }
    } catch (Throwable $e) { }
}
?>
<div class="container">
    <div class="page-header">
        <h1>Simuze Community</h1>
        <p>Overview of community data. Use the menu to manage groups, group types, reviews, reports, and messages.</p>
    </div>
    <div class="card">
        <div class="card-body">
            <h2 class="h5">Summary</h2>
            <ul class="list-unstyled">
                <li><strong>Groups:</strong> <?php echo (int) $stats['groups']; ?></li>
                <li><strong>Group types:</strong> <?php echo (int) $stats['group_types']; ?></li>
                <li><strong>Reviews:</strong> <?php echo (int) $stats['reviews']; ?></li>
                <li><strong>Reports:</strong> <?php echo (int) $stats['reports']; ?></li>
                <li><strong>Conversations (inbox):</strong> <?php echo (int) $stats['conversations']; ?></li>
                <li><strong>Activity stream:</strong> <?php echo (int) $stats['activity']; ?></li>
            </ul>
        </div>
    </div>
</div>
