<?php
/**
 * EventBus - Central event dispatching system
 * Handles event registration and dispatching to action handlers
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simuze_EventBus {
    
    private static $instance = null;
    private $handlers = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Auto-register action handlers
        $this->auto_register_handlers();
    }
    
    /**
     * Auto-register all action handlers in the actions directory
     */
    private function auto_register_handlers() {
        // Load action handler interface
        require_once dirname(__FILE__) . '/class-action-handler.php';
        
        $actions_dir = dirname(__FILE__) . '/actions';
        if (!is_dir($actions_dir)) {
            return;
        }
        
        $files = glob($actions_dir . '/class-action-*.php');
        foreach ($files as $file) {
            require_once $file;
            
            // Convert filename to class name
            // class-action-payment-unlock-content.php -> Simuze_Action_Payment_Unlock_Content
            $class_name = basename($file, '.php');
            $class_name = str_replace('class-action-', '', $class_name);
            $parts = explode('-', $class_name);
            $parts = array_map('ucfirst', $parts);
            $class_name = 'Simuze_Action_' . implode('_', $parts);
            
            if (class_exists($class_name)) {
                $handler = new $class_name();
                if ($handler instanceof Simuze_Action_Handler) {
                    $this->register_handler($handler);
                }
            }
        }
    }
    
    /**
     * Register an action handler for specific event types
     */
    public function register_handler(Simuze_Action_Handler $handler) {
        $event_types = $handler->get_event_types();
        foreach ($event_types as $event_type) {
            if (!isset($this->handlers[$event_type])) {
                $this->handlers[$event_type] = array();
            }
            $this->handlers[$event_type][] = $handler;
        }
    }
    
    /**
     * Dispatch an event to all registered handlers
     */
    public function dispatch(Simuze_Event $event) {
        $event_type = $event->get_type();
        
        if (!isset($this->handlers[$event_type])) {
            error_log("Simuze_EventBus: No handlers registered for event type: {$event_type}");
            return;
        }
        
        foreach ($this->handlers[$event_type] as $handler) {
            try {
                $handler->handle($event);
            } catch (Exception $e) {
                error_log("Simuze_EventBus: Error in handler " . get_class($handler) . ": " . $e->getMessage());
                // Continue to next handler even if one fails
            }
        }
    }
    
    /**
     * Get all registered handlers for an event type
     */
    public function get_handlers($event_type) {
        return isset($this->handlers[$event_type]) ? $this->handlers[$event_type] : array();
    }
}
