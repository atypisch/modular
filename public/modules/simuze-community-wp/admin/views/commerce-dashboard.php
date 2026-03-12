<?php
/**
 * Commerce Dashboard Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix . SIMUZE_DB_PREFIX;

// Get stats
$total_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}orders WHERE payment_status = 'paid'");
$total_revenue = $wpdb->get_var("SELECT COALESCE(SUM(total_price), 0) FROM {$prefix}orders WHERE payment_status = 'paid'");
$total_songs = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}songs WHERE deleted_at IS NULL");
$total_albums = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}albums WHERE deleted_at IS NULL");
$pending_payouts = $wpdb->get_var("SELECT COALESCE(SUM(pending_payout), 0) FROM {$prefix}seller_accounts");

// Recent orders
$recent_orders = $wpdb->get_results(
    "SELECT * FROM {$prefix}orders ORDER BY created_at DESC LIMIT 10",
    ARRAY_A
);

// Top sellers
$top_sellers = $wpdb->get_results(
    "SELECT seller_type, seller_id, SUM(seller_earnings) as total_earnings, COUNT(*) as sales_count
     FROM {$prefix}order_items oi
     INNER JOIN {$prefix}orders o ON oi.order_id = o.id
     WHERE o.payment_status = 'paid'
     GROUP BY seller_type, seller_id
     ORDER BY total_earnings DESC
     LIMIT 10",
    ARRAY_A
);
?>

<div class="wrap simuze-commerce-dashboard">
    <h1>Simuze Commerce Dashboard</h1>
    
    <!-- Stats Cards -->
    <div class="commerce-stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-cart"></span></div>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html($total_orders); ?></span>
                <span class="stat-label">Total Orders</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-money-alt"></span></div>
            <div class="stat-content">
                <span class="stat-value">€<?php echo number_format($total_revenue, 2); ?></span>
                <span class="stat-label">Total Revenue</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-format-audio"></span></div>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html($total_songs); ?></span>
                <span class="stat-label">Songs</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-album"></span></div>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html($total_albums); ?></span>
                <span class="stat-label">Albums</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-bank"></span></div>
            <div class="stat-content">
                <span class="stat-value">€<?php echo number_format($pending_payouts, 2); ?></span>
                <span class="stat-label">Pending Payouts</span>
            </div>
        </div>
    </div>
    
    <div class="commerce-panels">
        <!-- Recent Orders -->
        <div class="commerce-panel">
            <h2>Recent Orders</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Buyer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_orders)): ?>
                        <tr><td colspan="5">No orders yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td><a href="<?php echo admin_url('admin.php?page=simuze-orders&order=' . $order['id']); ?>"><?php echo esc_html($order['order_number']); ?></a></td>
                                <td><?php echo esc_html($order['buyer_email']); ?></td>
                                <td>€<?php echo number_format($order['total_price'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo esc_attr($order['payment_status']); ?>"><?php echo esc_html(ucfirst($order['payment_status'])); ?></span></td>
                                <td><?php echo esc_html(date('M j, Y H:i', strtotime($order['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <p><a href="<?php echo admin_url('admin.php?page=simuze-orders'); ?>" class="button">View All Orders</a></p>
        </div>
        
        <!-- Top Sellers -->
        <div class="commerce-panel">
            <h2>Top Sellers</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Seller</th>
                        <th>Type</th>
                        <th>Sales</th>
                        <th>Earnings</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_sellers)): ?>
                        <tr><td colspan="4">No sales yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($top_sellers as $seller): 
                            $seller_name = '';
                            if ($seller['seller_type'] === 'group') {
                                $group = Simuze_Group::get($seller['seller_id']);
                                $seller_name = $group ? $group->name : 'Unknown Group';
                            } else {
                                $user = Simuze_User::get($seller['seller_id']);
                                $seller_name = $user ? $user->display_name : 'Unknown User';
                            }
                        ?>
                            <tr>
                                <td><?php echo esc_html($seller_name); ?></td>
                                <td><?php echo esc_html(ucfirst($seller['seller_type'])); ?></td>
                                <td><?php echo esc_html($seller['sales_count']); ?></td>
                                <td>€<?php echo number_format($seller['total_earnings'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <p><a href="<?php echo admin_url('admin.php?page=simuze-payouts'); ?>" class="button">Manage Payouts</a></p>
        </div>
    </div>
</div>

<style>
.commerce-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: #f0f0f1;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #2271b1;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
}

.stat-label {
    display: block;
    color: #646970;
    font-size: 13px;
}

.commerce-panels {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.commerce-panel {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.commerce-panel h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #ccd0d4;
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.status-paid { background: #d4edda; color: #155724; }
.status-pending { background: #fff3cd; color: #856404; }
.status-failed { background: #f8d7da; color: #721c24; }
.status-refunded { background: #e2e3e5; color: #383d41; }
</style>











