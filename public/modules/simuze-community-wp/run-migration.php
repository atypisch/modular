<?php
/**
 * Direct migration runner
 * Access via: http://localhost/simuze/wp-content/plugins/simuze-community/run-migration.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check - only allow if logged in as admin
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Unauthorized. Please log in as administrator.');
}

// Load Simuze classes
require_once(__DIR__ . '/includes/class-database.php');
require_once(__DIR__ . '/includes/class-user.php');

global $wpdb;
$users_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Username Migration</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
        .success { color: #4ade80; }
        .error { color: #f87171; }
        .info { color: #60a5fa; }
        pre { background: #2a2a2a; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Username Migration</h1>
    <pre>
<?php

echo "Starting username migration...\n\n";

// Find all users
$users = $wpdb->get_results(
    "SELECT id, username, email FROM $users_table WHERE deleted_at IS NULL"
);

$migrated_count = 0;
$skipped_count = 0;
$errors = array();

foreach ($users as $user) {
    $needs_migration = false;
    
    // Check if username contains invalid characters
    if (preg_match('/[^a-z0-9-]/', $user->username) || // Contains invalid chars
        strpos($user->username, '@') !== false || // Contains @
        preg_match('/[A-Z]/', $user->username)) { // Contains uppercase
        $needs_migration = true;
    }
    
    if ($needs_migration) {
        echo "<span class='info'>Migrating user ID {$user->id}: '{$user->username}' -> ";
        
        // Generate clean username using the same logic as Simuze_User::generate_unique_username
        $new_username = Simuze_User::generate_unique_username($user->username);
        
        echo "'{$new_username}'</span>\n";
        
        // Update the username
        $result = $wpdb->update(
            $users_table,
            array('username' => $new_username),
            array('id' => $user->id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            $migrated_count++;
        } else {
            echo "<span class='error'>  ERROR: Failed to update user ID {$user->id}</span>\n";
            $errors[] = "User ID {$user->id}";
        }
    } else {
        $skipped_count++;
    }
}

echo "\n";
echo "<span class='success'>Migration complete!</span>\n";
echo "<span class='info'>Migrated: {$migrated_count} users</span>\n";
echo "<span class='info'>Skipped (already valid): {$skipped_count} users</span>\n";

if (!empty($errors)) {
    echo "<span class='error'>Errors: " . count($errors) . " users</span>\n";
}

?>
    </pre>
    <p><a href="<?php echo admin_url(); ?>">← Back to Admin</a></p>
</body>
</html>








