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
$bootstrap = dirname(__DIR__, 2) . '/admin/bootstrap.php';
if (!is_file($bootstrap)) {
    http_response_code(500);
    echo json_encode(['error' => 'bootstrap missing']);
    exit;
}
require_once $bootstrap;

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
