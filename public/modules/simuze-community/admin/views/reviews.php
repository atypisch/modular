<?php
/**
 * Reviews Admin Page – Modular styling with cards
 */
if (!defined('ABSPATH') && !defined('SIMUZE_MODULAR_RUN')) {
    exit;
}

$is_modular = defined('SIMUZE_MODULAR_RUN') && SIMUZE_MODULAR_RUN;
$reviews_base = $is_modular ? md_url('/admin/module-page.php?module=simuze-community&page=reviews') : admin_url('admin.php?page=simuze-reviews');

// Handle actions (Modular: handle here; WP: may be handled by plugin)
if ($is_modular && isset($_GET['action'], $_GET['review_id'])) {
    $rid = (int) $_GET['review_id'];
    if ($_GET['action'] === 'approve_review' && $rid) {
        if (class_exists('Simuze_Review')) {
            Simuze_Review::update_status($rid, 'approved');
        }
        header('Location: ' . $reviews_base . (strpos($reviews_base, '?') !== false ? '&' : '?') . 'status=pending');
        exit;
    }
    if ($_GET['action'] === 'reject_review' && $rid) {
        if (class_exists('Simuze_Review')) {
            Simuze_Review::update_status($rid, 'rejected');
        }
        header('Location: ' . $reviews_base . (strpos($reviews_base, '?') !== false ? '&' : '?') . 'status=pending');
        exit;
    }
}

$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';
$content_type = isset($_GET['content_type']) ? sanitize_text_field($_GET['content_type']) : null;
$page_num = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;

$result = Simuze_Review::get_queue(array(
    'status' => $status,
    'content_type' => $content_type,
    'per_page' => 20,
    'page' => $page_num,
));

$reviews = $result['reviews'];
$total = $result['total'];

$base_url = $reviews_base;
$pending_count = Simuze_Review::get_queue(array('status' => 'pending'))['total'];

$admin_css_url = defined('SIMUZE_COMMUNITY_PLUGIN_URL') ? rtrim(SIMUZE_COMMUNITY_PLUGIN_URL, '/') . '/admin/assets/admin.css' : '';
?>
<?php if ($admin_css_url): ?>
<link rel="stylesheet" href="<?php echo esc_url($admin_css_url); ?>">
<?php endif; ?>

<div class="simuze-admin-page">
    <div class="page-header">
        <h1>Review Queue</h1>
    </div>

    <ul class="simuze-tabs">
        <li><a href="<?php echo esc_url($base_url . (strpos($base_url, '?') !== false ? '&' : '?') . 'status=pending'); ?>" class="<?php echo $status === 'pending' ? 'current' : ''; ?>">Pending <span class="count">(<?php echo (int) $pending_count; ?>)</span></a></li>
        <li><a href="<?php echo esc_url($base_url . (strpos($base_url, '?') !== false ? '&' : '?') . 'status=approved'); ?>" class="<?php echo $status === 'approved' ? 'current' : ''; ?>">Approved</a></li>
        <li><a href="<?php echo esc_url($base_url . (strpos($base_url, '?') !== false ? '&' : '?') . 'status=rejected'); ?>" class="<?php echo $status === 'rejected' ? 'current' : ''; ?>">Rejected</a></li>
    </ul>

    <form method="get" class="simuze-form-toolbar">
        <?php if ($is_modular): ?>
        <input type="hidden" name="module" value="simuze-community">
        <input type="hidden" name="page" value="reviews">
        <?php else: ?>
        <input type="hidden" name="page" value="simuze-reviews">
        <?php endif; ?>
        <input type="hidden" name="status" value="<?php echo esc_attr($status); ?>">
        <select name="content_type">
            <option value="">All Types</option>
            <option value="song" <?php selected($content_type, 'song'); ?>>Song</option>
            <option value="playlist" <?php selected($content_type, 'playlist'); ?>>Playlist</option>
            <option value="album" <?php selected($content_type, 'album'); ?>>Album</option>
        </select>
        <button type="submit" class="button">Filter</button>
    </form>

    <div class="card">
        <div class="card-header">Reviews</div>
        <div class="card-body">
            <table class="simuze-table">
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
                                        <?php $sep = $is_modular ? '&' : '&'; $action_url = $is_modular ? md_url('/admin/module-page.php?module=simuze-community&page=reviews') : admin_url('admin.php?page=simuze-reviews'); ?>
                                        <a href="<?php echo esc_url($action_url . '?action=approve_review&review_id=' . (int) $review['id']); ?>" class="button btn btn-sm">Approve</a>
                                        <a href="<?php echo esc_url($action_url . '?action=reject_review&review_id=' . (int) $review['id']); ?>" class="button btn btn-sm">Reject</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="simuze-empty">No reviews found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
