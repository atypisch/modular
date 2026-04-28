<?php
/**
 * Download Token class for Simuze Commerce
 * Manages secure download access after purchase
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Download_Token {
    
    public $id;
    public $token;
    public $order_item_id;
    public $buyer_id;
    public $product_type;
    public $product_id;
    public $download_count;
    public $max_downloads;
    public $expires_at;
    public $last_download_at;
    public $ip_address;
    public $created_at;
    
    public function __construct($data = array()) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Get token by ID
     */
    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'download_tokens';
        
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
     * Get token by token string
     */
    public static function get_by_token($token) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'download_tokens';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE token = %s",
            $token
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Generate secure token
     */
    private static function generate_token() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Create new download token
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'download_tokens';
        
        $defaults = array(
            'token' => self::generate_token(),
            'download_count' => 0,
            'max_downloads' => intval(get_option('simuze_commerce_max_downloads', 5)),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
        );
        
        $data = array_merge($defaults, $data);
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            return self::get($wpdb->insert_id);
        }
        
        return null;
    }
    
    /**
     * Check if token is valid for download
     */
    public function is_valid() {
        // Check expiration
        if ($this->expires_at && strtotime($this->expires_at) < time()) {
            return false;
        }
        
        // Check download limit
        if ($this->max_downloads && $this->download_count >= $this->max_downloads) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate token for a specific buyer
     */
    public function validate_for_buyer($buyer_id) {
        if ($this->buyer_id != $buyer_id) {
            return false;
        }
        
        return $this->is_valid();
    }
    
    /**
     * Record download
     */
    public function record_download($ip_address = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'download_tokens';
        
        $wpdb->update(
            $table,
            array(
                'download_count' => $this->download_count + 1,
                'last_download_at' => current_time('mysql'),
                'ip_address' => $ip_address ?: $_SERVER['REMOTE_ADDR'],
            ),
            array('id' => $this->id)
        );
        
        $this->download_count++;
        $this->last_download_at = current_time('mysql');
        
        // Increment product download count
        if ($this->product_type === 'song') {
            $song = Simuze_Song::get($this->product_id);
            if ($song) {
                $song->increment_downloads();
            }
        } elseif ($this->product_type === 'album') {
            $album = Simuze_Album::get($this->product_id);
            if ($album) {
                $album->increment_downloads();
            }
        }
    }
    
    /**
     * Get download URL
     */
    public function get_download_url() {
        return home_url('/download/' . $this->product_type . '/' . $this->product_id . '/?token=' . $this->token);
    }
    
    /**
     * Get product
     */
    public function get_product() {
        if ($this->product_type === 'song') {
            return Simuze_Song::get($this->product_id);
        } elseif ($this->product_type === 'album') {
            return Simuze_Album::get($this->product_id);
        }
        return null;
    }
    
    /**
     * Get tokens for buyer
     */
    public static function get_by_buyer($buyer_id, $args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'download_tokens';
        
        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'valid_only' => true,
        );
        
        $args = array_merge($defaults, $args);
        
        $where = "buyer_id = %d";
        $params = array($buyer_id);
        
        if ($args['valid_only']) {
            $where .= " AND (expires_at IS NULL OR expires_at > NOW())";
            $where .= " AND (max_downloads IS NULL OR download_count < max_downloads)";
        }
        
        $tokens = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, array($args['limit'], $args['offset']))
        ), ARRAY_A);
        
        return array_map(function($data) {
            return new self($data);
        }, $tokens);
    }
    
    /**
     * Count tokens by buyer
     */
    public static function count_by_buyer($buyer_id, $valid_only = false) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'download_tokens';
        
        $where = "buyer_id = %d";
        
        if ($valid_only) {
            $where .= " AND (expires_at IS NULL OR expires_at > NOW())";
            $where .= " AND (max_downloads IS NULL OR download_count < max_downloads)";
        }
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE $where",
            $buyer_id
        ));
    }
    
    /**
     * Get tokens for order item
     */
    public static function get_by_order_item($order_item_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'download_tokens';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE order_item_id = %d",
            $order_item_id
        ), ARRAY_A);
        
        if ($data) {
            return new self($data);
        }
        
        return null;
    }
    
    /**
     * Convert to array
     */
    public function to_array() {
        $product = $this->get_product();
        
        return array(
            'id' => $this->id,
            'token' => $this->token,
            'order_item_id' => $this->order_item_id,
            'buyer_id' => $this->buyer_id,
            'product_type' => $this->product_type,
            'product_id' => $this->product_id,
            'product_title' => $product ? $product->title : null,
            'download_count' => $this->download_count,
            'max_downloads' => $this->max_downloads,
            'downloads_remaining' => $this->max_downloads ? max(0, $this->max_downloads - $this->download_count) : null,
            'expires_at' => $this->expires_at,
            'is_valid' => $this->is_valid(),
            'download_url' => $this->get_download_url(),
            'last_download_at' => $this->last_download_at,
            'created_at' => $this->created_at,
        );
    }
}

