<?php
/**
 * Payment Completed Event (alias for intent-succeeded for backward compatibility)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Event_Payment_Completed extends Simuze_Event {
    
    protected function get_event_type() {
        return 'payment.completed';
    }
}
