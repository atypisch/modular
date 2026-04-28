<?php
/**
 * Global Friends Template
 * Wraps profile-friends.php for the current logged-in user.
 */

if (!defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();

if (!$current_user) {
    $login_url = function_exists('md_home_url') ? md_home_url('/') : '/';
    echo '<div class="simuze-notice"><p>Please <a href="' . esc_url($login_url) . '">log in</a> to view friends.</p></div>';
    return;
}

// Set $user to current user for the profile-friends template
$user = $current_user;

include SIMUZE_COMMUNITY_PLUGIN_DIR . 'templates/profile-friends.php';
