<?php
/**
 * Seller Account class for managing payment accounts
 * Supports modular payment providers (PayPal, Stripe)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Seller_Account {
    
    public $id;
    public $seller_type;
    public $seller_id;
    public $simezu_account_id;
    public $payment_provider;
    public $provider_account_id;
    public $provider_email;
    public $is_verified;
    public $payout_threshold;
    public $total_earnings;
    public $pending_payout;
    public $total_paid_out;
    public $last_payout_at;
    public $created_at;
    public $updated_at;
    
    public function __construct($data = array()) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Get seller account by ID
     */
    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Get seller account by seller
     */
    public static function get_by_seller($seller_type, $seller_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE seller_type = %s AND seller_id = %d",
            $seller_type,
            $seller_id
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Create or update seller account
     */
    public static function create_or_update($data) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts';
        
        $existing = self::get_by_seller($data['seller_type'], $data['seller_id']);
        
        if ($existing) {
            $wpdb->update($table, $data, array('id' => $existing->id));
            return self::get($existing->id);
        } else {
            $defaults = array(
                'payment_provider' => 'paypal',
                'is_verified' => 0,
                'payout_threshold' => 25.00,
                'total_earnings' => 0,
                'pending_payout' => 0,
                'total_paid_out' => 0,
            );
            
            $data = array_merge($defaults, $data);
            $wpdb->insert($table, $data);
            return self::get($wpdb->insert_id);
        }
    }
    
    /**
     * Update account
     */
    public function update($data) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts';
        
        $result = $wpdb->update($table, $data, array('id' => $this->id));
        
        if ($result !== false) {
            $updated = self::get($this->id);
            if ($updated) {
                foreach (get_object_vars($updated) as $key => $value) {
                    $this->$key = $value;
                }
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Add earnings
     */
    public function add_earnings($amount) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET 
             total_earnings = total_earnings + %f,
             pending_payout = pending_payout + %f
             WHERE id = %d",
            $amount,
            $amount,
            $this->id
        ));
        
        $this->total_earnings += $amount;
        $this->pending_payout += $amount;
    }
    
    /**
     * Record payout
     */
    public function record_payout($amount) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET 
             pending_payout = pending_payout - %f,
             total_paid_out = total_paid_out + %f,
             last_payout_at = %s
             WHERE id = %d",
            $amount,
            $amount,
            current_time('mysql'),
            $this->id
        ));
        
        $this->pending_payout -= $amount;
        $this->total_paid_out += $amount;
        $this->last_payout_at = current_time('mysql');
    }
    
    /**
     * Check if payout threshold is reached
     */
    public function can_request_payout() {
        return $this->pending_payout >= $this->payout_threshold && $this->is_verified;
    }
    
    /**
     * Get seller (user or group)
     */
    public function get_seller() {
        if ($this->seller_type === 'user') {
            return Simuze_User::get($this->seller_id);
        } elseif ($this->seller_type === 'group') {
            return Simuze_Group::get($this->seller_id);
        }
        return null;
    }
    
    /**
     * Get transactions for this seller
     */
    public function get_transactions($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'transactions';
        
        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'type' => null,
            'status' => null,
        );
        
        $args = array_merge($defaults, $args);
        
        $where = "seller_type = %s AND seller_id = %d";
        $params = array($this->seller_type, $this->seller_id);
        
        if ($args['type']) {
            $where .= " AND transaction_type = %s";
            $params[] = $args['type'];
        }
        
        if ($args['status']) {
            $where .= " AND status = %s";
            $params[] = $args['status'];
        }
        
        $transactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, array($args['limit'], $args['offset']))
        ), ARRAY_A);
        
        return $transactions;
    }
    
    /**
     * Get earnings summary
     */
    public function get_earnings_summary($period = 'all') {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'transactions';
        
        $where = "seller_type = %s AND seller_id = %d AND transaction_type = 'sale' AND status = 'completed'";
        $params = array($this->seller_type, $this->seller_id);
        
        switch ($period) {
            case 'today':
                $where .= " AND DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $where .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $where .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'year':
                $where .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }
        
        $summary = $wpdb->get_row($wpdb->prepare(
            "SELECT 
             COUNT(*) as transaction_count,
             COALESCE(SUM(gross_amount), 0) as gross_total,
             COALESCE(SUM(simuze_fee), 0) as fees_total,
             COALESCE(SUM(net_amount), 0) as net_total
             FROM $table WHERE $where",
            $params
        ), ARRAY_A);
        
        return $summary;
    }
    
    /**
     * Convert to array
     */
    public function to_array() {
        $seller = $this->get_seller();
        
        return array(
            'id' => $this->id,
            'seller_type' => $this->seller_type,
            'seller_id' => $this->seller_id,
            'seller_name' => $seller ? ($seller->name ?? $seller->display_name) : null,
            'simezu_account_id' => $this->simezu_account_id,
            'payment_provider' => $this->payment_provider,
            'provider_email' => $this->provider_email,
            'is_verified' => (bool) $this->is_verified,
            'payout_threshold' => floatval($this->payout_threshold),
            'total_earnings' => floatval($this->total_earnings),
            'pending_payout' => floatval($this->pending_payout),
            'total_paid_out' => floatval($this->total_paid_out),
            'can_request_payout' => $this->can_request_payout(),
            'last_payout_at' => $this->last_payout_at,
            'created_at' => $this->created_at,
        );
    }
}

