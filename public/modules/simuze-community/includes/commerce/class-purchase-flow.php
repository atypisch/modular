<?php
/**
 * Purchase Flow Handler
 * Manages the purchase process including guest checkout
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
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
        md_hook_action('wp_ajax_simuze_init_purchase',        [$this, 'ajax_init_purchase']);
        md_hook_action('wp_ajax_nopriv_simuze_init_purchase', [$this, 'ajax_init_purchase']);

        md_hook_action('wp_ajax_simuze_guest_checkout',        [$this, 'ajax_guest_checkout']);
        md_hook_action('wp_ajax_nopriv_simuze_guest_checkout', [$this, 'ajax_guest_checkout']);

        md_hook_action('wp_ajax_simuze_get_or_create_song',        [$this, 'ajax_get_or_create_song']);
        md_hook_action('wp_ajax_nopriv_simuze_get_or_create_song', [$this, 'ajax_get_or_create_song']);

        md_hook_action('wp_footer',          [$this, 'render_purchase_modal']);
        md_hook_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts() {
        if (function_exists('wp_enqueue_script')) {
            wp_enqueue_script('simuze-purchase-flow', SIMUZE_COMMUNITY_PLUGIN_URL . 'assets/js/purchase-flow.js', ['jquery'], SIMUZE_COMMUNITY_VERSION, true);
        }

        $auth         = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();

        $login_url = md_db_var("SELECT option_value FROM settings WHERE option_name = 'simuze_login_url' LIMIT 1") ?: 'http://localhost/simezu/login.php';

        $data = [
            'ajaxUrl'        => md_url('/api/simezu-ajax.php'),
            'nonce'          => md_csrf_token(),
            'isLoggedIn'     => $current_user !== null,
            'loginUrl'       => $login_url,
            'currency'       => 'EUR',
            'currencySymbol' => '€',
        ];

        if (function_exists('wp_localize_script')) {
            wp_localize_script('simuze-purchase-flow', 'SimuzePurchase', $data);
        }
    }

    public static function has_purchased($user_id, $product_type, $product_id) {
        if (!$user_id) return false;
        return (int) md_db_var(
            "SELECT COUNT(*) FROM simuze_download_grants WHERE user_id = ? AND product_type = ? AND product_id = ?",
            [$user_id, $product_type, $product_id]
        ) > 0;
    }

    public static function is_free($product_type, $product_id) {
        $product = $product_type === 'song' ? Simuze_Song::get($product_id) : ($product_type === 'album' ? Simuze_Album::get($product_id) : null);
        if ($product) {
            $pricing = $product->get_final_price();
            return $pricing['final_price'] <= 0;
        }
        return false;
    }

    public static function get_download_button($product_type, $product_id, $classes = '') {
        $auth         = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        $user_id      = $current_user ? $current_user->id : null;

        $product = $product_type === 'song' ? Simuze_Song::get($product_id) : ($product_type === 'album' ? Simuze_Album::get($product_id) : null);
        if (!$product) return '';

        $pricing       = $product->get_final_price($user_id);
        $is_free       = $pricing['final_price'] <= 0;
        $has_purchased = $user_id && self::has_purchased($user_id, $product_type, $product_id);

        if ($has_purchased || $is_free) {
            if ($has_purchased) {
                $download_url = md_url('/download/' . $product_type . '/' . $product_id);
                return sprintf(
                    '<a href="%s" class="simuze-download-btn %s" download><i class="fas fa-download"></i> Download</a>',
                    htmlspecialchars($download_url, ENT_QUOTES),
                    htmlspecialchars($classes, ENT_QUOTES)
                );
            }

            return sprintf(
                '<button type="button" class="simuze-download-btn simuze-free-download %s" data-product-type="%s" data-product-id="%d"><i class="fas fa-download"></i> Free Download</button>',
                htmlspecialchars($classes, ENT_QUOTES),
                htmlspecialchars($product_type, ENT_QUOTES),
                (int) $product_id
            );
        }

        return sprintf(
            '<button type="button" class="simuze-purchase-btn %s" data-product-type="%s" data-product-id="%d" data-price="%s" data-title="%s"><i class="fas fa-shopping-cart"></i> Buy €%s</button>',
            htmlspecialchars($classes, ENT_QUOTES),
            htmlspecialchars($product_type, ENT_QUOTES),
            (int) $product_id,
            htmlspecialchars($pricing['final_price'], ENT_QUOTES),
            htmlspecialchars($product->title, ENT_QUOTES),
            number_format($pricing['final_price'], 2)
        );
    }

    public function ajax_init_purchase() {
        if (!md_verify_csrf($_POST['nonce'] ?? '')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Security check failed']);
            exit;
        }

        $product_type = md_sanitize_text($_POST['product_type'] ?? '');
        $product_id   = (int) ($_POST['product_id'] ?? 0);

        if (!in_array($product_type, ['song', 'album'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Invalid product type']);
            exit;
        }

        $product = $product_type === 'song' ? Simuze_Song::get($product_id) : Simuze_Album::get($product_id);
        if (!$product) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Product not found']);
            exit;
        }

        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        if (!$current_user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => ['guest_checkout' => true, 'product' => $product->to_array(), 'redirect_simezu' => true]]);
            exit;
        }

        $api_url = md_db_var("SELECT option_value FROM settings WHERE option_name = 'simezu_api_url' LIMIT 1") ?: '';
        $redirect = $api_url ? rtrim($api_url, '/') . '/checkout?product_type=' . urlencode($product_type) . '&product_id=' . $product_id . '&buyer_id=' . $current_user->id : '';

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => [
            'redirect_url' => $redirect,
            'message'      => 'Checkout runs via SIMEZU.',
        ]]);
        exit;
    }

    public function ajax_guest_checkout() {
        if (!md_verify_csrf($_POST['nonce'] ?? '')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Security check failed']);
            exit;
        }

        $product_type = md_sanitize_text($_POST['product_type'] ?? '');
        $product_id   = (int) ($_POST['product_id'] ?? 0);
        $email        = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Valid email address required']);
            exit;
        }

        $product = $product_type === 'song' ? Simuze_Song::get($product_id) : ($product_type === 'album' ? Simuze_Album::get($product_id) : null);
        if (!$product) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Product not found']);
            exit;
        }

        $api_url   = md_db_var("SELECT option_value FROM settings WHERE option_name = 'simezu_api_url' LIMIT 1") ?: '';
        $redirect  = $api_url ? rtrim($api_url, '/') . '/checkout?product_type=' . urlencode($product_type) . '&product_id=' . $product_id . '&email=' . urlencode($email) : '';

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => [
            'redirect_url' => $redirect,
            'message'      => 'Checkout runs via SIMEZU.',
        ]]);
        exit;
    }

    public function ajax_get_or_create_song() {
        if (!md_verify_csrf($_POST['nonce'] ?? '')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Security check failed']);
            exit;
        }

        $upload_id = (int) ($_POST['upload_id']   ?? 0);
        $price     = (float) ($_POST['price']      ?? 0);
        $title     = md_sanitize_text($_POST['title'] ?? '');

        if (!$upload_id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Missing required parameters']);
            exit;
        }

        // Try to find existing song by file_path or title
        $upload_data = md_db_row("SELECT file_path, song_name, genre FROM md_music_uploads WHERE id = ?", [$upload_id]);

        $existing_song = null;
        if ($upload_data) {
            $existing_song = md_db_row("SELECT * FROM simuze_songs WHERE file_path = ? AND deleted_at IS NULL", [$upload_data['file_path']]);

            if (!$existing_song && !empty($title)) {
                $existing_song = md_db_row("SELECT * FROM simuze_songs WHERE title = ? AND deleted_at IS NULL", [$title]);
            }
        }

        if ($existing_song) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => ['song_id' => $existing_song['id'], 'created' => false]]);
            exit;
        }

        if (!$upload_data) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Upload data not found']);
            exit;
        }

        // Determine group_id from upload
        $group_id = md_db_var("SELECT group_id FROM md_music_uploads WHERE id = ?", [$upload_id]) ?: 0;

        if (!$group_id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Group ID not found for track']);
            exit;
        }

        $song = Simuze_Song::create([
            'group_id'       => $group_id,
            'title'          => !empty($title) ? $title : ($upload_data['song_name'] ?? 'Track ' . $upload_id),
            'price'          => $price,
            'file_path'      => $upload_data['file_path'] ?? '',
            'genre'          => $upload_data['genre']     ?? '',
            'is_active'      => 1,
            'is_purchasable' => 1,
        ]);

        if (!$song) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'data' => 'Failed to create song']);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => ['song_id' => $song->id, 'created' => true]]);
        exit;
    }

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
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="stripe">
                                <span class="payment-icon"><i class="fas fa-credit-card"></i></span>
                                <span class="payment-name">Credit Card</span>
                            </label>
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
            position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 99999;
            display: flex; align-items: center; justify-content: center;
        }
        #simuze-purchase-modal .simuze-modal-overlay {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8);
        }
        #simuze-purchase-modal .simuze-modal-content {
            position: relative; background: #1a1a1a; border-radius: 12px;
            max-width: 450px; width: 90%; max-height: 90vh; overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        #simuze-purchase-modal .simuze-modal-close {
            position: absolute; top: 15px; right: 15px; background: none;
            border: none; color: #fff; font-size: 24px; cursor: pointer; opacity: 0.7;
        }
        .simuze-modal-close:hover { opacity: 1; }
        .purchase-modal-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .purchase-modal-header h2 { margin: 0; color: #fff; }
        .purchase-modal-body { padding: 20px; }
        .product-info { display: flex; align-items: center; gap: 15px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px; margin-bottom: 20px; }
        .product-artwork { width: 60px; height: 60px; border-radius: 6px; object-fit: cover; }
        .product-details { flex: 1; }
        .product-title { margin: 0; font-size: 16px; color: #fff; }
        .product-artist { margin: 5px 0 0; color: rgba(255,255,255,0.6); font-size: 14px; }
        .price-amount { font-size: 24px; font-weight: bold; color: #4CAF50; }
        .guest-checkout-form { margin-bottom: 20px; }
        .guest-checkout-form input { width: 100%; padding: 12px 15px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; color: #fff; font-size: 16px; }
        .payment-methods { margin-bottom: 20px; }
        .payment-methods h4 { margin: 0 0 10px; color: rgba(255,255,255,0.7); }
        .payment-method { display: flex; align-items: center; gap: 10px; padding: 12px 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; margin-bottom: 8px; cursor: pointer; transition: all 0.2s; }
        .payment-method:hover, .payment-method:has(input:checked) { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.3); }
        .payment-icon { font-size: 24px; width: 40px; text-align: center; }
        .fa-paypal { color: #003087; } .fa-credit-card { color: #635bff; }
        .btn-purchase { width: 100%; padding: 15px 20px; background: #4CAF50; border: none; border-radius: 8px; color: #fff; font-size: 16px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-purchase:hover { background: #45a049; }
        .secure-notice { text-align: center; margin: 15px 0 0; color: rgba(255,255,255,0.5); font-size: 13px; }
        .purchase-modal-loading { padding: 60px 20px; text-align: center; }
        .spinner { width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.1); border-top-color: #4CAF50; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .simuze-download-btn, .simuze-purchase-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s; text-decoration: none; border: none; }
        .simuze-download-btn { background: #4CAF50; color: #fff; }
        .simuze-download-btn:hover { background: #45a049; color: #fff; }
        .simuze-purchase-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
        .simuze-purchase-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(102,126,234,0.4); }
        </style>
        <?php
    }
}
