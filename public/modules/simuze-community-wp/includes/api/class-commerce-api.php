<?php
/**
 * Commerce REST API endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Commerce_API {
    
    private $namespace = 'simuze/v1';
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Products
        register_rest_route($this->namespace, '/products/songs', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_songs'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($this->namespace, '/products/songs/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_song'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($this->namespace, '/products/albums', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_albums'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($this->namespace, '/products/albums/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_album'),
            'permission_callback' => '__return_true',
        ));
        
        // Cart
        register_rest_route($this->namespace, '/cart', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_cart'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($this->namespace, '/cart/add', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_to_cart'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($this->namespace, '/cart/remove', array(
            'methods' => 'POST',
            'callback' => array($this, 'remove_from_cart'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($this->namespace, '/cart/clear', array(
            'methods' => 'POST',
            'callback' => array($this, 'clear_cart'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($this->namespace, '/cart/apply-discount', array(
            'methods' => 'POST',
            'callback' => array($this, 'apply_discount'),
            'permission_callback' => '__return_true',
        ));
        
        // Orders
        register_rest_route($this->namespace, '/orders', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_orders'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($this->namespace, '/orders/create', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_order'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($this->namespace, '/orders/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_order'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Payments
        register_rest_route($this->namespace, '/payments/create', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_payment'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($this->namespace, '/payments/webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'payment_webhook'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route($this->namespace, '/payments/methods', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_payment_methods'),
            'permission_callback' => '__return_true',
        ));
        
        // Downloads
        register_rest_route($this->namespace, '/downloads', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_downloads'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Seller endpoints
        register_rest_route($this->namespace, '/seller/earnings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_seller_earnings'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($this->namespace, '/seller/sales', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_seller_sales'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        register_rest_route($this->namespace, '/seller/register-account', array(
            'methods' => 'POST',
            'callback' => array($this, 'register_seller_account'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Discounts (admin)
        register_rest_route($this->namespace, '/discounts', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_discounts'),
                'permission_callback' => array($this, 'check_admin'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_discount'),
                'permission_callback' => array($this, 'check_admin'),
            ),
        ));
        
        register_rest_route($this->namespace, '/discounts/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_discount'),
                'permission_callback' => array($this, 'check_admin'),
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_discount'),
                'permission_callback' => array($this, 'check_admin'),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_discount'),
                'permission_callback' => array($this, 'check_admin'),
            ),
        ));
    }
    
    /**
     * Check authentication
     */
    public function check_auth() {
        $auth = Simuze_Auth::get_instance();
        return $auth->get_current_user() !== null;
    }
    
    /**
     * Check admin permission
     */
    public function check_admin() {
        return current_user_can('manage_options');
    }
    
    /**
     * Get songs list
     */
    public function get_songs($request) {
        $args = array(
            'limit' => $request->get_param('limit') ?: 20,
            'offset' => $request->get_param('offset') ?: 0,
            'group_id' => $request->get_param('group_id'),
            'purchasable_only' => $request->get_param('purchasable') !== 'false',
        );
        
        if ($args['group_id']) {
            $songs = Simuze_Song::get_by_group($args['group_id'], $args);
        } else {
            // Get all purchasable songs
            global $wpdb;
            $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'songs';
            
            $where = "deleted_at IS NULL AND is_active = 1";
            if ($args['purchasable_only']) {
                $where .= " AND is_purchasable = 1";
            }
            
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $args['limit'],
                $args['offset']
            ), ARRAY_A);
            
            $songs = array_map(function($data) {
                return new Simuze_Song($data);
            }, $results);
        }
        
        return rest_ensure_response(array_map(function($song) {
            return $song->to_array();
        }, $songs));
    }
    
    /**
     * Get single song
     */
    public function get_song($request) {
        $song = Simuze_Song::get($request->get_param('id'));
        
        if (!$song) {
            return new WP_Error('not_found', 'Song not found', array('status' => 404));
        }
        
        $song_data = $song->to_array();
        
        // Get MP3 URL - use audio converter to get actual MP3 file
        $mp3_url = null;
        $file_path = $song->get_file_path();
        if ($file_path && file_exists($file_path)) {
            $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            if ($extension === 'mp3') {
                $mp3_url = $song->get_file_url();
            } else if (class_exists('Simuze_Audio_Converter')) {
                $converter = Simuze_Audio_Converter::get_instance();
                $mp3_path = $converter->get_mp3_version($file_path, 'high');
                if ($mp3_path && file_exists($mp3_path)) {
                    $upload_dir = wp_upload_dir();
                    $mp3_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $mp3_path);
                } else {
                    $mp3_url = $song->get_file_url(); // Fallback
                }
            } else {
                $mp3_url = $song->get_file_url(); // Fallback
            }
        } else {
            $mp3_url = $song_data['file_url'] ?? null;
        }
        
        if ($mp3_url) {
            $song_data['mp3_url'] = $mp3_url;
            $song_data['url'] = $mp3_url;
        }
        
        // Get artist name from group
        $artist_name = '';
        if ($song->group_id && class_exists('Simuze_Group')) {
            $group = Simuze_Group::get($song->group_id);
            if ($group) {
                $artist_name = $group->name;
            }
        }
        
        // Add compatibility fields
        $song_data['song_id'] = $song_data['id'];
        $song_data['artist'] = $artist_name;
        $song_data['artist_name'] = $artist_name;
        $song_data['name'] = $song_data['title'];
        $song_data['cover'] = $song_data['artwork_url'] ?? null;
        $song_data['artwork'] = $song_data['artwork_url'] ?? null;
        
        return rest_ensure_response($song_data);
    }
    
    /**
     * Get albums list
     */
    public function get_albums($request) {
        $args = array(
            'limit' => $request->get_param('limit') ?: 20,
            'offset' => $request->get_param('offset') ?: 0,
            'group_id' => $request->get_param('group_id'),
        );
        
        if ($args['group_id']) {
            $albums = Simuze_Album::get_by_group($args['group_id'], $args);
        } else {
            global $wpdb;
            $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'albums';
            
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE deleted_at IS NULL AND is_active = 1 ORDER BY release_date DESC LIMIT %d OFFSET %d",
                $args['limit'],
                $args['offset']
            ), ARRAY_A);
            
            $albums = array_map(function($data) {
                return new Simuze_Album($data);
            }, $results);
        }
        
        return rest_ensure_response(array_map(function($album) {
            return $album->to_array();
        }, $albums));
    }
    
    /**
     * Get single album
     */
    public function get_album($request) {
        $album = Simuze_Album::get($request->get_param('id'));
        
        if (!$album) {
            return new WP_Error('not_found', 'Album not found', array('status' => 404));
        }
        
        return rest_ensure_response($album->to_array());
    }
    
    /**
     * Get cart
     */
    public function get_cart($request) {
        $cart = Simuze_Cart::get_instance();
        
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        $buyer_id = $user ? $user->id : null;
        
        return rest_ensure_response($cart->to_array($buyer_id));
    }
    
    /**
     * Add item to cart
     */
    public function add_to_cart($request) {
        $cart = Simuze_Cart::get_instance();
        
        $type = $request->get_param('type');
        $id = $request->get_param('id');
        
        if (!in_array($type, array('song', 'album'))) {
            return new WP_Error('invalid_type', 'Invalid product type', array('status' => 400));
        }
        
        $result = $cart->add_item($type, $id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        $buyer_id = $user ? $user->id : null;
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Item added to cart',
            'cart' => $cart->to_array($buyer_id),
        ));
    }
    
    /**
     * Remove item from cart
     */
    public function remove_from_cart($request) {
        $cart = Simuze_Cart::get_instance();
        
        $type = $request->get_param('type');
        $id = $request->get_param('id');
        
        $cart->remove_item($type, $id);
        
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        $buyer_id = $user ? $user->id : null;
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Item removed from cart',
            'cart' => $cart->to_array($buyer_id),
        ));
    }
    
    /**
     * Clear cart
     */
    public function clear_cart($request) {
        $cart = Simuze_Cart::get_instance();
        $cart->clear();
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Cart cleared',
            'cart' => $cart->to_array(),
        ));
    }
    
    /**
     * Apply discount code
     */
    public function apply_discount($request) {
        $cart = Simuze_Cart::get_instance();
        $code = $request->get_param('code');
        
        if (empty($code)) {
            return new WP_Error('missing_code', 'Discount code is required', array('status' => 400));
        }
        
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        $buyer_id = $user ? $user->id : null;
        
        $result = $cart->apply_discount_code($code, $buyer_id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response($result);
    }
    
    /**
     * Get user orders
     */
    public function get_orders($request) {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        
        $orders = Simuze_Order::get_by_buyer($user->id, array(
            'limit' => $request->get_param('limit') ?: 20,
            'offset' => $request->get_param('offset') ?: 0,
        ));
        
        return rest_ensure_response(array_map(function($order) {
            return $order->to_array();
        }, $orders));
    }
    
    /**
     * Create order from cart
     */
    public function create_order($request) {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        
        $cart = Simuze_Cart::get_instance();
        
        if ($cart->is_empty()) {
            return new WP_Error('empty_cart', 'Cart is empty', array('status' => 400));
        }
        
        $options = array(
            'payment_method' => $request->get_param('payment_method') ?: 'paypal',
            'discount_code' => $request->get_param('discount_code'),
            'currency' => $request->get_param('currency') ?: 'EUR',
        );
        
        $order = Simuze_Order::create_from_cart($user->id, $cart->get_items(), $options);
        
        if (is_wp_error($order)) {
            return $order;
        }
        
        // Clear cart after order creation
        $cart->clear();
        
        return rest_ensure_response(array(
            'success' => true,
            'order' => $order->to_array(),
        ));
    }
    
    /**
     * Get single order
     */
    public function get_order($request) {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        
        $order = Simuze_Order::get($request->get_param('id'));
        
        if (!$order || $order->buyer_id != $user->id) {
            return new WP_Error('not_found', 'Order not found', array('status' => 404));
        }
        
        return rest_ensure_response($order->to_array());
    }
    
    /**
     * Create payment for order
     */
    public function create_payment($request) {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        
        $order_id = $request->get_param('order_id');
        $payment_method = $request->get_param('payment_method') ?: 'paypal';
        
        $order = Simuze_Order::get($order_id);
        
        if (!$order || $order->buyer_id != $user->id) {
            return new WP_Error('not_found', 'Order not found', array('status' => 404));
        }
        
        if ($order->payment_status === 'paid') {
            return new WP_Error('already_paid', 'Order is already paid', array('status' => 400));
        }
        
        $payments = Simuze_Simezu_Payments::get_instance();
        $result = $payments->create_payment($order, $payment_method);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response($result);
    }
    
    /**
     * Payment webhook handler
     */
    public function payment_webhook($request) {
        $payments = Simuze_Simezu_Payments::get_instance();
        
        $payload = $request->get_body();
        $signature = $request->get_header('X-Simezu-Signature');
        
        $result = $payments->process_webhook($payload, $signature);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response($result);
    }
    
    /**
     * Get available payment methods
     */
    public function get_payment_methods($request) {
        $payments = Simuze_Simezu_Payments::get_instance();
        return rest_ensure_response($payments->get_payment_methods());
    }
    
    /**
     * Get user downloads
     */
    public function get_downloads($request) {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        
        $tokens = Simuze_Download_Token::get_by_buyer($user->id, array(
            'limit' => $request->get_param('limit') ?: 50,
            'valid_only' => $request->get_param('valid_only') !== 'false',
        ));
        
        return rest_ensure_response(array_map(function($token) {
            return $token->to_array();
        }, $tokens));
    }
    
    /**
     * Get seller earnings
     */
    public function get_seller_earnings($request) {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        
        $group_id = $request->get_param('group_id');
        
        // Check if user is admin of the group
        if ($group_id) {
            $group = Simuze_Group::get($group_id);
            if (!$group || !$group->is_admin($user->id)) {
                return new WP_Error('forbidden', 'You do not have permission to view this', array('status' => 403));
            }
            $seller_type = 'group';
            $seller_id = $group_id;
        } else {
            $seller_type = 'user';
            $seller_id = $user->id;
        }
        
        // Get local earnings data
        global $wpdb;
        $accounts_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts';
        
        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $accounts_table WHERE seller_type = %s AND seller_id = %d",
            $seller_type,
            $seller_id
        ), ARRAY_A);
        
        if (!$account) {
            $account = array(
                'total_earnings' => 0,
                'pending_payout' => 0,
                'total_paid_out' => 0,
                'is_verified' => 0,
            );
        }
        
        // Get recent transactions
        $transactions_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'transactions';
        $transactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $transactions_table 
             WHERE seller_type = %s AND seller_id = %d 
             ORDER BY created_at DESC LIMIT 20",
            $seller_type,
            $seller_id
        ), ARRAY_A);
        
        return rest_ensure_response(array(
            'total_earnings' => floatval($account['total_earnings']),
            'pending_payout' => floatval($account['pending_payout']),
            'total_paid_out' => floatval($account['total_paid_out']),
            'is_verified' => (bool) $account['is_verified'],
            'recent_transactions' => $transactions,
        ));
    }
    
    /**
     * Get seller sales
     */
    public function get_seller_sales($request) {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        
        $group_id = $request->get_param('group_id');
        
        if ($group_id) {
            $group = Simuze_Group::get($group_id);
            if (!$group || !$group->is_admin($user->id)) {
                return new WP_Error('forbidden', 'You do not have permission to view this', array('status' => 403));
            }
            $seller_type = 'group';
            $seller_id = $group_id;
        } else {
            $seller_type = 'user';
            $seller_id = $user->id;
        }
        
        $orders = Simuze_Order::get_by_seller($seller_type, $seller_id, array(
            'limit' => $request->get_param('limit') ?: 20,
            'offset' => $request->get_param('offset') ?: 0,
        ));
        
        return rest_ensure_response(array_map(function($order) {
            return $order->to_array();
        }, $orders));
    }
    
    /**
     * Register seller payment account
     */
    public function register_seller_account($request) {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
        
        $group_id = $request->get_param('group_id');
        $provider = $request->get_param('provider') ?: 'paypal';
        $account_data = $request->get_param('account_data');
        
        if ($group_id) {
            $group = Simuze_Group::get($group_id);
            if (!$group || !$group->is_admin($user->id)) {
                return new WP_Error('forbidden', 'You do not have permission', array('status' => 403));
            }
            $seller_type = 'group';
            $seller_id = $group_id;
        } else {
            $seller_type = 'user';
            $seller_id = $user->id;
        }
        
        $payments = Simuze_Simezu_Payments::get_instance();
        $result = $payments->register_seller_account($seller_type, $seller_id, $provider, $account_data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Payment account registered successfully',
            'account' => $result,
        ));
    }
    
    /**
     * Get discounts (admin)
     */
    public function get_discounts($request) {
        $discounts = Simuze_Discount::get_all(array(
            'limit' => $request->get_param('limit') ?: 50,
            'offset' => $request->get_param('offset') ?: 0,
        ));
        
        return rest_ensure_response(array_map(function($discount) {
            return $discount->to_array();
        }, $discounts));
    }
    
    /**
     * Get single discount
     */
    public function get_discount($request) {
        $discount = Simuze_Discount::get($request->get_param('id'));
        
        if (!$discount) {
            return new WP_Error('not_found', 'Discount not found', array('status' => 404));
        }
        
        return rest_ensure_response($discount->to_array());
    }
    
    /**
     * Create discount
     */
    public function create_discount($request) {
        $data = array(
            'name' => $request->get_param('name'),
            'code' => $request->get_param('code'),
            'target_type' => $request->get_param('target_type') ?: 'global',
            'target_id' => $request->get_param('target_id'),
            'product_type' => $request->get_param('product_type') ?: 'all',
            'discount_type' => $request->get_param('discount_type') ?: 'percentage',
            'discount_value' => $request->get_param('discount_value'),
            'min_purchase' => $request->get_param('min_purchase'),
            'max_uses' => $request->get_param('max_uses'),
            'active_from' => $request->get_param('active_from'),
            'active_until' => $request->get_param('active_until'),
            'is_active' => $request->get_param('is_active') !== false,
        );
        
        $discount = Simuze_Discount::create($data);
        
        if (!$discount) {
            return new WP_Error('creation_failed', 'Failed to create discount', array('status' => 500));
        }
        
        return rest_ensure_response($discount->to_array());
    }
    
    /**
     * Update discount
     */
    public function update_discount($request) {
        $discount = Simuze_Discount::get($request->get_param('id'));
        
        if (!$discount) {
            return new WP_Error('not_found', 'Discount not found', array('status' => 404));
        }
        
        $data = array_filter(array(
            'name' => $request->get_param('name'),
            'code' => $request->get_param('code'),
            'target_type' => $request->get_param('target_type'),
            'target_id' => $request->get_param('target_id'),
            'product_type' => $request->get_param('product_type'),
            'discount_type' => $request->get_param('discount_type'),
            'discount_value' => $request->get_param('discount_value'),
            'min_purchase' => $request->get_param('min_purchase'),
            'max_uses' => $request->get_param('max_uses'),
            'active_from' => $request->get_param('active_from'),
            'active_until' => $request->get_param('active_until'),
            'is_active' => $request->get_param('is_active'),
        ), function($v) { return $v !== null; });
        
        $discount->update($data);
        
        return rest_ensure_response($discount->to_array());
    }
    
    /**
     * Delete discount
     */
    public function delete_discount($request) {
        $discount = Simuze_Discount::get($request->get_param('id'));
        
        if (!$discount) {
            return new WP_Error('not_found', 'Discount not found', array('status' => 404));
        }
        
        $discount->delete();
        
        return rest_ensure_response(array('success' => true, 'message' => 'Discount deleted'));
    }
}











