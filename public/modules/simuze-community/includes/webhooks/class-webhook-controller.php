<?php
/**
 * WebhookController - Handles incoming webhooks from external systems
 * Validates, normalizes, and dispatches events via EventBus
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
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

    private function load_event_classes() {
        $events_dir = dirname(__FILE__) . '/../events/events';
        if (is_dir($events_dir)) {
            foreach (glob($events_dir . '/class-event-*.php') as $file) {
                require_once $file;
            }
        }
    }

    public function handle_webhook($request) {
        $url_parts     = explode('/', trim($request->get_route(), '/'));
        $webhook_index = array_search('webhooks', $url_parts);

        if ($webhook_index === false || !isset($url_parts[$webhook_index + 1]) || !isset($url_parts[$webhook_index + 2])) {
            return ['error' => 'invalid_webhook', 'message' => 'Invalid webhook URL format. Expected: /webhooks/{provider}/{event_type}', 'status' => 400];
        }

        $provider        = $url_parts[$webhook_index + 1];
        $event_type      = $url_parts[$webhook_index + 2];
        $full_event_type = $provider . '.' . $event_type;
        $payload         = $request->get_json_params();

        if (empty($provider) || empty($event_type)) {
            return ['error' => 'invalid_webhook', 'message' => 'Missing required parameters: provider and event_type', 'status' => 400];
        }

        if (!$this->verify_signature($request, $provider)) {
            return ['error' => 'invalid_signature', 'message' => 'Webhook signature verification failed', 'status' => 401];
        }

        $normalized_payload = $this->normalize_payload($payload, $provider, $full_event_type);
        $external_reference = $this->extract_external_reference($normalized_payload, $provider, $full_event_type);

        if (empty($external_reference)) {
            $external_reference = $provider . '-' . $event_type . '-' . time() . '-' . bin2hex(random_bytes(4));
        }

        $existing_webhook = $this->get_webhook_by_reference($external_reference);

        if ($existing_webhook) {
            if ($existing_webhook['status'] === 'processed') {
                return ['success' => true, 'message' => 'Webhook already processed', 'webhook_id' => $existing_webhook['id']];
            }
            $this->update_webhook_status($existing_webhook['id'], 'received');
            $webhook_id = $existing_webhook['id'];
        } else {
            $webhook_id = $this->store_webhook($provider, $full_event_type, $external_reference, $normalized_payload);
            if (!$webhook_id) {
                return ['error' => 'storage_failed', 'message' => 'Failed to store webhook', 'status' => 500];
            }
        }

        $event = $this->create_event($full_event_type, $normalized_payload, $provider, $external_reference);

        if (!$event) {
            $this->update_webhook_status($webhook_id, 'failed', 'Invalid event type');
            return ['error' => 'invalid_event_type', 'message' => 'Unknown event type: ' . $full_event_type, 'status' => 400];
        }

        try {
            $this->event_bus->dispatch($event);
            $this->update_webhook_status($webhook_id, 'processed');
            return ['success' => true, 'message' => 'Webhook processed successfully', 'webhook_id' => $webhook_id];
        } catch (\Exception $e) {
            $this->update_webhook_status($webhook_id, 'failed', $e->getMessage());
            return ['error' => 'processing_failed', 'message' => 'Failed to process webhook: ' . $e->getMessage(), 'status' => 500];
        }
    }

    private function verify_signature($request, $provider) {
        $signature = $request->get_header('X-Webhook-Signature');
        $secret    = $this->get_webhook_secret($provider);

        if (empty($secret)) {
            return md_hook_filter('simuze_webhook_allow_without_secret', false, $provider);
        }

        return md_hook_filter('simuze_webhook_verify_signature', true, $request, $provider, $signature, $secret);
    }

    private function get_webhook_secret($provider) {
        try {
            return md_db_var("SELECT option_value FROM settings WHERE option_name = ? LIMIT 1", ["simuze_webhook_secret_{$provider}"]) ?: '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function normalize_payload($payload, $provider, $event_type) {
        $normalized = $payload;
        $normalized = md_hook_filter("simuze_webhook_normalize_{$provider}", $normalized, $event_type);
        return md_hook_filter('simuze_webhook_normalize', $normalized, $provider, $event_type);
    }

    private function extract_external_reference($payload, $provider, $event_type) {
        $common_fields = ['id', 'event_id', 'webhook_id', 'reference', 'external_id', 'transaction_id'];

        if (is_array($payload)) {
            foreach ($common_fields as $field) {
                if (isset($payload[$field])) return $provider . '-' . $payload[$field];
            }
        } elseif (is_object($payload)) {
            foreach ($common_fields as $field) {
                if (isset($payload->$field)) return $provider . '-' . $payload->$field;
            }
        }

        return md_hook_filter("simuze_webhook_extract_reference_{$provider}", null, $payload, $event_type);
    }

    private function get_webhook_by_reference($external_reference) {
        return md_db_row("SELECT * FROM simuze_webhooks WHERE external_reference = ? LIMIT 1", [$external_reference]);
    }

    private function store_webhook($provider, $event_type, $external_reference, $payload) {
        return md_db_insert('simuze_webhooks', [
            'provider'           => $provider,
            'event_type'         => $event_type,
            'external_reference' => $external_reference,
            'payload'            => json_encode($payload),
            'status'             => 'received',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
    }

    private function update_webhook_status($webhook_id, $status, $error_message = null) {
        $data = ['status' => $status];
        if ($status === 'processed')  $data['processed_at'] = date('Y-m-d H:i:s');
        if ($error_message)           $data['error_message'] = $error_message;

        md_db_update('simuze_webhooks', $data, ['id' => $webhook_id]);
    }

    private function create_event($event_type, $payload, $provider, $external_reference) {
        $event_map = md_hook_filter('simuze_webhook_event_map', [
            'payment.intent-created'     => 'Simuze_Event_Payment_Intent_Created',
            'payment.intent-succeeded'   => 'Simuze_Event_Payment_Intent_Succeeded',
            'payment.intent-failed'      => 'Simuze_Event_Payment_Intent_Failed',
            'payment.completed'          => 'Simuze_Event_Payment_Completed',
            'media.upload-received'      => 'Simuze_Event_Media_Upload_Received',
            'media.processing-completed' => 'Simuze_Event_Media_Processing_Completed',
            'group.member-joined'        => 'Simuze_Event_Group_Member_Joined',
            'group.member-left'          => 'Simuze_Event_Group_Member_Left',
            'release.published'          => 'Simuze_Event_Release_Published',
        ]);

        if (!isset($event_map[$event_type])) return null;

        $event_class = $event_map[$event_type];
        if (!class_exists($event_class)) return null;

        return new $event_class($payload, $provider, $external_reference);
    }
}
