<?php
/**
 * Complete Test Report - All Pages and Functionality
 * Run this to test every page and functionality
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';

// Test database connection
$db = $GLOBALS['db'] ?? null;
$tests = [];

// Test 1: Database Connection
$tests['database'] = [
    'name' => 'Database Connection',
    'status' => $db ? 'PASS' : 'FAIL',
    'message' => $db ? 'Database connected' : 'Database connection failed'
];

// Test 2: Post Types - Create
if ($db) {
    try {
        require_once CORE_PATH . '/classes/PostType.php';
        $postType = new PostType();
        $testData = [
            'name' => 'Test Post Type ' . time(),
            'slug' => 'test-post-type-' . time(),
            'description' => 'Test description',
            'public' => 1,
            'show_in_menu' => 1
        ];
        $id = $postType->create($testData);
        $tests['post_type_create'] = [
            'name' => 'Post Type Creation',
            'status' => $id ? 'PASS' : 'FAIL',
            'message' => $id ? "Post type created with ID: $id" : 'Failed to create post type'
        ];
        
        // Clean up
        if ($id) {
            $postType->delete($id);
        }
    } catch (Exception $e) {
        $tests['post_type_create'] = [
            'name' => 'Post Type Creation',
            'status' => 'FAIL',
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

// Test 3: Users - Create
if ($db) {
    try {
        require_once ADMIN_PATH . '/data/userdata.php';
        $userData = new userdata();
        $testUser = [
            'username' => 'testuser' . time(),
            'email' => 'test' . time() . '@example.com',
            'password' => password_hash('Test123!', PASSWORD_DEFAULT),
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'editor'
        ];
        $userId = $userData->createUser($testUser);
        $tests['user_create'] = [
            'name' => 'User Creation',
            'status' => $userId ? 'PASS' : 'FAIL',
            'message' => $userId ? "User created with ID: $userId" : 'Failed to create user'
        ];
    } catch (Exception $e) {
        $tests['user_create'] = [
            'name' => 'User Creation',
            'status' => 'FAIL',
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

// Test 4: Check all required pages exist
$requiredPages = [
    '/modular/admin/dashboard/index.php',
    '/modular/admin/dashboard/analytics.php',
    '/modular/admin/dashboard/reports.php',
    '/modular/admin/post-types/index.php',
    '/modular/admin/post-types/edit.php',
    '/modular/admin/post-types/save.php',
    '/modular/admin/posts/index.php',
    '/modular/admin/posts/edit.php',
    '/modular/admin/posts/save.php',
    '/modular/admin/media/index.php',
    '/modular/admin/media/upload.php',
    '/modular/admin/media/edit.php',
    '/modular/admin/users/index.php',
    '/modular/admin/users/edit.php',
    '/modular/admin/users/save.php',
    '/modular/admin/forms/index.php',
    '/modular/admin/forms/builder.php',
    '/modular/admin/actions/index.php',
    '/modular/admin/settings/index.php',
    '/modular/admin/settings/appearance.php',
    '/modular/admin/settings/security.php',
    '/modular/admin/profile/index.php',
];

$missingPages = [];
foreach ($requiredPages as $page) {
    $filePath = dirname(__DIR__) . str_replace('/modular/admin', '', $page);
    if (!file_exists($filePath)) {
        $missingPages[] = $page;
    }
}

$tests['pages'] = [
    'name' => 'Required Pages',
    'status' => empty($missingPages) ? 'PASS' : 'FAIL',
    'message' => empty($missingPages) ? 'All pages exist' : 'Missing: ' . implode(', ', $missingPages)
];

// Output results
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Report</title>
    <style>
        body { font-family: monospace; padding: 2rem; }
        .pass { color: green; }
        .fail { color: red; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 0.5rem; text-align: left; border: 1px solid #ddd; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Modular CMS - Complete Test Report</h1>
    <table>
        <tr>
            <th>Test</th>
            <th>Status</th>
            <th>Message</th>
        </tr>
        <?php foreach ($tests as $test): ?>
        <tr>
            <td><?php echo htmlspecialchars($test['name']); ?></td>
            <td class="<?php echo strtolower($test['status']); ?>"><?php echo $test['status']; ?></td>
            <td><?php echo htmlspecialchars($test['message']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>


