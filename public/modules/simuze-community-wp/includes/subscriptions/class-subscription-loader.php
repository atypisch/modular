<?php
/**
 * Subscription System Loader
 * Loads all subscription-related classes and initializes the system
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Subscription_Loader {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_classes();
        $this->init_components();
    }
    
    /**
     * Load all subscription classes
     */
    private function load_classes() {
        $base_path = dirname(__FILE__);
        
        require_once $base_path . '/class-subscription.php';
        require_once $base_path . '/class-play-tracker.php';
        require_once $base_path . '/class-payout-calculator.php';
        require_once $base_path . '/class-subscription-payout-api.php';
        require_once $base_path . '/class-subscription-cron.php';
        require_once $base_path . '/class-subscription-api.php';
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize play tracker (hooks into track played events)
        Simuze_Play_Tracker::get_instance();
        
        // Initialize cron jobs
        Simuze_Subscription_Cron::get_instance();
        
        // Initialize REST API
        Simuze_Subscription_API::get_instance();
    }
    
    /**
     * Deactivation hook
     */
    public static function deactivate() {
        Simuze_Subscription_Cron::unschedule_cron_jobs();
    }
}

// Initialize on plugins_loaded
add_action('plugins_loaded', function() {
    Simuze_Subscription_Loader::get_instance();
}, 20); // After main plugin loads

// Deactivation hook
register_deactivation_hook(
    dirname(dirname(dirname(__FILE__))) . '/simuze-community.php',
    array('Simuze_Subscription_Loader', 'deactivate')
);
