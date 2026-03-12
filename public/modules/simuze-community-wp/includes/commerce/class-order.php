<?php
/**
 * Order class for Simuze Commerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Order {
    
    public $id;
    public $order_number;
    public $buyer_id;
    public $buyer_email;
    public $subtotal;
    public $discount_amount;
    public $total_price;
    public $currency;
    public $payment_status;
    public $payment_method;
    public $payment_session_id;
    public $simezu_transaction_id;
    public $discount_code;
    public $notes;
    public $ip_address;
    public $user_agent;
    public $paid_at;
    public $created_at;
    public $updated_at;
    
    private $items = null;
    
    public function __construct($data = array()) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Get order by ID
     */
    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'orders';
        
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
     * Get order by order number
     */
    public static function get_by_number($order_number) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'orders';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE order_number = %s",
            $order_number
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Get order by payment session ID
     */
    public static function get_by_session($session_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'orders';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE payment_session_id = %s",
            $session_id
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Generate unique order number
     */
    private static function generate_order_number() {
        $prefix = 'SIM';
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        return $prefix . $date . $random;
    }
    
    /**
     * Create new order from cart
     */
    public static function create_from_cart($buyer_id, $cart_items, $options = array()) {
        global $wpdb;
        $orders_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'orders';
        $items_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'order_items';
        
        // Get buyer info
        $buyer = Simuze_User::get($buyer_id);
        if (!$buyer) {
            return new WP_Error('invalid_buyer', 'Buyer not found');
        }
        
        // Calculate totals
        $subtotal = 0;
        $discount_amount = 0;
        $prepared_items = array();
        
        // Simuze fee percentage (configurable)
        $simuze_fee_percentage = floatval(get_option('simuze_commerce_fee_percentage', 15));
        
        foreach ($cart_items as $item) {
            $product = null;
            $pricing = null;
            $seller_type = 'group';
            $seller_id = null;
            
            if ($item['type'] === 'song') {
                $product = Simuze_Song::get($item['id']);
                if ($product) {
                    $pricing = $product->get_final_price($buyer_id);
                    $seller_id = $product->group_id;
                }
            } elseif ($item['type'] === 'album') {
                $product = Simuze_Album::get($item['id']);
                if ($product) {
                    $pricing = $product->get_final_price($buyer_id);
                    $seller_id = $product->group_id;
                }
            }
            
            if (!$product || !$pricing) {
                continue;
            }
            
            $unit_price = $pricing['base_price'];
            $item_discount = $pricing['discount_amount'];
            $total_price = $pricing['final_price'];
            
            // Calculate fees
            $simuze_fee = round($total_price * ($simuze_fee_percentage / 100), 2);
            $seller_earnings = round($total_price - $simuze_fee, 2);
            
            $subtotal += $unit_price;
            $discount_amount += $item_discount;
            
            $prepared_items[] = array(
                'seller_type' => $seller_type,
                'seller_id' => $seller_id,
                'product_type' => $item['type'],
                'product_id' => $item['id'],
                'product_title' => $product->title,
                'quantity' => 1,
                'unit_price' => $unit_price,
                'discount_amount' => $item_discount,
                'total_price' => $total_price,
                'simuze_fee' => $simuze_fee,
                'seller_earnings' => $seller_earnings,
                'metadata' => json_encode(array(
                    'discount_reasons' => $pricing['discount_reasons'] ?? array(),
                )),
            );
        }
        
        if (empty($prepared_items)) {
            return new WP_Error('empty_cart', 'No valid items in cart');
        }
        
        $total_price = $subtotal - $discount_amount;
        
        // Apply discount code if provided
        if (!empty($options['discount_code'])) {
            $code_discount = Simuze_Discount::get_by_code($options['discount_code']);
            if ($code_discount && $code_discount->is_valid()) {
                $code_discount_amount = $code_discount->calculate($total_price);
                $discount_amount += $code_discount_amount;
                $total_price -= $code_discount_amount;
            }
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Create order
            $order_data = array(
                'order_number' => self::generate_order_number(),
                'buyer_id' => $buyer_id,
                'buyer_email' => $buyer->email,
                'subtotal' => $subtotal,
                'discount_amount' => $discount_amount,
                'total_price' => max(0, $total_price),
                'currency' => $options['currency'] ?? 'EUR',
                'payment_status' => 'pending',
                'payment_method' => $options['payment_method'] ?? null,
                'discount_code' => $options['discount_code'] ?? null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            );
            
            $wpdb->insert($orders_table, $order_data);
            $order_id = $wpdb->insert_id;
            
            if (!$order_id) {
                throw new Exception('Failed to create order');
            }
            
            // Create order items
            foreach ($prepared_items as $item) {
                $item['order_id'] = $order_id;
                $wpdb->insert($items_table, $item);
                
                if (!$wpdb->insert_id) {
                    throw new Exception('Failed to create order item');
                }
            }
            
            $wpdb->query('COMMIT');
            
            return self::get($order_id);
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('order_failed', $e->getMessage());
        }
    }
    
    /**
     * Update order
     */
    public function update($data) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'orders';
        
        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $this->id)
        );
        
        if ($result !== false) {
            $updated = self::get($this->id);
            if ($updated) {
                foreach (get_object_vars($updated) as $key => $value) {
                    if ($key !== 'items') {
                        $this->$key = $value;
                    }
                }
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Get order items
     */
    public function get_items() {
        if ($this->items !== null) {
            return $this->items;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'order_items';
        
        $this->items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE order_id = %d ORDER BY id ASC",
            $this->id
        ), ARRAY_A);
        
        return $this->items;
    }
    
    /**
     * Mark order as paid
     */
    public function mark_as_paid($transaction_id = null) {
        $data = array(
            'payment_status' => 'paid',
            'paid_at' => current_time('mysql'),
        );
        
        if ($transaction_id) {
            $data['simezu_transaction_id'] = $transaction_id;
        }
        
        $result = $this->update($data);
        
        if ($result) {
            // Generate download tokens
            $this->generate_download_tokens();
            
            // Update seller earnings
            $this->update_seller_earnings();
            
            // Increment discount usage
            if ($this->discount_code) {
                $discount = Simuze_Discount::get_by_code($this->discount_code);
                if ($discount) {
                    $discount->increment_uses();
                }
            }
            
            // Record activity
            Simuze_Activity::record_user_activity(
                $this->buyer_id,
                'purchase_completed',
                'Purchased ' . count($this->get_items()) . ' item(s)',
                'commerce',
                $this->id
            );
        }
        
        return $result;
    }
    
    /**
     * Mark order as failed
     */
    public function mark_as_failed($reason = null) {
        return $this->update(array(
            'payment_status' => 'failed',
            'notes' => $reason,
        ));
    }
    
    /**
     * Generate download tokens for all items
     */
    public function generate_download_tokens() {
        $items = $this->get_items();
        $tokens = array();
        
        foreach ($items as $item) {
            $token = Simuze_Download_Token::create(array(
                'order_item_id' => $item['id'],
                'buyer_id' => $this->buyer_id,
                'product_type' => $item['product_type'],
                'product_id' => $item['product_id'],
            ));
            
            if ($token) {
                $tokens[] = $token;
            }
        }
        
        return $tokens;
    }
    
    /**
     * Update seller earnings cache
     */
    private function update_seller_earnings() {
        global $wpdb;
        $items = $this->get_items();
        $seller_accounts_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts';
        
        foreach ($items as $item) {
            // Update or insert seller account
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $seller_accounts_table WHERE seller_type = %s AND seller_id = %d",
                $item['seller_type'],
                $item['seller_id']
            ));
            
            if ($existing) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE $seller_accounts_table 
                     SET total_earnings = total_earnings + %f,
                         pending_payout = pending_payout + %f
                     WHERE id = %d",
                    $item['seller_earnings'],
                    $item['seller_earnings'],
                    $existing->id
                ));
            } else {
                $wpdb->insert($seller_accounts_table, array(
                    'seller_type' => $item['seller_type'],
                    'seller_id' => $item['seller_id'],
                    'total_earnings' => $item['seller_earnings'],
                    'pending_payout' => $item['seller_earnings'],
                ));
            }
        }
    }
    
    /**
     * Get orders by buyer
     */
    public static function get_by_buyer($buyer_id, $args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'orders';
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'status' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
        );
        
        $args = array_merge($defaults, $args);
        
        $where = "buyer_id = %d";
        $params = array($buyer_id);
        
        if ($args['status']) {
            $where .= " AND payment_status = %s";
            $params[] = $args['status'];
        }
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE $where ORDER BY $orderby LIMIT %d OFFSET %d",
            array_merge($params, array($args['limit'], $args['offset']))
        ), ARRAY_A);
        
        return array_map(function($data) {
            return new self($data);
        }, $orders);
    }
    
    /**
     * Count orders by buyer
     */
    public static function count_by_buyer($buyer_id, $status = 'paid') {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'orders';
        
        if ($status) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE buyer_id = %d AND payment_status = %s",
                $buyer_id,
                $status
            ));
        }
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE buyer_id = %d",
            $buyer_id
        ));
    }
    
    /**
     * Get orders by seller
     */
    public static function get_by_seller($seller_type, $seller_id, $args = array()) {
        global $wpdb;
        $orders_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'orders';
        $items_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'order_items';
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'status' => 'paid',
            'orderby' => 'created_at',
            'order' => 'DESC',
        );
        
        $args = array_merge($defaults, $args);
        
        $where = "oi.seller_type = %s AND oi.seller_id = %d";
        $params = array($seller_type, $seller_id);
        
        if ($args['status']) {
            $where .= " AND o.payment_status = %s";
            $params[] = $args['status'];
        }
        
        $orderby = sanitize_sql_orderby('o.' . $args['orderby'] . ' ' . $args['order']);
        
        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT o.* FROM $orders_table o
             INNER JOIN $items_table oi ON o.id = oi.order_id
             WHERE $where ORDER BY $orderby LIMIT %d OFFSET %d",
            array_merge($params, array($args['limit'], $args['offset']))
        ), ARRAY_A);
        
        return array_map(function($data) {
            return new self($data);
        }, $orders);
    }
    
    /**
     * Get buyer
     */
    public function get_buyer() {
        return Simuze_User::get($this->buyer_id);
    }
    
    /**
     * Convert to array
     */
    public function to_array() {
        $items = $this->get_items();
        
        return array(
            'id' => $this->id,
            'order_number' => $this->order_number,
            'buyer_id' => $this->buyer_id,
            'buyer_email' => $this->buyer_email,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'total_price' => $this->total_price,
            'currency' => $this->currency,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'discount_code' => $this->discount_code,
            'items_count' => count($items),
            'items' => $items,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
        );
    }
}

