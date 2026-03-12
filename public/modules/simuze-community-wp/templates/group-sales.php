<?php
/**
 * Group Sales History Template
 * Shows all sales received by the group (artist/label)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get group from context
$group = isset($group) ? $group : null;

if (!$group) {
    echo '<p>' . __('Group not found.', 'simuze') . '</p>';
    return;
}

// Check if current user can view sales
$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$can_view = false;

if ($current_user) {
    $role = $group->get_member_role($current_user->id);
    $can_view = in_array($role, array('owner', 'admin'));
}

if (!$can_view) {
    echo '<p>' . __('You don\'t have permission to view sales.', 'simuze') . '</p>';
    return;
}

// Get sales
$orders = Simuze_Order::get_by_seller('group', $group->id, array(
    'status' => 'paid',
    'limit' => 100,
));

// Calculate totals
$total_gross = 0;
$total_fees = 0;
$total_net = 0;

foreach ($orders as $order) {
    $items = $order->get_items();
    foreach ($items as $item) {
        if ($item['seller_id'] == $group->id) {
            $total_gross += floatval($item['total_price']);
            $total_fees += floatval($item['simuze_fee']);
            $total_net += floatval($item['seller_earnings']);
        }
    }
}

// Get seller account for payout info
global $wpdb;
$seller_accounts_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts';
$seller_account = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $seller_accounts_table WHERE seller_type = 'group' AND seller_id = %d",
    $group->id
), ARRAY_A);

$pending_payout = $seller_account ? floatval($seller_account['pending_payout']) : $total_net;
?>

<div class="group-sales">
    <div class="sales-header">
        <h2><?php _e('Sales & Earnings', 'simuze'); ?></h2>
    </div>
    
    <!-- Stats Overview -->
    <div class="sales-stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-content">
                <span class="stat-value"><?php echo count($orders); ?></span>
                <span class="stat-label"><?php _e('Total Sales', 'simuze'); ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-euro-sign"></i></div>
            <div class="stat-content">
                <span class="stat-value">€<?php echo number_format($total_gross, 2); ?></span>
                <span class="stat-label"><?php _e('Gross Revenue', 'simuze'); ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="stat-content">
                <span class="stat-value">€<?php echo number_format($total_net, 2); ?></span>
                <span class="stat-label"><?php _e('Net Earnings', 'simuze'); ?></span>
            </div>
        </div>
        <div class="stat-card highlight">
            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            <div class="stat-content">
                <span class="stat-value">€<?php echo number_format($pending_payout, 2); ?></span>
                <span class="stat-label"><?php _e('Pending Payout', 'simuze'); ?></span>
            </div>
        </div>
    </div>
    
    <?php if (empty($orders)): ?>
    <div class="sales-empty">
        <i class="fas fa-chart-line"></i>
        <h3><?php _e('No sales yet', 'simuze'); ?></h3>
        <p><?php _e('When people buy your music, sales will appear here.', 'simuze'); ?></p>
    </div>
    <?php else: ?>
    
    <!-- Sales Table -->
    <div class="sales-table-container">
        <table class="sales-table">
            <thead>
                <tr>
                    <th><?php _e('Date', 'simuze'); ?></th>
                    <th><?php _e('Product', 'simuze'); ?></th>
                    <th><?php _e('Buyer', 'simuze'); ?></th>
                    <th class="text-right"><?php _e('Gross', 'simuze'); ?></th>
                    <th class="text-right"><?php _e('Fee', 'simuze'); ?></th>
                    <th class="text-right"><?php _e('Net', 'simuze'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): 
                    $items = $order->get_items();
                    $buyer = null;
                    $buyer_name = __('Guest', 'simuze');
                    
                    if ($order->buyer_id > 0) {
                        $buyer = Simuze_User::get($order->buyer_id);
                        if ($buyer) {
                            $buyer_name = $buyer->display_name ?: $buyer->username;
                        }
                    }
                    
                    foreach ($items as $item):
                        if ($item['seller_id'] != $group->id) continue;
                ?>
                <tr>
                    <td class="sale-date">
                        <?php echo date_i18n('d M Y', strtotime($order->paid_at ?: $order->created_at)); ?>
                        <span class="sale-time"><?php echo date_i18n('H:i', strtotime($order->paid_at ?: $order->created_at)); ?></span>
                    </td>
                    <td class="sale-product">
                        <span class="product-title"><?php echo esc_html($item['product_title']); ?></span>
                        <span class="product-type">
                            <?php echo $item['product_type'] === 'song' ? __('Track', 'simuze') : __('Album', 'simuze'); ?>
                        </span>
                    </td>
                    <td class="sale-buyer">
                        <?php if ($buyer): ?>
                        <a href="<?php echo esc_url($buyer->get_url()); ?>">
                            <?php echo esc_html($buyer_name); ?>
                        </a>
                        <?php else: ?>
                        <span class="guest-purchase"><?php echo esc_html($buyer_name); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="sale-gross text-right">
                        €<?php echo number_format($item['total_price'], 2); ?>
                    </td>
                    <td class="sale-fee text-right">
                        -€<?php echo number_format($item['simuze_fee'], 2); ?>
                    </td>
                    <td class="sale-net text-right">
                        <strong>€<?php echo number_format($item['seller_earnings'], 2); ?></strong>
                    </td>
                </tr>
                <?php 
                    endforeach;
                endforeach; 
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Fee Info -->
    <div class="fee-info">
        <p>
            <i class="fas fa-info-circle"></i>
            <?php printf(
                __('Platform fee: %s%% of each sale. Payouts are processed via SIMEZU.', 'simuze'),
                get_option('simuze_commerce_fee_percentage', 15)
            ); ?>
        </p>
    </div>
    
    <?php endif; ?>
</div>

<style>
.group-sales {
    max-width: 1000px;
}

.sales-header {
    margin-bottom: 24px;
}

.sales-header h2 {
    margin: 0;
    font-size: 24px;
    color: #fff;
}

/* Stats Grid */
.sales-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 32px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-card.highlight {
    background: rgba(81, 172, 50, 0.1);
    border-color: rgba(81, 172, 50, 0.3);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.05);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: rgba(255, 255, 255, 0.5);
}

