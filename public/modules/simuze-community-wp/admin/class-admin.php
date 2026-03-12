<?php
/**
 * Admin interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Simuze Community',
            'Simuze',
            'manage_options',
            'simuze-community',
            array($this, 'render_dashboard'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'simuze-community',
            'Groups',
            'Groups',
            'manage_options',
            'simuze-groups',
            array($this, 'render_groups')
        );
        
        add_submenu_page(
            'simuze-community',
            'Group Types',
            'Group Types',
            'manage_options',
            'simuze-group-types',
            array($this, 'render_group_types')
        );
        
        add_submenu_page(
            'simuze-community',
            'Reviews',
            'Reviews',
            'manage_options',
            'simuze-reviews',
            array($this, 'render_reviews')
        );
        
        add_submenu_page(
            'simuze-community',
            'Reports',
            'Reports',
            'manage_options',
            'simuze-reports',
            array($this, 'render_reports')
        );
        
        add_submenu_page(
            'simuze-community',
            'Settings',
            'Settings',
            'manage_options',
            'simuze-settings',
            array($this, 'render_settings')
        );
        
        // Commerce menu items
        add_menu_page(
            'Simuze Commerce',
            'Commerce',
            'manage_options',
            'simuze-commerce',
            array($this, 'render_commerce_dashboard'),
            'dashicons-cart',
            31
        );
        
        add_submenu_page(
            'simuze-commerce',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'simuze-commerce',
            array($this, 'render_commerce_dashboard')
        );
        
        add_submenu_page(
            'simuze-commerce',
            'Orders',
            'Orders',
            'manage_options',
            'simuze-orders',
            array($this, 'render_orders')
        );
        
        add_submenu_page(
            'simuze-commerce',
            'Products',
            'Products',
            'manage_options',
            'simuze-products',
            array($this, 'render_products')
        );
        
        add_submenu_page(
            'simuze-commerce',
            'Discounts',
            'Discounts',
            'manage_options',
            'simuze-discounts',
            array($this, 'render_discounts')
        );
        
        add_submenu_page(
            'simuze-commerce',
            'Payouts',
            'Payouts',
            'manage_options',
            'simuze-payouts',
            array($this, 'render_payouts')
        );
        
        add_submenu_page(
            'simuze-commerce',
            'Payment Settings',
            'Payment Settings',
            'manage_options',
            'simuze-payment-settings',
            array($this, 'render_payment_settings')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('simuze_settings', 'simuze_auth_api_key');
        register_setting('simuze_settings', 'simuze_auth_api_url');
        register_setting('simuze_settings', 'simuze_geo_api_url');
        
        // Commerce settings
        register_setting('simuze_commerce_settings', 'simuze_commerce_fee_percentage');
        register_setting('simuze_commerce_settings', 'simuze_commerce_max_downloads');
        register_setting('simuze_commerce_settings', 'simuze_paypal_client_id');
        register_setting('simuze_commerce_settings', 'simuze_paypal_client_secret');
        register_setting('simuze_commerce_settings', 'simuze_paypal_sandbox');
        register_setting('simuze_commerce_settings', 'simuze_stripe_enabled');
        register_setting('simuze_commerce_settings', 'simuze_stripe_publishable_key');
        register_setting('simuze_commerce_settings', 'simuze_stripe_secret_key');
        register_setting('simuze_commerce_settings', 'simezu_api_url');
        register_setting('simuze_commerce_settings', 'simuze_simezu_api_key');
        register_setting('simuze_commerce_settings', 'simuze_simezu_app_id');
        register_setting('simuze_commerce_settings', 'simuze_simezu_webhook_secret');
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Render groups
     */
    public function render_groups() {
        // Check if we're viewing/editing a specific group
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['group_id'])) {
            include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/group-edit.php';
        } else {
            include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/groups.php';
        }
    }
    
    /**
     * Render group types
     */
    public function render_group_types() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/group-types.php';
    }
    
    /**
     * Render reviews
     */
    public function render_reviews() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/reviews.php';
    }
    
    /**
     * Render reports
     */
    public function render_reports() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/reports.php';
    }
    
    /**
     * Render settings
     */
    public function render_settings() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Render commerce dashboard
     */
    public function render_commerce_dashboard() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/commerce-dashboard.php';
    }
    
    /**
     * Render orders
     */
    public function render_orders() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/orders.php';
    }
    
    /**
     * Render products
     */
    public function render_products() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/products.php';
    }
    
    /**
     * Render discounts
     */
    public function render_discounts() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/discounts.php';
    }
    
    /**
     * Render payouts
     */
    public function render_payouts() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/payouts.php';
    }
    
    /**
     * Render payment settings
     */
    public function render_payment_settings() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/payment-settings.php';
    }
}

