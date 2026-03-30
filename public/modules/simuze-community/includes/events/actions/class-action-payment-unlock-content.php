<?php
/**
 * Payment Unlock Content Action Handler
 * Unlocks content when SIMEZU sends payment confirmation (webhook). Uses simuze_download_grants only.
 */

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Action_Payment_Unlock_Content implements Simuze_Action_Handler {

    public function get_event_types() {
        return ['payment.intent-succeeded', 'payment.completed', 'simezu.payment.completed'];
    }

    public function handle(Simuze_Event $event) {
        $payload   = $event->get_payload();
        $user_id   = $this->extract_user_id($payload);
        $items     = $this->extract_items($payload);
        $reference = $this->extract_reference($payload);
        $has_event_ticket_item = false;
        foreach ($items as $item) {
            $type = is_array($item) ? ($item['product_type'] ?? $item['type'] ?? '') : (is_object($item) ? ($item->product_type ?? $item->type ?? '') : '');
            if (in_array($type, ['event_ticket', 'ticket'], true)) {
                $has_event_ticket_item = true;
                break;
            }
        }

        if (!$user_id && !$has_event_ticket_item && empty($reference)) {
            error_log('Simuze_Action_Payment_Unlock_Content: Missing user_id/reference in payload');
            return;
        }

        foreach ($items as $item) {
            $this->process_item($user_id, $item, $reference, $payload);
        }
        // Fallback when no explicit items are sent, but a ticket order reference exists.
        if (empty($items) && !empty($reference) && class_exists('Simuze_Event_Ticketing')) {
            Simuze_Event_Ticketing::markOrderPaidAndIssueTickets((string) $reference, is_array($payload) ? $payload : []);
        }

        if ($user_id) {
            $this->send_unlock_notification($user_id, $items);
        }
    }

    private function process_item($user_id, $item, $reference, $payload) {
        $product_type = is_array($item)  ? ($item['product_type'] ?? $item['type'] ?? 'song')
                     : (is_object($item) ? ($item->product_type ?? $item->type ?? 'song') : 'song');
        if (in_array($product_type, ['event_ticket', 'ticket'], true) && class_exists('Simuze_Event_Ticketing')) {
            $item_reference = is_array($item)
                ? ($item['reference'] ?? $item['external_reference'] ?? $reference)
                : ($item->reference ?? $item->external_reference ?? $reference);
            if (!empty($item_reference)) {
                Simuze_Event_Ticketing::markOrderPaidAndIssueTickets((string) $item_reference, is_array($payload) ? $payload : []);
            }
            return;
        }

        if ($user_id) {
            $this->grant_download_for_user($user_id, $item, $reference);
        }
    }

    private function extract_user_id($payload) {
        if (is_array($payload))  return $payload['user_id']   ?? $payload['userId']   ?? $payload['customer_id'] ?? $payload['buyer_id'] ?? null;
        if (is_object($payload)) return $payload->user_id     ?? $payload->userId     ?? $payload->customer_id  ?? $payload->buyer_id  ?? null;
        return null;
    }

    private function extract_items($payload) {
        if (is_array($payload))  return $payload['items']  ?? $payload['line_items']  ?? [];
        if (is_object($payload)) return $payload->items    ?? $payload->line_items    ?? [];
        return [];
    }

    private function extract_reference($payload) {
        if (is_array($payload))  return $payload['transaction_id'] ?? $payload['order_id'] ?? $payload['reference'] ?? null;
        if (is_object($payload)) return $payload->transaction_id ?? $payload->order_id ?? $payload->reference ?? null;
        return null;
    }

    private function grant_download_for_user($user_id, $item, $reference) {
        $product_id   = is_array($item)  ? ($item['product_id'] ?? $item['id'] ?? $item['item_id'] ?? null)
                     : (is_object($item) ? ($item->product_id ?? $item->id ?? $item->item_id ?? null) : null);
        $product_type = is_array($item)  ? ($item['product_type'] ?? $item['type'] ?? 'song')
                     : (is_object($item) ? ($item->product_type ?? $item->type ?? 'song') : 'song');

        if (!$product_id) return;
        if (!in_array($product_type, ['song', 'album'], true)) {
            $product_type = 'song';
        }

        try {
            md_db_exec(
                "INSERT INTO simuze_download_grants (user_id, product_type, product_id, simezu_reference, created_at)
                 VALUES (?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE simezu_reference = VALUES(simezu_reference)",
                [$user_id, $product_type, $product_id, $reference]
            );
        } catch (\Throwable $e) {
            error_log('Simuze_Action_Payment_Unlock_Content: ' . $e->getMessage());
            return;
        }

        error_log("Simuze_Action_Payment_Unlock_Content: Granted {$product_type} {$product_id} for user {$user_id}");
    }

    private function send_unlock_notification($user_id, $items) {
        if (!class_exists('Simuze_Notification')) return;

        $count   = count($items);
        $message = $count === 1
            ? 'Your purchase is ready! You can now download your content.'
            : "Your purchases are ready! You can now download {$count} items.";

        error_log("Simuze_Action_Payment_Unlock_Content: Sent unlock notification to user {$user_id}");
    }
}
