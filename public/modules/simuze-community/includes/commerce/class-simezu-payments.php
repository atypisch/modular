<?php
/**
 * SIMEZU Payments Connector
 * Handles all payment operations via SIMEZU API
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
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
        $this->api_url        = $this->get_setting('simezu_api_url',             'http://localhost/simezu/api');
        $this->api_key        = $this->get_setting('simuze_simezu_api_key',      '');
        $this->app_id         = $this->get_setting('simuze_simezu_app_id',       'simuze');
        $this->webhook_secret = $this->get_setting('simuze_simezu_webhook_secret', '');
    }

    private function get_setting($name, $default = '') {
        try {
            $val = md_db_var("SELECT option_value FROM settings WHERE option_name = ? LIMIT 1", [$name]);
            return $val !== null ? $val : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public function get_payment_status($session_id) {
        return $this->api_request('GET', '/payments/status/' . $session_id);
    }

    /**
     * Process SIMEZU webhook: verify signature and dispatch payment.completed event.
     * No local orders; unlock is handled by Action_Payment_Unlock_Content via simuze_download_grants.
     */
    public function process_webhook($payload, $signature) {
        if (!$this->verify_webhook_signature($payload, $signature)) {
            return ['error' => 'invalid_signature', 'message' => 'Invalid webhook signature'];
        }

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            return ['error' => 'invalid_payload', 'message' => 'Invalid webhook payload'];
        }

        $status = $data['status'] ?? $data['payment_status'] ?? null;
        if (!in_array($status, ['paid', 'completed'], true)) {
            return ['success' => true];
        }

        $event_payload = [
            'user_id'         => $data['user_id'] ?? $data['userId'] ?? $data['buyer_id'] ?? $data['customer_id'] ?? null,
            'transaction_id'  => $data['transaction_id'] ?? $data['order_id'] ?? $data['reference'] ?? null,
            'items'           => $data['items'] ?? $data['line_items'] ?? [],
        ];

        $events_dir = dirname(__FILE__) . '/../events';
        if (!class_exists('Simuze_Event')) {
            if (is_file($events_dir . '/class-event.php')) require_once $events_dir . '/class-event.php';
        }
        if (!class_exists('Simuze_Event_Payment_Completed') && is_file($events_dir . '/events/class-event-payment-completed.php')) {
            require_once $events_dir . '/events/class-event-payment-completed.php';
        }
        if (!class_exists('Simuze_EventBus') && is_file($events_dir . '/class-event-bus.php')) {
            require_once $events_dir . '/class-event-bus.php';
        }
        if (class_exists('Simuze_EventBus') && class_exists('Simuze_Event_Payment_Completed')) {
            $event = new Simuze_Event_Payment_Completed($event_payload, 'simezu', $event_payload['transaction_id']);
            Simuze_EventBus::get_instance()->dispatch($event);
        }

        return ['success' => true];
    }

    public function get_seller_earnings($seller_type, $seller_id, $args = []) {
        $params = array_merge(['app_id' => $this->app_id, 'seller_type' => $seller_type, 'seller_id' => $seller_id], $args);
        return $this->api_request('GET', '/finance/earnings', $params);
    }

    public function get_payout_status($seller_type, $seller_id) {
        return $this->api_request('GET', '/finance/payouts', ['app_id' => $this->app_id, 'seller_type' => $seller_type, 'seller_id' => $seller_id]);
    }

    public function request_payout($seller_type, $seller_id, $amount = null) {
        return $this->api_request('POST', '/finance/payouts/request', ['app_id' => $this->app_id, 'seller_type' => $seller_type, 'seller_id' => $seller_id, 'amount' => $amount]);
    }

    public function register_seller_account($seller_type, $seller_id, $provider, $account_data) {
        $payload = ['app_id' => $this->app_id, 'seller_type' => $seller_type, 'seller_id' => $seller_id, 'provider' => $provider, 'account_data' => $account_data];
        return $this->api_request('POST', '/sellers/register', $payload);
    }

    private function api_request($method, $endpoint, $data = []) {
        $url     = rtrim($this->api_url, '/') . $endpoint;
        $headers = ['Content-Type' => 'application/json', 'X-API-Key' => $this->api_key, 'X-App-ID' => $this->app_id];

        if ($method === 'GET') {
            if (!empty($data)) $url .= '?' . http_build_query($data);
            $response = md_http_get($url, ['timeout' => 30, 'headers' => $headers]);
        } else {
            $response = md_http_post($url, ['timeout' => 30, 'headers' => $headers, 'body' => json_encode($data)]);
        }

        if ($response === false) {
            error_log('SIMEZU API Error: HTTP request failed');
            return ['error' => 'http_request_failed', 'message' => 'HTTP request failed'];
        }

        $result      = json_decode($response['body'], true);
        $status_code = $response['code'];

        if ($status_code >= 400) {
            return ['error' => 'api_error', 'message' => $result['error'] ?? 'API request failed', 'status' => $status_code];
        }

        return $result;
    }

    private function verify_webhook_signature($payload, $signature) {
        if (empty($this->webhook_secret)) return true;
        return hash_equals(hash_hmac('sha256', $payload, $this->webhook_secret), $signature);
    }

    public function get_payment_account($user_id, $access_token = null) {
        $endpoints = [
            '/api/account/payments',
            '/api/dashboard/account/payments',
            '/api/payments/account',
            '/api/auth/users/' . urlencode($user_id) . '/payments',
            '/api/users/' . urlencode($user_id) . '/payments',
            '/api/payments/account?user_id=' . urlencode($user_id),
        ];

        $headers = ['Content-Type' => 'application/json', 'X-API-Key' => $this->api_key, 'X-App-ID' => $this->app_id];
        if (!empty($access_token)) $headers['Authorization'] = 'Bearer ' . $access_token;

        foreach ($endpoints as $endpoint) {
            $url      = rtrim($this->api_url, '/') . $endpoint;
            $response = md_http_get($url, ['timeout' => 30, 'headers' => $headers]);

            if ($response === false) continue;

            $status_code = $response['code'];
            $data        = json_decode($response['body'], true);

            if ($status_code >= 200 && $status_code < 300) {
                if (empty($data) || !is_array($data)) continue;
                return $this->normalize_payment_account_response($data);
            }

            if ($status_code === 404 || $status_code === 401 || $status_code === 403) continue;
        }

        return ['paypal' => ['connected' => false, 'email' => ''], 'stripe' => ['connected' => false, 'publishable_key' => '', 'secret_key' => ''], 'credit_card' => ['enabled' => false], 'default_payment_method' => 'paypal'];
    }

    private function normalize_payment_account_response($data) {
        $normalized = ['paypal' => ['connected' => false, 'email' => ''], 'stripe' => ['connected' => false, 'publishable_key' => '', 'secret_key' => ''], 'credit_card' => ['enabled' => false], 'default_payment_method' => 'paypal'];

        if (isset($data['data']) && is_array($data['data'])) $data = $data['data'];

        if (isset($data['paypal'])) {
            $paypal                = is_array($data['paypal']) ? $data['paypal'] : [];
            $paypal_email          = $paypal['email'] ?? $paypal['email_address'] ?? '';
            $paypal_verified       = $paypal['verified'] ?? false;
            $normalized['paypal']  = ['connected' => !empty($paypal_email) && $paypal_verified, 'email' => $paypal_email, 'status' => (!empty($paypal_email) && $paypal_verified) ? 'connected' : 'not_connected'];
        } elseif (isset($data['paypal_email'])) {
            $normalized['paypal'] = ['connected' => !empty($data['paypal_email']), 'email' => $data['paypal_email'], 'status' => !empty($data['paypal_email']) ? 'connected' : 'not_connected'];
        }

        if (isset($data['stripe'])) {
            $stripe                = is_array($data['stripe']) ? $data['stripe'] : [];
            $stripe_connected      = $stripe['connected'] ?? false;
            $stripe_account_id     = $stripe['account_id'] ?? '';
            $normalized['stripe']  = ['connected' => $stripe_connected && !empty($stripe_account_id), 'publishable_key' => $stripe['publishable_key'] ?? $stripe['publishableKey'] ?? $stripe_account_id ?? '', 'secret_key' => '', 'status' => ($stripe_connected && !empty($stripe_account_id)) ? 'connected' : 'not_connected'];
        } elseif (isset($data['stripe_publishable_key'])) {
            $normalized['stripe'] = ['connected' => !empty($data['stripe_publishable_key']), 'publishable_key' => $data['stripe_publishable_key'], 'secret_key' => '', 'status' => !empty($data['stripe_publishable_key']) ? 'connected' : 'not_connected'];
        }

        if (isset($data['credit_card'])) {
            $cc = is_array($data['credit_card']) ? $data['credit_card'] : [];
            $normalized['credit_card'] = ['enabled' => !empty($cc['enabled']) || !empty($cc['credit_card_enabled'])];
        } elseif (isset($data['credit_card_enabled'])) {
            $normalized['credit_card'] = ['enabled' => (bool) $data['credit_card_enabled']];
        }

        if (isset($data['default_payment_method'])) $normalized['default_payment_method'] = $data['default_payment_method'];

        $normalized['connected']       = $normalized['paypal']['connected'] || $normalized['stripe']['connected'];
        $normalized['account_status']  = $normalized['connected'] ? 'active' : 'not_connected';

        return $normalized;
    }

    public function update_payment_account($user_id, $settings, $access_token = null) {
        $endpoints = [
            '/api/account/payments',
            '/api/dashboard/account/payments',
            '/api/auth/users/' . urlencode($user_id) . '/payments',
            '/api/payments/account/update',
            '/api/users/' . urlencode($user_id) . '/payments',
        ];

        $headers = ['Content-Type' => 'application/json', 'X-API-Key' => $this->api_key, 'X-App-ID' => $this->app_id];
        if (!empty($access_token)) $headers['Authorization'] = 'Bearer ' . $access_token;

        $payload = [];
        if (isset($settings['paypal']['email'])) {
            $payload['paypal'] = ['email' => $settings['paypal']['email'], 'verified' => true];
        }
        if (isset($settings['stripe'])) {
            $payload['stripe'] = ['account_id' => $settings['stripe']['publishable_key'] ?? $settings['stripe']['account_id'] ?? '', 'connected' => !empty($settings['stripe']['publishable_key']) || !empty($settings['stripe']['account_id'])];
        }
        if (isset($settings['credit_card']))           $payload['credit_card'] = $settings['credit_card'];
        if (isset($settings['default_payment_method'])) $payload['default_payment_method'] = $settings['default_payment_method'];

        foreach ($endpoints as $endpoint) {
            $url      = rtrim($this->api_url, '/') . $endpoint;
            $response = md_http_post($url, ['timeout' => 30, 'headers' => $headers, 'body' => json_encode($payload)]);

            if ($response === false) continue;

            $status_code = $response['code'];
            $data        = json_decode($response['body'], true);

            if ($status_code >= 200 && $status_code < 300) return $data;
            if ($status_code === 404) continue;
        }

        return ['error' => 'update_failed', 'message' => 'Failed to update payment account settings'];
    }

    public function get_payment_methods() {
        return [
            'paypal' => ['id' => 'paypal', 'name' => 'PayPal', 'description' => 'Pay securely via SIMEZU', 'icon' => 'fab fa-paypal', 'enabled' => true],
            'stripe' => ['id' => 'stripe', 'name' => 'Credit Card', 'description' => 'Pay via SIMEZU', 'icon' => 'fas fa-credit-card', 'enabled' => true],
        ];
    }
}
