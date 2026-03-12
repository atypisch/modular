<?php
/**
 * Activity Admin View – Modular styling with cards
 */
if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

$user_act_table  = 'simuze_user_activity';
$group_act_table = 'simuze_group_activity';
$users_table     = 'simuze_users';
$groups_table    = 'simuze_groups';

if (isset($_GET['action'], $_GET['activity_id'], $_GET['activity_type'], $_GET['_csrf'])
    && $_GET['action'] === 'delete'
    && isset($_GET['_csrf']) && $_GET['_csrf'] === md_csrf_token()
) {
    $aid  = (int) $_GET['activity_id'];
    $type = $_GET['activity_type'] === 'group' ? 'group' : 'user';
    $tbl  = $type === 'group' ? $group_act_table : $user_act_table;
    md_db_exec("UPDATE {$tbl} SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL", [$aid]);
    header('Location: ' . md_url('/admin/module-page.php?module=simuze-community&page=activity&message=deleted'));
    exit;
}

$filter_type   = isset($_GET['activity_type']) ? sanitize_text_field($_GET['activity_type']) : 'user';
$filter_action = isset($_GET['action_type'])   ? sanitize_text_field($_GET['action_type'])   : '';
$search        = isset($_GET['s'])             ? sanitize_text_field($_GET['s'])             : '';
$paged         = isset($_GET['paged'])         ? max(1, (int) $_GET['paged'])               : 1;
$per_page      = 25;
$offset        = ($paged - 1) * $per_page;

if ($filter_type === 'group') {
    $where_parts = ['a.deleted_at IS NULL'];
    $params      = [];
    if ($filter_action !== '') {
        $where_parts[] = 'a.action = ?';
        $params[]      = $filter_action;
    }
    if ($search !== '') {
        $like          = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
        $where_parts[] = '(a.content LIKE ? OR u.username LIKE ? OR g.name LIKE ?)';
        $params[]      = $like;
        $params[]      = $like;
        $params[]      = $like;
    }
    $where_sql = 'WHERE ' . implode(' AND ', $where_parts);
    $total = (int) md_db_var(
        "SELECT COUNT(*) FROM {$group_act_table} a
         LEFT JOIN {$users_table} u ON u.id = a.user_id
         LEFT JOIN {$groups_table} g ON g.id = a.group_id
         {$where_sql}",
        $params
    );
    $rows = md_db_query(
        "SELECT a.*, u.username, g.name AS group_name
         FROM {$group_act_table} a
         LEFT JOIN {$users_table} u ON u.id = a.user_id
         LEFT JOIN {$groups_table} g ON g.id = a.group_id
         {$where_sql}
         ORDER BY a.created_at DESC
         LIMIT {$per_page} OFFSET {$offset}",
        $params
    );
} else {
    $where_parts = ['a.deleted_at IS NULL'];
    $params      = [];
    if ($filter_action !== '') {
        $where_parts[] = 'a.action = ?';
        $params[]      = $filter_action;
    }
    if ($search !== '') {
        $like          = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
        $where_parts[] = '(a.content LIKE ? OR u.username LIKE ?)';
        $params[]      = $like;
        $params[]      = $like;
    }
    $where_sql = 'WHERE ' . implode(' AND ', $where_parts);
    $total = (int) md_db_var(
        "SELECT COUNT(*) FROM {$user_act_table} a
         LEFT JOIN {$users_table} u ON u.id = a.user_id
         {$where_sql}",
        $params
    );
    $rows = md_db_query(
        "SELECT a.*, u.username
         FROM {$user_act_table} a
         LEFT JOIN {$users_table} u ON u.id = a.user_id
         {$where_sql}
         ORDER BY a.created_at DESC
         LIMIT {$per_page} OFFSET {$offset}",
        $params
    );
}

$total_pages = max(1, ceil($total / $per_page));
$user_actions  = array_column(md_db_query("SELECT DISTINCT action FROM {$user_act_table} WHERE deleted_at IS NULL ORDER BY action"), 'action');
$group_actions = array_column(md_db_query("SELECT DISTINCT action FROM {$group_act_table} WHERE deleted_at IS NULL ORDER BY action"), 'action');
$all_actions   = $filter_type === 'group' ? $group_actions : $user_actions;

