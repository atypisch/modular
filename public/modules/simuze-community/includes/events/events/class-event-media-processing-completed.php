<?php
/**
 * Media Processing Completed Event
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Event_Media_Processing_Completed extends Simuze_Event {
    
    protected function get_event_type() {
        return 'media.processing-completed';
    }
}
