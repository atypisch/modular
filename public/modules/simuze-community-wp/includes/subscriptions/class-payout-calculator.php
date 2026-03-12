<?php
/**
 * Payout Calculator
 * Calculates artist payouts from subscription pool based on play share
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Payout_Calculator {
    
    private static $instance = null;
    
    // Minimum payout threshold in cents
    const MIN_PAYOUT_THRESHOLD = 100; // €1.00
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {}
    
    /**
     * Calculate payouts for a period
     * 
     * @param string $period Period in YYYY-MM format
     * @return array Payout data per artist
     */
    public function calculate_payouts($period) {
        // Get total subscription pool for the period
        $pool_total = Simuze_Subscription::get_pool_total_for_period($period);
        
        if ($pool_total <= 0) {
            return array(
                'period' => $period,
                'pool_total' => 0,
                'total_plays' => 0,
                'payouts' => array(),
            );
        }
        
        // Get total subscription plays
        $total_plays = Simuze_Play_Tracker::get_total_subscription_plays($period);
        
        if ($total_plays <= 0) {
            return array(
                'period' => $period,
                'pool_total' => $pool_total,
                'total_plays' => 0,
                'payouts' => array(),
            );
        }
        
        // Get aggregated plays per artist
        $artist_plays = Simuze_Play_Tracker::get_aggregated_artist_plays($period);
        
        // Calculate per-play rate
        $rate_per_play = $pool_total / $total_plays;
        
        // Calculate payout for each artist
        $payouts = array();
        foreach ($artist_plays as $artist_id => $plays) {
            $amount = (int) round($plays * $rate_per_play);
            
            // Only include if above minimum threshold
            if ($amount >= self::MIN_PAYOUT_THRESHOLD) {
                $payouts[] = array(
                    'artist_id' => $artist_id,
                    'plays' => $plays,
                    'play_share' => round(($plays / $total_plays) * 100, 4),
                    'amount' => $amount,
                    'amount_display' => '€' . number_format($amount / 100, 2),
                );
            }
        }
        
        // Sort by amount descending
        usort($payouts, function($a, $b) {
            return $b['amount'] - $a['amount'];
        });
        
        return array(
            'period' => $period,
            'pool_total' => $pool_total,
            'pool_total_display' => '€' . number_format($pool_total / 100, 2),
            'total_plays' => $total_plays,
            'rate_per_play' => $rate_per_play,
            'rate_per_play_display' => '€' . number_format($rate_per_play / 100, 6),
            'payouts' => $payouts,
            'payout_count' => count($payouts),
        );
    }
    
    /**
     * Generate payout batch for SIMEZU
     * 
     * @param string $period Period in YYYY-MM format
     * @return array Batch data ready for SIMEZU API
     */
    public function generate_payout_batch($period) {
        global $wpdb;
        
        $calculation = $this->calculate_payouts($period);
        
        if (empty($calculation['payouts'])) {
            return new WP_Error('no_payouts', 'No payouts to process for this period');
        }
        
        // Generate unique batch ID for idempotency
        $batch_id = 'SUB_' . str_replace('-', '', $period) . '_' . wp_generate_password(8, false, false);
        
        $batch_items = array();
        $payouts_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'artist_payouts';
        
        foreach ($calculation['payouts'] as $payout) {
            // Get artist's SIMEZU account info
            $artist_account = $this->get_artist_simezu_account($payout['artist_id']);
            
            if (!$artist_account) {
                // Skip artists without SIMEZU accounts
                error_log("Simuze_Payout_Calculator: Artist {$payout['artist_id']} has no SIMEZU account, skipping");
                continue;
            }
            
            // Create payout record
            $payout_id = $this->create_payout_record($period, $payout, $batch_id);
            
            $batch_items[] = array(
                'recipient_id' => $artist_account['simezu_account_id'],
                'recipient_type' => 'artist',
                'simuze_artist_id' => $payout['artist_id'],
                'amount' => $payout['amount'],
                'currency' => 'EUR',
                'reference' => 'payout_' . $payout_id,
                'description' => sprintf(
                    'Subscription revenue share for %s (%d plays, %.4f%% share)',
                    date('F Y', strtotime($period . '-01')),
                    $payout['plays'],
                    $payout['play_share']
                ),
            );
        }
        
        if (empty($batch_items)) {
            return new WP_Error('no_valid_recipients', 'No artists with valid SIMEZU accounts');
        }
        
        return array(
            'batch_id' => $batch_id,
            'period' => $period,
            'pool_total' => $calculation['pool_total'],
            'total_plays' => $calculation['total_plays'],
            'item_count' => count($batch_items),
            'total_amount' => array_sum(array_column($batch_items, 'amount')),
            'items' => $batch_items,
        );
    }
    
    /**
     * Get artist's SIMEZU account
     */
    private function get_artist_simezu_account($artist_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE seller_type = 'artist' AND seller_id = %d AND is_verified = 1",
            $artist_id
        ), ARRAY_A);
    }
    
    /**
     * Create payout record in database
     */
    private function create_payout_record($period, $payout, $batch_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'artist_payouts';
        
        $wpdb->insert($table, array(
            'artist_id' => $payout['artist_id'],
            'period' => $period,
            'play_count' => $payout['plays'],
            'play_share' => $payout['play_share'],
            'amount' => $payout['amount'],
            'currency' => 'EUR',
            'batch_id' => $batch_id,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        ));
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get payout status for an artist
     */
    public static function get_artist_payouts($artist_id, $limit = 12) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'artist_payouts';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE artist_id = %d ORDER BY period DESC LIMIT %d",
            $artist_id,
            $limit
        ), ARRAY_A);
    }
    
    /**
     * Get pending payouts for a batch
     */
    public static function get_pending_payouts($batch_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'artist_payouts';
        
        $where = "status = 'pending'";
        $params = array();
        
        if ($batch_id) {
            $where .= " AND batch_id = %s";
            $params[] = $batch_id;
        }
        
        if ($params) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE $where ORDER BY created_at ASC",
                $params
            ), ARRAY_A);
        }
        
        return $wpdb->get_results(
            "SELECT * FROM $table WHERE $where ORDER BY created_at ASC",
            ARRAY_A
        );
    }
    
    /**
     * Update payout status
     */
    public static function update_payout_status($payout_id, $status, $simezu_reference = null, $error = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'artist_payouts';
        
        $data = array(
            'status' => $status,
            'updated_at' => current_time('mysql'),
        );
        
        if ($simezu_reference) {
            $data['simezu_payout_id'] = $simezu_reference;
        }
        
        if ($error) {
            $data['error_message'] = $error;
        }
        
        if ($status === 'completed') {
            $data['paid_at'] = current_time('mysql');
        }
        
        return $wpdb->update($table, $data, array('id' => $payout_id));
    }
}
