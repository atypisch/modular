<?php
/**
 * Subscription REST API
 * Lightweight, cacheable endpoints for app support
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Subscription_API {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        $namespace = 'simuze/v1';
        
        // Get subscription status (cacheable)
        register_rest_route($namespace, '/subscription/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_subscription_status'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Get subscription tiers (public, highly cacheable)
        register_rest_route($namespace, '/subscription/tiers', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_subscription_tiers'),
            'permission_callback' => '__return_true',
        ));
        
        // Create/upgrade subscription
        register_rest_route($namespace, '/subscription/subscribe', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_subscription'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Cancel subscription
        register_rest_route($namespace, '/subscription/cancel', array(
            'methods' => 'POST',
            'callback' => array($this, 'cancel_subscription'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Record play (internal use)
        register_rest_route($namespace, '/plays/record', array(
            'methods' => 'POST',
            'callback' => array($this, 'record_play'),
            'permission_callback' => '__return_true', // Can be anonymous
        ));
        
        // Get artist payout history
        register_rest_route($namespace, '/subscription/payouts/(?P<artist_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_artist_payouts'),
            'permission_callback' => array($this, 'check_auth'),
        ));
        
        // Webhook for payout status updates from SIMEZU
        register_rest_route($namespace, '/webhooks/subscription/payout-status', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_payout_webhook'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Check authentication
     */
    public function check_auth() {
        if (class_exists('Simuze_Auth')) {
            $auth = Simuze_Auth::get_instance();
            return $auth->get_current_user() !== null;
        }
        return is_user_logged_in();
    }
    
    /**
     * Get current user ID
     */
    private function get_current_user_id() {
        if (class_exists('Simuze_Auth')) {
            $auth = Simuze_Auth::get_instance();
            $user = $auth->get_current_user();
            return $user ? $user->id : null;
        }
        return get_current_user_id();
    }
    
    /**
     * GET /subscription/status
     * Returns current subscription status (lightweight, cacheable)
     */
    public function get_subscription_status($request) {
        $user_id = $this->get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $subscription = Simuze_Subscription::get_active_for_user($user_id);
        
        // Build lightweight response
        $response = array(
            'has_subscription' => $subscription !== null,
            'type' => $subscription ? $subscription->type : 'free',
            'is_paid' => $subscription ? $subscription->is_paid() : false,
            'is_active' => $subscription ? $subscription->is_active() : false,
            'expires_at' => $subscription ? $subscription->expires_at : null,
        );
        
        // Add cache headers (5 minutes)
        $result = rest_ensure_response($response);
        $result->header('Cache-Control', 'private, max-age=300');
        
        return $result;
    }
    
    /**
     * GET /subscription/tiers
     * Returns available subscription tiers (public, highly cacheable)
     */
    public function get_subscription_tiers($request) {
        $tiers = Simuze_Subscription::get_tier_info();
        
        $result = rest_ensure_response(array(
            'tiers' => $tiers,
        ));
        
        // Cache for 1 hour (prices don't change often)
        $result->header('Cache-Control', 'public, max-age=3600');
        
        return $result;
    }
    
    /**
     * POST /subscription/subscribe
     * Create or upgrade subscription
     */
    public function create_subscription($request) {
        $user_id = $this->get_current_user_id();
        $type = $request->get_param('type');
        
        if (!$user_id) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        if (!in_array($type, array('free', 'basic', 'premium'))) {
            return new WP_Error('invalid_type', 'Invalid subscription type', array('status' => 400));
        }
        
        // Check for existing subscription
        $existing = Simuze_Subscription::get_active_for_user($user_id);
        
        if ($type === 'free') {
            // Free tier - create directly
            if ($existing && $existing->type !== 'free') {
                // Downgrade from paid to free
                $existing->cancel();
            }
            
            $subscription = Simuze_Subscription::create(array(
                'user_id' => $user_id,
                'type' => 'free',
            ));
            
            if (is_wp_error($subscription)) {
                return $subscription;
            }
            
            return rest_ensure_response(array(
                'success' => true,
                'subscription' => array(
                    'id' => $subscription->id,
                    'type' => $subscription->type,
                    'status' => $subscription->status,
                ),
            ));
        }
        
        // Paid subscription - redirect to SIMEZU for payment
        $tier = Simuze_Subscription::get_tier_info($type);
        
        // Create pending subscription
        $subscription = Simuze_Subscription::create(array(
            'user_id' => $user_id,
            'type' => $type,
            'price' => $tier['price'],
            'status' => 'pending',
        ));
        
        if (is_wp_error($subscription)) {
            return $subscription;
        }
        
        // Create payment session via SIMEZU
        $payments = Simuze_Simezu_Payments::get_instance();
        
        // Build subscription payment request
        $payment_data = array(
            'type' => 'subscription',
            'subscription_id' => $subscription->id,
            'user_id' => $user_id,
            'amount' => $tier['price'],
            'currency' => 'EUR',
            'description' => 'Simuze ' . $tier['name'] . ' Subscription',
            'return_url' => home_url('/subscription/success/?subscription_id=' . $subscription->id),
            'cancel_url' => home_url('/subscription/'),
            'webhook_url' => rest_url('simuze/v1/webhooks/subscription/payment'),
        );
        
        // Note: SIMEZU handles the actual payment - we just redirect
        return rest_ensure_response(array(
            'success' => true,
            'subscription_id' => $subscription->id,
            'redirect_url' => $this->get_simezu_subscription_url($payment_data),
            'message' => 'Redirect to SIMEZU to complete payment',
        ));
    }
    
    /**
     * Get SIMEZU subscription URL
     */
    private function get_simezu_subscription_url($data) {
        $api_url = get_option('simezu_api_url', 'http://localhost/simezu/api');
        $base_url = str_replace('/api', '', $api_url);
        
        return add_query_arg(array(
            'app' => 'simuze',
            'type' => 'subscription',
            'subscription_id' => $data['subscription_id'],
            'amount' => $data['amount'],
            'return_url' => urlencode($data['return_url']),
            'cancel_url' => urlencode($data['cancel_url']),
        ), $base_url . '/subscribe');
    }
    
    /**
     * POST /subscription/cancel
     * Cancel current subscription
     */
    public function cancel_subscription($request) {
        $user_id = $this->get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error('not_authenticated', 'User not authenticated', array('status' => 401));
        }
        
        $subscription = Simuze_Subscription::get_active_for_user($user_id);
        
        if (!$subscription) {
            return new WP_Error('no_subscription', 'No active subscription found', array('status' => 404));
        }
        
        if ($subscription->type === 'free') {
            return new WP_Error('cannot_cancel_free', 'Cannot cancel free subscription', array('status' => 400));
        }
        
        // Cancel via SIMEZU if it's a SIMEZU subscription
        if ($subscription->simezu_subscription_id) {
            $payments = Simuze_Simezu_Payments::get_instance();
            // Note: Implement cancel_subscription in Simuze_Simezu_Payments if needed
        }
        
        $subscription->cancel();
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Subscription cancelled. You will retain access until ' . $subscription->expires_at,
        ));
    }
    
    /**
     * POST /plays/record
     * Record a play (called by player)
     */
    public function record_play($request) {
        $track_id = $request->get_param('track_id');
        $artist_id = $request->get_param('artist_id');
        
        if (!$track_id || !$artist_id) {
            return new WP_Error('missing_params', 'track_id and artist_id are required', array('status' => 400));
        }
        
        $user_id = $this->get_current_user_id();
        
        // Fire play event (PlayTracker hooks into this)
        do_action('simuze_track_played', $track_id, $artist_id, $user_id);
        
        return rest_ensure_response(array(
            'success' => true,
        ));
    }
    
    /**
     * GET /subscription/payouts/{artist_id}
     * Get artist payout history
     */
    public function get_artist_payouts($request) {
        $artist_id = $request->get_param('artist_id');
        $user_id = $this->get_current_user_id();
        
        // Verify user has access to this artist's data
        // (either is the artist or has admin rights)
        if (!$this->can_view_artist_payouts($user_id, $artist_id)) {
            return new WP_Error('forbidden', 'Access denied', array('status' => 403));
        }
        
        $payouts = Simuze_Payout_Calculator::get_artist_payouts($artist_id);
        
        // Format for response
        $formatted = array_map(function($payout) {
            return array(
                'period' => $payout['period'],
                'period_display' => date('F Y', strtotime($payout['period'] . '-01')),
                'play_count' => (int) $payout['play_count'],
                'play_share' => (float) $payout['play_share'],
                'amount' => (int) $payout['amount'],
                'amount_display' => '€' . number_format($payout['amount'] / 100, 2),
                'status' => $payout['status'],
                'paid_at' => $payout['paid_at'],
            );
        }, $payouts);
        
        return rest_ensure_response(array(
            'artist_id' => $artist_id,
            'payouts' => $formatted,
        ));
    }
    
    /**
     * Check if user can view artist payouts
     */
    private function can_view_artist_payouts($user_id, $artist_id) {
        // Check if user is admin of the artist group
        if (class_exists('Simuze_Group')) {
            $group = Simuze_Group::get($artist_id);
            if ($group && $group->is_admin($user_id)) {
                return true;
            }
        }
        
        // Check if user is platform admin
        if (class_exists('Simuze_Auth')) {
            $auth = Simuze_Auth::get_instance();
            if ($auth->is_admin()) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * POST /webhooks/subscription/payout-status
     * Handle payout status webhook from SIMEZU
     */
    public function handle_payout_webhook($request) {
        $payload = $request->get_json_params();
        
        // Verify webhook signature
        $signature = $request->get_header('X-Webhook-Signature');
        $secret = get_option('simuze_simezu_webhook_secret', '');
        
        if ($secret) {
            $expected = hash_hmac('sha256', json_encode($payload), $secret);
            if (!hash_equals($expected, $signature ?? '')) {
                return new WP_Error('invalid_signature', 'Invalid webhook signature', array('status' => 401));
            }
        }
        
        $payout_api = Simuze_Subscription_Payout_API::get_instance();
        $result = $payout_api->process_payout_webhook($payload);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response($result);
    }
}
