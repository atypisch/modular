<?php
/**
 * Payout system for artists
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Payout {
    
    /**
     * Request payout
     */
    public static function request_payout($seller_type, $seller_id, $amount) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'payouts';
        
        // Get seller account
        $account = Simuze_Seller_Account::get_by_seller($seller_type, $seller_id);
        if (!$account) {
            return array(
                'success' => false,
                'error' => 'Seller account not found',
            );
        }
        
        // Validate amount
        $available_balance = $account->total_earnings - $account->total_paid_out - $account->pending_payout;
        
        if ($amount <= 0) {
            return array(
                'success' => false,
                'error' => 'Invalid payout amount',
            );
        }
        
        if ($amount > $available_balance) {
            return array(
                'success' => false,
                'error' => 'Insufficient balance. Available: ' . number_format($available_balance, 2),
            );
        }
        
        if ($amount < $account->payout_threshold) {
            return array(
                'success' => false,
                'error' => sprintf('Minimum payout amount is %s', number_format($account->payout_threshold, 2)),
            );
        }
        
        // Check if account is verified
        if (!$account->is_verified) {
            return array(
                'success' => false,
                'error' => 'Seller account must be verified before requesting payouts',
            );
        }
        
        // Create payout request
        $data = array(
            'seller_type' => $seller_type,
            'seller_id' => $seller_id,
            'amount' => $amount,
            'status' => 'pending',
            'payment_provider' => $account->payment_provider,
            'provider_account_id' => $account->provider_account_id,
            'created_at' => current_time('mysql'),
        );
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            $payout_id = $wpdb->insert_id;
            
            // Update seller account pending payout
            $wpdb->update(
                $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts',
                array('pending_payout' => $account->pending_payout + $amount),
                array('id' => $account->id),
                array('%f'),
                array('%d')
            );
            
            // Record activity
            Simuze_Activity::record_user_activity(
                $seller_id,
                'payout_requested',
                sprintf('Requested payout of %s', number_format($amount, 2)),
                'commerce',
                $payout_id
            );
            
            return array(
                'success' => true,
                'payout_id' => $payout_id,
                'message' => 'Payout request submitted successfully',
            );
        }
        
        return array(
            'success' => false,
            'error' => 'Failed to create payout request',
        );
    }
    
    /**
     * Process payout (admin function)
     */
    public static function process_payout($payout_id, $transaction_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'payouts';
        
        $payout = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $payout_id
        ), ARRAY_A);
        
        if (!$payout) {
            return array('success' => false, 'error' => 'Payout not found');
        }
        
        if ($payout['status'] !== 'pending') {
            return array('success' => false, 'error' => 'Payout is not pending');
        }
        
        // Update payout status
        $update_data = array(
            'status' => 'completed',
            'processed_at' => current_time('mysql'),
        );
        
        if ($transaction_id) {
            $update_data['transaction_id'] = $transaction_id;
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $payout_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result) {
            // Update seller account
            $account = Simuze_Seller_Account::get_by_seller($payout['seller_type'], $payout['seller_id']);
            if ($account) {
                $wpdb->update(
                    $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts',
                    array(
                        'pending_payout' => max(0, $account->pending_payout - $payout['amount']),
                        'total_paid_out' => $account->total_paid_out + $payout['amount'],
                        'last_payout_at' => current_time('mysql'),
                    ),
                    array('id' => $account->id),
                    array('%f', '%f', '%s'),
                    array('%d')
                );
            }
            
            return array('success' => true, 'message' => 'Payout processed successfully');
        }
        
        return array('success' => false, 'error' => 'Failed to process payout');
    }
    
    /**
     * Cancel payout request
     */
    public static function cancel_payout($payout_id, $seller_id) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'payouts';
        
        $payout = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND seller_id = %d",
            $payout_id,
            $seller_id
        ), ARRAY_A);
        
        if (!$payout) {
            return array('success' => false, 'error' => 'Payout not found');
        }
        
        if ($payout['status'] !== 'pending') {
            return array('success' => false, 'error' => 'Only pending payouts can be cancelled');
        }
        
        $result = $wpdb->update(
            $table,
            array('status' => 'cancelled', 'cancelled_at' => current_time('mysql')),
            array('id' => $payout_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result) {
            // Update seller account
            $account = Simuze_Seller_Account::get_by_seller($payout['seller_type'], $payout['seller_id']);
            if ($account) {
                $wpdb->update(
                    $wpdb->prefix . SIMUZE_DB_PREFIX . 'seller_accounts',
                    array('pending_payout' => max(0, $account->pending_payout - $payout['amount'])),
                    array('id' => $account->id),
                    array('%f'),
                    array('%d')
                );
            }
            
            return array('success' => true, 'message' => 'Payout cancelled');
        }
        
        return array('success' => false, 'error' => 'Failed to cancel payout');
    }
    
    /**
     * Get payout history
     */
    public static function get_payout_history($seller_type, $seller_id, $args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'payouts';
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'status' => null,
        );
        
        $args = array_merge($defaults, $args);
        
        $where = "seller_type = %s AND seller_id = %d";
        $where_values = array($seller_type, $seller_id);
        
        if ($args['status']) {
            $where .= " AND status = %s";
            $where_values[] = $args['status'];
        }
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        $where_values[] = $args['per_page'];
        $where_values[] = $offset;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
            WHERE $where
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
            $where_values
        ), ARRAY_A);
        
        return $results;
    }
    
    /**
     * Get available balance for payout
     */
    public static function get_available_balance($seller_type, $seller_id) {
        $account = Simuze_Seller_Account::get_by_seller($seller_type, $seller_id);
        if (!$account) {
            return 0;
        }
        
        return max(0, $account->total_earnings - $account->total_paid_out - $account->pending_payout);
    }
}








