<?php
/**
 * Purchase Flow Handler
 * Manages the purchase process including guest checkout
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Purchase_Flow {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_simuze_init_purchase', array($this, 'ajax_init_purchase'));
        add_action('wp_ajax_nopriv_simuze_init_purchase', array($this, 'ajax_init_purchase'));
        
        add_action('wp_ajax_simuze_guest_checkout', array($this, 'ajax_guest_checkout'));
        add_action('wp_ajax_nopriv_simuze_guest_checkout', array($this, 'ajax_guest_checkout'));
        
        add_action('wp_ajax_simuze_get_or_create_song', array($this, 'ajax_get_or_create_song'));
        add_action('wp_ajax_nopriv_simuze_get_or_create_song', array($this, 'ajax_get_or_create_song'));
        
        // Add purchase modal to footer
        add_action('wp_footer', array($this, 'render_purchase_modal'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue purchase flow scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'simuze-purchase-flow',
            SIMUZE_COMMUNITY_PLUGIN_URL . 'assets/js/purchase-flow.js',
            array('jquery'),
            SIMUZE_COMMUNITY_VERSION,
            true
        );
        
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        wp_localize_script('simuze-purchase-flow', 'SimuzePurchase', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simuze_purchase'),
            'isLoggedIn' => $current_user !== null,
            'loginUrl' => get_option('simuze_login_url', 'http://localhost/simezu/login.php'),
            'currency' => 'EUR',
            'currencySymbol' => '€',
        ));
    }
    
    /**
     * Check if user has purchased a product
     */
    public static function has_purchased($user_id, $product_type, $product_id) {
        if (!$user_id) {
            return false;
        }
        
        global $wpdb;
        $orders_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'orders';
        $items_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'order_items';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $orders_table o
             INNER JOIN $items_table oi ON o.id = oi.order_id
             WHERE o.buyer_id = %d 
             AND o.payment_status = 'paid'
             AND oi.product_type = %s 
             AND oi.product_id = %d",
            $user_id,
            $product_type,
            $product_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Check if product is free
     */
    public static function is_free($product_type, $product_id) {
        if ($product_type === 'song') {
            $product = Simuze_Song::get($product_id);
            if ($product) {
                $pricing = $product->get_final_price();
                return $pricing['final_price'] <= 0;
            }
        } elseif ($product_type === 'album') {
            $product = Simuze_Album::get($product_id);
            if ($product) {
                $pricing = $product->get_final_price();
                return $pricing['final_price'] <= 0;
            }
        }
        return false;
    }
    
    /**
     * Get download button HTML
     * Shows appropriate button based on purchase status
     */
    public static function get_download_button($product_type, $product_id, $classes = '') {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        $user_id = $current_user ? $current_user->id : null;
        
        // Get product info
        $product = null;
        $pricing = null;
        
        if ($product_type === 'song') {
            $product = Simuze_Song::get($product_id);
            if ($product) {
                $pricing = $product->get_final_price($user_id);
            }
        } elseif ($product_type === 'album') {
            $product = Simuze_Album::get($product_id);
            if ($product) {
                $pricing = $product->get_final_price($user_id);
            }
        }
        
        if (!$product) {
            return '';
        }
        
        $is_free = $pricing['final_price'] <= 0;
        $has_purchased = $user_id && self::has_purchased($user_id, $product_type, $product_id);
        
        if ($has_purchased || $is_free) {
            // User has purchased or it's free - show download button
            if ($has_purchased) {
                // Get download token
                $token = self::get_or_create_download_token($user_id, $product_type, $product_id);
                if ($token) {
                    return sprintf(
                        '<a href="%s" class="simuze-download-btn %s" download>
                            <i class="fas fa-download"></i> Download
                        </a>',
                        esc_url($token->get_download_url()),
                        esc_attr($classes)
                    );
                }
            }
            
            // Free download (no token needed for free items)
            return sprintf(
                '<button type="button" class="simuze-download-btn simuze-free-download %s" 
                         data-product-type="%s" data-product-id="%d">
                    <i class="fas fa-download"></i> Free Download
                </button>',
                esc_attr($classes),
                esc_attr($product_type),
                esc_attr($product_id)
            );
        }
        
        // Show purchase button
        return sprintf(
            '<button type="button" class="simuze-purchase-btn %s" 
                     data-product-type="%s" 
                     data-product-id="%d" 
                     data-price="%s"
                     data-title="%s">
                <i class="fas fa-shopping-cart"></i> Buy €%s
            </button>',
            esc_attr($classes),
            esc_attr($product_type),
            esc_attr($product_id),
            esc_attr($pricing['final_price']),
            esc_attr($product->title),
            number_format($pricing['final_price'], 2)
        );
    }
    
    /**
     * Get or create download token for a purchased item
     */
    private static function get_or_create_download_token($user_id, $product_type, $product_id) {
        global $wpdb;
        $tokens_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'download_tokens';
        
        // Check for existing valid token
        $token_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tokens_table 
             WHERE buyer_id = %d 
             AND product_type = %s 
             AND product_id = %d
             AND (expires_at IS NULL OR expires_at > NOW())
             AND (max_downloads IS NULL OR download_count < max_downloads)
             ORDER BY created_at DESC LIMIT 1",
            $user_id,
            $product_type,
            $product_id
        ), ARRAY_A);
        
        if ($token_data) {
            return new Simuze_Download_Token($token_data);
        }
        
        // Find order item for this purchase
        $orders_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'orders';
        $items_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'order_items';
        
        $order_item = $wpdb->get_row($wpdb->prepare(
            "SELECT oi.* FROM $items_table oi
             INNER JOIN $orders_table o ON oi.order_id = o.id
             WHERE o.buyer_id = %d 
             AND o.payment_status = 'paid'
             AND oi.product_type = %s 
             AND oi.product_id = %d
             ORDER BY o.created_at DESC LIMIT 1",
            $user_id,
            $product_type,
            $product_id
        ), ARRAY_A);
        
        if ($order_item) {
            // Create new token
            return Simuze_Download_Token::create(array(
                'order_item_id' => $order_item['id'],
                'buyer_id' => $user_id,
                'product_type' => $product_type,
                'product_id' => $product_id,
            ));
        }
        
        return null;
    }
    
    /**
     * AJAX: Initialize purchase
     */
    public function ajax_init_purchase() {
        check_ajax_referer('simuze_purchase', 'nonce');
        
        $product_type = sanitize_text_field($_POST['product_type'] ?? '');
        $product_id = intval($_POST['product_id'] ?? 0);
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? 'paypal');
        
        if (!in_array($product_type, array('song', 'album'))) {
            wp_send_json_error('Invalid product type');
        }
        
        // Get product
        $product = null;
        if ($product_type === 'song') {
            $product = Simuze_Song::get($product_id);
        } else {
            $product = Simuze_Album::get($product_id);
        }
        
        if (!$product) {
            wp_send_json_error('Product not found');
        }
        
        // Get current user
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            // Guest checkout - redirect to PayPal directly
            wp_send_json_success(array(
                'guest_checkout' => true,
                'product' => $product->to_array(),
            ));
            return;
        }
        
        // Create order
        $cart_items = array(
            array('type' => $product_type, 'id' => $product_id)
        );
        
        $order = Simuze_Order::create_from_cart($current_user->id, $cart_items, array(
            'payment_method' => $payment_method,
        ));
        
        if (is_wp_error($order)) {
            wp_send_json_error($order->get_error_message());
        }
        
        // Create payment session
        $payments = Simuze_Simezu_Payments::get_instance();
        $result = $payments->create_payment($order, $payment_method);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'redirect_url' => $result['redirect_url'] ?? $result['paypal_redirect_url'] ?? null,
        ));
    }
    
    /**
     * AJAX: Guest checkout
     */
    public function ajax_guest_checkout() {
        check_ajax_referer('simuze_purchase', 'nonce');
        
        $product_type = sanitize_text_field($_POST['product_type'] ?? '');
        $product_id = intval($_POST['product_id'] ?? 0);
        $email = sanitize_email($_POST['email'] ?? '');
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? 'paypal');
        
        if (!is_email($email)) {
            wp_send_json_error('Valid email address required');
        }
        
        // Get product
        $product = null;
        if ($product_type === 'song') {
            $product = Simuze_Song::get($product_id);
        } else {
            $product = Simuze_Album::get($product_id);
        }
        
        if (!$product) {
            wp_send_json_error('Product not found');
        }
        
        $pricing = $product->get_final_price();
        
        // Create guest order directly in database
        global $wpdb;
        $orders_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'orders';
        $items_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'order_items';
        
        // Generate order number
        $order_number = 'SIM' . date('Ymd') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        
        // Calculate fees
        $simuze_fee_percentage = floatval(get_option('simuze_commerce_fee_percentage', 15));
        $simuze_fee = round($pricing['final_price'] * ($simuze_fee_percentage / 100), 2);
        $seller_earnings = round($pricing['final_price'] - $simuze_fee, 2);
        
        // Create order
        $wpdb->insert($orders_table, array(
            'order_number' => $order_number,
            'buyer_id' => 0, // Guest
            'buyer_email' => $email,
            'subtotal' => $pricing['base_price'],
            'discount_amount' => $pricing['discount_amount'],
            'total_price' => $pricing['final_price'],
            'currency' => 'EUR',
            'payment_status' => 'pending',
            'payment_method' => $payment_method,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ));
        
        $order_id = $wpdb->insert_id;
        
        if (!$order_id) {
            wp_send_json_error('Failed to create order');
        }
        
        // Create order item
        $wpdb->insert($items_table, array(
            'order_id' => $order_id,
            'seller_type' => 'group',
            'seller_id' => $product->group_id,
            'product_type' => $product_type,
            'product_id' => $product_id,
            'product_title' => $product->title,
            'quantity' => 1,
            'unit_price' => $pricing['base_price'],
            'discount_amount' => $pricing['discount_amount'],
            'total_price' => $pricing['final_price'],
            'simuze_fee' => $simuze_fee,
            'seller_earnings' => $seller_earnings,
        ));
        
        // Get order object
        $order = Simuze_Order::get($order_id);
        
        // Create payment via SIMEZU (ONLY - no direct PayPal/Stripe)
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'paypal';
        $payments = Simuze_Simezu_Payments::get_instance();
        $result = $payments->create_payment($order, $payment_method);
        
        if (is_wp_error($result)) {
            // All payments must go through SIMEZU - no direct payment provider fallbacks
            error_log('SIMEZU Payment Error: ' . $result->get_error_message());
            wp_send_json_error(array(
                'message' => $result->get_error_message() ?: 'Payment could not be processed through SIMEZU. Please try again later.',
                'code' => 'simezu_payment_failed'
            ));
            return;
        }
        
        wp_send_json_success(array(
            'order_id' => $order_id,
            'order_number' => $order_number,
            'redirect_url' => $result['redirect_url'] ?? $result['paypal_redirect_url'] ?? null,
        ));
    }
    
    /**
     * AJAX: Get or create Simuze_Song for a track
     */
    public function ajax_get_or_create_song() {
        check_ajax_referer('simuze_purchase', 'nonce');
        
        $upload_id = intval($_POST['upload_id'] ?? 0);
        $track_post_id = intval($_POST['track_post_id'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        
        if (!$upload_id || !$track_post_id) {
            wp_send_json_error('Missing required parameters');
        }
        
        if (!class_exists('Simuze_Song')) {
            wp_send_json_error('Simuze_Song class not available');
        }
        
        global $wpdb;
        $songs_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'songs';
        
        // Try to find existing song by matching file_path or title
        // First get upload data to match
        $upload_data = $wpdb->get_row($wpdb->prepare(
            "SELECT file_path, song_name FROM {$wpdb->prefix}music_uploads WHERE id = %d",
            $upload_id
        ));
        
        $existing_song = null;
        if ($upload_data) {
            // Try to find by file_path
            $existing_song = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $songs_table WHERE file_path = %s AND deleted_at IS NULL",
                $upload_data->file_path
            ), ARRAY_A);
            
            // If not found, try by title
            if (!$existing_song && !empty($title)) {
                $existing_song = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $songs_table WHERE title = %s AND deleted_at IS NULL",
                    $title
                ), ARRAY_A);
            }
        }
        
        if ($existing_song) {
            wp_send_json_success(array(
                'song_id' => $existing_song['id'],
                'created' => false
            ));
        }
        
        // Get track data to create song
        $track_post = get_post($track_post_id);
        if (!$track_post) {
            wp_send_json_error('Track post not found');
        }
        
        // Get group_id from track post meta
        $group_id = get_post_meta($track_post_id, '_simuze_group_id', true);
        if (!$group_id) {
            // Try to get from album
            $upload_data = $wpdb->get_row($wpdb->prepare(
                "SELECT album_id FROM {$wpdb->prefix}music_uploads WHERE id = %d",
                $upload_id
            ));
            if ($upload_data && $upload_data->album_id) {
                $group_id = get_post_meta($upload_data->album_id, '_simuze_group_id', true);
            }
        }
        
        if (!$group_id) {
            wp_send_json_error('Group ID not found for track');
        }
        
        // Get file path from upload
        $upload_data = $wpdb->get_row($wpdb->prepare(
            "SELECT file_path, genre FROM {$wpdb->prefix}music_uploads WHERE id = %d",
            $upload_id
        ));
        
        if (!$upload_data) {
            wp_send_json_error('Upload data not found');
        }
        
        // Create Simuze_Song
        $song_data = array(
            'group_id' => $group_id,
            'title' => !empty($title) ? $title : $track_post->post_title,
            'description' => $track_post->post_content,
            'price' => $price,
            'file_path' => $upload_data->file_path,
            'genre' => $upload_data->genre,
            'is_active' => 1,
            'is_purchasable' => 1
        );
        
        $song = Simuze_Song::create($song_data);
        
        if (!$song) {
            wp_send_json_error('Failed to create song');
        }
        
        wp_send_json_success(array(
            'song_id' => $song->id,
            'created' => true
        ));
    }
    
    /**
     * Render purchase modal in footer
     */
    public function render_purchase_modal() {
        ?>
        <div id="simuze-purchase-modal" class="simuze-modal" style="display: none;">
            <div class="simuze-modal-overlay"></div>
            <div class="simuze-modal-content">
                <button type="button" class="simuze-modal-close">&times;</button>
                
                <div class="purchase-modal-header">
                    <h2>Purchase</h2>
                </div>
                
                <div class="purchase-modal-body">
                    <div class="product-info">
                        <img class="product-artwork" src="" alt="">
                        <div class="product-details">
                            <h3 class="product-title"></h3>
                            <p class="product-artist"></p>
                        </div>
                        <div class="product-price">
                            <span class="price-amount"></span>
                        </div>
                    </div>
                    
                    <div class="purchase-options">
                        <div class="guest-checkout-form" style="display: none;">
                            <p>Enter your email to receive the download link:</p>
                            <input type="email" id="guest-email" placeholder="your@email.com" required>
                        </div>
                        
                        <div class="payment-methods">
                            <h4>Pay with:</h4>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="paypal" checked>
                                <span class="payment-icon"><i class="fab fa-paypal"></i></span>
                                <span class="payment-name">PayPal</span>
                            </label>
                            <?php if (get_option('simuze_stripe_enabled')): ?>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="stripe">
                                <span class="payment-icon"><i class="fas fa-credit-card"></i></span>
                                <span class="payment-name">Credit Card</span>
                            </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="purchase-actions">
                        <button type="button" class="btn-purchase" id="btn-complete-purchase">
                            <i class="fas fa-lock"></i> Complete Purchase
                        </button>
                        <p class="secure-notice">
                            <i class="fas fa-shield-alt"></i> Secure payment via SIMEZU
                        </p>
                    </div>
                </div>
                
                <div class="purchase-modal-loading" style="display: none;">
                    <div class="spinner"></div>
                    <p>Processing your payment...</p>
                </div>
            </div>
        </div>
        
        <style>
        #simuze-purchase-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #simuze-purchase-modal .simuze-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
        }
        
        #simuze-purchase-modal .simuze-modal-content {
            position: relative;
            background: #1a1a1a;
            border-radius: 12px;
            max-width: 450px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        
        #simuze-purchase-modal .simuze-modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            opacity: 0.7;
        }
        
        .simuze-modal-close:hover {
            opacity: 1;
        }
        
        .purchase-modal-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .purchase-modal-header h2 {
            margin: 0;
            color: #fff;
        }
        
        .purchase-modal-body {
            padding: 20px;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .product-artwork {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            object-fit: cover;
        }
        
        .product-details {
            flex: 1;
        }
        
        .product-title {
            margin: 0;
            font-size: 16px;
            color: #fff;
        }
        
        .product-artist {
            margin: 5px 0 0;
            color: rgba(255,255,255,0.6);
            font-size: 14px;
        }
        
        .price-amount {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }
        
        .guest-checkout-form {
            margin-bottom: 20px;
        }
        
        .guest-checkout-form input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 6px;
            color: #fff;
            font-size: 16px;
        }
        
        .payment-methods {
            margin-bottom: 20px;
        }
        
        .payment-methods h4 {
            margin: 0 0 10px;
            color: rgba(255,255,255,0.7);
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .payment-method:hover,
        .payment-method:has(input:checked) {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.3);
        }
        
        .payment-icon {
            font-size: 24px;
            width: 40px;
            text-align: center;
        }
        
        .fa-paypal { color: #003087; }
        .fa-credit-card { color: #635bff; }
        
        .btn-purchase {
            width: 100%;
            padding: 15px 20px;
            background: #4CAF50;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-purchase:hover {
            background: #45a049;
        }
        
        .secure-notice {
            text-align: center;
            margin: 15px 0 0;
            color: rgba(255,255,255,0.5);
            font-size: 13px;
        }
        
        .purchase-modal-loading {
            padding: 60px 20px;
            text-align: center;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255,255,255,0.1);
            border-top-color: #4CAF50;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Download buttons */
        .simuze-download-btn,
        .simuze-purchase-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
        }
        
        .simuze-download-btn {
            background: #4CAF50;
            color: #fff;
        }
        
        .simuze-download-btn:hover {
            background: #45a049;
            color: #fff;
        }
        
        .simuze-purchase-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }
        
        .simuze-purchase-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        </style>
        <?php
    }
}










