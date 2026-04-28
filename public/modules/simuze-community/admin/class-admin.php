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
            'Messages',
            'Messages',
            'manage_options',
            'simuze-messages',
            array($this, 'render_messages')
        );

        add_submenu_page(
            'simuze-community',
            'Activity',
            'Activity',
            'manage_options',
            'simuze-activity',
            array($this, 'render_activity')
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

    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('simuze_settings', 'simuze_auth_api_key');
        register_setting('simuze_settings', 'simuze_auth_api_url');
        register_setting('simuze_settings', 'simuze_geo_api_url');
        register_setting('simuze_settings', 'simezu_api_url');
        register_setting('simuze_settings', 'simuze_simezu_api_key');
        register_setting('simuze_settings', 'simuze_simezu_app_id');
        register_setting('simuze_settings', 'simuze_simezu_webhook_secret');
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
     * Render messages moderation
     */
    public function render_messages() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/messages.php';
    }

    /**
     * Render activity log
     */
    public function render_activity() {
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'admin/views/activity.php';
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

}

