<?php
/**
 * Event ticketing service.
 * Handles checkout init payload, order upsert, and ticket issuance.
 */
if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

class Simuze_Event_Ticketing {

    public static function getCheckoutBaseUrl(): string {
        $api_url = md_db_var("SELECT option_value FROM settings WHERE option_name = 'simezu_api_url' LIMIT 1") ?: '';
        if ($api_url === '') {
            return '';
        }
        return rtrim($api_url, '/') . '/checkout';
    }

    public static function initCheckout(int $event_id, int $quantity, ?int $buyer_user_id, ?string $buyer_email, ?string $buyer_name = null): array {
        $event = EventRepository::findById($event_id);
        if (!$event) {
            return ['success' => false, 'message' => 'Show not found'];
        }
        if (empty($event['ticket_sales_enabled']) || (float) ($event['ticket_price'] ?? 0) <= 0) {
            return ['success' => false, 'message' => 'Ticket sales not enabled for this show'];
        }

        $quantity = max(1, min(10, $quantity));
        $price = (float) $event['ticket_price'];
        $currency = !empty($event['ticket_currency']) ? strtoupper((string) $event['ticket_currency']) : 'EUR';

        $reference = self::buildReference($event_id);
        $order_id = self::createPendingOrder(
            $event_id,
            $buyer_user_id,
            $buyer_email,
            $quantity,
            $price,
            $currency,
            $reference,
            ['event_slug' => $event['slug'] ?? null],
            $buyer_name
        );
        if (!$order_id) {
            return ['success' => false, 'message' => 'Could not initialize order'];
        }

        $checkout_base = self::getCheckoutBaseUrl();
        if ($checkout_base === '') {
            return ['success' => false, 'message' => 'SIMEZU API URL not configured'];
        }
        $params = [
            'flow' => 'event_ticket',
            'event_id' => $event_id,
            'reference' => $reference,
            'qty' => $quantity,
            'unit_price' => number_format($price, 2, '.', ''),
            'currency' => $currency,
        ];
        if ($buyer_user_id) {
            $params['buyer_id'] = $buyer_user_id;
        }
        if (!empty($buyer_email)) {
            $params['email'] = $buyer_email;
        }

        return [
            'success' => true,
            'order_id' => (int) $order_id,
            'reference' => $reference,
            'redirect_url' => $checkout_base . '?' . http_build_query($params),
        ];
    }

