<?php
/**
 * Migrate all board posts from author_type='user' to the default 'simuze' group.
 *
 * This ensures every activity post belongs to a group, which is the new requirement.
 * The 'simuze' group is created if it doesn't exist (via simuze_default_group_id()).
 */

$db = $GLOBALS['db'] ?? null;
if (!$db instanceof PDO) {
    throw new RuntimeException('Database connection not available.');
}

if (is_file(dirname(__DIR__) . '/functions/common.php')) {
    require_once dirname(__DIR__) . '/functions/common.php';
}
if (is_file(dirname(__DIR__) . '/functions/database.php')) {
    require_once dirname(__DIR__) . '/functions/database.php';
}

$simuze_group_id = function_exists('simuze_default_group_id') ? simuze_default_group_id() : null;

if (!$simuze_group_id) {
    throw new RuntimeException('Could not resolve or create the default simuze group.');
}

$stmt = $db->prepare(
    "UPDATE simuze_activity_posts
        SET author_type = 'group', author_id = ?
      WHERE author_type = 'user' AND deleted_at IS NULL"
);
$stmt->execute([$simuze_group_id]);
$affected = $stmt->rowCount();

error_log("Migration 008: Migrated {$affected} board post(s) from author_type='user' to group ID {$simuze_group_id}.");
