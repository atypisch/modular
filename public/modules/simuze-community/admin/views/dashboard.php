<?php
/**
 * Dashboard Admin Page – Modular styling with cards
 */
if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

$stats = array(
    'users'           => md_db_var("SELECT COUNT(*) FROM simuze_users WHERE deleted_at IS NULL"),
    'groups'          => md_db_var("SELECT COUNT(*) FROM simuze_groups WHERE deleted_at IS NULL"),
    'pending_reviews' => md_db_var("SELECT COUNT(*) FROM simuze_reviews WHERE status = 'pending'"),
    'pending_reports' => md_db_var("SELECT COUNT(*) FROM simuze_reports WHERE status = 'pending'"),
);

$base_url = md_url('/admin/module-page.php?module=simuze-community&page=');
$admin_css_url = defined('SIMUZE_COMMUNITY_PLUGIN_URL') ? rtrim(SIMUZE_COMMUNITY_PLUGIN_URL, '/') . '/admin/assets/admin.css' : '';
?>
<?php if ($admin_css_url): ?>
<link rel="stylesheet" href="<?php echo esc_url($admin_css_url); ?>">
<?php endif; ?>

<div class="simuze-admin-page">
    <div class="page-header">
        <h1>Simuze Community Dashboard</h1>
    </div>

    <div class="simuze-stat-grid">
        <div class="simuze-stat-card">
            <div class="value"><?php echo esc_html(number_format($stats['users'])); ?></div>
            <div class="label">Total Users</div>
        </div>
        <div class="simuze-stat-card">
            <div class="value"><?php echo esc_html(number_format($stats['groups'])); ?></div>
            <div class="label">Total Groups</div>
        </div>
        <div class="simuze-stat-card">
            <div class="value"><?php echo esc_html(number_format($stats['pending_reviews'])); ?></div>
            <div class="label">Pending Reviews</div>
        </div>
        <div class="simuze-stat-card">
            <div class="value"><?php echo esc_html(number_format($stats['pending_reports'])); ?></div>
            <div class="label">Pending Reports</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Quick Links</div>
        <div class="card-body" style="padding: 0;">
            <ul class="simuze-quick-links">
                <li><a href="<?php echo esc_url($base_url . 'group-types'); ?>">Manage Group Types</a></li>
                <li><a href="<?php echo esc_url($base_url . 'reviews'); ?>">Review Queue</a></li>
                <li><a href="<?php echo esc_url($base_url . 'reports'); ?>">Reports Queue</a></li>
                <li><a href="<?php echo esc_url($base_url . 'messages'); ?>">Messages</a></li>
                <li><a href="<?php echo esc_url($base_url . 'activity'); ?>">Activity Log</a></li>
                <li><a href="<?php echo esc_url($base_url . 'settings'); ?>">Settings</a></li>
            </ul>
        </div>
    </div>
</div>
