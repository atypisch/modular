<?php
/**
 * Payment Intent Failed Event
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Event_Payment_Intent_Failed extends Simuze_Event {
    
    protected function get_event_type() {
        return 'payment.intent-failed';
    }
}
