<?php
/**
 * Dashboard Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$users_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';
$groups_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
$reviews_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'reviews';
$reports_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'reports';

$stats = array(
    'users' => $wpdb->get_var("SELECT COUNT(*) FROM $users_table WHERE deleted_at IS NULL"),
    'groups' => $wpdb->get_var("SELECT COUNT(*) FROM $groups_table WHERE deleted_at IS NULL"),
    'pending_reviews' => $wpdb->get_var("SELECT COUNT(*) FROM $reviews_table WHERE status = 'pending'"),
    'pending_reports' => $wpdb->get_var("SELECT COUNT(*) FROM $reports_table WHERE status = 'pending'"),
);
?>

<div class="wrap">
    <h1>Simuze Community Dashboard</h1>
    
    <div class="simuze-stats" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">
        <div class="stat-box" style="background: #fff; padding: 20px; border: 1px solid #ccc;">
            <h2><?php echo esc_html($stats['users']); ?></h2>
            <p>Total Users</p>
        </div>
        <div class="stat-box" style="background: #fff; padding: 20px; border: 1px solid #ccc;">
            <h2><?php echo esc_html($stats['groups']); ?></h2>
            <p>Total Groups</p>
        </div>
        <div class="stat-box" style="background: #fff; padding: 20px; border: 1px solid #ccc;">
            <h2><?php echo esc_html($stats['pending_reviews']); ?></h2>
            <p>Pending Reviews</p>
        </div>
        <div class="stat-box" style="background: #fff; padding: 20px; border: 1px solid #ccc;">
            <h2><?php echo esc_html($stats['pending_reports']); ?></h2>
            <p>Pending Reports</p>
        </div>
    </div>
    
    <div class="quick-links">
        <h2>Quick Links</h2>
        <ul>
            <li><a href="<?php echo admin_url('admin.php?page=simuze-group-types'); ?>">Manage Group Types</a></li>
            <li><a href="<?php echo admin_url('admin.php?page=simuze-reviews'); ?>">Review Queue</a></li>
            <li><a href="<?php echo admin_url('admin.php?page=simuze-reports'); ?>">Reports Queue</a></li>
            <li><a href="<?php echo admin_url('admin.php?page=simuze-settings'); ?>">Settings</a></li>
        </ul>
    </div>
</div>