.stat-card.highlight .stat-icon {
    background: rgba(81, 172, 50, 0.2);
    color: #51ac32;
}

.stat-content {
    flex: 1;
}

.stat-value {
    display: block;
    font-size: 22px;
    font-weight: 600;
    color: #fff;
    line-height: 1.2;
}

.stat-label {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.5);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Empty State */
.sales-empty {
    text-align: center;
    padding: 60px 20px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
}

.sales-empty i {
    font-size: 48px;
    color: rgba(255, 255, 255, 0.2);
    margin-bottom: 16px;
}

.sales-empty h3 {
    margin: 0 0 8px;
    color: #fff;
}

.sales-empty p {
    color: rgba(255, 255, 255, 0.6);
    margin: 0;
}

/* Sales Table */
.sales-table-container {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    overflow: hidden;
}

.sales-table {
    width: 100%;
    border-collapse: collapse;
}

.sales-table th {
    padding: 14px 16px;
    text-align: left;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: rgba(255, 255, 255, 0.5);
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.sales-table th.text-right {
    text-align: right;
}

.sales-table td {
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: #fff;
    vertical-align: middle;
}

.sales-table tr:last-child td {
    border-bottom: none;
}

.sales-table tr:hover td {
    background: rgba(255, 255, 255, 0.02);
}

.sale-date {
    white-space: nowrap;
}

.sale-time {
    display: block;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.4);
}

.product-title {
    display: block;
    font-weight: 500;
}

.product-type {
    font-size: 11px;
    color: rgba(255, 255, 255, 0.4);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.sale-buyer a {
    color: #51ac32;
    text-decoration: none;
}

.sale-buyer a:hover {
    text-decoration: underline;
}

.guest-purchase {
    color: rgba(255, 255, 255, 0.5);
    font-style: italic;
}

.text-right {
    text-align: right;
}

.sale-fee {
    color: rgba(255, 255, 255, 0.5);
}

.sale-net strong {
    color: #51ac32;
}

/* Fee Info */
.fee-info {
    margin-top: 20px;
    padding: 16px 20px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 8px;
}

.fee-info p {
    margin: 0;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.5);
}

.fee-info i {
    margin-right: 8px;
}

@media (max-width: 900px) {
    .sales-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .sales-stats {
        grid-template-columns: 1fr;
    }
    
    .sales-table-container {
        overflow-x: auto;
    }
    
    .sales-table {
        min-width: 600px;
    }
}
</style>

