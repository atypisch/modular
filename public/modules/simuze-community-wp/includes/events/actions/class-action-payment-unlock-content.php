<?php
/**
 * Payment Unlock Content Action Handler
 * Example action handler that unlocks content when payment is completed
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Action_Payment_Unlock_Content implements Simuze_Action_Handler {
    
    /**
     * Get event types this handler responds to
     */
    public function get_event_types() {
        return array(
            'payment.intent-succeeded',
            'payment.completed'
        );
    }
    
    /**
     * Handle payment completed event - unlock content for user
     */
    public function handle(Simuze_Event $event) {
        $payload = $event->get_payload();
        
        // Extract order/user information from payload
        $order_id = $this->extract_order_id($payload);
        $user_id = $this->extract_user_id($payload);
        $items = $this->extract_items($payload);
        
        if (!$order_id || !$user_id) {
            error_log("Simuze_Action_Payment_Unlock_Content: Missing order_id or user_id in payload");
            return;
        }
        
        // Unlock content for each purchased item
        foreach ($items as $item) {
            $this->unlock_item_for_user($user_id, $item);
        }
        
        // Update order status
        $this->update_order_status($order_id, 'completed');
        
        // Send notification to user
        $this->send_unlock_notification($user_id, $items);
    }
    
    /**
     * Extract order ID from payload
     */
    private function extract_order_id($payload) {
        if (is_array($payload)) {
            return isset($payload['order_id']) ? $payload['order_id'] : 
                   (isset($payload['orderId']) ? $payload['orderId'] : null);
        }
        if (is_object($payload)) {
            return isset($payload->order_id) ? $payload->order_id : 
                   (isset($payload->orderId) ? $payload->orderId : null);
        }
        return null;
    }
    
    /**
     * Extract user ID from payload
     */
    private function extract_user_id($payload) {
        if (is_array($payload)) {
            return isset($payload['user_id']) ? $payload['user_id'] : 
                   (isset($payload['userId']) ? $payload['userId'] : 
                   (isset($payload['customer_id']) ? $payload['customer_id'] : null));
        }
        if (is_object($payload)) {
            return isset($payload->user_id) ? $payload->user_id : 
                   (isset($payload->userId) ? $payload->userId : 
                   (isset($payload->customer_id) ? $payload->customer_id : null));
        }
        return null;
    }
    
    /**
     * Extract items from payload
     */
    private function extract_items($payload) {
        if (is_array($payload)) {
            return isset($payload['items']) ? $payload['items'] : 
                   (isset($payload['line_items']) ? $payload['line_items'] : array());
        }
        if (is_object($payload)) {
            return isset($payload->items) ? $payload->items : 
                   (isset($payload->line_items) ? $payload->line_items : array());
        }
        return array();
    }
    
    /**
     * Unlock a specific item for a user
     */
    private function unlock_item_for_user($user_id, $item) {
        global $wpdb;
        
        $item_id = is_array($item) ? ($item['id'] ?? $item['item_id'] ?? null) : 
                   (is_object($item) ? ($item->id ?? $item->item_id ?? null) : null);
        $item_type = is_array($item) ? ($item['type'] ?? 'download') : 
                     (is_object($item) ? ($item->type ?? 'download') : 'download');
        
        if (!$item_id) {
            return;
        }
        
        // Create download token or unlock access
        $tokens_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'download_tokens';
        
        $token = wp_generate_password(32, false);
        
        $wpdb->insert(
            $tokens_table,
            array(
                'user_id' => $user_id,
                'item_id' => $item_id,
                'item_type' => $item_type,
                'token' => $token,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        error_log("Simuze_Action_Payment_Unlock_Content: Unlocked item {$item_id} for user {$user_id}");
    }
    
    /**
     * Update order status
     */
    private function update_order_status($order_id, $status) {
        global $wpdb;
        $orders_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'orders';
        
        $wpdb->update(
            $orders_table,
            array('status' => $status, 'updated_at' => current_time('mysql')),
            array('id' => $order_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Send unlock notification to user
     */
    private function send_unlock_notification($user_id, $items) {
        if (!class_exists('Simuze_Notification')) {
            return;
        }
        
        $count = count($items);
        $message = $count === 1 
            ? "Your purchase is ready! You can now download your content."
            : "Your purchases are ready! You can now download {$count} items.";
        
        // Create notification (if notification system exists)
        // Simuze_Notification::create($user_id, 'content_unlocked', $message);
        
        error_log("Simuze_Action_Payment_Unlock_Content: Sent unlock notification to user {$user_id}");
    }
}
