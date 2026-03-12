<?php
/**
 * WebhookController - Handles incoming webhooks from external systems
 * Validates, normalizes, and dispatches events via EventBus
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/../events/class-event-bus.php';
require_once dirname(__FILE__) . '/../events/class-event.php';

class Simuze_WebhookController {
    
    private static $instance = null;
    private $event_bus;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->event_bus = Simuze_EventBus::get_instance();
        $this->load_event_classes();
    }
    
    /**
     * Load all event classes
     */
    private function load_event_classes() {
        $events_dir = dirname(__FILE__) . '/../events/events';
        if (is_dir($events_dir)) {
            $files = glob($events_dir . '/class-event-*.php');
            foreach ($files as $file) {
                require_once $file;
            }
        }
    }
    
    /**
     * Handle incoming webhook
     */
    public function handle_webhook($request) {
        // Extract provider and event_type from URL path
        // URL format: /webhooks/{provider}/{event_type}
        $url_parts = explode('/', trim($request->get_route(), '/'));
        
        // Find 'webhooks' in the path and extract what follows
        $webhook_index = array_search('webhooks', $url_parts);
        if ($webhook_index === false || !isset($url_parts[$webhook_index + 1]) || !isset($url_parts[$webhook_index + 2])) {
            return new WP_Error(
                'invalid_webhook',
                'Invalid webhook URL format. Expected: /webhooks/{provider}/{event_type}',
                array('status' => 400)
            );
        }
        
        $provider = $url_parts[$webhook_index + 1];
        $event_type = $url_parts[$webhook_index + 2];
        
        // Combine provider and event_type for full event type
        $full_event_type = $provider . '.' . $event_type;
        
        $payload = $request->get_json_params();
        
        // Validate required parameters
        if (empty($provider) || empty($event_type)) {
            return new WP_Error(
                'invalid_webhook',
                'Missing required parameters: provider and event_type',
                array('status' => 400)
            );
        }
        
        // Verify signature (stub - implement actual verification per provider)
        $signature_valid = $this->verify_signature($request, $provider);
        if (!$signature_valid) {
            return new WP_Error(
                'invalid_signature',
                'Webhook signature verification failed',
                array('status' => 401)
            );
        }
        
        // Normalize payload
        $normalized_payload = $this->normalize_payload($payload, $provider, $full_event_type);
        
        // Extract external reference for idempotency
        $external_reference = $this->extract_external_reference($normalized_payload, $provider, $full_event_type);
        
        if (empty($external_reference)) {
            // Generate a fallback reference if not provided
            $external_reference = $provider . '-' . $event_type . '-' . time() . '-' . wp_generate_password(8, false);
        }
        
        // Check idempotency
        $existing_webhook = $this->get_webhook_by_reference($external_reference);
        if ($existing_webhook) {
            // Webhook already processed
            if ($existing_webhook->status === 'processed') {
                return rest_ensure_response(array(
                    'success' => true,
                    'message' => 'Webhook already processed',
                    'webhook_id' => $existing_webhook->id
                ));
            }
            
            // Update existing webhook if it failed before
            $this->update_webhook_status($existing_webhook->id, 'received');
            $webhook_id = $existing_webhook->id;
        } else {
            // Store webhook
            $webhook_id = $this->store_webhook($provider, $full_event_type, $external_reference, $normalized_payload);
            if (!$webhook_id) {
                return new WP_Error(
                    'storage_failed',
                    'Failed to store webhook',
                    array('status' => 500)
                );
            }
        }
        
        // Create event instance
        $event = $this->create_event($full_event_type, $normalized_payload, $provider, $external_reference);
        
        if (!$event) {
            $this->update_webhook_status($webhook_id, 'failed', 'Invalid event type');
            return new WP_Error(
                'invalid_event_type',
                'Unknown event type: ' . $full_event_type,
                array('status' => 400)
            );
        }
        
        // Dispatch event via EventBus
        try {
            $this->event_bus->dispatch($event);
            
            // Mark webhook as processed
            $this->update_webhook_status($webhook_id, 'processed');
            
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Webhook processed successfully',
                'webhook_id' => $webhook_id
            ));
            
        } catch (Exception $e) {
            // Mark webhook as failed
            $this->update_webhook_status($webhook_id, 'failed', $e->getMessage());
            
            return new WP_Error(
                'processing_failed',
                'Failed to process webhook: ' . $e->getMessage(),
                array('status' => 500)
            );
        }
    }
    
    /**
     * Verify webhook signature (stub - implement per provider)
     */
    private function verify_signature($request, $provider) {
        // Stub implementation - implement actual signature verification per provider
        // For example: Stripe, PayPal, etc. each have different signature verification methods
        
        $signature = $request->get_header('X-Webhook-Signature');
        $secret = $this->get_webhook_secret($provider);
        
        if (empty($secret)) {
            // If no secret configured, allow webhook (for development/testing)
            // In production, always require signature verification
            return apply_filters('simuze_webhook_allow_without_secret', false, $provider);
        }
        
        // Implement actual signature verification here
        // This is a stub that always returns true for now
        return apply_filters('simuze_webhook_verify_signature', true, $request, $provider, $signature, $secret);
    }
    
    /**
     * Get webhook secret for provider
     */
    private function get_webhook_secret($provider) {
        return get_option("simuze_webhook_secret_{$provider}", '');
    }
    
    /**
     * Normalize payload from different providers to a common format
     */
    private function normalize_payload($payload, $provider, $event_type) {
        // Default normalization - just return as-is
        $normalized = $payload;
        
        // Provider-specific normalization can be added here
        $normalized = apply_filters("simuze_webhook_normalize_{$provider}", $normalized, $event_type);
        $normalized = apply_filters('simuze_webhook_normalize', $normalized, $provider, $event_type);
        
        return $normalized;
    }
    
    /**
     * Extract external reference from payload for idempotency
     */
    private function extract_external_reference($payload, $provider, $event_type) {
        // Try common field names
        $common_fields = array('id', 'event_id', 'webhook_id', 'reference', 'external_id', 'transaction_id');
        
        if (is_array($payload)) {
            foreach ($common_fields as $field) {
                if (isset($payload[$field])) {
                    return $provider . '-' . $payload[$field];
                }
            }
        } elseif (is_object($payload)) {
            foreach ($common_fields as $field) {
                if (isset($payload->$field)) {
                    return $provider . '-' . $payload->$field;
                }
            }
        }
        
        // Provider-specific extraction
        $reference = apply_filters("simuze_webhook_extract_reference_{$provider}", null, $payload, $event_type);
        if ($reference) {
            return $reference;
        }
        
        return null;
    }
    
    /**
     * Get webhook by external reference
     */
    private function get_webhook_by_reference($external_reference) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'webhooks';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE external_reference = %s LIMIT 1",
            $external_reference
        ));
    }
    
    /**
     * Store webhook in database
     */
    private function store_webhook($provider, $event_type, $external_reference, $payload) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'webhooks';
        
        $result = $wpdb->insert(
            $table,
            array(
                'provider' => $provider,
                'event_type' => $event_type,
                'external_reference' => $external_reference,
                'payload' => json_encode($payload),
                'status' => 'received',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log("Simuze_WebhookController: Failed to store webhook: " . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update webhook status
     */
    private function update_webhook_status($webhook_id, $status, $error_message = null) {
        global $wpdb;
        $table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'webhooks';
        
        $data = array('status' => $status);
        $format = array('%s');
        
        if ($status === 'processed') {
            $data['processed_at'] = current_time('mysql');
            $format[] = '%s';
        }
        
        if ($error_message) {
            $data['error_message'] = $error_message;
            $format[] = '%s';
        }
        
        $wpdb->update(
            $table,
            $data,
            array('id' => $webhook_id),
            $format,
            array('%d')
        );
    }
    
    /**
     * Create event instance from event type
     */
    private function create_event($event_type, $payload, $provider, $external_reference) {
        // Map event types to event classes
        $event_map = array(
            'payment.intent-created' => 'Simuze_Event_Payment_Intent_Created',
            'payment.intent-succeeded' => 'Simuze_Event_Payment_Intent_Succeeded',
            'payment.intent-failed' => 'Simuze_Event_Payment_Intent_Failed',
            'payment.completed' => 'Simuze_Event_Payment_Completed',
            'media.upload-received' => 'Simuze_Event_Media_Upload_Received',
            'media.processing-completed' => 'Simuze_Event_Media_Processing_Completed',
            'group.member-joined' => 'Simuze_Event_Group_Member_Joined',
            'group.member-left' => 'Simuze_Event_Group_Member_Left',
            'release.published' => 'Simuze_Event_Release_Published',
        );
        
        // Allow filtering of event map
        $event_map = apply_filters('simuze_webhook_event_map', $event_map);
        
        if (!isset($event_map[$event_type])) {
            return null;
        }
        
        $event_class = $event_map[$event_type];
        
        if (!class_exists($event_class)) {
            return null;
        }
        
        return new $event_class($payload, $provider, $external_reference);
    }
}
