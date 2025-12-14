<?php
/**
 * Comprehensive Test Script for Modular CMS
 * Tests all database operations and page functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/data/init.php';
require_once CORE_PATH . '/functions/auth.php';
require_once ADMIN_PATH . '/data/userdata.php';
require_once CORE_PATH . '/classes/PostType.php';

echo "<h1>Modular CMS - Comprehensive Test Report</h1>\n";
echo "<style>body{font-family:monospace;padding:20px;} .pass{color:green;} .fail{color:red;} .info{color:blue;}</style>\n";

$tests_passed = 0;
$tests_failed = 0;
$errors = [];

function test($name, $callback) {
    global $tests_passed, $tests_failed, $errors;
    try {
        $result = $callback();
        if ($result === true) {
            echo "<div class='pass'>✓ PASS: $name</div>\n";
            $tests_passed++;
            return true;
        } else {
            echo "<div class='fail'>✗ FAIL: $name - $result</div>\n";
            $tests_failed++;
            $errors[] = "$name: $result";
            return false;
        }
    } catch (Exception $e) {
        echo "<div class='fail'>✗ FAIL: $name - Exception: " . $e->getMessage() . "</div>\n";
        $tests_failed++;
        $errors[] = "$name: " . $e->getMessage();
        return false;
    }
}

// Test 1: Database Connection
test("Database Connection", function() {
    if (!isset($GLOBALS['db'])) {
        return "Database connection not available in \$GLOBALS['db']";
    }
    try {
        $GLOBALS['db']->query("SELECT 1");
        return true;
    } catch (Exception $e) {
        return "Database query failed: " . $e->getMessage();
    }
});

// Test 2: Users Table Structure
test("Users Table Structure", function() {
    try {
        $stmt = $GLOBALS['db']->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $required = ['id', 'username', 'email', 'password', 'first_name', 'last_name', 'role', 'status', 'created_at'];
        $missing = array_diff($required, $columns);
        if (!empty($missing)) {
            return "Missing columns: " . implode(', ', $missing);
        }
        return true;
    } catch (Exception $e) {
        return "Error checking table: " . $e->getMessage();
    }
});

// Test 3: Post Types Table Structure
test("Post Types Table Structure", function() {
    try {
        $stmt = $GLOBALS['db']->query("DESCRIBE post_types");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $required = ['id', 'name', 'slug', 'description', 'public', 'show_in_menu', 'created_at'];
        $missing = array_diff($required, $columns);
        if (!empty($missing)) {
            return "Missing columns: " . implode(', ', $missing);
        }
        return true;
    } catch (Exception $e) {
        return "Error checking table: " . $e->getMessage();
    }
});

// Test 4: UserData Class - Create User
test("UserData - Create User", function() {
    $userData = new userdata();
    $test_data = [
        'username' => 'test_user_' . time(),
        'email' => 'test_' . time() . '@example.com',
        'password' => password_hash('Test123!@#', PASSWORD_DEFAULT),
        'first_name' => 'Test',
        'last_name' => 'User',
        'role' => 'editor'
    ];
    $user_id = $userData->createUser($test_data);
    if ($user_id === false) {
        return "Failed to create user";
    }
    // Clean up
    $userData->deleteUser($user_id);
    return true;
});

// Test 5: UserData Class - Get All Users
test("UserData - Get All Users", function() {
    $userData = new userdata();
    $users = $userData->getAllUsers();
    if ($users === false) {
        return "getAllUsers returned false";
    }
    if (!is_array($users)) {
        return "getAllUsers did not return an array";
    }
    return true;
});

// Test 6: UserData Class - Get User By Email
test("UserData - Get User By Email", function() {
    $userData = new userdata();
    // Create test user
    $test_email = 'test_get_' . time() . '@example.com';
    $test_data = [
        'username' => 'test_get_' . time(),
        'email' => $test_email,
        'password' => password_hash('Test123!@#', PASSWORD_DEFAULT),
        'first_name' => 'Test',
        'last_name' => 'Get',
        'role' => 'editor'
    ];
    $user_id = $userData->createUser($test_data);
    if ($user_id === false) {
        return "Failed to create test user";
    }
    
    $user = $userData->getUserByEmail($test_email);
    if ($user === false) {
        $userData->deleteUser($user_id);
        return "Failed to get user by email";
    }
    if ($user['email'] !== $test_email) {
        $userData->deleteUser($user_id);
        return "Retrieved user email doesn't match";
    }
    
    // Clean up
    $userData->deleteUser($user_id);
    return true;
});

// Test 7: PostType Class - Create Post Type
test("PostType - Create Post Type", function() {
    $postType = new PostType();
    $test_data = [
        'name' => 'Test Post Type ' . time(),
        'slug' => 'test-post-type-' . time(),
        'description' => 'Test description',
        'public' => 1,
        'show_in_menu' => 1
    ];
    try {
        $post_type_id = $postType->create($test_data);
        if (!$post_type_id) {
            return "Failed to create post type";
        }
        // Clean up
        $postType->delete($post_type_id);
        return true;
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
});

// Test 8: PostType Class - Get All Post Types
test("PostType - Get All Post Types", function() {
    $postType = new PostType();
    try {
        $post_types = $postType->getAll();
        if (!is_array($post_types)) {
            return "getAll did not return an array";
        }
        return true;
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
});

// Test 9: PostType Class - Get By Slug
test("PostType - Get By Slug", function() {
    $postType = new PostType();
    $test_slug = 'test-slug-' . time();
    $test_data = [
        'name' => 'Test Post Type',
        'slug' => $test_slug,
        'description' => 'Test',
        'public' => 1,
        'show_in_menu' => 1
    ];
    try {
        $post_type_id = $postType->create($test_data);
        if (!$post_type_id) {
            return "Failed to create test post type";
        }
        
        $found = $postType->getBySlug($test_slug);
        if (!$found) {
            $postType->delete($post_type_id);
            return "Failed to get post type by slug";
        }
        if ($found['slug'] !== $test_slug) {
            $postType->delete($post_type_id);
            return "Retrieved slug doesn't match";
        }
        
        // Clean up
        $postType->delete($post_type_id);
        return true;
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
});

// Test 10: File Existence Checks
test("Required Files Exist", function() {
    $required_files = [
        ADMIN_PATH . '/users/index.php',
        ADMIN_PATH . '/users/edit.php',
        ADMIN_PATH . '/post-types/index.php',
        ADMIN_PATH . '/post-types/edit.php',
        ADMIN_PATH . '/post-types/save.php',
        ADMIN_PATH . '/post-types/post-types-delete.php',
        ADMIN_PATH . '/media/index.php',
        ADMIN_PATH . '/settings/index.php',
        ADMIN_PATH . '/profile/index.php',
        ADMIN_PATH . '/dashboard/index.php',
        CORE_PATH . '/classes/PostType.php',
        ADMIN_PATH . '/data/userdata.php'
    ];
    $missing = [];
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            $missing[] = $file;
        }
    }
    if (!empty($missing)) {
        return "Missing files: " . implode(', ', $missing);
    }
    return true;
});

// Test 11: Page Syntax Check
test("Page PHP Syntax", function() {
    $pages = [
        ADMIN_PATH . '/users/index.php',
        ADMIN_PATH . '/users/edit.php',
        ADMIN_PATH . '/post-types/index.php',
        ADMIN_PATH . '/post-types/edit.php',
        ADMIN_PATH . '/post-types/save.php'
    ];
    $errors = [];
    foreach ($pages as $page) {
        $output = [];
        $return_var = 0;
        exec("php -l " . escapeshellarg($page) . " 2>&1", $output, $return_var);
        if ($return_var !== 0) {
            $errors[] = "$page: " . implode(' ', $output);
        }
    }
    if (!empty($errors)) {
        return implode('; ', $errors);
    }
    return true;
});

// Summary
echo "\n<h2>Test Summary</h2>\n";
echo "<div class='info'>Total Tests: " . ($tests_passed + $tests_failed) . "</div>\n";
echo "<div class='pass'>Passed: $tests_passed</div>\n";
echo "<div class='fail'>Failed: $tests_failed</div>\n";

if (!empty($errors)) {
    echo "\n<h2>Errors</h2>\n";
    foreach ($errors as $error) {
        echo "<div class='fail'>$error</div>\n";
    }
}

if ($tests_failed === 0) {
    echo "\n<div class='pass'><h2>✓ All Tests Passed!</h2></div>\n";
} else {
    echo "\n<div class='fail'><h2>✗ Some Tests Failed</h2></div>\n";
}


