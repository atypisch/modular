<?php
/**
 * Reviews Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';
$content_type = isset($_GET['content_type']) ? sanitize_text_field($_GET['content_type']) : null;
$page = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;

$result = Simuze_Review::get_queue(array(
    'status' => $status,
    'content_type' => $content_type,
    'per_page' => 20,
    'page' => $page,
));

$reviews = $result['reviews'];
$total = $result['total'];
?>

<div class="wrap">
    <h1>Review Queue</h1>
    
    <ul class="subsubsub">
        <li><a href="<?php echo admin_url('admin.php?page=simuze-reviews&status=pending'); ?>" class="<?php echo $status === 'pending' ? 'current' : ''; ?>">Pending (<?php echo Simuze_Review::get_queue(array('status' => 'pending'))['total']; ?>)</a> |</li>
        <li><a href="<?php echo admin_url('admin.php?page=simuze-reviews&status=approved'); ?>" class="<?php echo $status === 'approved' ? 'current' : ''; ?>">Approved</a> |</li>
        <li><a href="<?php echo admin_url('admin.php?page=simuze-reviews&status=rejected'); ?>" class="<?php echo $status === 'rejected' ? 'current' : ''; ?>">Rejected</a></li>
    </ul>
    
    <form method="get" style="margin: 20px 0;">
        <input type="hidden" name="page" value="simuze-reviews">
        <input type="hidden" name="status" value="<?php echo esc_attr($status); ?>">
        <select name="content_type">
            <option value="">All Types</option>
            <option value="song" <?php selected($content_type, 'song'); ?>>Song</option>
            <option value="playlist" <?php selected($content_type, 'playlist'); ?>>Playlist</option>
            <option value="album" <?php selected($content_type, 'album'); ?>>Album</option>
        </select>
        <button type="submit" class="button">Filter</button>
    </form>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Content Type</th>
                <th>Content ID</th>
                <th>Reviewer</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td><?php echo esc_html($review['id']); ?></td>
                        <td><?php echo esc_html($review['content_type']); ?></td>
                        <td><?php echo esc_html($review['content_id']); ?></td>
                        <td><?php echo esc_html($review['reviewer_name'] ?: $review['reviewer_username']); ?></td>
                        <td><?php echo esc_html($review['status']); ?></td>
                        <td><?php echo esc_html($review['notes']); ?></td>
                        <td><?php echo esc_html($review['created_at']); ?></td>
                        <td>
                            <?php if ($review['status'] === 'pending'): ?>
                                <a href="<?php echo admin_url('admin.php?action=approve_review&review_id=' . $review['id']); ?>" class="button">Approve</a>
                                <a href="<?php echo admin_url('admin.php?action=reject_review&review_id=' . $review['id']); ?>" class="button">Reject</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No reviews found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>











