<?php
/**
 * Seller Dashboard Template
 * Shows earnings, sales, and payout information for groups/artists
 */

if (!defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();

if (!$current_user) {
    echo '<div class="simuze-error"><p>Please log in to view your seller dashboard.</p></div>';
    return;
}

// Get group if specified
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;
$seller_type = 'user';
$seller_id = $current_user->id;
$seller_name = $current_user->display_name;

if ($group_id) {
    $group = Simuze_Group::get($group_id);
    if ($group && $group->is_admin($current_user->id)) {
        $seller_type = 'group';
        $seller_id = $group_id;
        $seller_name = $group->name;
    } else {
        echo '<div class="simuze-error"><p>You do not have permission to view this dashboard.</p></div>';
        return;
    }
}

// Get seller account
$seller_account = Simuze_Seller_Account::get_by_seller($seller_type, $seller_id);

// Get earnings summary
$summary_all = $seller_account ? $seller_account->get_earnings_summary('all') : array('transaction_count' => 0, 'gross_total' => 0, 'fees_total' => 0, 'net_total' => 0);
$summary_month = $seller_account ? $seller_account->get_earnings_summary('month') : array('transaction_count' => 0, 'net_total' => 0);
$summary_week = $seller_account ? $seller_account->get_earnings_summary('week') : array('transaction_count' => 0, 'net_total' => 0);

// Get recent transactions
$recent_transactions = $seller_account ? $seller_account->get_transactions(array('limit' => 20)) : array();

// Get user's groups for switcher
$user_groups = Simuze_Group::get_user_groups($current_user->id, 50);
$admin_groups = array_filter($user_groups, function($g) use ($current_user) {
    $group = is_array($g) ? Simuze_Group::get($g['id']) : $g;
    return $group && $group->is_admin($current_user->id);
});
?>

<div class="seller-dashboard">
    <!-- Seller Switcher -->
    <?php if (!empty($admin_groups)): ?>
    <div class="seller-switcher">
        <label for="seller-select">Viewing dashboard for:</label>
        <select id="seller-select" onchange="window.location.href=this.value">
            <option value="?tab=seller" <?php selected(!$group_id); ?>>My Personal Sales</option>
            <?php foreach ($admin_groups as $g): 
                $grp = is_array($g) ? Simuze_Group::get($g['id']) : $g;
                if (!$grp) continue;
            ?>
                <option value="?tab=seller&group_id=<?php echo esc_attr($grp->id); ?>" <?php selected($group_id, $grp->id); ?>>
                    <?php echo esc_html($grp->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    
    <h2>Sales Dashboard: <?php echo esc_html($seller_name); ?></h2>
    
    <!-- Stats Cards -->
    <div class="seller-stats">
        <div class="stat-card">
            <span class="stat-value">€<?php echo number_format($seller_account ? $seller_account->total_earnings : 0, 2); ?></span>
            <span class="stat-label">Total Earnings</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">€<?php echo number_format($seller_account ? $seller_account->pending_payout : 0, 2); ?></span>
            <span class="stat-label">Pending Payout</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">€<?php echo number_format($seller_account ? $seller_account->total_paid_out : 0, 2); ?></span>
            <span class="stat-label">Total Paid Out</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?php echo esc_html($summary_all['transaction_count']); ?></span>
            <span class="stat-label">Total Sales</span>
        </div>
    </div>
    
    <!-- Period Stats -->
    <div class="period-stats">
        <div class="period-card">
            <h4>This Week</h4>
            <span class="amount">€<?php echo number_format($summary_week['net_total'], 2); ?></span>
            <span class="count"><?php echo esc_html($summary_week['transaction_count']); ?> sales</span>
        </div>
        <div class="period-card">
            <h4>This Month</h4>
            <span class="amount">€<?php echo number_format($summary_month['net_total'], 2); ?></span>
            <span class="count"><?php echo esc_html($summary_month['transaction_count']); ?> sales</span>
        </div>
    </div>
    
    <!-- Payment Account Status -->
    <div class="payment-account-section">
        <h3>Payment Account</h3>
        <?php if ($seller_account && $seller_account->is_verified): ?>
            <div class="account-status verified">
                <span class="status-icon">✓</span>
                <span class="status-text">
                    Payment account verified via <?php echo esc_html(ucfirst($seller_account->payment_provider)); ?>
                    (<?php echo esc_html($seller_account->provider_email); ?>)
                </span>
            </div>
            
            <?php if ($seller_account->can_request_payout()): ?>
                <button class="button button-primary request-payout-btn" 
                        data-seller-type="<?php echo esc_attr($seller_type); ?>"
                        data-seller-id="<?php echo esc_attr($seller_id); ?>">
                    Request Payout (€<?php echo number_format($seller_account->pending_payout, 2); ?>)
                </button>
            <?php else: ?>
                <p class="payout-info">
                    Minimum payout threshold: €<?php echo number_format($seller_account->payout_threshold, 2); ?>
                </p>
            <?php endif; ?>
        <?php else: ?>
            <div class="account-status not-verified">
                <span class="status-icon">!</span>
                <span class="status-text">No payment account connected</span>
            </div>
            <a href="<?php echo esc_url(add_query_arg('tab', 'payment-setup', $current_user->get_url())); ?>" class="button">
                Set Up Payment Account
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Recent Transactions -->
    <div class="transactions-section">
        <h3>Recent Sales</h3>
        <?php if (empty($recent_transactions)): ?>
            <p class="no-data">No sales yet. Start selling your music!</p>
        <?php else: ?>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Gross</th>
                        <th>Fee</th>
                        <th>Net</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_transactions as $tx): ?>
                        <tr>
                            <td><?php echo esc_html(date('M j, Y', strtotime($tx['created_at']))); ?></td>
                            <td>
                                <?php
                                if ($tx['order_item_id']) {
                                    global $wpdb;
                                    $item = $wpdb->get_row($wpdb->prepare(
                                        "SELECT * FROM {$wpdb->prefix}simuze_order_items WHERE id = %d",
                                        $tx['order_item_id']
                                    ));
                                    if ($item) {
                                        echo esc_html($item->product_title);
                                    }
                                }
                                ?>
                            </td>
                            <td>€<?php echo number_format($tx['gross_amount'], 2); ?></td>
                            <td>-€<?php echo number_format($tx['simuze_fee'], 2); ?></td>
                            <td class="net-amount">€<?php echo number_format($tx['net_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($tx['status']); ?>">
                                    <?php echo esc_html(ucfirst($tx['status'])); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.seller-dashboard {
    max-width: 1200px;
}

.seller-switcher {
    margin-bottom: 20px;
    padding: 15px;
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
}

.seller-switcher select {
    margin-left: 10px;
    padding: 8px 12px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    border-radius: 4px;
}

.seller-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 2rem;
    font-weight: bold;
    color: #fff;
}

.stat-label {
    display: block;
    color: rgba(255,255,255,0.6);
    margin-top: 5px;
}

.period-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin: 20px 0;
}

.period-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 15px 20px;
}

