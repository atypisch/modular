<?php
/**
 * Direct username migration script
 * Run this once to migrate all users with invalid usernames
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if we're in CLI or have proper permissions
if (!defined('WP_CLI') && !current_user_can('manage_options')) {
    die('Unauthorized');
}

global $wpdb;
$users_table = $wpdb->prefix . SIMUZE_DB_PREFIX . 'users';

echo "Starting username migration...\n\n";

// Find all users
$users = $wpdb->get_results(
    "SELECT id, username, email FROM $users_table WHERE deleted_at IS NULL"
);

$migrated_count = 0;
$skipped_count = 0;

foreach ($users as $user) {
    $needs_migration = false;
    
    // Check if username contains invalid characters
    if (preg_match('/[^a-z0-9-]/', $user->username) || // Contains invalid chars
        strpos($user->username, '@') !== false || // Contains @
        preg_match('/[A-Z]/', $user->username)) { // Contains uppercase
        $needs_migration = true;
    }
    
    if ($needs_migration) {
        echo "Migrating user ID {$user->id}: '{$user->username}' -> ";
        
        // Generate clean username using the same logic as Simuze_User::generate_unique_username
        $new_username = Simuze_User::generate_unique_username($user->username);
        
        echo "'{$new_username}'\n";
        
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
            echo "  ERROR: Failed to update user ID {$user->id}\n";
        }
    } else {
        $skipped_count++;
    }
}

echo "\n";
echo "Migration complete!\n";
echo "Migrated: {$migrated_count} users\n";
echo "Skipped (already valid): {$skipped_count} users\n";








