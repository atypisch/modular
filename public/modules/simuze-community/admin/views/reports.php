<?php
/**
 * Reports Admin Page – Modular styling with cards
 */
if (!defined('ABSPATH') && !defined('SIMUZE_MODULAR_RUN')) {
    exit;
}

$is_modular = defined('SIMUZE_MODULAR_RUN') && SIMUZE_MODULAR_RUN;
$reports_base = $is_modular ? md_url('/admin/module-page.php?module=simuze-community&page=reports') : admin_url('admin.php?page=simuze-reports');

if ($is_modular && isset($_GET['action'], $_GET['report_id'])) {
    $rid = (int) $_GET['report_id'];
    $reviewed_by = 0;
    if (class_exists('Simuze_Auth')) {
        $u = Simuze_Auth::get_instance()->get_current_user();
        if ($u) $reviewed_by = (int) $u->id;
    }
    if ($rid && $_GET['action'] === 'resolve_report' && class_exists('Simuze_Report')) {
        Simuze_Report::mark_resolved($rid, $reviewed_by);
        header('Location: ' . $reports_base . (strpos($reports_base, '?') !== false ? '&' : '?') . 'status=pending');
        exit;
    }
    if ($rid && $_GET['action'] === 'remove_report' && class_exists('Simuze_Report')) {
        Simuze_Report::mark_removed($rid, $reviewed_by);
        header('Location: ' . $reports_base . (strpos($reports_base, '?') !== false ? '&' : '?') . 'status=pending');
        exit;
    }
}

$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';
$content_type = isset($_GET['content_type']) ? sanitize_text_field($_GET['content_type']) : null;
$page_num = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;

$result = Simuze_Report::get_queue(array(
    'status' => $status,
    'content_type' => $content_type,
    'per_page' => 20,
    'page' => $page_num,
));

$reports = $result['reports'];
$total = $result['total'];

$base_url = $reports_base;
$pending_count = Simuze_Report::get_queue(array('status' => 'pending'))['total'];

$admin_css_url = defined('SIMUZE_COMMUNITY_PLUGIN_URL') ? rtrim(SIMUZE_COMMUNITY_PLUGIN_URL, '/') . '/admin/assets/admin.css' : '';
?>
<?php if ($admin_css_url): ?>
<link rel="stylesheet" href="<?php echo esc_url($admin_css_url); ?>">
<?php endif; ?>

<div class="simuze-admin-page">
    <div class="page-header">
        <h1>Reports Queue</h1>
    </div>

    <ul class="simuze-tabs">
        <li><a href="<?php echo esc_url($base_url . (strpos($base_url, '?') !== false ? '&' : '?') . 'status=pending'); ?>" class="<?php echo $status === 'pending' ? 'current' : ''; ?>">Pending <span class="count">(<?php echo (int) $pending_count; ?>)</span></a></li>
        <li><a href="<?php echo esc_url($base_url . (strpos($base_url, '?') !== false ? '&' : '?') . 'status=resolved'); ?>" class="<?php echo $status === 'resolved' ? 'current' : ''; ?>">Resolved</a></li>
        <li><a href="<?php echo esc_url($base_url . (strpos($base_url, '?') !== false ? '&' : '?') . 'status=removed'); ?>" class="<?php echo $status === 'removed' ? 'current' : ''; ?>">Removed</a></li>
    </ul>

    <form method="get" class="simuze-form-toolbar">
        <?php if ($is_modular): ?>
        <input type="hidden" name="module" value="simuze-community">
        <input type="hidden" name="page" value="reports">
        <?php else: ?>
        <input type="hidden" name="page" value="simuze-reports">
        <?php endif; ?>
        <input type="hidden" name="status" value="<?php echo esc_attr($status); ?>">
        <select name="content_type">
            <option value="">All Types</option>
            <option value="song" <?php selected($content_type, 'song'); ?>>Song</option>
            <option value="playlist" <?php selected($content_type, 'playlist'); ?>>Playlist</option>
            <option value="album" <?php selected($content_type, 'album'); ?>>Album</option>
            <option value="group" <?php selected($content_type, 'group'); ?>>Group</option>
            <option value="user" <?php selected($content_type, 'user'); ?>>User</option>
        </select>
        <button type="submit" class="button">Filter</button>
    </form>

    <div class="card">
        <div class="card-header">Reports</div>
        <div class="card-body">
            <table class="simuze-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Content Type</th>
                        <th>Content ID</th>
                        <th>Report Type</th>
                        <th>Reporter</th>
                        <th>Comment</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reports)): ?>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo esc_html($report['id']); ?></td>
                                <td><?php echo esc_html($report['content_type']); ?></td>
                                <td><?php echo esc_html($report['content_id']); ?></td>
                                <td><?php echo esc_html($report['report_type']); ?></td>
                                <td><?php echo esc_html($report['reporter_name'] ?: $report['reporter_username']); ?></td>
                                <td><?php echo esc_html($report['comment']); ?></td>
                                <td><?php echo esc_html($report['status']); ?></td>
                                <td><?php echo esc_html($report['created_at']); ?></td>
                                <td>
                                    <?php if ($report['status'] === 'pending'): ?>
                                        <?php $action_url = $is_modular ? md_url('/admin/module-page.php?module=simuze-community&page=reports') : admin_url('admin.php?page=simuze-reports'); ?>
                                        <a href="<?php echo esc_url($action_url . '?action=resolve_report&report_id=' . (int) $report['id']); ?>" class="button btn btn-sm">Resolve</a>
                                        <a href="<?php echo esc_url($action_url . '?action=remove_report&report_id=' . (int) $report['id']); ?>" class="button btn btn-sm">Remove</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="simuze-empty">No reports found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
