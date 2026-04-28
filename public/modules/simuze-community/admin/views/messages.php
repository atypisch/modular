<?php
/**
 * Messages Admin View – Modular styling with cards
 */
if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

$messages_table = 'simuze_messages';
$users_table    = 'simuze_users';
$admin_page_url = md_url('/admin/module-page.php?module=simuze-community&page=messages');

// Handle soft-delete action (verified via CSRF token in GET param)
if (isset($_GET['action'], $_GET['message_id'], $_GET['_csrf'])
    && $_GET['action'] === 'delete'
    && $_GET['_csrf'] === md_csrf_token()
) {
    $mid = (int) $_GET['message_id'];
    md_db()->prepare("UPDATE $messages_table SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL")->execute([$mid]);
    header('Location: ' . $admin_page_url . '&message=deleted');
    exit;
}

// Handle restore action
if (isset($_GET['action'], $_GET['message_id'], $_GET['_csrf'])
    && $_GET['action'] === 'restore'
    && $_GET['_csrf'] === md_csrf_token()
) {
    $mid = (int) $_GET['message_id'];
    md_db()->prepare("UPDATE $messages_table SET deleted_at = NULL WHERE id = ?")->execute([$mid]);
    header('Location: ' . $admin_page_url . '&message=restored');
    exit;
}

// Filters
$filter_status = isset($_GET['status']) ? trim(strip_tags($_GET['status'])) : 'active';
$search        = isset($_GET['s'])      ? trim(strip_tags($_GET['s']))      : '';
$paged         = isset($_GET['paged'])  ? max(1, (int) $_GET['paged'])      : 1;
$per_page      = 25;
$offset        = ($paged - 1) * $per_page;

$where_parts = [];
$params      = [];
if ($filter_status === 'deleted') {
    $where_parts[] = 'm.deleted_at IS NOT NULL';
} else {
    $where_parts[] = 'm.deleted_at IS NULL';
}
if ($filter_status === 'unread') {
    $where_parts[] = 'm.is_read = 0 AND m.deleted_at IS NULL';
}
if ($search !== '') {
    $like          = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
    $where_parts[] = '(m.message LIKE ? OR m.subject LIKE ?)';
    $params[]      = $like;
    $params[]      = $like;
}
$where_sql = $where_parts ? ('WHERE ' . implode(' AND ', $where_parts)) : '';

$total    = (int) md_db_var("SELECT COUNT(*) FROM {$messages_table} m {$where_sql}", $params);
$messages = md_db_query(
    "SELECT m.*, s.username AS sender_name, r.username AS recipient_name
     FROM {$messages_table} m
     LEFT JOIN {$users_table} s ON s.id = m.sender_id
     LEFT JOIN {$users_table} r ON r.id = m.recipient_id
     {$where_sql}
     ORDER BY m.created_at DESC
     LIMIT {$per_page} OFFSET {$offset}",
    $params
);
$total_pages = max(1, (int) ceil($total / $per_page));

$count_active  = (int) md_db_var("SELECT COUNT(*) FROM {$messages_table} WHERE deleted_at IS NULL");
$count_unread  = (int) md_db_var("SELECT COUNT(*) FROM {$messages_table} WHERE is_read = 0 AND deleted_at IS NULL");
$count_deleted = (int) md_db_var("SELECT COUNT(*) FROM {$messages_table} WHERE deleted_at IS NOT NULL");

$admin_css_url = defined('SIMUZE_COMMUNITY_PLUGIN_URL') ? rtrim(SIMUZE_COMMUNITY_PLUGIN_URL, '/') . '/admin/assets/admin.css' : '';
?>
<?php if ($admin_css_url): ?>
<link rel="stylesheet" href="<?php echo esc_url($admin_css_url); ?>">
<?php endif; ?>

