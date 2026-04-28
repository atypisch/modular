<?php
/**
 * Payment Flow Test - €0.01 Test Transaction
 * 
 * This script tests the complete payment flow for a €0.01 transaction:
 * 1. Order creation
 * 2. Payment session creation via SIMEZU API
 * 3. Webhook processing
 * 4. Order completion
 * 5. Download token creation
 * 
 * Usage: Run via WordPress CLI or browser (requires authentication)
 */

if (!defined('ABSPATH')) {
    require_once dirname(__FILE__) . '/../../../../wp-load.php';
}

// Only allow in development or with proper authentication
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    if (!current_user_can('manage_options')) {
        die('Access denied. This script is for testing purposes only.');
    }
}

// Enable error logging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create log file
$log_file = dirname(__FILE__) . '/payment-test-' . date('Y-m-d-His') . '.log';
$log = function($message, $data = null) use (&$log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    if ($data !== null) {
        $log_entry .= "  Data: " . print_r($data, true) . "\n";
    }
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo $log_entry;
};

$log("=== PAYMENT FLOW TEST - €0.01 TRANSACTION ===");
$log("Test started at: " . date('Y-m-d H:i:s'));

// Step 1: Get or create test user
$log("\n--- STEP 1: User Setup ---");
$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();

if (!$current_user) {
    $log("ERROR: No authenticated user found. Please log in first.");
    die("Please log in to run this test.");
}

$log("User authenticated: ID={$current_user->id}, Email={$current_user->email}");

// Step 2: Get or create test product (song/album)
$log("\n--- STEP 2: Product Setup ---");
global $wpdb;

// Try to find an existing test product or create one
// Note: music_uploads table uses song_name (not title), artist_id (not artist), and doesn't have price/group_id columns
// We'll use the Simuze_Song class or create a minimal test record
$test_product_id = null;
$test_product_type = 'song';

// Check if Simuze_Song class exists and use it
if (class_exists('Simuze_Song')) {
    // Try to find existing test product
    $songs_table = $wpdb->prefix . 'music_uploads';
    $test_product = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $songs_table WHERE song_name = %s AND status = %s LIMIT 1",
        'Test Product €0.01',
        'published'
    ));
    
    if ($test_product) {
        $test_product_id = $test_product->id;
        $log("Using existing test product: ID=$test_product_id, Name={$test_product->song_name}");
    } else {
        // Get first available group for seller
        $groups_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'groups';
        $test_group = $wpdb->get_row("SELECT id FROM $groups_table WHERE deleted_at IS NULL LIMIT 1");
        
        if (!$test_group) {
            $log("ERROR: No group found. Cannot create test product.");
            die("Please create a group first.");
        }
        
        // Get user's artist_id (usually same as user_id)
        $artist_id = $current_user->id;
        
        // Create a minimal test product using correct column names
        $test_product_id = $wpdb->insert(
            $songs_table,
            array(
                'song_name' => 'Test Product €0.01',
                'artist_id' => $artist_id,
                'status' => 'published',
                'upload_date' => current_time('mysql'),
                'file_path' => '/test/test.mp3', // Dummy path for test
            ),
            array('%s', '%d', '%s', '%s', '%s')
        );
        
        if ($test_product_id) {
            $test_product_id = $wpdb->insert_id;
            $log("Test product created: ID=$test_product_id");
            
            // Set price via post meta or custom table if it exists
            // For now, we'll note that price needs to be set separately
            $log("NOTE: Price (€0.01) needs to be set via product settings or custom pricing table");
        } else {
            $log("ERROR: Failed to create test product: " . $wpdb->last_error);
            die("Failed to create test product.");
        }
    }
} else {
    $log("ERROR: Simuze_Song class not found. Cannot create test product.");
    die("Simuze_Song class required for test.");
}

// Step 3: Create order
$log("\n--- STEP 3: Order Creation ---");
$cart_items = array(
    array('type' => $test_product_type, 'id' => $test_product_id)
);

$order = Simuze_Order::create_from_cart($current_user->id, $cart_items, array(
    'payment_method' => 'paypal',
    'currency' => 'EUR',
));

if (is_wp_error($order)) {
    $log("ERROR: Failed to create order: " . $order->get_error_message());
    die("Order creation failed.");
}

$log("Order created successfully:");
$log("  Order ID: {$order->id}");
$log("  Order Number: {$order->order_number}");
$log("  Buyer ID: {$order->buyer_id}");
$log("  Total Price: €{$order->total_price}");
$log("  Currency: {$order->currency}");
$log("  Payment Status: {$order->payment_status}");

// Step 4: Create payment session via SIMEZU
$log("\n--- STEP 4: Payment Session Creation ---");
$payments = Simuze_Simezu_Payments::get_instance();
$payment_result = $payments->create_payment($order, 'paypal');

if (is_wp_error($payment_result)) {
    $log("ERROR: Failed to create payment session: " . $payment_result->get_error_message());
    $log("  Error Code: " . $payment_result->get_error_code());
    $log("  Error Data: " . print_r($payment_result->get_error_data(), true));
    die("Payment session creation failed.");
}

