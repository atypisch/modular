<?php
/**
 * Discography Template
 * Shows current user's artist group discography, or global discography browser.
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();

if (!$current_user) {
    $login_url = function_exists('md_home_url') ? md_home_url('/') : '/';
    echo '<div class="simuze-notice"><p>Please <a href="' . esc_url($login_url) . '">log in</a> to view your discography.</p></div>';
    return;
}

// Find user's primary artist group and redirect to its discography tab
if (class_exists('Simuze_Group')) {
    try {
        $artist_groups = Simuze_Group::get_user_groups($current_user->id, 1, array('role' => 'admin', 'group_type' => 'artist'));
        if (!empty($artist_groups)) {
            $group = $artist_groups[0];
            $group_obj = is_array($group) ? Simuze_Group::get_by_id($group['id'] ?? $group->id) : $group;
            if ($group_obj) {
                $disc_url = $group_obj->get_url() . '?tab=discography';
                header('Location: ' . $disc_url);
                exit;
            }
        }
    } catch (Exception $e) {
        // fall through
    }
}

// Fallback: redirect to profile
header('Location: ' . md_home_url('/profile/'));
exit;
