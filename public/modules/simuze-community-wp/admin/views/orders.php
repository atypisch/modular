<?php
/**
 * Orders Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix . SIMUZE_DB_PREFIX;

// Pagination
$per_page = 20;
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($page - 1) * $per_page;

// Filters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Build query
$where = "1=1";
if ($status_filter) {
    $where .= $wpdb->prepare(" AND payment_status = %s", $status_filter);
}

$total_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}orders WHERE $where");
$orders = $wpdb->get_results(
    "SELECT * FROM {$prefix}orders WHERE $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset",
    ARRAY_A
);

$total_pages = ceil($total_orders / $per_page);
?>

<div class="wrap">
    <h1>Orders</h1>
    
    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="status" id="filter-status">
                <option value="">All Statuses</option>
                <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending</option>
                <option value="processing" <?php selected($status_filter, 'processing'); ?>>Processing</option>
                <option value="paid" <?php selected($status_filter, 'paid'); ?>>Paid</option>
                <option value="failed" <?php selected($status_filter, 'failed'); ?>>Failed</option>
                <option value="refunded" <?php selected($status_filter, 'refunded'); ?>>Refunded</option>
            </select>
            <button type="button" class="button" onclick="applyFilter()">Filter</button>
        </div>
        
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo esc_html($total_orders); ?> orders</span>
            <?php if ($total_pages > 1): ?>
                <span class="pagination-links">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo add_query_arg('paged', $page - 1); ?>" class="prev-page button">&laquo;</a>
                    <?php endif; ?>
                    <span class="paging-input"><?php echo $page; ?> of <?php echo $total_pages; ?></span>
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo add_query_arg('paged', $page + 1); ?>" class="next-page button">&raquo;</a>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 120px;">Order #</th>
                <th>Buyer</th>
                <th>Items</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="8">No orders found.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $order): 
                    $items = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM {$prefix}order_items WHERE order_id = %d",
                        $order['id']
                    ), ARRAY_A);
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($order['order_number']); ?></strong></td>
                        <td>
                            <?php echo esc_html($order['buyer_email']); ?>
                            <?php if ($order['buyer_id']): ?>
                                <br><small>ID: <?php echo esc_html($order['buyer_id']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo count($items); ?> item(s)
                            <br><small>
                            <?php 
                            $item_names = array_map(function($i) { return $i['product_title']; }, $items);
                            echo esc_html(implode(', ', array_slice($item_names, 0, 2)));
                            if (count($items) > 2) echo '...';
                            ?>
                            </small>
                        </td>
                        <td>
                            €<?php echo number_format($order['total_price'], 2); ?>
                            <?php if ($order['discount_amount'] > 0): ?>
                                <br><small class="discount">-€<?php echo number_format($order['discount_amount'], 2); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($order['payment_method'] ?: '-'); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($order['payment_status']); ?>">
                                <?php echo esc_html(ucfirst($order['payment_status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo esc_html(date('M j, Y', strtotime($order['created_at']))); ?>
                            <br><small><?php echo esc_html(date('H:i', strtotime($order['created_at']))); ?></small>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=simuze-orders&action=view&order_id=' . $order['id']); ?>" class="button button-small">View</a>
                            <?php if ($order['payment_status'] === 'paid'): ?>
                                <button type="button" class="button button-small refund-btn" data-order-id="<?php echo esc_attr($order['id']); ?>">Refund</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}
.status-paid { background: #d4edda; color: #155724; }
.status-pending { background: #fff3cd; color: #856404; }
.status-processing { background: #cce5ff; color: #004085; }
.status-failed { background: #f8d7da; color: #721c24; }
.status-refunded { background: #e2e3e5; color: #383d41; }
.discount { color: #dc3545; }
</style>

<script>
function applyFilter() {
    var status = document.getElementById('filter-status').value;
    var url = new URL(window.location.href);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    url.searchParams.delete('paged');
    window.location.href = url.toString();
}
</script>