/**
 * Payment Provider Interface
 * Base class for payment provider implementations
 */
abstract class Simuze_Payment_Provider {
    
    protected $id;
    protected $name;
    protected $config;
    
    public function __construct($config = array()) {
        $this->config = $config;
    }
    
    /**
     * Get provider ID
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Get provider name
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Check if provider is configured
     */
    abstract public function is_configured();
    
    /**
     * Create payment session
     */
    abstract public function create_payment($order, $return_url, $cancel_url);
    
    /**
     * Process payment callback
     */
    abstract public function process_callback($data);
    
    /**
     * Process refund
     */
    abstract public function process_refund($transaction_id, $amount = null);
    
    /**
     * Process payout to seller
     */
    abstract public function process_payout($seller_account, $amount);
}

/**
 * PayPal Payment Provider
 */
class Simuze_PayPal_Provider extends Simuze_Payment_Provider {
    
    protected $id = 'paypal';
    protected $name = 'PayPal';
    
    private $client_id;
    private $client_secret;
    private $sandbox;
    private $api_url;
    
    public function __construct($config = array()) {
        parent::__construct($config);
        
        $this->client_id = $config['client_id'] ?? get_option('simuze_paypal_client_id', '');
        $this->client_secret = $config['client_secret'] ?? get_option('simuze_paypal_client_secret', '');
        $this->sandbox = $config['sandbox'] ?? get_option('simuze_paypal_sandbox', true);
        
        $this->api_url = $this->sandbox 
            ? 'https://api-m.sandbox.paypal.com' 
            : 'https://api-m.paypal.com';
    }
    
    /**
     * Check if configured
     */
    public function is_configured() {
        return !empty($this->client_id) && !empty($this->client_secret);
    }
    
