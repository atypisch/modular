<?php
/**
 * Base Event class
 * All events must extend this class
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class Simuze_Event {
    
    protected $type;
    protected $payload;
    protected $provider;
    protected $external_reference;
    protected $timestamp;
    
    public function __construct($payload, $provider = null, $external_reference = null) {
        $this->payload = $payload;
        $this->provider = $provider;
        $this->external_reference = $external_reference;
        $this->timestamp = current_time('mysql');
        $this->type = $this->get_event_type();
    }
    
    /**
     * Get the event type (must be implemented by child classes)
     */
    abstract protected function get_event_type();
    
    /**
     * Get event type
     */
    public function get_type() {
        return $this->type;
    }
    
    /**
     * Get payload
     */
    public function get_payload() {
        return $this->payload;
    }
    
    /**
     * Get provider
     */
    public function get_provider() {
        return $this->provider;
    }
    
    /**
     * Get external reference
     */
    public function get_external_reference() {
        return $this->external_reference;
    }
    
    /**
     * Get timestamp
     */
    public function get_timestamp() {
        return $this->timestamp;
    }
    
    /**
     * Get a specific value from payload
     */
    public function get($key, $default = null) {
        if (is_array($this->payload) && isset($this->payload[$key])) {
            return $this->payload[$key];
        }
        if (is_object($this->payload) && isset($this->payload->$key)) {
            return $this->payload->$key;
        }
        return $default;
    }
}
