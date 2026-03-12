<?php
/**
 * Subscription Cron Jobs
 * Monthly aggregation and payout processing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Subscription_Cron {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Register cron hooks
        add_action('simuze_monthly_payout_calculation', array($this, 'run_monthly_payout_calculation'));
        add_action('simuze_retry_failed_payouts', array($this, 'run_retry_failed_payouts'));
        
        // Schedule cron jobs on plugin activation
        add_action('init', array($this, 'schedule_cron_jobs'));
    }
    
    /**
     * Schedule cron jobs if not already scheduled
     */
    public function schedule_cron_jobs() {
        // Monthly payout calculation - runs on 1st of each month at 2:00 AM
        if (!wp_next_scheduled('simuze_monthly_payout_calculation')) {
            // Calculate next 1st of month at 2:00 AM
            $next_run = strtotime('first day of next month 02:00:00');
            wp_schedule_event($next_run, 'monthly', 'simuze_monthly_payout_calculation');
        }
        
        // Retry failed payouts - runs daily at 3:00 AM
        if (!wp_next_scheduled('simuze_retry_failed_payouts')) {
            $next_run = strtotime('tomorrow 03:00:00');
            wp_schedule_single_event($next_run, 'simuze_retry_failed_payouts');
        }
    }
    
    /**
     * Run monthly payout calculation
     * Called by cron on the 1st of each month
     */
    public function run_monthly_payout_calculation() {
        // Calculate for previous month
        $period = date('Y-m', strtotime('first day of last month'));
        
        error_log("Simuze_Subscription_Cron: Starting monthly payout calculation for {$period}");
        
        try {
            // Generate payout batch
            $calculator = Simuze_Payout_Calculator::get_instance();
            $batch = $calculator->generate_payout_batch($period);
            
            if (is_wp_error($batch)) {
                error_log("Simuze_Subscription_Cron: Failed to generate batch - " . $batch->get_error_message());
                return;
            }
            
            error_log("Simuze_Subscription_Cron: Generated batch {$batch['batch_id']} with {$batch['item_count']} items, total €" . number_format($batch['total_amount'] / 100, 2));
            
            // Send to SIMEZU
            $payout_api = Simuze_Subscription_Payout_API::get_instance();
            $result = $payout_api->send_payout_batch($batch);
            
            if (is_wp_error($result)) {
                error_log("Simuze_Subscription_Cron: Failed to send batch - " . $result->get_error_message());
                return;
            }
            
            error_log("Simuze_Subscription_Cron: Batch sent successfully - " . json_encode($result));
            
            // Store batch summary
            $this->store_batch_summary($period, $batch, $result);
            
        } catch (Exception $e) {
            error_log("Simuze_Subscription_Cron: Exception - " . $e->getMessage());
        }
    }
    
    /**
     * Run retry for failed payouts
     */
    public function run_retry_failed_payouts() {
        error_log("Simuze_Subscription_Cron: Starting retry for failed payouts");
        
        try {
            $payout_api = Simuze_Subscription_Payout_API::get_instance();
            $results = $payout_api->retry_failed_payouts();
            
            error_log("Simuze_Subscription_Cron: Retry results - " . json_encode($results));
            
        } catch (Exception $e) {
            error_log("Simuze_Subscription_Cron: Exception during retry - " . $e->getMessage());
        }
        
        // Reschedule for tomorrow
        wp_schedule_single_event(strtotime('tomorrow 03:00:00'), 'simuze_retry_failed_payouts');
    }
    
    /**
     * Store batch summary for reporting
     */
    private function store_batch_summary($period, $batch, $result) {
        $summaries = get_option('simuze_payout_batch_summaries', array());
        
        $summaries[$period] = array(
            'batch_id' => $batch['batch_id'],
            'pool_total' => $batch['pool_total'],
            'total_plays' => $batch['total_plays'],
            'item_count' => $batch['item_count'],
            'total_amount' => $batch['total_amount'],
            'simezu_batch_id' => $result['simezu_batch_id'] ?? null,
            'status' => $result['status'] ?? 'unknown',
            'created_at' => current_time('mysql'),
        );
        
        // Keep only last 24 months
        if (count($summaries) > 24) {
            ksort($summaries);
            $summaries = array_slice($summaries, -24, 24, true);
        }
        
        update_option('simuze_payout_batch_summaries', $summaries);
    }
    
    /**
     * Manual trigger for payout calculation (admin use)
     */
    public static function trigger_payout_calculation($period = null) {
        if (!$period) {
            $period = date('Y-m', strtotime('first day of last month'));
        }
        
        $instance = self::get_instance();
        
        // Override period for manual trigger
        add_filter('simuze_payout_period', function() use ($period) {
            return $period;
        });
        
        $instance->run_monthly_payout_calculation();
    }
    
    /**
     * Get batch summaries for reporting
     */
    public static function get_batch_summaries($limit = 12) {
        $summaries = get_option('simuze_payout_batch_summaries', array());
        krsort($summaries); // Most recent first
        return array_slice($summaries, 0, $limit, true);
    }
    
    /**
     * Unschedule cron jobs (for plugin deactivation)
     */
    public static function unschedule_cron_jobs() {
        wp_clear_scheduled_hook('simuze_monthly_payout_calculation');
        wp_clear_scheduled_hook('simuze_retry_failed_payouts');
    }
}

// Register monthly schedule if not exists
add_filter('cron_schedules', function($schedules) {
    if (!isset($schedules['monthly'])) {
        $schedules['monthly'] = array(
            'interval' => 30 * DAY_IN_SECONDS,
            'display' => 'Once Monthly',
        );
    }
    return $schedules;
});
