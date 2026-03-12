<?php
/**
 * Discount class for Simuze Commerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Discount {
    
    public $id;
    public $name;
    public $code;
    public $target_type;
    public $target_id;
    public $product_type;
    public $discount_type;
    public $discount_value;
    public $min_purchase;
    public $max_uses;
    public $uses_count;
    public $active_from;
    public $active_until;
    public $is_active;
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
     * Get discount by ID
     */
    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'discounts';
        
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
     * Get discount by code
     */
    public static function get_by_code($code) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'discounts';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE code = %s AND is_active = 1",
            strtoupper($code)
        ), ARRAY_A);
        
        if ($data) {
            $discount = new self($data);
            if ($discount->is_valid()) {
                return $discount;
            }
        }
        
        return null;
    }
    
    /**
     * Create new discount
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'discounts';
        
        // Uppercase code
        if (!empty($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }
        
        $defaults = array(
            'target_type' => 'global',
            'product_type' => 'all',
            'discount_type' => 'percentage',
            'is_active' => 1,
            'uses_count' => 0,
        );
        
        $data = array_merge($defaults, $data);
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            return self::get($wpdb->insert_id);
        }
        
        return null;
    }
    
    /**
     * Update discount
     */
    public function update($data) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'discounts';
        
        if (!empty($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }
        
        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $this->id)
        );
        
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
     * Delete discount
     */
    public function delete() {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'discounts';
        
        return $wpdb->delete($table, array('id' => $this->id));
    }
    
    /**
     * Check if discount is currently valid
     */
    public function is_valid() {
        if (!$this->is_active) {
            return false;
        }
        
        $now = current_time('mysql');
        
        // Check date range
        if ($this->active_from && $now < $this->active_from) {
            return false;
        }
        
        if ($this->active_until && $now > $this->active_until) {
            return false;
        }
        
        // Check max uses
        if ($this->max_uses && $this->uses_count >= $this->max_uses) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Calculate discount amount for a given price
     */
    public function calculate($price) {
        if (!$this->is_valid()) {
            return 0;
        }
        
        // Check minimum purchase
        if ($this->min_purchase && $price < $this->min_purchase) {
            return 0;
        }
        
        if ($this->discount_type === 'percentage') {
            return round($price * ($this->discount_value / 100), 2);
        } else {
            // Fixed amount
            return min($price, floatval($this->discount_value));
        }
    }
    
    /**
     * Get display value (e.g., "20%" or "€5.00")
     */
    public function get_display_value() {
        if ($this->discount_type === 'percentage') {
            return $this->discount_value . '%';
        } else {
            return '€' . number_format($this->discount_value, 2);
        }
    }
    
    /**
     * Increment usage count
     */
    public function increment_uses() {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'discounts';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET uses_count = uses_count + 1 WHERE id = %d",
            $this->id
        ));
        
        $this->uses_count++;
    }
    
    /**
     * Get active discount for a group
     */
    public static function get_active_for_group($group_id, $product_type = 'all') {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'discounts';
        $now = current_time('mysql');
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE target_type = 'group' 
             AND target_id = %d 
             AND is_active = 1
             AND (product_type = 'all' OR product_type = %s)
             AND (active_from IS NULL OR active_from <= %s)
             AND (active_until IS NULL OR active_until >= %s)
             AND (max_uses IS NULL OR uses_count < max_uses)
             ORDER BY discount_value DESC
             LIMIT 1",
            $group_id,
            $product_type,
            $now,
            $now
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Get active global discount
     */
    public static function get_active_global($product_type = 'all') {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'discounts';
        $now = current_time('mysql');
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE target_type = 'global' 
             AND is_active = 1
             AND (product_type = 'all' OR product_type = %s)
             AND (active_from IS NULL OR active_from <= %s)
             AND (active_until IS NULL OR active_until >= %s)
             AND (max_uses IS NULL OR uses_count < max_uses)
             ORDER BY discount_value DESC
             LIMIT 1",
            $product_type,
            $now,
            $now
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Get all discounts
     */
    public static function get_all($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'discounts';
        
        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'active_only' => false,
            'target_type' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
        );
        
        $args = array_merge($defaults, $args);
        
        $where = "1=1";
        $params = array();
        
        if ($args['active_only']) {
            $where .= " AND is_active = 1";
        }
        
        if ($args['target_type']) {
            $where .= " AND target_type = %s";
            $params[] = $args['target_type'];
        }
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $query = "SELECT * FROM $table WHERE $where ORDER BY $orderby LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        $discounts = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
        
        return array_map(function($data) {
            return new self($data);
        }, $discounts);
    }
    
    /**
     * Convert to array
     */
    public function to_array() {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'product_type' => $this->product_type,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'display_value' => $this->get_display_value(),
            'min_purchase' => $this->min_purchase,
            'max_uses' => $this->max_uses,
            'uses_count' => $this->uses_count,
            'active_from' => $this->active_from,
            'active_until' => $this->active_until,
            'is_active' => $this->is_active,
            'is_valid' => $this->is_valid(),
            'created_at' => $this->created_at,
        );
    }
}











