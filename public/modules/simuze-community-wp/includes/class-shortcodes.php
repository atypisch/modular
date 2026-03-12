<?php
/**
 * Shortcodes for frontend display
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Shortcodes {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode('simuze_profile', array($this, 'render_profile'));
        add_shortcode('simuze_group', array($this, 'render_group'));
        add_shortcode('simuze_messages', array($this, 'render_messages'));
        add_shortcode('simuze_discography', array($this, 'render_discography'));
        add_shortcode('simuze_activity', array($this, 'render_activity'));
        add_shortcode('simuze_friends', array($this, 'render_friends'));
        add_shortcode('simuze_groups', array($this, 'render_groups'));
    }
    
    /**
     * Render user profile page
     */
    public function render_profile($atts) {
        $atts = shortcode_atts(array(
            'username' => '',
        ), $atts);
        
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        // If not logged in and no username specified, show login prompt
        if (!$current_user && empty($atts['username'])) {
            $login_url = $auth->get_login_url(home_url('/profile/'));
            return sprintf(
                '<div class="simuze-login-prompt">
                    <h2>Welcome to Simuze</h2>
                    <p>Please log in to access your dashboard and profile.</p>
                    <a href="%s" class="button button-primary">Login with Simezu</a>
                    <p class="register-link">Don\'t have an account? <a href="%s">Register</a></p>
                </div>
                <style>
                .simuze-login-prompt {
                    max-width: 500px;
                    margin: 60px auto;
                    padding: 40px;
                    text-align: center;
                    background: rgba(255,255,255,0.05);
                    border-radius: 16px;
                }
                .simuze-login-prompt h2 { margin-bottom: 15px; }
                .simuze-login-prompt p { margin-bottom: 25px; color: rgba(255,255,255,0.7); }
                .simuze-login-prompt .button { padding: 15px 40px; font-size: 1.1rem; }
                .simuze-login-prompt .register-link { margin-top: 20px; font-size: 0.9rem; }
                </style>',
                esc_url($login_url),
                esc_url($auth->get_register_url(home_url('/profile/')))
            );
        }
        
        // Get profile user
        if (!empty($atts['username'])) {
            $user = Simuze_User::get_by_username($atts['username']);
        } else {
            $user = $current_user;
        }
        
        if (!$user) {
            return '<p>User not found.</p>';
        }
        
        // Get current tab
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        
        ob_start();
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile.php';
        return ob_get_clean();
    }
    
    /**
     * Render group page
     */
    public function render_group($atts) {
        $atts = shortcode_atts(array(
            'slug' => '',
        ), $atts);
        
        // Get group slug from URL or attribute
        if (empty($atts['slug'])) {
            $slug = get_query_var('group_slug');
        } else {
            $slug = $atts['slug'];
        }
        
        if (empty($slug)) {
            return '<p>Group not found.</p>';
        }
        
        $group = Simuze_Group::get_by_slug($slug);
        
        if (!$group) {
            return '<p>Group not found.</p>';
        }
        
        // Get current tab
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        
        ob_start();
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/group.php';
        return ob_get_clean();
    }
    
    /**
     * Render messages page
     */
    public function render_messages($atts) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return '<p>Please log in to view messages.</p>';
        }
        
        ob_start();
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/messages.php';
        return ob_get_clean();
    }
    
    /**
     * Render discography page
     */
    public function render_discography($atts) {
        $atts = shortcode_atts(array(
            'group_id' => 0,
        ), $atts);
        
        ob_start();
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/discography.php';
        return ob_get_clean();
    }
    
    /**
     * Render activity feed
     */
    public function render_activity($atts) {
        ob_start();
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/activity.php';
        return ob_get_clean();
    }
    
    /**
     * Render friends page
     */
    public function render_friends($atts) {
        $auth = Simuze_Auth::get_instance();
        $current_user = $auth->get_current_user();
        
        if (!$current_user) {
            return '<p>Please log in to view friends.</p>';
        }
        
        ob_start();
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/friends.php';
        return ob_get_clean();
    }
    
    /**
     * Render groups list
     */
    public function render_groups($atts) {
        $atts = shortcode_atts(array(
            'group_type' => '',
            'per_page' => 20,
        ), $atts);
        
        ob_start();
        include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/groups-list.php';
        return ob_get_clean();
    }
}

