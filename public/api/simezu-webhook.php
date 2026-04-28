<?php
/**
 * Simezu Payment Webhook
 *
 * Receives payment events from SIMEZU (payment.completed, payment.failed, payment.refunded).
 * Verifies HMAC signature, then records or updates simuze_purchases rows.
 *
 * POST /api/simezu-webhook
 */

// Bootstrap Modular (loads DB, config, functions)
if (!defined('MODULAR_PATH')) {
    define('MODULAR_PATH', dirname(__DIR__, 2));
}
$_wh_config = MODULAR_PATH . '/admin/config/config.php';
if (is_file($_wh_config)) {
    require_once $_wh_config;
}
unset($_wh_config);

// Load core function files before bootstrap (same order as simezu-ajax.php)
foreach (['http', 'database'] as $_wh_fn) {
    $_wh_fp = MODULAR_PATH . '/core/functions/' . $_wh_fn . '.php';
    if (is_file($_wh_fp)) {
        require_once $_wh_fp;
    }
}
unset($_wh_fn, $_wh_fp);

$bootstrap = MODULAR_PATH . '/admin/bootstrap.php';
if (!is_file($bootstrap)) {
    http_response_code(500);
    echo json_encode(['error' => 'bootstrap missing']);
    exit;
}
require_once $bootstrap;
if (!defined('SIMUZE_MODULAR_RUN')) {
    define('SIMUZE_MODULAR_RUN', true);
}

// Load event ticketing class if not already loaded
$_wh_et = dirname(__DIR__) . '/modules/simuze-community/includes/class-event-ticketing.php';
if (!class_exists('Simuze_Event_Ticketing') && is_file($_wh_et)) {
    require_once $_wh_et;
}
// Load mail helper
$_wh_mail = dirname(__DIR__, 2) . '/core/functions/mail.php';
if (!function_exists('mail_send_template') && is_file($_wh_mail)) {
    require_once $_wh_mail;
}
unset($_wh_et, $_wh_mail);

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method not allowed']);
    exit;
}

// Read raw body
$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    http_response_code(400);
    echo json_encode(['error' => 'empty body']);
    exit;
}

// Verify HMAC-SHA256 signature from X-Simezu-Signature header
$secret = defined('SIMEZU_JWT_SECRET') ? SIMEZU_JWT_SECRET : '';
$sig_header = $_SERVER['HTTP_X_SIMEZU_SIGNATURE'] ?? '';

if ($secret !== '' && $sig_header !== '') {
    $expected = 'sha256=' . hash_hmac('sha256', $raw, $secret);
    if (!hash_equals($expected, $sig_header)) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid signature']);
        exit;
    }
}

// Parse JSON payload
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid json']);
    exit;
}

$event   = $payload['event']   ?? '';
$data    = $payload['data']    ?? [];

// Required fields for purchase events
$transaction_id    = $data['transaction_id']    ?? ($data['id'] ?? '');
$simezu_payment_id = $data['payment_id']        ?? ($data['simezu_id'] ?? '');
$user_id           = (int) ($data['user_id']    ?? 0);
$product_type      = $data['product_type']      ?? '';   // album|track|playlist
$product_id        = (int) ($data['product_id'] ?? 0);
$amount            = (float) ($data['amount']   ?? 0.00);
$currency          = strtoupper(substr($data['currency'] ?? 'EUR', 0, 3));

// Normalise product_type
if ($product_type === 'song') {
    $product_type = 'track';
}
$allowed_types = ['album', 'track', 'playlist'];

