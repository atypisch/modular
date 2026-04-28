<?php
/**
 * Group Member Joined Event
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Event_Group_Member_Joined extends Simuze_Event {
    
    protected function get_event_type() {
        return 'group.member-joined';
    }
}