    /**
     * Get access token
     */
    private function get_access_token() {
        $cache_key = 'simuze_paypal_token_' . md5($this->client_id);
        $token = get_transient($cache_key);
        
        if ($token) {
            return $token;
        }
        
        $response = wp_remote_post($this->api_url . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => 'grant_type=client_credentials',
        ));
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($data['access_token'])) {
            set_transient($cache_key, $data['access_token'], $data['expires_in'] - 60);
            return $data['access_token'];
        }
        
        return null;
    }
    
    /**
     * Create payment
     */
    public function create_payment($order, $return_url, $cancel_url) {
        $token = $this->get_access_token();
        
        if (!$token) {
            return new WP_Error('auth_failed', 'Failed to authenticate with PayPal');
        }
        
        $items = $order->get_items();
        $line_items = array();
        
        foreach ($items as $item) {
            $line_items[] = array(
                'name' => $item['product_title'],
                'quantity' => '1',
                'unit_amount' => array(
                    'currency_code' => $order->currency,
                    'value' => number_format($item['total_price'], 2, '.', ''),
                ),
            );
        }
        
        $payload = array(
            'intent' => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'reference_id' => $order->order_number,
                    'amount' => array(
                        'currency_code' => $order->currency,
                        'value' => number_format($order->total_price, 2, '.', ''),
                        'breakdown' => array(
                            'item_total' => array(
                                'currency_code' => $order->currency,
                                'value' => number_format($order->subtotal - $order->discount_amount, 2, '.', ''),
                            ),
                        ),
                    ),
                    'items' => $line_items,
                ),
            ),
            'application_context' => array(
                'brand_name' => 'Simuze',
                'return_url' => $return_url,
                'cancel_url' => $cancel_url,
                'user_action' => 'PAY_NOW',
            ),
        );
        
        $response = wp_remote_post($this->api_url . '/v2/checkout/orders', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($payload),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($data['id'])) {
            // Find approval URL
            $approval_url = null;
            foreach ($data['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approval_url = $link['href'];
                    break;
                }
            }
            
            return array(
                'success' => true,
                'payment_id' => $data['id'],
                'approval_url' => $approval_url,
            );
        }
        
        return new WP_Error('payment_failed', $data['message'] ?? 'Failed to create PayPal order');
    }
    
    /**
     * Process callback (capture payment)
     */
    public function process_callback($data) {
        $token = $this->get_access_token();
        
        if (!$token) {
            return new WP_Error('auth_failed', 'Failed to authenticate with PayPal');
        }
        
        $payment_id = $data['token'] ?? $data['payment_id'];
        
        $response = wp_remote_post($this->api_url . '/v2/checkout/orders/' . $payment_id . '/capture', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $result = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($result['status'] === 'COMPLETED') {
            return array(
                'success' => true,
                'status' => 'paid',
                'transaction_id' => $result['purchase_units'][0]['payments']['captures'][0]['id'],
            );
        }
        
        return new WP_Error('capture_failed', 'Failed to capture PayPal payment');
    }
    
    /**
     * Process refund
     */
    public function process_refund($transaction_id, $amount = null) {
        $token = $this->get_access_token();
        
        if (!$token) {
            return new WP_Error('auth_failed', 'Failed to authenticate with PayPal');
        }
        
        $payload = array();
        if ($amount !== null) {
            $payload['amount'] = array(
                'currency_code' => 'EUR',
                'value' => number_format($amount, 2, '.', ''),
            );
        }
        
        $response = wp_remote_post($this->api_url . '/v2/payments/captures/' . $transaction_id . '/refund', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($payload),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $result = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($result['status'] === 'COMPLETED') {
            return array(
                'success' => true,
                'refund_id' => $result['id'],
            );
        }
        
        return new WP_Error('refund_failed', 'Failed to process refund');
    }
    
    /**
     * Process payout (PayPal Payouts API)
     */
    public function process_payout($seller_account, $amount) {
        $token = $this->get_access_token();
        
        if (!$token) {
            return new WP_Error('auth_failed', 'Failed to authenticate with PayPal');
        }
        
        if (empty($seller_account->provider_email)) {
            return new WP_Error('no_email', 'Seller has no PayPal email configured');
        }
        
        $batch_id = 'SIMUZE_' . $seller_account->id . '_' . time();
        
        $payload = array(
            'sender_batch_header' => array(
                'sender_batch_id' => $batch_id,
                'email_subject' => 'Your Simuze earnings payout',
                'email_message' => 'You have received a payout from your Simuze sales.',
            ),
            'items' => array(
                array(
                    'recipient_type' => 'EMAIL',
                    'amount' => array(
                        'currency' => 'EUR',
                        'value' => number_format($amount, 2, '.', ''),
                    ),
                    'receiver' => $seller_account->provider_email,
                    'note' => 'Simuze earnings payout',
                    'sender_item_id' => 'payout_' . $seller_account->id . '_' . time(),
                ),
            ),
        );
        
        $response = wp_remote_post($this->api_url . '/v1/payments/payouts', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($payload),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $result = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($result['batch_header']['payout_batch_id'])) {
            return array(
                'success' => true,
                'batch_id' => $result['batch_header']['payout_batch_id'],
                'status' => $result['batch_header']['batch_status'],
            );
        }
        
        return new WP_Error('payout_failed', 'Failed to create payout');
    }
}

/**
 * Stripe Payment Provider (Placeholder for future implementation)
 */
class Simuze_Stripe_Provider extends Simuze_Payment_Provider {
    
    protected $id = 'stripe';
    protected $name = 'Stripe';
    
    private $secret_key;
    private $publishable_key;
    
    public function __construct($config = array()) {
        parent::__construct($config);
        
        $this->secret_key = $config['secret_key'] ?? get_option('simuze_stripe_secret_key', '');
        $this->publishable_key = $config['publishable_key'] ?? get_option('simuze_stripe_publishable_key', '');
    }
    
    public function is_configured() {
        return !empty($this->secret_key) && !empty($this->publishable_key);
    }
    
    public function create_payment($order, $return_url, $cancel_url) {
        // TODO: Implement Stripe Checkout Session
        return new WP_Error('not_implemented', 'Stripe integration coming soon');
    }
    
    public function process_callback($data) {
        // TODO: Implement Stripe webhook handling
        return new WP_Error('not_implemented', 'Stripe integration coming soon');
    }
    
    public function process_refund($transaction_id, $amount = null) {
        // TODO: Implement Stripe refunds
        return new WP_Error('not_implemented', 'Stripe integration coming soon');
    }
    
    public function process_payout($seller_account, $amount) {
        // TODO: Implement Stripe Connect payouts
        return new WP_Error('not_implemented', 'Stripe integration coming soon');
    }
}

/**
 * Payment Provider Factory
 */
class Simuze_Payment_Provider_Factory {
    
    private static $providers = array();
    
    /**
     * Get payment provider instance
     */
    public static function get_provider($provider_id) {
        if (!isset(self::$providers[$provider_id])) {
            switch ($provider_id) {
                case 'paypal':
                    self::$providers[$provider_id] = new Simuze_PayPal_Provider();
                    break;
                case 'stripe':
                    self::$providers[$provider_id] = new Simuze_Stripe_Provider();
                    break;
                default:
                    return null;
            }
        }
        
        return self::$providers[$provider_id];
    }
    
    /**
     * Get all available providers
     */
    public static function get_available_providers() {
        $providers = array();
        
        $paypal = self::get_provider('paypal');
        if ($paypal && $paypal->is_configured()) {
            $providers['paypal'] = array(
                'id' => 'paypal',
                'name' => 'PayPal',
                'configured' => true,
            );
        }
        
        $stripe = self::get_provider('stripe');
        if ($stripe && $stripe->is_configured()) {
            $providers['stripe'] = array(
                'id' => 'stripe',
                'name' => 'Stripe',
                'configured' => true,
            );
        }
        
        return $providers;
    }
}