switch ($event) {

    case 'payment.completed':
        // Event ticket flow (uses order reference, not product_id)
        $event_reference = $data['reference'] ?? '';
        if ($product_type === 'event_ticket' && $event_reference !== '') {
            if (class_exists('Simuze_Event_Ticketing')) {
                $result = Simuze_Event_Ticketing::markOrderPaidAndIssueTickets($event_reference, $data);
                if ($result) {
                    _simezu_webhook_send_ticket_confirmation($result['order'] ?? [], $result['tickets'] ?? []);
                }
            }
            http_response_code(200);
            echo json_encode(['ok' => true, 'event' => $event, 'flow' => 'event_ticket']);
            break;
        }

        if ($user_id < 1 || $product_id < 1 || !in_array($product_type, $allowed_types, true) || $transaction_id === '') {
            http_response_code(422);
            echo json_encode(['error' => 'missing required fields']);
            exit;
        }

        // Upsert by transaction_id
        $existing = md_db_var(
            'SELECT id FROM simuze_purchases WHERE transaction_id = ? LIMIT 1',
            [$transaction_id]
        );

        if ($existing) {
            md_db_update(
                'simuze_purchases',
                ['status' => 'completed', 'simezu_payment_id' => $simezu_payment_id, 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => (int) $existing]
            );
        } else {
            md_db_insert('simuze_purchases', [
                'user_id'           => $user_id,
                'product_type'      => $product_type,
                'product_id'        => $product_id,
                'amount'            => $amount,
                'currency'          => $currency,
                'transaction_id'    => $transaction_id,
                'simezu_payment_id' => $simezu_payment_id,
                'status'            => 'completed',
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
        }

        http_response_code(200);
        echo json_encode(['ok' => true, 'event' => $event]);
        break;

    case 'payment.failed':
        if ($transaction_id !== '') {
            md_db_update(
                'simuze_purchases',
                ['status' => 'failed', 'updated_at' => date('Y-m-d H:i:s')],
                ['transaction_id' => $transaction_id]
            );
        } elseif ($user_id > 0 && $product_id > 0 && in_array($product_type, $allowed_types, true)) {
            md_db_insert('simuze_purchases', [
                'user_id'           => $user_id,
                'product_type'      => $product_type,
                'product_id'        => $product_id,
                'amount'            => $amount,
                'currency'          => $currency,
                'transaction_id'    => $transaction_id ?: uniqid('fail_', true),
                'simezu_payment_id' => $simezu_payment_id,
                'status'            => 'failed',
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
        }

        http_response_code(200);
        echo json_encode(['ok' => true, 'event' => $event]);
        break;

    case 'payment.refunded':
        if ($transaction_id !== '') {
            md_db_update(
                'simuze_purchases',
                ['status' => 'refunded', 'updated_at' => date('Y-m-d H:i:s')],
                ['transaction_id' => $transaction_id]
            );
        }

        http_response_code(200);
        echo json_encode(['ok' => true, 'event' => $event]);
        break;

    default:
        // Unknown event — acknowledge without error so SIMEZU doesn't retry
        http_response_code(200);
        echo json_encode(['ok' => true, 'event' => $event, 'note' => 'unhandled event type']);
        break;
}

/**
 * Send ticket confirmation email to the buyer.
 */
function _simezu_webhook_send_ticket_confirmation(array $order, array $tickets): void {
    $to = $order['buyer_email'] ?? '';
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    if (!function_exists('mail_send_template')) {
        return;
    }
    // Load event info
    $event_id   = (int) ($order['event_id'] ?? 0);
    $event_row  = $event_id > 0 ? md_db_row('SELECT title, slug, event_date, venue_label FROM simuze_events WHERE id = ? LIMIT 1', [$event_id]) : null;
    $event_title = $event_row['title'] ?? 'Your show';
    $event_date  = !empty($event_row['event_date']) ? date('D j M Y, H:i', strtotime($event_row['event_date'])) : '';
    $venue       = $event_row['venue_label'] ?? '';
    $codes       = implode(', ', array_column($tickets, 'ticket_code'));
    $quantity    = (int) ($order['quantity'] ?? count($tickets));
    $total       = number_format((float) ($order['total_price'] ?? 0), 2);
    $currency    = $order['currency'] ?? 'EUR';
    $show_url    = !empty($event_row['slug']) ? (defined('SITE_URL') ? SITE_URL : '') . '/show/' . $event_row['slug'] : '';

    mail_send_template('ticket_confirmation', $to, [
        'buyer_name'   => $order['buyer_name'] ?? '',
        'event_title'  => $event_title,
        'event_date'   => $event_date,
        'venue'        => $venue,
        'ticket_codes' => $codes,
        'quantity'     => $quantity,
        'total_price'  => $currency . ' ' . $total,
        'show_url'     => $show_url,
        // Fallback subject/body when no DB template is configured
        'subject_fallback' => 'Your tickets for ' . $event_title,
        'body_fallback'    => "Hi,\n\nHere are your tickets for {$event_title}:\n\nDate: {$event_date}\nVenue: {$venue}\nTicket(s): {$codes}\nTotal: {$currency} {$total}\n\nSee you there!\n\nSimuze",
    ]);

    // TODO: cron md_hook_run('simuze_pre_show_reminder', $event_id) — 24h before event_date
    // TODO: cron md_hook_run('simuze_post_show_feedback', $event_id) — 24h after event_date
}
