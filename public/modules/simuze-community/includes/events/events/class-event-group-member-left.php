<?php
/**
 * Group Member Left Event
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Event_Group_Member_Left extends Simuze_Event {
    
    protected function get_event_type() {
        return 'group.member-left';
    }
}