$user_count  = (int) md_db_var("SELECT COUNT(*) FROM {$user_act_table} WHERE deleted_at IS NULL");
$group_count = (int) md_db_var("SELECT COUNT(*) FROM {$group_act_table} WHERE deleted_at IS NULL");

$base_url = md_url('/admin/module-page.php?module=simuze-community&page=activity');

$admin_css_url = defined('SIMUZE_COMMUNITY_PLUGIN_URL') ? rtrim(SIMUZE_COMMUNITY_PLUGIN_URL, '/') . '/admin/assets/admin.css' : '';
?>
<?php if ($admin_css_url): ?>
<link rel="stylesheet" href="<?php echo esc_url($admin_css_url); ?>">
<?php endif; ?>

<div class="simuze-admin-page">
    <div class="page-header">
        <h1>Activity Log</h1>
    </div>

    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success">
            <p><?php echo esc_html(ucfirst($_GET['message'])); ?> successfully.</p>
        </div>
    <?php endif; ?>

    <ul class="simuze-tabs">
        <li>
            <a href="<?php echo esc_url($base_url . '&activity_type=user'); ?>" class="<?php echo $filter_type === 'user' ? 'current' : ''; ?>">
                User Activity <span class="count">(<?php echo $user_count; ?>)</span>
            </a>
        </li>
        <li>
            <a href="<?php echo esc_url($base_url . '&activity_type=group'); ?>" class="<?php echo $filter_type === 'group' ? 'current' : ''; ?>">
                Group Activity <span class="count">(<?php echo $group_count; ?>)</span>
            </a>
        </li>
    </ul>

    <form method="get" class="simuze-form-toolbar">
        <input type="hidden" name="module" value="simuze-community">
        <input type="hidden" name="page" value="activity">
        <input type="hidden" name="activity_type" value="<?php echo esc_attr($filter_type); ?>">
        <?php if (!empty($all_actions)): ?>
            <select name="action_type">
                <option value="">All Actions</option>
                <?php foreach ($all_actions as $act): ?>
                    <option value="<?php echo esc_attr($act); ?>" <?php selected($filter_action, $act); ?>><?php echo esc_html($act); ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search…">
        <button type="submit" class="button">Filter</button>
    </form>

    <div class="card">
        <div class="card-header">Activity entries</div>
        <div class="card-body">
            <table class="simuze-table">
                <thead>
                    <tr>
                        <th style="width:40px">ID</th>
                        <th style="width:120px">User</th>
                        <?php if ($filter_type === 'group'): ?>
                            <th style="width:130px">Group</th>
                        <?php endif; ?>
                        <th style="width:110px">Action</th>
                        <th style="width:90px">Component</th>
                        <th>Content</th>
                        <th style="width:140px">Date</th>
                        <th style="width:80px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="<?php echo $filter_type === 'group' ? 8 : 7; ?>" class="simuze-empty">No activity found.</td></tr>
                    <?php else: foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo (int) $row['id']; ?></td>
                            <td><?php echo esc_html($row['username'] ?: 'user #' . $row['user_id']); ?></td>
                            <?php if ($filter_type === 'group'): ?>
                                <td><?php echo esc_html($row['group_name'] ?: 'group #' . $row['group_id']); ?></td>
                            <?php endif; ?>
                            <td><code><?php echo esc_html($row['action']); ?></code></td>
                            <td><?php echo esc_html($row['component'] ?: '—'); ?></td>
                            <td><?php echo esc_html(mb_strimwidth($row['content'] ?? '', 0, 100, '…')); ?></td>
                            <td><?php echo esc_html($row['created_at']); ?></td>
                            <td>
                                <a href="<?php echo esc_url($base_url . '&action=delete&activity_id=' . $row['id'] . '&activity_type=' . $filter_type . '&_csrf=' . urlencode(md_csrf_token())); ?>" class="btn btn-sm btn-icon btn-icon-danger" title="Delete" onclick="return confirm('Delete this activity entry?')"><i class="fas fa-trash-alt" aria-hidden="true"></i></a>
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
                <?php $url = $base_url . '&activity_type=' . urlencode($filter_type) . '&paged=' . $p; ?>
                <a href="<?php echo esc_url($url); ?>" class="button<?php echo $p === $paged ? ' button-primary' : ''; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
