<?php
/**
 * Play Tracker
 * Tracks plays for revenue distribution (aggregated, not per-user)
 * Optimized for millions of plays - uses atomic increments
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Play_Tracker {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        md_hook_action('simuze_track_played', [$this, 'record_play'], 10);
    }

    public static function get_current_period() {
        return date('Y-m');
    }

    public function record_play($track_id, $artist_id, $user_id = null) {
        // Play tracking tables removed; subscriptions/payouts via SIMEZU only.
    }

    public static function get_track_plays($track_id, $period = null) {
        return 0;
    }

    public static function get_artist_plays($artist_id, $period = null) {
        return 0;
    }

    public static function get_artist_subscription_plays($artist_id, $period = null) {
        return 0;
    }

    public static function get_total_subscription_plays($period = null) {
        return 0;
    }

    public static function get_aggregated_artist_plays($period = null) {
        return array();
    }

    public static function get_top_artists($period = null, $limit = 100) {
        return array();
    }

    public static function get_top_tracks($period = null, $limit = 100) {
        return array();
    }
}
