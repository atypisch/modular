<?php
/**
 * Release Published Event
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Event_Release_Published extends Simuze_Event {
    
    protected function get_event_type() {
        return 'release.published';
    }
}
