<?php
/**
 * User Purchase History Template
 * Shows all purchases made by the user
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$auth = Simuze_Auth::get_instance();
$profile_user = isset($user) ? $user : $auth->get_current_user();

if (!$profile_user) {
    echo '<p>' . __('Please log in to view your purchases.', 'simuze') . '</p>';
    return;
}

// Get purchases
$orders = Simuze_Order::get_by_buyer($profile_user->id, array(
    'status' => 'paid',
    'limit' => 50,
));

$total_spent = 0;
foreach ($orders as $order) {
    $total_spent += floatval($order->total_price);
}
?>

<div class="profile-purchases">
    <div class="purchases-header">
        <h2><?php _e('Your Purchases', 'simuze'); ?></h2>
        <div class="purchases-stats">
            <div class="stat">
                <span class="stat-value"><?php echo count($orders); ?></span>
                <span class="stat-label"><?php _e('Purchases', 'simuze'); ?></span>
            </div>
            <div class="stat">
                <span class="stat-value">€<?php echo number_format($total_spent, 2); ?></span>
                <span class="stat-label"><?php _e('Total Spent', 'simuze'); ?></span>
            </div>
        </div>
    </div>
    
    <?php if (empty($orders)): ?>
    <div class="purchases-empty">
        <i class="fas fa-shopping-bag"></i>
        <h3><?php _e('No purchases yet', 'simuze'); ?></h3>
        <p><?php _e('When you buy music, it will appear here.', 'simuze'); ?></p>
        <a href="<?php echo esc_url(home_url('/music/')); ?>" class="btn-primary">
            <?php _e('Browse Music', 'simuze'); ?>
        </a>
    </div>
    <?php else: ?>
    
    <div class="purchases-list">
        <?php foreach ($orders as $order): 
            $items = $order->get_items();
        ?>
        <div class="purchase-card">
            <div class="purchase-header">
                <div class="purchase-info">
                    <span class="purchase-date">
                        <?php echo date_i18n(get_option('date_format'), strtotime($order->paid_at ?: $order->created_at)); ?>
                    </span>
                    <span class="purchase-order-number">#<?php echo esc_html($order->order_number); ?></span>
                </div>
                <div class="purchase-amount">
                    €<?php echo number_format($order->total_price, 2); ?>
                </div>
            </div>
            
            <div class="purchase-items">
                <?php foreach ($items as $item):
                    // Get product
                    $product = null;
                    $artwork = '';
                    
                    if ($item['product_type'] === 'song') {
                        $product = Simuze_Song::get($item['product_id']);
                        if ($product) {
                            $group = $product->get_group();
                            $artwork = $product->artwork_url ?: ($group ? $group->get_avatar_url('thumb') : '');
                        }
                    } else {
                        $product = Simuze_Album::get($item['product_id']);
                        if ($product) {
                            $group = $product->get_group();
                            $artwork = $product->cover_image ?: ($group ? $group->get_avatar_url('thumb') : '');
                        }
                    }
                    
                    // Get download token
                    $token = Simuze_Download_Token::get_by_order_item($item['id']);
                ?>
                <div class="purchase-item">
                    <div class="item-artwork">
                        <?php if ($artwork): ?>
                        <img src="<?php echo esc_url($artwork); ?>" alt="">
                        <?php else: ?>
                        <div class="artwork-placeholder"><i class="fas fa-music"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="item-info">
                        <h4 class="item-title"><?php echo esc_html($product ? $product->title : $item['product_title']); ?></h4>
                        <?php if ($product && $product->get_group()): ?>
                        <p class="item-artist"><?php echo esc_html($product->get_group()->name); ?></p>
                        <?php endif; ?>
                        <span class="item-type">
                            <?php echo $item['product_type'] === 'song' ? __('Track', 'simuze') : __('Album', 'simuze'); ?>
                        </span>
                    </div>
                    <div class="item-actions">
                        <?php if ($token && $token->is_valid()): ?>
                        <a href="<?php echo esc_url(home_url('/download/access/?token=' . $token->token)); ?>" 
                           class="btn-download">
                            <i class="fas fa-download"></i>
                            <?php _e('Download', 'simuze'); ?>
                        </a>
                        <?php else: ?>
                        <span class="download-expired">
                            <i class="fas fa-clock"></i>
                            <?php _e('Link expired', 'simuze'); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php endif; ?>
</div>

<style>
.profile-purchases {
    max-width: 800px;
}

.purchases-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 32px;
}

.purchases-header h2 {
    margin: 0;
    font-size: 24px;
    color: #fff;
}

.purchases-stats {
    display: flex;
    gap: 24px;
}

.purchases-stats .stat {
    text-align: right;
}

.purchases-stats .stat-value {
    display: block;
    font-size: 20px;
    font-weight: 600;
    color: #fff;
}

.purchases-stats .stat-label {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.5);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Empty State */
.purchases-empty {
    text-align: center;
    padding: 60px 20px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
}

.purchases-empty i {
    font-size: 48px;
    color: rgba(255, 255, 255, 0.2);
    margin-bottom: 16px;
}

.purchases-empty h3 {
    margin: 0 0 8px;
    color: #fff;
}

.purchases-empty p {
    color: rgba(255, 255, 255, 0.6);
    margin: 0 0 24px;
}

.purchases-empty .btn-primary {
    display: inline-block;
    padding: 12px 24px;
    background: #51ac32;
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
}

/* Purchase Cards */
.purchases-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.purchase-card {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    overflow: hidden;
}

.purchase-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.purchase-info {
    display: flex;
    align-items: center;
    gap: 16px;
}

.purchase-date {
    color: #fff;
    font-weight: 500;
}

.purchase-order-number {
    color: rgba(255, 255, 255, 0.4);
    font-size: 13px;
}

.purchase-amount {
    font-size: 18px;
    font-weight: 600;
    color: #51ac32;
}

.purchase-items {
    padding: 12px;
}

.purchase-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px;
    border-radius: 8px;
    transition: background 0.2s;
}

.purchase-item:hover {
    background: rgba(255, 255, 255, 0.03);
}

.item-artwork {
    width: 50px;
    height: 50px;
    border-radius: 6px;
    overflow: hidden;
    flex-shrink: 0;
}

.item-artwork img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.artwork-placeholder {
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255, 255, 255, 0.3);
}

.item-info {
    flex: 1;
    min-width: 0;
}

.item-title {
    margin: 0 0 4px;
    font-size: 15px;
    font-weight: 500;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-artist {
    margin: 0 0 4px;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.6);
}

.item-type {
    font-size: 11px;
    color: rgba(255, 255, 255, 0.4);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.item-actions {
    flex-shrink: 0;
}

.btn-download {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #51ac32;
    color: #fff;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-download:hover {
    background: #45a028;
    color: #fff;
}

.download-expired {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: rgba(255, 255, 255, 0.4);
    font-size: 13px;
}

@media (max-width: 600px) {
    .purchases-header {
        flex-direction: column;
        gap: 16px;
    }
    
    .purchases-stats {
        align-self: flex-start;
    }
    
    .purchase-item {
        flex-wrap: wrap;
    }
    
    .item-actions {
        width: 100%;
        margin-top: 8px;
        padding-left: 66px;
    }
}
</style>
