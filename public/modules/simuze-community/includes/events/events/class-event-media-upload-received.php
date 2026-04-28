<?php
/**
 * Media Upload Received Event
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_Event_Media_Upload_Received extends Simuze_Event {
    
    protected function get_event_type() {
        return 'media.upload-received';
    }
}
