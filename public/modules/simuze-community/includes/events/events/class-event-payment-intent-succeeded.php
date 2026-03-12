<?php
/**
 * Payment Intent Succeeded Event
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Event_Payment_Intent_Succeeded extends Simuze_Event {
    
    protected function get_event_type() {
        return 'payment.intent-succeeded';
    }
}
