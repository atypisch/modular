<?php
/**
 * Reports Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';
$content_type = isset($_GET['content_type']) ? sanitize_text_field($_GET['content_type']) : null;
$page = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;

$result = Simuze_Report::get_queue(array(
    'status' => $status,
    'content_type' => $content_type,
    'per_page' => 20,
    'page' => $page,
));

$reports = $result['reports'];
$total = $result['total'];
?>

<div class="wrap">
    <h1>Reports Queue</h1>
    
    <ul class="subsubsub">
        <li><a href="<?php echo admin_url('admin.php?page=simuze-reports&status=pending'); ?>" class="<?php echo $status === 'pending' ? 'current' : ''; ?>">Pending (<?php echo Simuze_Report::get_queue(array('status' => 'pending'))['total']; ?>)</a> |</li>
        <li><a href="<?php echo admin_url('admin.php?page=simuze-reports&status=resolved'); ?>" class="<?php echo $status === 'resolved' ? 'current' : ''; ?>">Resolved</a> |</li>
        <li><a href="<?php echo admin_url('admin.php?page=simuze-reports&status=removed'); ?>" class="<?php echo $status === 'removed' ? 'current' : ''; ?>">Removed</a></li>
    </ul>
    
    <form method="get" style="margin: 20px 0;">
        <input type="hidden" name="page" value="simuze-reports">
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
    
    <table class="wp-list-table widefat fixed striped">
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
                                <a href="<?php echo admin_url('admin.php?action=resolve_report&report_id=' . $report['id']); ?>" class="button">Resolve</a>
                                <a href="<?php echo admin_url('admin.php?action=remove_report&report_id=' . $report['id']); ?>" class="button">Remove</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">No reports found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>