.period-card h4 {
    margin: 0 0 10px 0;
    color: rgba(255,255,255,0.7);
}

.period-card .amount {
    font-size: 1.5rem;
    font-weight: bold;
    color: #4CAF50;
}

.period-card .count {
    display: block;
    color: rgba(255,255,255,0.5);
    font-size: 0.9rem;
}

.payment-account-section {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.account-status {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.account-status.verified {
    background: rgba(76, 175, 80, 0.1);
    border: 1px solid rgba(76, 175, 80, 0.3);
}

.account-status.not-verified {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.status-icon {
    font-size: 1.5rem;
}

.transactions-section {
    margin-top: 30px;
}

.transactions-table {
    width: 100%;
    border-collapse: collapse;
}

.transactions-table th,
.transactions-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.transactions-table th {
    background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.7);
    font-weight: 500;
}

.net-amount {
    color: #4CAF50;
    font-weight: 600;
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
}

.status-completed { background: rgba(76, 175, 80, 0.2); color: #4CAF50; }
.status-pending { background: rgba(255, 193, 7, 0.2); color: #FFC107; }
.status-failed { background: rgba(244, 67, 54, 0.2); color: #F44336; }

.no-data {
    text-align: center;
    padding: 40px;
    color: rgba(255,255,255,0.5);
}

@media (max-width: 768px) {
    .seller-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .period-stats {
        grid-template-columns: 1fr;
    }
}
</style>











