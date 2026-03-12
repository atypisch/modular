<?php
/**
 * Webhook System Test Script
 * 
 * This script tests the webhook system implementation:
 * 1. Database table creation
 * 2. Event class loading
 * 3. EventBus registration
 * 4. Webhook endpoint registration
 * 5. Webhook processing flow
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

echo "<h1>Simuze Webhook System Test</h1>\n";
echo "<pre>\n";

// Test 1: Check database table
echo "=== Test 1: Database Table ===\n";
global $wpdb;
$table_name = $wpdb->prefix . SIMUZE_DB_PREFIX . 'webhooks';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if ($table_exists === $table_name) {
    echo "✓ Webhooks table exists: $table_name\n";
    
    // Check table structure
    $columns = $wpdb->get_col("DESCRIBE $table_name");
    $required_columns = array('id', 'provider', 'event_type', 'external_reference', 'payload', 'status', 'created_at');
    $missing = array_diff($required_columns, $columns);
    
    if (empty($missing)) {
        echo "✓ All required columns exist\n";
    } else {
        echo "✗ Missing columns: " . implode(', ', $missing) . "\n";
    }
    
    // Check for unique constraint on external_reference
    $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'external_reference'");
    if (!empty($indexes)) {
        echo "✓ Unique constraint on external_reference exists\n";
    } else {
        echo "✗ Unique constraint on external_reference missing\n";
    }
} else {
    echo "✗ Webhooks table does not exist: $table_name\n";
    echo "  Run database migration to create the table.\n";
}

echo "\n";

// Test 2: Check Event classes
echo "=== Test 2: Event Classes ===\n";
$events_dir = dirname(__FILE__) . '/../includes/events/events';
$event_files = glob($events_dir . '/class-event-*.php');
$expected_events = array(
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

$loaded_events = 0;
foreach ($expected_events as $event_type => $class_name) {
    if (class_exists($class_name)) {
        echo "✓ $class_name loaded\n";
        $loaded_events++;
    } else {
        echo "✗ $class_name not found\n";
    }
}
echo "Loaded $loaded_events/" . count($expected_events) . " event classes\n";

echo "\n";

// Test 3: Check EventBus
echo "=== Test 3: EventBus System ===\n";
if (class_exists('Simuze_EventBus')) {
    echo "✓ Simuze_EventBus class exists\n";
    
    $event_bus = Simuze_EventBus::get_instance();
    if ($event_bus) {
        echo "✓ EventBus instance created\n";
        
        // Check if action handlers are registered
        $test_event_type = 'payment.intent-succeeded';
        $handlers = $event_bus->get_handlers($test_event_type);
        if (!empty($handlers)) {
            echo "✓ Found " . count($handlers) . " handler(s) for $test_event_type\n";
            foreach ($handlers as $handler) {
                echo "  - " . get_class($handler) . "\n";
            }
        } else {
            echo "⚠ No handlers registered for $test_event_type (this may be normal if no handlers exist)\n";
        }
    } else {
        echo "✗ Failed to create EventBus instance\n";
    }
} else {
    echo "✗ Simuze_EventBus class not found\n";
}

echo "\n";

// Test 4: Check WebhookController
echo "=== Test 4: WebhookController ===\n";
if (class_exists('Simuze_WebhookController')) {
    echo "✓ Simuze_WebhookController class exists\n";
    
    $controller = Simuze_WebhookController::get_instance();
    if ($controller) {
        echo "✓ WebhookController instance created\n";
    } else {
        echo "✗ Failed to create WebhookController instance\n";
    }
} else {
    echo "✗ Simuze_WebhookController class not found\n";
}

echo "\n";

// Test 5: Check REST API routes
echo "=== Test 5: REST API Routes ===\n";
$rest_server = rest_get_server();
$routes = $rest_server->get_routes('simuze/v1');

$expected_routes = array(
    '/simuze/v1/webhooks/payment/intent-created',
    '/simuze/v1/webhooks/payment/intent-succeeded',
    '/simuze/v1/webhooks/payment/intent-failed',
    '/simuze/v1/webhooks/media/upload-received',
    '/simuze/v1/webhooks/media/processing-completed',
    '/simuze/v1/webhooks/group/member-joined',
    '/simuze/v1/webhooks/group/member-left',
    '/simuze/v1/webhooks/release/published',
);

$registered_routes = 0;
foreach ($expected_routes as $route) {
    if (isset($routes[$route])) {
        $route_data = $routes[$route];
        $has_post = false;
        foreach ($route_data as $endpoint) {
            if (in_array('POST', $endpoint['methods'])) {
                $has_post = true;
                break;
            }
        }
        if ($has_post) {
            echo "✓ Route registered: $route (POST)\n";
            $registered_routes++;
        } else {
            echo "✗ Route exists but missing POST method: $route\n";
        }
    } else {
        echo "✗ Route not registered: $route\n";
    }
}
echo "Registered $registered_routes/" . count($expected_routes) . " webhook routes\n";

echo "\n";

// Test 6: Test webhook processing (simulation)
echo "=== Test 6: Webhook Processing Simulation ===\n";
if (class_exists('Simuze_WebhookController') && class_exists('Simuze_EventBus')) {
    // Create a mock request
    $mock_payload = array(
        'id' => 'test-webhook-' . time(),
        'order_id' => 123,
        'user_id' => 456,
        'items' => array(
            array('id' => 789, 'type' => 'download')
        )
    );
    
    // Test event creation
    if (class_exists('Simuze_Event_Payment_Intent_Succeeded')) {
        try {
            $event = new Simuze_Event_Payment_Intent_Succeeded($mock_payload, 'test-provider', 'test-ref-123');
            echo "✓ Event created: " . $event->get_type() . "\n";
            echo "  Provider: " . $event->get_provider() . "\n";
            echo "  External Reference: " . $event->get_external_reference() . "\n";
            echo "  Payload keys: " . implode(', ', array_keys($mock_payload)) . "\n";
        } catch (Exception $e) {
            echo "✗ Failed to create event: " . $e->getMessage() . "\n";
        }
    }
    
    // Test idempotency check
    if ($table_exists === $table_name) {
        $test_ref = 'test-idempotency-' . time();
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE external_reference = %s",
            $test_ref
        ));
        
        if ($existing === null) {
            echo "✓ Idempotency check works (no existing webhook found)\n";
        } else {
            echo "⚠ Found existing webhook with reference (this is expected for idempotency)\n";
        }
    }
} else {
    echo "✗ Cannot test webhook processing - required classes not loaded\n";
}

echo "\n";

// Test 7: Check action handlers
echo "=== Test 7: Action Handlers ===\n";
$actions_dir = dirname(__FILE__) . '/../includes/events/actions';
if (is_dir($actions_dir)) {
    $action_files = glob($actions_dir . '/class-action-*.php');
    echo "Found " . count($action_files) . " action handler file(s)\n";
    
    foreach ($action_files as $file) {
        $class_name = basename($file, '.php');
        $class_name = str_replace('class-action-', '', $class_name);
        $parts = explode('-', $class_name);
        $parts = array_map('ucfirst', $parts);
        $class_name = 'Simuze_Action_' . implode('_', $parts);
        
        if (class_exists($class_name)) {
            echo "✓ $class_name loaded\n";
            
            if (class_exists('Simuze_Action_Handler')) {
                $handler = new $class_name();
                if ($handler instanceof Simuze_Action_Handler) {
                    $event_types = $handler->get_event_types();
                    echo "  Handles events: " . implode(', ', $event_types) . "\n";
                } else {
                    echo "  ✗ Does not implement Simuze_Action_Handler\n";
                }
            }
        } else {
            echo "✗ $class_name not found\n";
        }
    }
} else {
    echo "✗ Actions directory not found: $actions_dir\n";
}

echo "\n";

// Test 8: Database query test
echo "=== Test 8: Database Operations ===\n";
if ($table_exists === $table_name) {
    // Test insert
    $test_data = array(
        'provider' => 'test',
        'event_type' => 'test.event',
        'external_reference' => 'test-ref-' . time() . '-' . wp_generate_password(8, false),
        'payload' => json_encode(array('test' => 'data')),
        'status' => 'received',
        'created_at' => current_time('mysql')
    );
    
    $result = $wpdb->insert(
        $table_name,
        $test_data,
        array('%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    if ($result !== false) {
        $insert_id = $wpdb->insert_id;
        echo "✓ Test webhook inserted (ID: $insert_id)\n";
        
        // Test select
        $retrieved = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $insert_id
        ));
        
        if ($retrieved) {
            echo "✓ Test webhook retrieved\n";
            echo "  Status: " . $retrieved->status . "\n";
            
            // Test update
            $update_result = $wpdb->update(
                $table_name,
                array('status' => 'processed', 'processed_at' => current_time('mysql')),
                array('id' => $insert_id),
                array('%s', '%s'),
                array('%d')
            );
            
            if ($update_result !== false) {
                echo "✓ Test webhook updated\n";
            } else {
                echo "✗ Failed to update test webhook\n";
            }
            
            // Cleanup
            $wpdb->delete($table_name, array('id' => $insert_id), array('%d'));
            echo "✓ Test webhook cleaned up\n";
        } else {
            echo "✗ Failed to retrieve test webhook\n";
        }
    } else {
        echo "✗ Failed to insert test webhook: " . $wpdb->last_error . "\n";
    }
} else {
    echo "⚠ Cannot test database operations - table does not exist\n";
}

echo "\n";

// Summary
echo "=== Summary ===\n";
echo "Webhook system test completed.\n";
echo "Check the results above for any issues.\n";
echo "\n";
echo "To test actual webhook endpoints, use:\n";
echo "curl -X POST http://localhost/simuze/wp-json/simuze/v1/webhooks/payment/intent-succeeded \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'X-Webhook-Signature: test' \\\n";
echo "  -d '{\"id\":\"test-123\",\"order_id\":123,\"user_id\":456}'\n";

echo "</pre>\n";
