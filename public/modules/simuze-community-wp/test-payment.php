<?php
/**
 * Test Payment to SIMEZU
 * Run this file directly to test payment integration
 * 
 * Usage: php test-payment.php
 * Or visit: http://localhost/simuze/wp-content/plugins/simuze-community/test-payment.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if we're in WordPress context
if (!defined('ABSPATH')) {
    die('This script must be run within WordPress context');
}

// Get SIMEZU API configuration
$api_url = get_option('simezu_api_url', 'http://localhost/simezu/api');
$api_key = get_option('simuze_simezu_api_key', 'a1719440-ecd9-11f0-ba55-155569101740');
$app_id = get_option('simuze_simezu_app_id', 'a1719436-ecd9-11f0-ba55-155569101740');

echo "<h1>SIMEZU Payment Test</h1>";
echo "<pre>";

echo "API URL: " . $api_url . "\n";
echo "App ID: " . $app_id . "\n";
echo "API Key: " . substr($api_key, 0, 20) . "...\n\n";

// Test payment payload (matching new SIMEZU API format)
$payload = array(
    'order_id' => 'test-order-' . time(),
    'buyer_id' => 'test-buyer-123',
    'line_items' => array(
        array(
            'product_id' => 'test-product-1',
            'name' => 'Test Product - Simuze Payment Test',
            'quantity' => 1,
            'price' => 0.01, // Small test amount
            'seller_id' => 'test-seller-1'
        )
    ),
    'total' => 0.01,
    'return_url' => home_url('/checkout/success/'),
    'cancel_url' => home_url('/checkout/cancel/')
);

echo "Sending payment request to SIMEZU...\n\n";
echo "Payload:\n";
print_r($payload);
echo "\n";

// Make API request
$response = wp_remote_post($api_url . '/payments/create', array(
    'headers' => array(
        'Content-Type' => 'application/json',
        'X-API-Key' => $api_key,
        'X-App-ID' => $app_id,
    ),
    'body' => json_encode($payload),
    'timeout' => 30,
));

if (is_wp_error($response)) {
    echo "ERROR: " . $response->get_error_message() . "\n";
    echo "Error Code: " . $response->get_error_code() . "\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    echo "Response Status: " . $status_code . "\n\n";
    echo "Response Body:\n";
    print_r($data);
    
    if ($status_code === 200 && (!empty($data['payment_session_id']) || !empty($data['checkout_url']) || !empty($data['redirect_url']))) {
        echo "\n✅ SUCCESS! Payment session created.\n";
        if (!empty($data['payment_session_id'])) {
            echo "Payment Session ID: " . $data['payment_session_id'] . "\n";
        }
        if (!empty($data['checkout_url'])) {
            echo "Checkout URL: " . $data['checkout_url'] . "\n";
            echo "\n<a href='" . esc_url($data['checkout_url']) . "' target='_blank' style='display:inline-block;padding:10px 20px;background:#a8d5ba;color:#1a1a1a;text-decoration:none;border-radius:6px;font-weight:600;margin-top:10px;'>Open Checkout Page</a>\n";
        }
        if (!empty($data['redirect_url'])) {
            echo "Redirect URL: " . $data['redirect_url'] . "\n";
            echo "\n<a href='" . esc_url($data['redirect_url']) . "' target='_blank' style='display:inline-block;padding:10px 20px;background:#a8d5ba;color:#1a1a1a;text-decoration:none;border-radius:6px;font-weight:600;margin-top:10px;'>Open Payment Page</a>\n";
        }
        echo "\n<p style='margin-top:20px;color:#a8d5ba;'><strong>Next Steps:</strong></p>";
        echo "<ol style='color:#fff;'>";
        echo "<li>Click the link above to open the payment page</li>";
        echo "<li>Complete the test payment (€0.01)</li>";
        echo "<li>Check SIMEZU dashboard to verify payment appears</li>";
        echo "</ol>";
    } else {
        echo "\n❌ FAILED! Payment session could not be created.\n";
        echo "Status Code: " . $status_code . "\n";
        if (!empty($data['error'])) {
            echo "Error: " . $data['error'] . "\n";
        }
        if (!empty($data['message'])) {
            echo "Message: " . $data['message'] . "\n";
        }
        if (!empty($data['errors'])) {
            echo "Errors: " . print_r($data['errors'], true) . "\n";
        }
        echo "\n<p style='color:#ff6b6b;'><strong>Troubleshooting:</strong></p>";
        echo "<ul style='color:#fff;'>";
        echo "<li>Check that SIMEZU API is running at: " . $api_url . "</li>";
        echo "<li>Verify API Key and App ID are correct</li>";
        echo "<li>Check SIMEZU logs for errors</li>";
        echo "</ul>";
    }
}

echo "</pre>";