    public static function createPendingOrder(
        int $event_id,
        ?int $buyer_user_id,
        ?string $buyer_email,
        int $quantity,
        float $unit_price,
        string $currency,
        string $reference,
        array $metadata = [],
        ?string $buyer_name = null
    ): ?int {
        $now = date('Y-m-d H:i:s');
        $quantity = max(1, min(10, $quantity));
        $total = $quantity * max(0, $unit_price);
        $existing = md_db_row('SELECT id FROM simuze_event_orders WHERE external_reference = ? LIMIT 1', [$reference]);
        if ($existing) {
            return (int) $existing['id'];
        }
        md_db_insert('simuze_event_orders', [
            'event_id' => $event_id,
            'buyer_user_id' => $buyer_user_id ?: null,
            'buyer_name' => $buyer_name ? mb_substr(trim($buyer_name), 0, 255) : null,
            'buyer_email' => $buyer_email ?: null,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'total_price' => $total,
            'currency' => strtoupper($currency ?: 'EUR'),
            'status' => 'pending',
            'external_reference' => $reference,
            'payment_provider' => 'simezu',
            'metadata' => !empty($metadata) ? json_encode($metadata) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $id = md_db_var('SELECT id FROM simuze_event_orders WHERE external_reference = ? LIMIT 1', [$reference]);
        return $id ? (int) $id : null;
    }

    public static function markOrderPaidAndIssueTickets(string $reference, array $payment_payload = []): ?array {
        $order = md_db_row('SELECT * FROM simuze_event_orders WHERE external_reference = ? LIMIT 1', [$reference]);
        if (!$order) {
            return null;
        }
        if (($order['status'] ?? '') === 'paid') {
            $existing_tickets = md_db_query('SELECT * FROM simuze_event_tickets WHERE order_id = ? ORDER BY id ASC', [(int) $order['id']]) ?: [];
            return ['order' => $order, 'tickets' => $existing_tickets];
        }

        md_db_update('simuze_event_orders', [
            'status' => 'paid',
            'metadata' => json_encode(array_merge(
                is_string($order['metadata'] ?? null) ? (json_decode((string) $order['metadata'], true) ?: []) : [],
                ['payment_payload' => $payment_payload]
            )),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => (int) $order['id']]);

        $quantity = max(1, (int) ($order['quantity'] ?? 1));
        $tickets = [];
        for ($i = 0; $i < $quantity; $i++) {
            $tickets[] = self::createTicket((int) $order['event_id'], (int) $order['id'], $order['buyer_user_id'] ? (int) $order['buyer_user_id'] : null, $order['buyer_email'] ?? null);
        }
        return ['order' => $order, 'tickets' => array_values(array_filter($tickets))];
    }

    public static function findTicket(string $ticket_code = '', string $reference = ''): ?array {
        if ($ticket_code !== '') {
            return md_db_row(
                "SELECT t.*, o.external_reference, e.title AS event_title, e.event_date
                 FROM simuze_event_tickets t
                 LEFT JOIN simuze_event_orders o ON o.id = t.order_id
                 LEFT JOIN simuze_events e ON e.id = t.event_id
                 WHERE t.ticket_code = ?
                 LIMIT 1",
                [$ticket_code]
            ) ?: null;
        }
        if ($reference !== '') {
            return md_db_row(
                "SELECT t.*, o.external_reference, e.title AS event_title, e.event_date
                 FROM simuze_event_tickets t
                 LEFT JOIN simuze_event_orders o ON o.id = t.order_id
                 LEFT JOIN simuze_events e ON e.id = t.event_id
                 WHERE o.external_reference = ?
                 ORDER BY t.id ASC
                 LIMIT 1",
                [$reference]
            ) ?: null;
        }
        return null;
    }

    private static function createTicket(int $event_id, int $order_id, ?int $buyer_user_id, ?string $buyer_email): ?array {
        $now = date('Y-m-d H:i:s');
        $ticket_code = self::generateUniqueTicketCode();
        $qr_token = bin2hex(random_bytes(24));
        md_db_insert('simuze_event_tickets', [
            'event_id' => $event_id,
            'order_id' => $order_id,
            'buyer_user_id' => $buyer_user_id,
            'buyer_email' => $buyer_email ?: null,
            'ticket_code' => $ticket_code,
            'qr_token' => $qr_token,
            'status' => 'valid',
            'issued_at' => $now,
            'metadata' => null,
        ]);
        return md_db_row('SELECT * FROM simuze_event_tickets WHERE ticket_code = ? LIMIT 1', [$ticket_code]) ?: null;
    }

    private static function buildReference(int $event_id): string {
        return 'evt-' . $event_id . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    }

    private static function generateUniqueTicketCode(): string {
        for ($i = 0; $i < 5; $i++) {
            $code = strtoupper('TKT-' . bin2hex(random_bytes(5)));
            $exists = md_db_var('SELECT id FROM simuze_event_tickets WHERE ticket_code = ? LIMIT 1', [$code]);
            if (!$exists) {
                return $code;
            }
        }
        return strtoupper('TKT-' . bin2hex(random_bytes(8)));
    }
}

function simuze_ajax_init_event_ticket_checkout() {
    // Normalise nonce field — md_verify_csrf() always reads $_POST['_csrf_token']
    if (empty($_POST['_csrf_token'])) {
        $alt = $_POST['nonce'] ?? $_POST['_ajax_nonce'] ?? '';
        if ($alt !== '') {
            $_POST['_csrf_token'] = $alt;
        }
    }
    if (!md_verify_csrf()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'data' => 'Security check failed']);
        exit;
    }

    $event_id      = (int) ($_POST['event_id'] ?? 0);
    $quantity      = (int) ($_POST['quantity'] ?? 1);
    $email         = isset($_POST['email'])       ? trim((string) $_POST['email'])       : '';
    $buyer_name    = isset($_POST['buyer_name'])  ? trim(strip_tags((string) $_POST['buyer_name'])) : '';
    $create_account = !empty($_POST['create_account']) && $_POST['create_account'] !== '0';
    if ($event_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'data' => 'Missing event']);
        exit;
    }

    $buyer_user_id = null;
    if (class_exists('Simuze_Auth')) {
        $user = Simuze_Auth::get_instance()->get_current_user();
        if ($user && !empty($user->id)) {
            $buyer_user_id = (int) $user->id;
            if ($email === '' && !empty($user->email)) {
                $email = (string) $user->email;
            }
        }
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'data' => 'Invalid email']);
        exit;
    }

    // Guest: optional account creation
    if ($buyer_user_id === null && $create_account && $email !== '' && class_exists('Simuze_User')) {
        $existing = Simuze_User::get_by_email($email);
        if (!$existing) {
            $parts    = explode(' ', $buyer_name, 2);
            $new_user = Simuze_User::create([
                'email'      => $email,
                'first_name' => $parts[0] ?? '',
                'last_name'  => $parts[1] ?? '',
                'status'     => 'active',
            ]);
            if ($new_user && !empty($new_user->id)) {
                $buyer_user_id = (int) $new_user->id;
            }
        } else {
            $buyer_user_id = (int) ($existing->id ?? $existing['id'] ?? 0) ?: null;
        }
    }

    $res = Simuze_Event_Ticketing::initCheckout($event_id, $quantity, $buyer_user_id, $email ?: null, $buyer_name ?: null);
    header('Content-Type: application/json');
    if (!empty($res['success'])) {
        echo json_encode(['success' => true, 'data' => $res]);
    } else {
        echo json_encode(['success' => false, 'data' => $res['message'] ?? 'Could not start checkout']);
    }
    exit;
}

if (function_exists('add_action')) {
    add_action('wp_ajax_simuze_init_event_ticket_checkout', 'simuze_ajax_init_event_ticket_checkout');
    add_action('wp_ajax_nopriv_simuze_init_event_ticket_checkout', 'simuze_ajax_init_event_ticket_checkout');
}


