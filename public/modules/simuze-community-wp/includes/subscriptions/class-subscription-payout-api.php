<?php
/**
 * Subscription Payout API
 * Handles sending payout batches to SIMEZU with retry and idempotency
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Subscription_Payout_API {
    
    private static $instance = null;
    
    private $api_url;
    private $api_key;
    private $app_id;
    
    // Retry configuration
    const MAX_RETRIES = 3;
    const RETRY_DELAY_SECONDS = 60;
    
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
    }
    
    /**
     * Send payout batch to SIMEZU
     * Uses idempotency key to prevent duplicate payouts
     * 
     * @param array $batch Batch data from Simuze_Payout_Calculator::generate_payout_batch()
     * @return array|WP_Error Response from SIMEZU or error
     */
    public function send_payout_batch($batch) {
        if (empty($batch['items'])) {
            return new WP_Error('empty_batch', 'Batch contains no items');
        }
        
        // Check if batch was already sent (idempotency)
        $existing = $this->get_batch_status($batch['batch_id']);
        if ($existing && !is_wp_error($existing)) {
            if ($existing['status'] === 'completed') {
                return array(
                    'success' => true,
                    'message' => 'Batch already processed',
                    'batch_id' => $batch['batch_id'],
                    'simezu_batch_id' => $existing['simezu_batch_id'] ?? null,
                );
            }
        }
        
        // Prepare payload
        $payload = array(
            'app_id' => $this->app_id,
            'batch_id' => $batch['batch_id'],
            'batch_type' => 'subscription_revenue_share',
            'period' => $batch['period'],
            'total_amount' => $batch['total_amount'],
            'currency' => 'EUR',
            'item_count' => $batch['item_count'],
            'items' => $batch['items'],
            'metadata' => array(
                'pool_total' => $batch['pool_total'],
                'total_plays' => $batch['total_plays'],
            ),
        );
        
        // Send to SIMEZU with retry logic
        $response = $this->api_request_with_retry('POST', '/payouts/batch', $payload);
        
        if (is_wp_error($response)) {
            // Mark payouts as failed
            $this->mark_batch_failed($batch['batch_id'], $response->get_error_message());
            return $response;
        }
        
        // Update payout records with SIMEZU reference
        if (!empty($response['simezu_batch_id'])) {
            $this->mark_batch_processing($batch['batch_id'], $response['simezu_batch_id']);
        }
        
        return array(
            'success' => true,
            'batch_id' => $batch['batch_id'],
            'simezu_batch_id' => $response['simezu_batch_id'] ?? null,
            'status' => $response['status'] ?? 'processing',
        );
    }
    
    /**
     * Check batch status with SIMEZU
     */
    public function get_batch_status($batch_id) {
        $response = $this->api_request('GET', '/payouts/batch/' . urlencode($batch_id));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response;
    }
    
    /**
     * Process webhook callback from SIMEZU about payout status
     */
    public function process_payout_webhook($payload) {
        $batch_id = $payload['batch_id'] ?? null;
        $status = $payload['status'] ?? null;
        $items = $payload['items'] ?? array();
        
        if (!$batch_id || !$status) {
            return new WP_Error('invalid_payload', 'Missing batch_id or status');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'artist_payouts';
        
        switch ($status) {
            case 'completed':
                // Update all payouts in batch to completed
                $wpdb->update(
                    $table,
                    array(
                        'status' => 'completed',
                        'paid_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql'),
                    ),
                    array('batch_id' => $batch_id)
                );
                
                // Update individual items if provided
                foreach ($items as $item) {
                    if (!empty($item['reference']) && !empty($item['simezu_payout_id'])) {
                        $payout_id = str_replace('payout_', '', $item['reference']);
                        Simuze_Payout_Calculator::update_payout_status(
                            $payout_id,
                            'completed',
                            $item['simezu_payout_id']
                        );
                    }
                }
                break;
                
            case 'failed':
                $error = $payload['error'] ?? 'Unknown error';
                $this->mark_batch_failed($batch_id, $error);
                break;
                
            case 'partial':
                // Some items succeeded, some failed
                foreach ($items as $item) {
                    $payout_id = str_replace('payout_', '', $item['reference'] ?? '');
                    if ($payout_id) {
                        Simuze_Payout_Calculator::update_payout_status(
                            $payout_id,
                            $item['status'] ?? 'failed',
                            $item['simezu_payout_id'] ?? null,
                            $item['error'] ?? null
                        );
                    }
                }
                break;
        }
        
        return array('success' => true);
    }
    
    /**
     * Retry failed payouts
     */
    public function retry_failed_payouts($batch_id = null) {
        $pending = Simuze_Payout_Calculator::get_pending_payouts($batch_id);
        
        if (empty($pending)) {
            return array('success' => true, 'message' => 'No pending payouts to retry');
        }
        
        // Group by batch
        $batches = array();
        foreach ($pending as $payout) {
            $batches[$payout['batch_id']][] = $payout;
        }
        
        $results = array();
        foreach ($batches as $bid => $payouts) {
            // Check retry count
            $retry_count = (int) get_option("simuze_payout_retry_{$bid}", 0);
            if ($retry_count >= self::MAX_RETRIES) {
                $results[$bid] = array(
                    'success' => false,
                    'message' => 'Max retries exceeded',
                );
                continue;
            }
            
            // Increment retry count
            update_option("simuze_payout_retry_{$bid}", $retry_count + 1);
            
            // Regenerate batch from pending payouts
            $batch = $this->rebuild_batch_from_payouts($bid, $payouts);
            
            // Send to SIMEZU
            $response = $this->send_payout_batch($batch);
            $results[$bid] = $response;
        }
        
        return $results;
    }
    
    /**
     * Rebuild batch data from existing payout records
     */
    private function rebuild_batch_from_payouts($batch_id, $payouts) {
        $items = array();
        $total_amount = 0;
        
        foreach ($payouts as $payout) {
            $artist_account = $this->get_artist_simezu_account($payout['artist_id']);
            if (!$artist_account) {
                continue;
            }
            
            $items[] = array(
                'recipient_id' => $artist_account['simezu_account_id'],
                'recipient_type' => 'artist',
                'simuze_artist_id' => $payout['artist_id'],
                'amount' => $payout['amount'],
                'currency' => 'EUR',
                'reference' => 'payout_' . $payout['id'],
                'description' => sprintf(
                    'Subscription revenue share for %s (retry)',
                    date('F Y', strtotime($payout['period'] . '-01'))
                ),
            );
            
            $total_amount += $payout['amount'];
        }
        
        return array(
            'batch_id' => $batch_id,
            'period' => $payouts[0]['period'] ?? '',
            'pool_total' => 0, // Not recalculated for retries
            'total_plays' => 0,
            'item_count' => count($items),
            'total_amount' => $total_amount,
            'items' => $items,
        );
    }
    
    /**
     * Get artist's SIMEZU account
     */
    private function get_artist_simezu_account($artist_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE seller_type = 'artist' AND seller_id = %d AND is_verified = 1",
            $artist_id
        ), ARRAY_A);
    }
    
    /**
     * Mark batch as processing
     */
    private function mark_batch_processing($batch_id, $simezu_batch_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'artist_payouts';
        
        $wpdb->update(
            $table,
            array(
                'status' => 'processing',
                'simezu_payout_id' => $simezu_batch_id,
                'updated_at' => current_time('mysql'),
            ),
            array('batch_id' => $batch_id)
        );
    }
    
    /**
     * Mark batch as failed
     */
    private function mark_batch_failed($batch_id, $error) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'artist_payouts';
        
        $wpdb->update(
            $table,
            array(
                'status' => 'failed',
                'error_message' => $error,
                'updated_at' => current_time('mysql'),
            ),
            array('batch_id' => $batch_id)
        );
    }
    
    /**
     * Make API request with retry logic
     */
    private function api_request_with_retry($method, $endpoint, $data = array(), $attempt = 1) {
        $response = $this->api_request($method, $endpoint, $data);
        
        if (is_wp_error($response) && $attempt < self::MAX_RETRIES) {
            // Check if error is retryable
            $code = $response->get_error_code();
            $retryable_codes = array('timeout', 'http_request_failed', 'server_error');
            
            if (in_array($code, $retryable_codes)) {
                sleep(self::RETRY_DELAY_SECONDS * $attempt);
                return $this->api_request_with_retry($method, $endpoint, $data, $attempt + 1);
            }
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
            'timeout' => 60, // Longer timeout for batch operations
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
            error_log('SIMEZU Payout API Error: ' . $response->get_error_message());
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code >= 500) {
            return new WP_Error('server_error', 'SIMEZU server error', array('status' => $status_code));
        }
        
        if ($status_code >= 400) {
            return new WP_Error(
                'api_error',
                $result['error'] ?? 'API request failed',
                array('status' => $status_code)
            );
        }
        
        return $result;
    }
}
