<?php
/**
 * Base Action Handler interface
 * All action handlers must implement this interface
 */

if (!defined('ABSPATH')) {
    exit;
}

interface Simuze_Action_Handler {
    
    /**
     * Get the event types this handler responds to
     * @return array Array of event type strings
     */
    public function get_event_types();
    
    /**
     * Handle the event
     * @param Simuze_Event $event The event to handle
     */
    public function handle(Simuze_Event $event);
}