$log("Payment session created successfully:");
$log("  Payment Session ID: " . ($payment_result['payment_session_id'] ?? 'N/A'));
$log("  Redirect URL: " . ($payment_result['redirect_url'] ?? 'N/A'));
$log("  PayPal Redirect URL: " . ($payment_result['paypal_redirect_url'] ?? 'N/A'));

// Update order status
$order = Simuze_Order::get($order->id);
$log("Order updated - Payment Status: {$order->payment_status}, Session ID: {$order->payment_session_id}");

// Step 5: Simulate webhook callback (payment completed)
$log("\n--- STEP 5: Webhook Simulation ---");
$log("Simulating payment completion webhook...");

$webhook_payload = array(
    'order_id' => $order->id,
    'order_number' => $order->order_number,
    'status' => 'paid',
    'transaction_id' => 'TEST-TX-' . time(),
    'transaction_ids' => array('TEST-TX-' . time() . '-1'),
    'amount' => $order->total_price,
    'currency' => $order->currency,
    'payment_method' => 'paypal',
    'paid_at' => current_time('mysql'),
);

$webhook_signature = hash_hmac('sha256', json_encode($webhook_payload), get_option('simuze_simezu_webhook_secret', 'test-secret'));

$log("Webhook payload:");
$log("  Order ID: {$webhook_payload['order_id']}");
$log("  Status: {$webhook_payload['status']}");
$log("  Transaction ID: {$webhook_payload['transaction_id']}");

// Process webhook
$webhook_result = $payments->process_webhook(json_encode($webhook_payload), $webhook_signature);

if (is_wp_error($webhook_result)) {
    $log("ERROR: Webhook processing failed: " . $webhook_result->get_error_message());
    $log("  Error Code: " . $webhook_result->get_error_code());
} else {
    $log("Webhook processed successfully");
    $log("  Result: " . print_r($webhook_result, true));
}

// Step 6: Verify order status
$log("\n--- STEP 6: Order Verification ---");
$order = Simuze_Order::get($order->id);
$log("Order status after webhook:");
$log("  Order ID: {$order->id}");
$log("  Payment Status: {$order->payment_status}");
$log("  Paid At: " . ($order->paid_at ?? 'N/A'));
$log("  SIMEZU Transaction ID: " . ($order->simezu_transaction_id ?? 'N/A'));

// Step 7: Check download tokens
$log("\n--- STEP 7: Download Token Verification ---");
$items = $order->get_items();
$log("Order items: " . count($items));

foreach ($items as $item) {
    $log("  Item: {$item['product_title']} (ID: {$item['id']})");
    
    $token = Simuze_Download_Token::get_by_order_item($item['id']);
    if ($token) {
        $log("    Download token created: {$token->token}");
        $log("    Max downloads: {$token->max_downloads}");
        $log("    Expires at: {$token->expires_at}");
    } else {
        $log("    WARNING: No download token found for this item");
    }
}

// Step 8: Check transactions table
$log("\n--- STEP 8: Transaction Records ---");
$transactions_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'transactions';
$transactions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $transactions_table WHERE order_id = %d",
    $order->id
));

if (!empty($transactions)) {
    $log("Transactions found: " . count($transactions));
    foreach ($transactions as $tx) {
        $log("  Transaction ID: {$tx->simezu_transaction_id}");
        $log("    Gross Amount: €{$tx->gross_amount}");
        $log("    Simuze Fee: €{$tx->simuze_fee}");
        $log("    Net Amount: €{$tx->net_amount}");
        $log("    Status: {$tx->status}");
    }
} else {
    $log("WARNING: No transactions found in database");
}

// Step 9: Check webhook storage
$log("\n--- STEP 9: Webhook Storage ---");
$webhooks_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'webhooks';
$webhooks = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $webhooks_table WHERE payload LIKE %s ORDER BY created_at DESC LIMIT 5",
    '%"order_id":' . $order->id . '%'
));

if (!empty($webhooks)) {
    $log("Webhooks found: " . count($webhooks));
    foreach ($webhooks as $webhook) {
        $log("  Webhook ID: {$webhook->id}");
        $log("    Provider: {$webhook->provider}");
        $log("    Event Type: {$webhook->event_type}");
        $log("    Status: {$webhook->status}");
        $log("    Created: {$webhook->created_at}");
    }
} else {
    $log("INFO: No webhooks found (webhook system may not have been triggered)");
}

// Step 10: Summary
$log("\n--- STEP 10: Test Summary ---");
$log("Test completed at: " . date('Y-m-d H:i:s'));
$log("Order Number: {$order->order_number}");
$log("Order Status: {$order->payment_status}");
$log("Total Amount: €{$order->total_price}");

if ($order->payment_status === 'paid' || $order->payment_status === 'completed') {
    $log("✓ TEST PASSED: Order marked as paid");
} else {
    $log("✗ TEST FAILED: Order not marked as paid (Status: {$order->payment_status})");
}

$log("\n=== END OF TEST ===");
$log("Full log saved to: $log_file");

echo "\n\n<h2>Payment Flow Test Complete</h2>\n";
echo "<p>Order Number: <strong>{$order->order_number}</strong></p>\n";
echo "<p>Status: <strong>{$order->payment_status}</strong></p>\n";
echo "<p>Total: <strong>€{$order->total_price}</strong></p>\n";
echo "<p>Log file: <code>$log_file</code></p>\n";