<div class="simuze-admin-page">
    <div class="page-header">
        <h1>Messages</h1>
    </div>

    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success">
            <p><?php echo esc_html(ucfirst($_GET['message'])); ?> successfully.</p>
        </div>
    <?php endif; ?>

    <ul class="simuze-tabs">
        <li>
            <a href="<?php echo esc_url($admin_page_url . '&status=active'); ?>" class="<?php echo $filter_status === 'active' ? 'current' : ''; ?>">
                Active <span class="count">(<?php echo $count_active; ?>)</span>
            </a>
        </li>
        <li>
            <a href="<?php echo esc_url($admin_page_url . '&status=unread'); ?>" class="<?php echo $filter_status === 'unread' ? 'current' : ''; ?>">
                Unread <span class="count">(<?php echo $count_unread; ?>)</span>
            </a>
        </li>
        <li>
            <a href="<?php echo esc_url($admin_page_url . '&status=deleted'); ?>" class="<?php echo $filter_status === 'deleted' ? 'current' : ''; ?>">
                Deleted <span class="count">(<?php echo $count_deleted; ?>)</span>
            </a>
        </li>
    </ul>

    <form method="get" class="simuze-form-toolbar">
        <?php if (defined('SIMUZE_MODULAR_RUN') && SIMUZE_MODULAR_RUN): ?>
        <input type="hidden" name="module" value="simuze-community">
        <input type="hidden" name="page" value="messages">
        <?php else: ?>
        <input type="hidden" name="page" value="simuze-messages">
        <?php endif; ?>
        <input type="hidden" name="status" value="<?php echo esc_attr($filter_status); ?>">
        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search messages…">
        <button type="submit" class="button">Search</button>
    </form>

    <div class="card">
        <div class="card-header">Message list</div>
        <div class="card-body">
            <table class="simuze-table">
                <thead>
                    <tr>
                        <th style="width:40px">ID</th>
                        <th style="width:130px">From</th>
                        <th style="width:130px">To</th>
                        <th>Subject / Preview</th>
                        <th style="width:70px">Read</th>
                        <th style="width:140px">Date</th>
                        <th style="width:110px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                        <tr><td colspan="7" class="simuze-empty">No messages found.</td></tr>
                    <?php else: foreach ($messages as $msg): ?>
                        <tr<?php echo $msg['deleted_at'] ? ' style="opacity:.6"' : ''; ?>>
                            <td><?php echo (int) $msg['id']; ?></td>
                            <td><?php echo esc_html($msg['sender_name'] ?: 'user #' . $msg['sender_id']); ?></td>
                            <td><?php echo esc_html($msg['recipient_name'] ?: ($msg['group_id'] ? 'group #' . $msg['group_id'] : '—')); ?></td>
                            <td>
                                <?php if ($msg['subject']): ?>
                                    <strong><?php echo esc_html($msg['subject']); ?></strong><br>
                                <?php endif; ?>
                                <span style="color:#666"><?php echo esc_html(mb_strimwidth($msg['message'], 0, 120, '…')); ?></span>
                            </td>
                            <td><?php echo $msg['is_read'] ? '✓' : '<strong>No</strong>'; ?></td>
                            <td><?php echo esc_html($msg['created_at']); ?></td>
                            <td>
                                <?php $csrf = urlencode(md_csrf_token()); ?>
                                <?php if ($msg['deleted_at']): ?>
                                    <a href="<?php echo esc_url($admin_page_url . '&action=restore&message_id=' . (int) $msg['id'] . '&_csrf=' . $csrf); ?>" class="btn btn-sm btn-icon" title="Restore"><i class="fas fa-undo" aria-hidden="true"></i></a>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($admin_page_url . '&action=delete&message_id=' . (int) $msg['id'] . '&_csrf=' . $csrf); ?>" class="btn btn-sm btn-icon btn-icon-danger" title="Delete" onclick="return confirm('Delete this message?')"><i class="fas fa-trash-alt" aria-hidden="true"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pages > 1): ?>
        <div class="simuze-tablenav" style="padding: 0.75rem 1rem; border-top: 1px solid var(--border-color, #eee);">
            <span class="displaying-num"><?php echo $total; ?> items</span>
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <?php $url = $admin_page_url . '&status=' . urlencode($filter_status) . '&paged=' . $p . ($search ? '&s=' . urlencode($search) : ''); ?>
                <a href="<?php echo esc_url($url); ?>" class="button<?php echo $p === $paged ? ' button-primary' : ''; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
