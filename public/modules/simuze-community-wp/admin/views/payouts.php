<?php
/**
 * Payouts Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix . SIMUZE_DB_PREFIX;

// Get all seller accounts with pending payouts
$seller_accounts = $wpdb->get_results(
    "SELECT * FROM {$prefix}seller_accounts ORDER BY pending_payout DESC",
    ARRAY_A
);

// Get recent payout transactions
$payout_transactions = $wpdb->get_results(
    "SELECT * FROM {$prefix}transactions WHERE transaction_type = 'payout' ORDER BY created_at DESC LIMIT 50",
    ARRAY_A
);

// Calculate totals
$total_pending = $wpdb->get_var("SELECT COALESCE(SUM(pending_payout), 0) FROM {$prefix}seller_accounts");
$total_paid = $wpdb->get_var("SELECT COALESCE(SUM(total_paid_out), 0) FROM {$prefix}seller_accounts");
?>

<div class="wrap">
    <h1>Payouts Management</h1>
    
    <!-- Summary Cards -->
    <div class="payout-summary">
        <div class="summary-card">
            <span class="amount">€<?php echo number_format($total_pending, 2); ?></span>
            <span class="label">Total Pending Payouts</span>
        </div>
        <div class="summary-card">
            <span class="amount">€<?php echo number_format($total_paid, 2); ?></span>
            <span class="label">Total Paid Out</span>
        </div>
        <div class="summary-card">
            <span class="amount"><?php echo count($seller_accounts); ?></span>
            <span class="label">Active Sellers</span>
        </div>
    </div>
    
    <!-- Seller Accounts -->
    <h2>Seller Accounts</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Seller</th>
                <th>Type</th>
                <th>Payment Provider</th>
                <th>Total Earnings</th>
                <th>Pending</th>
                <th>Paid Out</th>
                <th>Verified</th>
                <th>Last Payout</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($seller_accounts)): ?>
                <tr><td colspan="9">No seller accounts found.</td></tr>
            <?php else: ?>
                <?php foreach ($seller_accounts as $account): 
                    $seller_name = '';
                    if ($account['seller_type'] === 'group') {
                        $group = Simuze_Group::get($account['seller_id']);
                        $seller_name = $group ? $group->name : 'Unknown Group #' . $account['seller_id'];
                    } else {
                        $user = Simuze_User::get($account['seller_id']);
                        $seller_name = $user ? $user->display_name : 'Unknown User #' . $account['seller_id'];
                    }
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($seller_name); ?></strong></td>
                        <td><?php echo esc_html(ucfirst($account['seller_type'])); ?></td>
                        <td>
                            <?php echo esc_html(ucfirst($account['payment_provider'])); ?>
                            <?php if ($account['provider_email']): ?>
                                <br><small><?php echo esc_html($account['provider_email']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>€<?php echo number_format($account['total_earnings'], 2); ?></td>
                        <td class="<?php echo $account['pending_payout'] > 0 ? 'pending-amount' : ''; ?>">
                            €<?php echo number_format($account['pending_payout'], 2); ?>
                        </td>
                        <td>€<?php echo number_format($account['total_paid_out'], 2); ?></td>
                        <td>
                            <?php if ($account['is_verified']): ?>
                                <span class="verified-badge">✓ Verified</span>
                            <?php else: ?>
                                <span class="unverified-badge">Not Verified</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $account['last_payout_at'] ? esc_html(date('M j, Y', strtotime($account['last_payout_at']))) : 'Never'; ?>
                        </td>
                        <td>
                            <?php if ($account['pending_payout'] >= $account['payout_threshold'] && $account['is_verified']): ?>
                                <button type="button" class="button button-primary button-small process-payout-btn" 
                                        data-account-id="<?php echo esc_attr($account['id']); ?>"
                                        data-amount="<?php echo esc_attr($account['pending_payout']); ?>">
                                    Process Payout
                                </button>
                            <?php elseif (!$account['is_verified']): ?>
                                <button type="button" class="button button-small verify-btn" 
                                        data-account-id="<?php echo esc_attr($account['id']); ?>">
                                    Verify
                                </button>
                            <?php else: ?>
                                <span class="threshold-info">Below threshold (€<?php echo number_format($account['payout_threshold'], 2); ?>)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Recent Payouts -->
    <h2>Recent Payout Transactions</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Seller</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Batch ID</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payout_transactions)): ?>
                <tr><td colspan="5">No payout transactions yet.</td></tr>
            <?php else: ?>
                <?php foreach ($payout_transactions as $tx): 
                    $seller_name = '';
                    if ($tx['seller_type'] === 'group') {
                        $group = Simuze_Group::get($tx['seller_id']);
                        $seller_name = $group ? $group->name : 'Unknown';
                    } else {
                        $user = Simuze_User::get($tx['seller_id']);
                        $seller_name = $user ? $user->display_name : 'Unknown';
                    }
                ?>
                    <tr>
                        <td><?php echo esc_html(date('M j, Y H:i', strtotime($tx['created_at']))); ?></td>
                        <td><?php echo esc_html($seller_name); ?></td>
                        <td>€<?php echo number_format($tx['net_amount'], 2); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($tx['payout_status']); ?>">
                                <?php echo esc_html(ucfirst($tx['payout_status'])); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($tx['payout_batch_id'] ?: '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.payout-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin: 20px 0;
}

.summary-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.summary-card .amount {
    display: block;
    font-size: 28px;
    font-weight: 600;
    color: #1d2327;
}

.summary-card .label {
    display: block;
    color: #646970;
    margin-top: 5px;
}

.pending-amount {
    color: #0073aa;
    font-weight: 600;
}

.verified-badge {
    color: #46b450;
    font-weight: 500;
}

.unverified-badge {
    color: #dc3232;
}

.threshold-info {
    color: #646970;
    font-size: 12px;
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-processing { background: #cce5ff; color: #004085; }
.status-paid { background: #d4edda; color: #155724; }
.status-failed { background: #f8d7da; color: #721c24; }
</style>

<script>
jQuery(document).ready(function($) {
    $('.process-payout-btn').on('click', function() {
        var accountId = $(this).data('account-id');
        var amount = $(this).data('amount');
        
        if (confirm('Process payout of €' + parseFloat(amount).toFixed(2) + '?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'simuze_process_payout',
                    account_id: accountId,
                    _wpnonce: '<?php echo wp_create_nonce('simuze_payout'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Payout processed successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        }
    });
    
    $('.verify-btn').on('click', function() {
        var accountId = $(this).data('account-id');
        
        if (confirm('Mark this account as verified?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'simuze_verify_seller',
                    account_id: accountId,
                    _wpnonce: '<?php echo wp_create_nonce('simuze_verify'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        }
    });
});
</script>











