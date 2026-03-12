<?php
/**
 * SIMEZU Payments Connector
 * Handles all payment operations via SIMEZU API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Simezu_Payments {
    
    private static $instance = null;
    
    private $api_url;
    private $api_key;
    private $app_id;
    private $webhook_secret;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->api_url = get_option('simezu_api_url', 'http://localhost/simezu/api');
        $this->api_key = get_option('simuze_simezu_api_key', '');
        $this->app_id = get_option('simuze_simezu_app_id', 'simuze');
        $this->webhook_secret = get_option('simuze_simezu_webhook_secret', '');
    }
    
    /**
     * Create payment session for order
     */
    public function create_payment($order, $payment_method = 'paypal') {
        $items = $order->get_items();
        
        $line_items = array();
        foreach ($items as $item) {
            $line_item = array(
                'order_item_id' => $item['id'],
                'seller_type' => $item['seller_type'],
                'seller_id' => $item['seller_id'],
                'product_type' => $item['product_type'],
                'product_id' => $item['product_id'],
                'title' => $item['product_title'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price'],
                'simuze_fee' => $item['simuze_fee'],
                'seller_earnings' => $item['seller_earnings'],
            );
            
            // If seller is a group, add payment recipient information
            if ($item['seller_type'] === 'group' && !empty($item['seller_id'])) {
                $group = Simuze_Group::get($item['seller_id']);
                if ($group && !empty($group->settings)) {
                    $settings = is_array($group->settings) ? $group->settings : json_decode($group->settings, true);
                    if (!empty($settings['payment_recipient_user_id'])) {
                        $recipient_user = Simuze_User::get($settings['payment_recipient_user_id']);
                        if ($recipient_user) {
                            $line_item['payment_recipient_user_id'] = $settings['payment_recipient_user_id'];
                            $line_item['payment_recipient_simezu_id'] = $recipient_user->simezu_id ?? '';
                            $line_item['payment_recipient_email'] = $recipient_user->user_email ?? '';
                        }
                    }
                }
            }
            
            $line_items[] = $line_item;
        }
        
        // Build line_items in new SIMEZU API format
        $simezu_line_items = array();
        foreach ($line_items as $item) {
            $simezu_line_items[] = array(
                'product_id' => $item['product_id'] ?? $item['order_item_id'] ?? '',
                'name' => $item['title'] ?? $item['product_title'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['unit_price'] ?? $item['total_price'] ?? 0,
                'seller_id' => $item['seller_id'] ?? '',
            );
        }
        
        $payload = array(
            'order_id' => $order->order_number, // Use order_number as order_id for SIMEZU
            'buyer_id' => $order->buyer_id,
            'line_items' => $simezu_line_items,
            'total' => $order->total_price,
            'return_url' => home_url('/checkout/success/?order=' . $order->order_number),
            'cancel_url' => home_url('/checkout/cancel/?order=' . $order->order_number),
        );
        
        $response = $this->api_request('POST', '/payments/create', $payload);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Check for successful payment response (new SIMEZU API format)
        if (!empty($response['payment_session_id']) || !empty($response['checkout_url']) || !empty($response['redirect_url'])) {
            // Update order with payment session
            $session_id = $response['payment_session_id'] ?? $response['checkout_url'] ?? null;
            if ($session_id) {
                $order->update(array(
                    'payment_session_id' => $session_id,
                    'payment_method' => $payment_method,
                    'payment_status' => 'processing',
                ));
            }
            
            return array(
                'success' => true,
                'payment_session_id' => $response['payment_session_id'] ?? null,
                'checkout_url' => $response['checkout_url'] ?? null,
                'redirect_url' => $response['redirect_url'] ?? $response['checkout_url'] ?? null,
                'paypal_redirect_url' => $response['paypal_redirect_url'] ?? null,
            );
        }
        
        return new WP_Error('payment_failed', $response['error'] ?? $response['message'] ?? 'Failed to create payment session');
    }
    
    /**
     * Get payment status
     */
    public function get_payment_status($session_id) {
        $response = $this->api_request('GET', '/payments/status/' . $session_id);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response;
    }
    
    /**
     * Process webhook callback from SIMEZU
     */
    public function process_webhook($payload, $signature) {
        // Verify signature
        if (!$this->verify_webhook_signature($payload, $signature)) {
            return new WP_Error('invalid_signature', 'Invalid webhook signature');
        }
        
        $data = json_decode($payload, true);
        
        if (empty($data['order_id']) || empty($data['status'])) {
            return new WP_Error('invalid_payload', 'Invalid webhook payload');
        }
        
        $order = Simuze_Order::get($data['order_id']);
        if (!$order) {
            return new WP_Error('order_not_found', 'Order not found');
        }
        
        switch ($data['status']) {
            case 'paid':
            case 'completed':
                $order->mark_as_paid($data['transaction_id'] ?? null);
                
                // Create transactions for each item
                if (!empty($data['transaction_ids'])) {
                    $this->create_local_transactions($order, $data['transaction_ids']);
                }
                
                // Send confirmation email
                $this->send_order_confirmation($order);
                
                break;
                
            case 'failed':
                $order->mark_as_failed($data['error'] ?? 'Payment failed');
                break;
                
            case 'refunded':
                $order->update(array('payment_status' => 'refunded'));
                break;
        }
        
        return array('success' => true);
    }
    
    /**
     * Create local transaction records
     */
    private function create_local_transactions($order, $transaction_ids) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'transactions';
        $items = $order->get_items();
        
        foreach ($items as $index => $item) {
            $simezu_tx_id = $transaction_ids[$index] ?? ('SIM-' . $order->id . '-' . $item['id']);
            
            $wpdb->insert($table, array(
                'simezu_transaction_id' => $simezu_tx_id,
                'order_id' => $order->id,
                'order_item_id' => $item['id'],
                'seller_type' => $item['seller_type'],
                'seller_id' => $item['seller_id'],
                'transaction_type' => 'sale',
                'gross_amount' => $item['total_price'],
                'simuze_fee' => $item['simuze_fee'],
                'payment_provider_fee' => 0, // Will be updated from SIMEZU
                'net_amount' => $item['seller_earnings'],
                'currency' => $order->currency,
                'status' => 'completed',
                'payout_status' => 'pending',
            ));
        }
    }
    
    /**
     * Request refund
     */
    public function request_refund($order, $reason = null, $items = null) {
        $payload = array(
            'app_id' => $this->app_id,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'amount' => $items ? null : $order->total_price, // Full refund if no items specified
            'items' => $items,
            'reason' => $reason,
        );
        
        $response = $this->api_request('POST', '/payments/refund', $payload);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        if (!empty($response['success'])) {
            $order->update(array('payment_status' => 'refunded'));
            return array('success' => true, 'refund_id' => $response['refund_id'] ?? null);
        }
        
        return new WP_Error('refund_failed', $response['error'] ?? 'Refund failed');
    }
    
    /**
     * Get seller earnings from SIMEZU
     */
    public function get_seller_earnings($seller_type, $seller_id, $args = array()) {
        $params = array_merge(array(
            'app_id' => $this->app_id,
            'seller_type' => $seller_type,
            'seller_id' => $seller_id,
        ), $args);
        
        $response = $this->api_request('GET', '/finance/earnings', $params);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response;
    }
    
    /**
     * Get payout status for seller
     */
    public function get_payout_status($seller_type, $seller_id) {
        $params = array(
            'app_id' => $this->app_id,
            'seller_type' => $seller_type,
            'seller_id' => $seller_id,
        );
        
        $response = $this->api_request('GET', '/finance/payouts', $params);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response;
    }
    
    /**
     * Request payout for seller
     */
    public function request_payout($seller_type, $seller_id, $amount = null) {
        $payload = array(
            'app_id' => $this->app_id,
            'seller_type' => $seller_type,
            'seller_id' => $seller_id,
            'amount' => $amount, // null = all available
        );
        
        $response = $this->api_request('POST', '/finance/payouts/request', $payload);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response;
    }
    
    /**
     * Register seller payment account
     */
    public function register_seller_account($seller_type, $seller_id, $provider, $account_data) {
        $payload = array(
            'app_id' => $this->app_id,
            'seller_type' => $seller_type,
            'seller_id' => $seller_id,
            'provider' => $provider,
            'account_data' => $account_data,
        );
        
        $response = $this->api_request('POST', '/sellers/register', $payload);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Update local seller account
        if (!empty($response['simezu_account_id'])) {
            global $wpdb;
            $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts';
            
            $wpdb->replace($table, array(
                'seller_type' => $seller_type,
                'seller_id' => $seller_id,
                'simezu_account_id' => $response['simezu_account_id'],
                'payment_provider' => $provider,
                'provider_account_id' => $account_data['account_id'] ?? null,
                'provider_email' => $account_data['email'] ?? null,
                'is_verified' => $response['is_verified'] ?? 0,
            ));
        }
        
        return $response;
    }
    
    /**
     * Make API request to SIMEZU
     */
    private function api_request($method, $endpoint, $data = array()) {
        $url = rtrim($this->api_url, '/') . $endpoint;
        
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key,
                'X-App-ID' => $this->app_id,
            ),
        );
        
        if ($method === 'GET' && !empty($data)) {
            $url = add_query_arg($data, $url);
        } elseif (!empty($data)) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            error_log('SIMEZU API Error: ' . $response->get_error_message());
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code >= 400) {
            return new WP_Error(
                'api_error',
                $data['error'] ?? 'API request failed',
                array('status' => $status_code)
            );
        }
        
        return $data;
    }
    
    /**
     * Verify webhook signature
     */
    private function verify_webhook_signature($payload, $signature) {
        if (empty($this->webhook_secret)) {
            return true; // Skip verification if no secret configured
        }
        
        $expected = hash_hmac('sha256', $payload, $this->webhook_secret);
        return hash_equals($expected, $signature);
    }
    
    /**
     * Send order confirmation email
     */
    private function send_order_confirmation($order) {
        $buyer = $order->get_buyer();
        if (!$buyer) {
            return;
        }
        
        $items = $order->get_items();
        $tokens = array();
        
        foreach ($items as $item) {
            $token = Simuze_Download_Token::get_by_order_item($item['id']);
            if ($token) {
                $tokens[] = $token;
            }
        }
        
        $subject = 'Your Simuze order #' . $order->order_number . ' is ready!';
        
        $message = "Hi " . ($buyer->display_name ?: 'there') . ",\n\n";
        $message .= "Thank you for your purchase! Your order #" . $order->order_number . " has been completed.\n\n";
        $message .= "Order Details:\n";
        $message .= "-------------\n";
        
        foreach ($items as $item) {
            $message .= "- " . $item['product_title'] . " (€" . number_format($item['total_price'], 2) . ")\n";
        }
        
        $message .= "\nTotal: €" . number_format($order->total_price, 2) . "\n\n";
        
        if (!empty($tokens)) {
            $message .= "Download Links:\n";
            $message .= "---------------\n";
            foreach ($tokens as $token) {
                $product = $token->get_product();
                if ($product) {
                    $message .= "- " . $product->title . ": " . $token->get_download_url() . "\n";
                }
            }
            $message .= "\nNote: Each download link is valid for " . $tokens[0]->max_downloads . " downloads and expires in 30 days.\n";
        }
        
        $message .= "\nYou can also access your downloads from your account:\n";
        $message .= home_url('/profile/?tab=downloads') . "\n\n";
        $message .= "Thanks for supporting independent music!\n";
        $message .= "The Simuze Team";
        
        wp_mail($buyer->email, $subject, $message);
    }
    
    /**
     * Get payment account settings for a user
     */
    public function get_payment_account($user_id, $access_token = null) {
        // The SIMEZU dashboard shows /dashboard/account/payments
        // The API endpoint is likely /api/dashboard/account/payments or /api/account/payments
        // Try multiple possible endpoints in order of likelihood
        $endpoints = array(
            '/api/account/payments',            // SIMEZU payments API endpoint (account/payments)
            '/api/dashboard/account/payments',  // Dashboard endpoint
            '/api/payments/account',            // Direct account endpoint
            '/api/auth/users/' . urlencode($user_id) . '/payments',  // User-specific endpoint
            '/api/users/' . urlencode($user_id) . '/payments',       // Alternative user endpoint
            '/api/payments/account?user_id=' . urlencode($user_id), // Query param version
        );
        
        // Build headers - prioritize Bearer token if available
        $headers = array(
            'Content-Type' => 'application/json',
            'X-API-Key' => $this->api_key,
            'X-App-ID' => $this->app_id,
        );
        
        // Add Authorization header if token provided (this is critical for user-specific data)
        if (!empty($access_token)) {
            $headers['Authorization'] = 'Bearer ' . $access_token;
            error_log('SIMEZU Payment Account: Using Bearer token authentication');
        } else {
            error_log('SIMEZU Payment Account: No access token provided, using API key only');
        }
        
        foreach ($endpoints as $endpoint) {
            $url = rtrim($this->api_url, '/') . $endpoint;
            
            error_log('SIMEZU Payment Account: Trying endpoint: ' . $url);
            
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'headers' => $headers,
                'sslverify' => false, // For local development
            ));
            
            if (is_wp_error($response)) {
                error_log('SIMEZU Payment Account API Error (' . $endpoint . '): ' . $response->get_error_message());
                continue; // Try next endpoint
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            error_log('SIMEZU Payment Account API Response (' . $endpoint . '): Status ' . $status_code . ', Body length: ' . strlen($body));
            
            // Log response structure for debugging
            if ($status_code >= 200 && $status_code < 300) {
                error_log('SIMEZU Payment Account API Success: ' . $endpoint);
                error_log('SIMEZU Payment Account Response Data: ' . print_r($data, true));
            }
            
            // If successful (200-299), return the data
            if ($status_code >= 200 && $status_code < 300) {
                // Handle empty response - return structured default
                if (empty($data) || !is_array($data)) {
                    error_log('SIMEZU Payment Account API: Empty or invalid response, trying next endpoint...');
                    continue; // Try next endpoint
                }
                
                // Normalize the response structure - SIMEZU might return data in different formats
                $normalized = $this->normalize_payment_account_response($data);
                error_log('SIMEZU Payment Account Normalized: ' . print_r($normalized, true));
                
                return $normalized;
            }
            
            // If 404, try next endpoint
            if ($status_code === 404) {
                error_log('SIMEZU Payment Account API 404: ' . $endpoint . ', trying next...');
                continue;
            }
            
            // For authentication errors, log and try next
            if ($status_code === 401 || $status_code === 403) {
                error_log('SIMEZU Payment Account API Auth Error (' . $endpoint . '): Status ' . $status_code . ', Body: ' . substr($body, 0, 200));
                continue;
            }
            
            // For other errors, log and try next
            error_log('SIMEZU Payment Account API Error (' . $endpoint . '): Status ' . $status_code . ', Body: ' . substr($body, 0, 500));
        }
        
        // If all endpoints failed, return default structure matching SIMEZU dashboard format
        error_log('SIMEZU Payment Account API: All endpoints failed, returning defaults');
        return array(
            'paypal' => array('connected' => false, 'email' => ''),
            'stripe' => array('connected' => false, 'publishable_key' => '', 'secret_key' => ''),
            'credit_card' => array('enabled' => false),
            'default_payment_method' => 'paypal',
        );
    }
    
    /**
     * Normalize payment account response from SIMEZU
     * Handles different possible response structures
     */
    private function normalize_payment_account_response($data) {
        $normalized = array(
            'paypal' => array('connected' => false, 'email' => ''),
            'stripe' => array('connected' => false, 'publishable_key' => '', 'secret_key' => ''),
            'credit_card' => array('enabled' => false),
            'default_payment_method' => 'paypal',
        );
        
        // Check if data is nested (e.g., { data: { paypal: ... } })
        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }
        
        // Handle PayPal data - SIMEZU returns: paypal: { email: '', verified: bool }
        if (isset($data['paypal'])) {
            $paypal = is_array($data['paypal']) ? $data['paypal'] : array();
            $paypal_email = $paypal['email'] ?? $paypal['email_address'] ?? '';
            $paypal_verified = $paypal['verified'] ?? false;
            $normalized['paypal'] = array(
                'connected' => !empty($paypal_email) && $paypal_verified,
                'email' => $paypal_email,
                'status' => (!empty($paypal_email) && $paypal_verified) ? 'connected' : 'not_connected',
            );
        } elseif (isset($data['paypal_email'])) {
            // Alternative structure: paypal_email at root level
            $normalized['paypal'] = array(
                'connected' => !empty($data['paypal_email']),
                'email' => $data['paypal_email'],
                'status' => !empty($data['paypal_email']) ? 'connected' : 'not_connected',
            );
        }
        
        // Handle Stripe data - SIMEZU returns: stripe: { connected: bool, account_id: '' }
        if (isset($data['stripe'])) {
            $stripe = is_array($data['stripe']) ? $data['stripe'] : array();
            $stripe_connected = $stripe['connected'] ?? false;
            $stripe_account_id = $stripe['account_id'] ?? '';
            $normalized['stripe'] = array(
                'connected' => $stripe_connected && !empty($stripe_account_id),
                'publishable_key' => $stripe['publishable_key'] ?? $stripe['publishableKey'] ?? $stripe_account_id ?? '',
                'secret_key' => '', // Never return secret key, it's encrypted
                'status' => ($stripe_connected && !empty($stripe_account_id)) ? 'connected' : 'not_connected',
            );
        } elseif (isset($data['stripe_publishable_key'])) {
            // Alternative structure: stripe keys at root level
            $normalized['stripe'] = array(
                'connected' => !empty($data['stripe_publishable_key']),
                'publishable_key' => $data['stripe_publishable_key'],
                'secret_key' => '',
                'status' => !empty($data['stripe_publishable_key']) ? 'connected' : 'not_connected',
            );
        }
        
        // Handle credit card settings
        if (isset($data['credit_card'])) {
            $cc = is_array($data['credit_card']) ? $data['credit_card'] : array();
            $normalized['credit_card'] = array(
                'enabled' => !empty($cc['enabled']) || !empty($cc['credit_card_enabled']),
            );
        } elseif (isset($data['credit_card_enabled'])) {
            $normalized['credit_card'] = array(
                'enabled' => (bool) $data['credit_card_enabled'],
            );
        }
        
        // Handle default payment method
        if (isset($data['default_payment_method'])) {
            $normalized['default_payment_method'] = $data['default_payment_method'];
        }
        
        // Set overall connection status
        $normalized['connected'] = $normalized['paypal']['connected'] || $normalized['stripe']['connected'];
        $normalized['account_status'] = $normalized['connected'] ? 'active' : 'not_connected';
        
        return $normalized;
    }
    
    /**
     * Update payment account settings for a user
     */
    public function update_payment_account($user_id, $settings, $access_token = null) {
        // SIMEZU endpoint is /api/account/payments (POST)
        $endpoints = array(
            '/api/account/payments',            // SIMEZU payments API endpoint
            '/api/dashboard/account/payments',
            '/api/auth/users/' . urlencode($user_id) . '/payments',
            '/api/payments/account/update',
            '/api/users/' . urlencode($user_id) . '/payments',
            '/dashboard/account/payments', // Direct dashboard endpoint
        );
        
        $headers = array(
            'Content-Type' => 'application/json',
            'X-API-Key' => $this->api_key,
            'X-App-ID' => $this->app_id,
        );
        
        // Add Authorization header if token provided
        if (!empty($access_token)) {
            $headers['Authorization'] = 'Bearer ' . $access_token;
        }
        
        // Build payload matching SIMEZU API format
        $payload = array();
        
        // Map Simuze format to SIMEZU format
        if (isset($settings['paypal']['email'])) {
            $payload['paypal'] = array(
                'email' => $settings['paypal']['email'],
                'verified' => true // Mark as verified when saving
            );
        }
        
        if (isset($settings['stripe'])) {
            $payload['stripe'] = array(
                'account_id' => $settings['stripe']['publishable_key'] ?? $settings['stripe']['account_id'] ?? '',
                'connected' => !empty($settings['stripe']['publishable_key']) || !empty($settings['stripe']['account_id'])
            );
        }
        
        if (isset($settings['credit_card'])) {
            $payload['credit_card'] = $settings['credit_card'];
        }
        
        if (isset($settings['default_payment_method'])) {
            $payload['default_payment_method'] = $settings['default_payment_method'];
        }
        
        foreach ($endpoints as $endpoint) {
            $url = rtrim($this->api_url, '/') . $endpoint;
            
            $response = wp_remote_post($url, array(
                'timeout' => 30,
                'headers' => $headers,
                'body' => json_encode($payload),
            ));
            
            if (is_wp_error($response)) {
                error_log('SIMEZU Payment Account Update API Error (' . $endpoint . '): ' . $response->get_error_message());
                continue;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($status_code >= 200 && $status_code < 300) {
                error_log('SIMEZU Payment Account Update API Success: ' . $endpoint);
                return $data;
            }
            
            if ($status_code === 404) {
                continue; // Try next endpoint
            }
            
            error_log('SIMEZU Payment Account Update API Error (' . $endpoint . '): Status ' . $status_code);
        }
        
        return new WP_Error('update_failed', 'Failed to update payment account settings');
    }
    
    /**
     * Get supported payment methods
     */
    public function get_payment_methods() {
        return array(
            'paypal' => array(
                'id' => 'paypal',
                'name' => 'PayPal',
                'description' => 'Pay securely with PayPal',
                'icon' => 'fab fa-paypal',
                'enabled' => true,
            ),
            'stripe' => array(
                'id' => 'stripe',
                'name' => 'Credit Card',
                'description' => 'Pay with credit or debit card',
                'icon' => 'fas fa-credit-card',
                'enabled' => (bool) get_option('simuze_stripe_enabled', false),
            ),
        );
    }
}











