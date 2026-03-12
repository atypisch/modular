<?php
/**
 * Subscription Model
 * Handles user subscriptions (free and paid tiers)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Subscription {
    
    // Subscription types
    const TYPE_FREE = 'free';
    const TYPE_BASIC = 'basic';
    const TYPE_PREMIUM = 'premium';
    
    // Subscription prices (in EUR cents)
    const PRICES = array(
        'free' => 0,
        'basic' => 499,      // €4.99/month
        'premium' => 999,    // €9.99/month
    );
    
    // Pool percentages (how much of subscription goes to artist pool)
    const POOL_PERCENTAGES = array(
        'free' => 0,         // Free users don't contribute
        'basic' => 70,       // 70% to artists
        'premium' => 75,     // 75% to artists
    );
    
    public $id;
    public $user_id;
    public $type;
    public $price;
    public $currency;
    public $simezu_subscription_id;
    public $status;
    public $started_at;
    public $expires_at;
    public $cancelled_at;
    public $created_at;
    public $updated_at;
    
    private static $table_name = null;
    
    /**
     * Get table name
     */
    private static function get_table() {
        if (self::$table_name === null) {
            global $wpdb;
            self::$table_name = $wpdb->prefix . SIMUZE_DB_PREFIX . 'subscriptions';
        }
        return self::$table_name;
    }
    
    /**
     * Create subscription from database row
     */
    public function __construct($data = null) {
        if ($data) {
            foreach ($data as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }
    
    /**
     * Get subscription by ID
     */
    public static function get($id) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table() . " WHERE id = %d",
            $id
        ));
        return $row ? new self($row) : null;
    }
    
    /**
     * Get active subscription for user
     */
    public static function get_active_for_user($user_id) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table() . " 
             WHERE user_id = %d 
             AND status = 'active' 
             AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY created_at DESC
             LIMIT 1",
            $user_id
        ));
        return $row ? new self($row) : null;
    }
    
    /**
     * Check if user has active paid subscription
     */
    public static function user_has_paid_subscription($user_id) {
        $subscription = self::get_active_for_user($user_id);
        return $subscription && $subscription->type !== self::TYPE_FREE;
    }
    
    /**
     * Get user's subscription type (defaults to free)
     */
    public static function get_user_type($user_id) {
        $subscription = self::get_active_for_user($user_id);
        return $subscription ? $subscription->type : self::TYPE_FREE;
    }
    
    /**
     * Create new subscription
     */
    public static function create($data) {
        global $wpdb;
        
        $defaults = array(
            'type' => self::TYPE_FREE,
            'price' => 0,
            'currency' => 'EUR',
            'status' => 'active',
            'started_at' => current_time('mysql'),
            'created_at' => current_time('mysql'),
        );
        
        $data = array_merge($defaults, $data);
        
        // Set price based on type if not provided
        if (!isset($data['price']) || $data['price'] === 0) {
            $data['price'] = self::PRICES[$data['type']] ?? 0;
        }
        
        $result = $wpdb->insert(self::get_table(), $data);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create subscription: ' . $wpdb->last_error);
        }
        
        return self::get($wpdb->insert_id);
    }
    
    /**
     * Update subscription
     */
    public function update($data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            self::get_table(),
            $data,
            array('id' => $this->id)
        );
        
        if ($result !== false) {
            foreach ($data as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
        
        return $result !== false;
    }
    
    /**
     * Cancel subscription
     */
    public function cancel() {
        return $this->update(array(
            'status' => 'cancelled',
            'cancelled_at' => current_time('mysql'),
        ));
    }
    
    /**
     * Check if subscription is active
     */
    public function is_active() {
        if ($this->status !== 'active') {
            return false;
        }
        if ($this->expires_at && strtotime($this->expires_at) < time()) {
            return false;
        }
        return true;
    }
    
    /**
     * Check if subscription is paid
     */
    public function is_paid() {
        return $this->type !== self::TYPE_FREE;
    }
    
    /**
     * Get pool contribution amount for this subscription
     */
    public function get_pool_contribution() {
        $percentage = self::POOL_PERCENTAGES[$this->type] ?? 0;
        return ($this->price * $percentage) / 100;
    }
    
    /**
     * Get all subscriptions for a period
     */
    public static function get_active_for_period($period) {
        global $wpdb;
        
        // Period format: YYYY-MM
        $start_date = $period . '-01 00:00:00';
        $end_date = date('Y-m-t 23:59:59', strtotime($start_date));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . self::get_table() . "
             WHERE status = 'active'
             AND started_at <= %s
             AND (expires_at IS NULL OR expires_at >= %s)
             AND type != 'free'",
            $end_date,
            $start_date
        ));
    }
    
    /**
     * Calculate total pool amount for a period
     */
    public static function get_pool_total_for_period($period) {
        $subscriptions = self::get_active_for_period($period);
        $total = 0;
        
        foreach ($subscriptions as $sub) {
            $subscription = new self($sub);
            $total += $subscription->get_pool_contribution();
        }
        
        return $total;
    }
    
    /**
     * Fetch subscription plans from SIMEZU API
     * Uses caching to avoid excessive API calls
     */
    public static function fetch_simezu_subscriptions($force_refresh = false) {
        // Check cache first (1 hour)
        $cache_key = 'simuze_simezu_subscriptions';
        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                error_log('[Simuze Subscription] Using cached subscription data');
                return $cached;
            }
        }
        
        // SIMEZU App credentials
        $app_id = 'a1719436-ecd9-11f0-ba55-155569101740';
        $api_key = 'a1719440-ecd9-11f0-ba55-155569101740';
        
        // Get base API URL from options or use default
        $api_url = get_option('simezu_api_url', 'http://localhost/simezu/api');
        
        // Try multiple endpoints for subscriptions/plans
        $endpoints = array(
            '/subscriptions/plans',
            '/subscriptions',
            '/products/subscriptions',
            '/plans',
        );
        
        $headers = array(
            'Content-Type' => 'application/json',
            'X-App-ID' => $app_id,
            'X-API-Key' => $api_key,
        );
        
        // Try to get JWT token from cookies for authenticated requests
        $jwt_token = null;
        if (isset($_COOKIE['accessToken'])) {
            $jwt_token = $_COOKIE['accessToken'];
        } elseif (isset($_COOKIE['simuze_jwt_token'])) {
            $jwt_token = $_COOKIE['simuze_jwt_token'];
        }
        
        if ($jwt_token) {
            $headers['Authorization'] = 'Bearer ' . $jwt_token;
        }
        
        foreach ($endpoints as $endpoint) {
            $full_url = rtrim($api_url, '/') . $endpoint;
            
            error_log('[Simuze Subscription] Attempting to fetch from: ' . $full_url);
            
            $response = wp_remote_get($full_url, array(
                'headers' => $headers,
                'timeout' => 10,
                'sslverify' => false,
            ));
            
            if (is_wp_error($response)) {
                error_log('[Simuze Subscription] Failed to fetch from ' . $endpoint . ': ' . $response->get_error_message());
                continue;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            error_log('[Simuze Subscription] Response from ' . $endpoint . ': Status ' . $status_code);
            error_log('[Simuze Subscription] Response body: ' . substr($body, 0, 500));
            
            if ($status_code === 200) {
                $data = json_decode($body, true);
                
                if ($data && (isset($data['plans']) || isset($data['subscriptions']) || (is_array($data) && !empty($data)))) {
                    error_log('[Simuze Subscription] Successfully fetched subscriptions from ' . $endpoint);
                    
                    // Cache the result for 1 hour
                    set_transient($cache_key, $data, HOUR_IN_SECONDS);
                    
                    return $data;
                }
            } elseif ($status_code === 401) {
                error_log('[Simuze Subscription] Authentication failed for ' . $endpoint . ' - may need valid JWT token');
            } elseif ($status_code === 404) {
                error_log('[Simuze Subscription] Endpoint not found: ' . $endpoint);
            }
        }
        
        error_log('[Simuze Subscription] Failed to fetch subscriptions from all endpoints');
        return null;
    }
    
    /**
     * Get subscription tier info
     * Fetches from SIMEZU API if available, falls back to hardcoded tiers
     */
    public static function get_tier_info($type = null) {
        // Try to fetch from SIMEZU first
        $simezu_data = self::fetch_simezu_subscriptions();
        
        $tiers = array();
        
        if ($simezu_data) {
            // Parse SIMEZU subscription data
            $plans = array();
            
            // Handle different response formats
            if (isset($simezu_data['plans']) && is_array($simezu_data['plans'])) {
                $plans = $simezu_data['plans'];
            } elseif (isset($simezu_data['subscriptions']) && is_array($simezu_data['subscriptions'])) {
                $plans = $simezu_data['subscriptions'];
            } elseif (is_array($simezu_data) && !empty($simezu_data)) {
                $plans = $simezu_data;
            }
            
            foreach ($plans as $plan) {
                // Map SIMEZU plan to our tier format
                $plan_id = $plan['id'] ?? $plan['plan_id'] ?? null;
                $plan_slug = $plan['slug'] ?? $plan['identifier'] ?? strtolower($plan['name'] ?? '');
                $plan_name = $plan['name'] ?? 'Unknown';
                $price = isset($plan['price']) ? (int)($plan['price'] * 100) : 0; // Convert to cents
                $price_display = isset($plan['price']) ? '€' . number_format($plan['price'], 2) . '/month' : 'Free';
                
                // Extract features from plan description or features array
                $features = array();
                if (isset($plan['features']) && is_array($plan['features'])) {
                    $features = $plan['features'];
                } elseif (isset($plan['description'])) {
                    // Try to parse features from description
                    $features = array_filter(array_map('trim', explode("\n", $plan['description'])));
                }
                
                // Default features if none provided
                if (empty($features)) {
                    if ($price === 0) {
                        $features = array(
                            'Stream unlimited music',
                            'Support artists with your plays',
                            'Create playlists',
                            'Basic audio quality',
                        );
                    } else {
                        $features = array(
                            'Stream unlimited music',
                            'High quality audio',
                            'Support artists',
                        );
                    }
                }
                
                // Get pool percentage from plan metadata or use default
                $pool_percentage = isset($plan['pool_percentage']) ? (int)$plan['pool_percentage'] : 
                                  (isset($plan['metadata']['pool_percentage']) ? (int)$plan['metadata']['pool_percentage'] : 70);
                
                // Use plan slug as type, or generate from name
                $tier_type = $plan_slug ?: strtolower(str_replace(' ', '-', $plan_name));
                
                $tiers[$tier_type] = array(
                    'name' => $plan_name,
                    'price' => $price,
                    'price_display' => $price_display,
                    'features' => $features,
                    'pool_percentage' => $pool_percentage,
                    'simezu_plan_id' => $plan_id,
                    'simezu_slug' => $plan_slug,
                );
            }
            
            // Always include free tier if not in SIMEZU data
            if (!isset($tiers['free'])) {
                $tiers['free'] = array(
                    'name' => 'Free',
                    'price' => 0,
                    'price_display' => 'Free',
                    'features' => array(
                        'Stream unlimited music',
                        'Support artists with your plays',
                        'Create playlists',
                        'Basic audio quality',
                    ),
                    'pool_percentage' => 0,
                );
            }
        }
        
        // Fallback to hardcoded tiers if SIMEZU fetch failed
        if (empty($tiers)) {
            error_log('[Simuze Subscription] Using fallback hardcoded tiers');
            $tiers = array(
                self::TYPE_FREE => array(
                    'name' => 'Free',
                    'price' => 0,
                    'price_display' => 'Free',
                    'features' => array(
                        'Stream unlimited music',
                        'Support artists with your plays',
                        'Create playlists',
                        'Basic audio quality',
                    ),
                    'pool_percentage' => 0,
                ),
                self::TYPE_BASIC => array(
                    'name' => 'Basic',
                    'price' => self::PRICES['basic'],
                    'price_display' => '€4.99/month',
                    'features' => array(
                        'Everything in Free',
                        'High quality audio',
                        'Offline listening',
                        '70% of your subscription supports artists',
                        'No ads',
                    ),
                    'pool_percentage' => 70,
                ),
                self::TYPE_PREMIUM => array(
                    'name' => 'Premium',
                    'price' => self::PRICES['premium'],
                    'price_display' => '€9.99/month',
                    'features' => array(
                        'Everything in Basic',
                        'Lossless audio quality',
                        'Early access to new releases',
                        '75% of your subscription supports artists',
                        'Exclusive content',
                        'Priority support',
                    ),
                    'pool_percentage' => 75,
                ),
            );
        }
        
        // Sort tiers by price (free first, then ascending)
        uasort($tiers, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });
        
        if ($type) {
            return $tiers[$type] ?? null;
        }
        
        return $tiers;
    }
}
