<?php
/**
 * Download Success Page Template
 * Shown after successful payment
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variable available: $order
$items = $order ? $order->get_items() : array();
$tokens = array();

// Get download tokens for each item
foreach ($items as $item) {
    $token = Simuze_Download_Token::get_by_order_item($item['id']);
    if ($token) {
        $product = $token->get_product();
        $tokens[] = array(
            'token' => $token,
            'product' => $product,
            'item' => $item,
        );
    }
}
?>

<div class="download-success-page">
    <div class="download-success-container">
        
        <?php if ($order && $order->payment_status === 'paid'): ?>
        
        <!-- Success Header -->
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1><?php _e('Thank you for your purchase!', 'simuze'); ?></h1>
            <p><?php _e('Your download is ready. We\'ve also sent a confirmation email with your download link.', 'simuze'); ?></p>
        </div>
        
        <!-- Order Summary -->
        <div class="order-summary">
            <div class="order-summary-header">
                <span class="order-number"><?php printf(__('Order %s', 'simuze'), esc_html($order->order_number)); ?></span>
                <span class="order-total">€<?php echo number_format($order->total_price, 2); ?></span>
            </div>
        </div>
        
        <!-- Download Items -->
        <div class="download-items">
            <?php foreach ($tokens as $token_data): 
                $token = $token_data['token'];
                $product = $token_data['product'];
                $item = $token_data['item'];
                
                $group = $product ? $product->get_group() : null;
                $artwork = '';
                
                if ($product) {
                    if (isset($product->artwork_url) && $product->artwork_url) {
                        $artwork = $product->artwork_url;
                    } elseif (isset($product->cover_image) && $product->cover_image) {
                        $artwork = $product->cover_image;
                    } elseif ($group) {
                        $artwork = $group->get_avatar_url('thumb');
                    }
                }
            ?>
            <div class="download-item">
                <div class="download-item-artwork">
                    <img src="<?php echo esc_url($artwork); ?>" alt="">
                </div>
                <div class="download-item-info">
                    <h3><?php echo esc_html($product ? $product->title : $item['product_title']); ?></h3>
                    <?php if ($group): ?>
                    <p class="download-item-artist"><?php echo esc_html($group->name); ?></p>
                    <?php endif; ?>
                    <span class="download-item-type">
                        <?php echo $item['product_type'] === 'song' ? __('Track', 'simuze') : __('Album', 'simuze'); ?>
                    </span>
                </div>
                <div class="download-item-actions">
                    <a href="<?php echo esc_url(home_url('/download/access/?token=' . $token->token)); ?>" 
                       class="download-btn-primary">
                        <i class="fas fa-download"></i>
                        <?php _e('Download', 'simuze'); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Footer Links -->
        <div class="success-footer">
            <a href="<?php echo esc_url(home_url('/music/')); ?>" class="continue-browsing">
                <?php _e('Continue browsing music', 'simuze'); ?>
            </a>
            
            <?php 
            $auth = Simuze_Auth::get_instance();
            $current_user = $auth->get_current_user();
            if ($current_user): 
            ?>
            <a href="<?php echo esc_url(home_url('/profile/?tab=purchases')); ?>" class="view-purchases">
                <?php _e('View all purchases', 'simuze'); ?>
            </a>
            <?php endif; ?>
        </div>
        
        <?php elseif ($order && $order->payment_status === 'processing'): ?>
        
        <!-- Processing State -->
        <div class="processing-state">
            <div class="loading-spinner"></div>
            <h1><?php _e('Processing your payment...', 'simuze'); ?></h1>
            <p><?php _e('Please wait while we confirm your payment. This page will update automatically.', 'simuze'); ?></p>
        </div>
        
        <script>
        // Auto-refresh to check payment status
        setTimeout(function() {
            window.location.reload();
        }, 3000);
        </script>
        
        <?php else: ?>
        
        <!-- Failed/Unknown State -->
        <div class="failed-state">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h1><?php _e('Payment could not be verified', 'simuze'); ?></h1>
            <p><?php _e('We couldn\'t verify your payment. If you believe this is an error, please contact support.', 'simuze'); ?></p>
            <a href="<?php echo esc_url(home_url()); ?>" class="btn-secondary">
                <?php _e('Return to homepage', 'simuze'); ?>
            </a>
        </div>
        
        <?php endif; ?>
        
    </div>
</div>

<style>
.download-success-page {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
}

.download-success-container {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 48px;
    max-width: 600px;
    width: 100%;
    text-align: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.success-header {
    margin-bottom: 32px;
}

.success-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(81, 172, 50, 0.2);
    border-radius: 50%;
}

.success-icon i {
    font-size: 40px;
    color: #51ac32;
}

.success-header h1 {
    font-size: 28px;
    margin: 0 0 12px;
    color: #fff;
}

.success-header p {
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
}

.order-summary {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 24px;
}

.order-summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-number {
    color: rgba(255, 255, 255, 0.6);
    font-size: 14px;
}

.order-total {
    font-size: 20px;
    font-weight: 600;
    color: #51ac32;
}

.download-items {
    text-align: left;
}

.download-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    margin-bottom: 12px;
}

.download-item-artwork img {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
}

.download-item-info {
    flex: 1;
}

.download-item-info h3 {
    margin: 0 0 4px;
    font-size: 16px;
    color: #fff;
}

.download-item-artist {
    margin: 0 0 4px;
    color: rgba(255, 255, 255, 0.6);
    font-size: 14px;
}

.download-item-type {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.4);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.download-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: #51ac32;
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.download-btn-primary:hover {
    background: #45a028;
    transform: translateY(-1px);
    color: #fff;
}

.success-footer {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: space-between;
}

.continue-browsing,
.view-purchases {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 14px;
    transition: color 0.2s;
}

.continue-browsing:hover,
.view-purchases:hover {
    color: #51ac32;
}

/* Processing State */
.processing-state,
.failed-state {
    padding: 40px 0;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 3px solid rgba(255, 255, 255, 0.1);
    border-top-color: #51ac32;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 24px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.error-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(220, 53, 69, 0.2);
    border-radius: 50%;
}

.error-icon i {
    font-size: 40px;
    color: #dc3545;
}

.btn-secondary {
    display: inline-block;
    padding: 12px 24px;
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    margin-top: 20px;
    transition: background 0.2s;
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

@media (max-width: 600px) {
    .download-success-container {
        padding: 32px 20px;
    }
    
    .download-item {
        flex-direction: column;
        text-align: center;
    }
    
    .success-footer {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
}
</style>

