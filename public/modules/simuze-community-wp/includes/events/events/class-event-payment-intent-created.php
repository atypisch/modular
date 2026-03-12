<?php
/**
 * Payment Intent Created Event
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Event_Payment_Intent_Created extends Simuze_Event {
    
    protected function get_event_type() {
        return 'payment.intent-created';
    }
}
