<?php
/**
 * Module Name: Example Module
 * Version: 1.0.0
 * Description: An example module to demonstrate the module system
 * Author: Modular CMS
 * Author URI: https://modularcms.com
 * Text Domain: example-module
 * Requires PHP: 8.1
 * Requires Modular: 1.0
 */

// Module activation hook
add_action('module_activate', function($slug) {
    if ($slug === 'example-module') {
        error_log('Example Module activated!');
    }
});

// Module deactivation hook
add_action('module_deactivate', function($slug) {
    if ($slug === 'example-module') {
        error_log('Example Module deactivated!');
    }
});

// Initialize module
add_action('init', function() {
    // Module initialization code here
    error_log('Example Module initialized');
});

// Example: Add admin menu item
add_action('admin_menu', function() {
    // This hook can be used to add custom admin menu items
    // Implementation depends on your admin menu system
});

// Example: Filter post title
add_filter('the_title', function($title) {
    // Modify title if needed
    return $title;
});

// Example: Filter post content
add_filter('the_content', function($content) {
    // Modify content if needed
    return $content;
});

