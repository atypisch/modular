<?php
/**
 * Simuze Community – Messages admin (inbox / conversations)
 * Schema supports: direct inbox (user-to-user) and later group inbox and chat.
 * Tables: simuze_conversations, simuze_conversation_participants, simuze_messages.
 */
$db = $GLOBALS['db'] ?? null;
$prefix = defined('SIMUZE_DB_PREFIX') ? SIMUZE_DB_PREFIX : 'simuze_';
$conversations = [];
if ($db) {
    try {
        $t = $prefix . 'conversations';
        $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($t));
        if ($stmt->rowCount() > 0) {
            $stmt = $db->query("SELECT id, type, title, created_at, updated_at FROM {$t} ORDER BY updated_at DESC LIMIT 100");
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) { }
}
?>
<div class="container">
    <div class="page-header">
        <h1>Messages</h1>
        <p>Inbox and conversations. The schema supports direct messages (user inbox) first; group inbox and chat can be added later.</p>
    </div>
    <div class="card">
        <div class="card-body">
            <p><strong>Schema:</strong> <code>simuze_conversations</code>, <code>simuze_conversation_participants</code>, <code>simuze_messages</code>. Conversations have a <code>type</code> (e.g. <code>direct</code> for user inbox). Use the module API or future inbox UI to create and manage conversations.</p>
            <?php if (!empty($conversations)): ?>
                <h2 class="h6 mt-3">Recent conversations</h2>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Title</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conversations as $c): ?>
                            <tr>
                                <td><?php echo (int) $c['id']; ?></td>
                                <td><?php echo htmlspecialchars($c['type'] ?? 'direct'); ?></td>
                                <td><?php echo htmlspecialchars($c['title'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($c['updated_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="mt-2 text-muted">No conversations yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
