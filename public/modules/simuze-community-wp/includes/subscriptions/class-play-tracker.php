<?php
/**
 * Play Tracker
 * Tracks plays for revenue distribution (aggregated, not per-user)
 * Optimized for millions of plays - uses atomic increments
 */

if (!defined('ABSPATH')) {
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
        // Hook into play events
        add_action('simuze_track_played', array($this, 'record_play'), 10, 3);
    }
    
    /**
     * Get current period (YYYY-MM format)
     */
    public static function get_current_period() {
        return date('Y-m');
    }
    
    /**
     * Record a play - called when a track is played
     * 
     * @param int $track_id The track/upload ID
     * @param int $artist_id The artist/group ID
     * @param int|null $user_id The user who played (null for anonymous)
     */
    public function record_play($track_id, $artist_id, $user_id = null) {
        global $wpdb;
        
        $period = self::get_current_period();
        
        // Always increment total plays (atomic operation)
        $this->increment_total_plays($track_id, $artist_id, $period);
        
        // If user has paid subscription, also increment subscription plays
        if ($user_id) {
            $subscription = Simuze_Subscription::get_active_for_user($user_id);
            if ($subscription && $subscription->is_paid()) {
                $this->increment_subscription_plays($subscription->id, $track_id, $artist_id, $period);
            }
        }
    }
    
    /**
     * Increment total plays (atomic upsert)
     */
    private function increment_total_plays($track_id, $artist_id, $period) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'plays_total';
        
        // Use INSERT ... ON DUPLICATE KEY UPDATE for atomic increment
        // This is safe for high concurrency
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (track_id, artist_id, period, play_count, updated_at)
             VALUES (%d, %d, %s, 1, NOW())
             ON DUPLICATE KEY UPDATE 
             play_count = play_count + 1,
             updated_at = NOW()",
            $track_id,
            $artist_id,
            $period
        ));
    }
    
    /**
     * Increment subscription plays (atomic upsert)
     */
    private function increment_subscription_plays($subscription_id, $track_id, $artist_id, $period) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'plays_subscription';
        
        // Use INSERT ... ON DUPLICATE KEY UPDATE for atomic increment
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (subscription_id, track_id, artist_id, period, play_count, updated_at)
             VALUES (%d, %d, %d, %s, 1, NOW())
             ON DUPLICATE KEY UPDATE 
             play_count = play_count + 1,
             updated_at = NOW()",
            $subscription_id,
            $track_id,
            $artist_id,
            $period
        ));
    }
    
    /**
     * Get total plays for a track in a period
     */
    public static function get_track_plays($track_id, $period = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'plays_total';
        
        if ($period === null) {
            $period = self::get_current_period();
        }
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT play_count FROM $table WHERE track_id = %d AND period = %s",
            $track_id,
            $period
        ));
    }
    
    /**
     * Get total plays for an artist in a period
     */
    public static function get_artist_plays($artist_id, $period = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'plays_total';
        
        if ($period === null) {
            $period = self::get_current_period();
        }
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(play_count) FROM $table WHERE artist_id = %d AND period = %s",
            $artist_id,
            $period
        ));
    }
    
    /**
     * Get subscription plays for an artist in a period
     * This is what determines their share of the subscription pool
     */
    public static function get_artist_subscription_plays($artist_id, $period = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'plays_subscription';
        
        if ($period === null) {
            $period = self::get_current_period();
        }
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(play_count) FROM $table WHERE artist_id = %d AND period = %s",
            $artist_id,
            $period
        ));
    }
    
    /**
     * Get total subscription plays for a period (across all artists)
     */
    public static function get_total_subscription_plays($period = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'plays_subscription';
        
        if ($period === null) {
            $period = self::get_current_period();
        }
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(play_count) FROM $table WHERE period = %s",
            $period
        ));
    }
    
    /**
     * Get aggregated artist plays for a period (for payout calculation)
     * Returns array of artist_id => play_count
     */
    public static function get_aggregated_artist_plays($period = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'plays_subscription';
        
        if ($period === null) {
            $period = self::get_current_period();
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT artist_id, SUM(play_count) as total_plays
             FROM $table 
             WHERE period = %s
             GROUP BY artist_id
             ORDER BY total_plays DESC",
            $period
        ), ARRAY_A);
        
        $aggregated = array();
        foreach ($results as $row) {
            $aggregated[$row['artist_id']] = (int) $row['total_plays'];
        }
        
        return $aggregated;
    }
    
    /**
     * Get top artists for a period
     */
    public static function get_top_artists($period = null, $limit = 100) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'plays_total';
        
        if ($period === null) {
            $period = self::get_current_period();
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT artist_id, SUM(play_count) as total_plays
             FROM $table 
             WHERE period = %s
             GROUP BY artist_id
             ORDER BY total_plays DESC
             LIMIT %d",
            $period,
            $limit
        ), ARRAY_A);
    }
    
    /**
     * Get top tracks for a period
     */
    public static function get_top_tracks($period = null, $limit = 100) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'plays_total';
        
        if ($period === null) {
            $period = self::get_current_period();
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT track_id, artist_id, play_count
             FROM $table 
             WHERE period = %s
             ORDER BY play_count DESC
             LIMIT %d",
            $period,
            $limit
        ), ARRAY_A);
    }
}
