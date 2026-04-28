<?php
/**
 * Cart class for Simuze Commerce
 * Session-based shopping cart
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Cart {
    
    private static $instance = null;
    private $session_key = 'simuze_cart';
    private $items = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_from_session();
    }
    
    /**
     * Load cart from session
     */
    private function load_from_session() {
        if (!session_id()) {
            session_start();
        }
        
        if (isset($_SESSION[$this->session_key])) {
            $this->items = $_SESSION[$this->session_key];
        }
    }
    
    /**
     * Save cart to session
     */
    private function save_to_session() {
        if (!session_id()) {
            session_start();
        }
        
        $_SESSION[$this->session_key] = $this->items;
    }
    
    /**
     * Add item to cart
     */
    public function add_item($type, $id) {
        // Validate product exists
        $product = null;
        if ($type === 'song') {
            $product = Simuze_Song::get($id);
        } elseif ($type === 'album') {
            $product = Simuze_Album::get($id);
        }
        
        if (!$product) {
            return new WP_Error('invalid_product', 'Product not found');
        }
        
        if (!$product->is_active || !$product->is_purchasable) {
            return new WP_Error('not_purchasable', 'This product is not available for purchase');
        }
        
        // Check if already in cart
        $key = $type . '_' . $id;
        if (isset($this->items[$key])) {
            return new WP_Error('already_in_cart', 'This item is already in your cart');
        }
        
        // Check if user already owns this
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        if ($current_user) {
            if ($this->user_owns_product($current_user->id, $type, $id)) {
                return new WP_Error('already_owned', 'You already own this item');
            }
        }
        
        $this->items[$key] = array(
            'type' => $type,
            'id' => $id,
            'added_at' => time(),
        );
        
        $this->save_to_session();
        
        return true;
    }
    
    /**
     * Remove item from cart
     */
    public function remove_item($type, $id) {
        $key = $type . '_' . $id;
        
        if (isset($this->items[$key])) {
            unset($this->items[$key]);
            $this->save_to_session();
            return true;
        }
        
        return false;
    }
    
    /**
     * Clear cart
     */
    public function clear() {
        $this->items = array();
        $this->save_to_session();
    }
    
    /**
     * Get all items
     */
    public function get_items() {
        return array_values($this->items);
    }
    
    /**
     * Get item count
     */
    public function get_count() {
        return count($this->items);
    }
    
    /**
     * Check if cart is empty
     */
    public function is_empty() {
        return empty($this->items);
    }
    
    /**
     * Check if item is in cart
     */
    public function has_item($type, $id) {
        $key = $type . '_' . $id;
        return isset($this->items[$key]);
    }
    
    /**
     * Calculate cart totals
     */
    public function get_totals($buyer_id = null) {
        $subtotal = 0;
        $discount_total = 0;
        $items_data = array();
        
        foreach ($this->items as $item) {
            $product = null;
            $pricing = null;
            
            if ($item['type'] === 'song') {
                $product = Simuze_Song::get($item['id']);
                if ($product) {
                    $pricing = $product->get_final_price($buyer_id);
                }
            } elseif ($item['type'] === 'album') {
                $product = Simuze_Album::get($item['id']);
                if ($product) {
                    $pricing = $product->get_final_price($buyer_id);
                }
            }
            
            if ($product && $pricing) {
                $subtotal += $pricing['base_price'];
                $discount_total += $pricing['discount_amount'];
                
                $items_data[] = array(
                    'type' => $item['type'],
                    'id' => $item['id'],
                    'title' => $product->title,
                    'base_price' => $pricing['base_price'],
                    'final_price' => $pricing['final_price'],
                    'discount_amount' => $pricing['discount_amount'],
                    'discount_reasons' => $pricing['discount_reasons'],
                    'artwork_url' => $product->artwork_url ?? $product->cover_image ?? null,
                    'group_id' => $product->group_id,
                );
            }
        }
        
        $total = $subtotal - $discount_total;
        
        return array(
            'items' => $items_data,
            'items_count' => count($items_data),
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discount_total, 2),
            'total' => round(max(0, $total), 2),
            'currency' => 'EUR',
        );
    }
    
    /**
     * Apply discount code
     */
    public function apply_discount_code($code, $buyer_id = null) {
        $discount = Simuze_Discount::get_by_code($code);
        
        if (!$discount) {
            return new WP_Error('invalid_code', 'Invalid discount code');
        }
        
        if (!$discount->is_valid()) {
            return new WP_Error('expired_code', 'This discount code has expired or is no longer valid');
        }
        
        $totals = $this->get_totals($buyer_id);
        
        if ($discount->min_purchase && $totals['total'] < $discount->min_purchase) {
            return new WP_Error('min_purchase', 'Minimum purchase of €' . number_format($discount->min_purchase, 2) . ' required');
        }
        
        $discount_amount = $discount->calculate($totals['total']);
        
        return array(
            'success' => true,
            'discount' => $discount->to_array(),
            'discount_amount' => $discount_amount,
            'new_total' => round($totals['total'] - $discount_amount, 2),
        );
    }
    
    /**
     * Check if user owns a product
     */
    private function user_owns_product($user_id, $type, $product_id) {
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
            $type,
            $product_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Convert to array for API response
     */
    public function to_array($buyer_id = null) {
        $totals = $this->get_totals($buyer_id);
        
        return array(
            'items' => $totals['items'],
            'items_count' => $totals['items_count'],
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discount_total'],
            'total' => $totals['total'],
            'currency' => $totals['currency'],
            'is_empty' => $this->is_empty(),
        );
    }
}











