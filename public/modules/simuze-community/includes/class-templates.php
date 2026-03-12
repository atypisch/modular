<?php
/**
 * Template loader
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Templates {
    
    /**
     * Load template
     */
    public static function load($template_name, $vars = array()) {
        extract($vars);
        
        $template_path = SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/' . $template_name . '.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<p>Template not found: ' . esc_html($template_name) . '</p>';
        }
    }
}











