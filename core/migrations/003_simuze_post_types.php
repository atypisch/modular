<?php
/**
 * Seed Simuze-relevant post types: album, track, playlist, event
 * Version: 003
 */
require_once __DIR__ . '/../../admin/config/config.php';
require_once __DIR__ . '/../classes/Migration.php';

if (!isset($GLOBALS['db'])) {
    return;
}
$migration = new \Modular\Migration($GLOBALS['db']);
$version = '003';
$name = 'simuze_post_types';
if ($migration->hasRun($version)) {
    return;
}

$db = $GLOBALS['db'];
$userId = 1;
$supports = json_encode(['title', 'editor']);
$types = [
    ['name' => 'Album', 'slug' => 'album', 'description' => 'Music album'],
    ['name' => 'Track', 'slug' => 'track', 'description' => 'Music track'],
    ['name' => 'Playlist', 'slug' => 'playlist', 'description' => 'Playlist'],
    ['name' => 'Event', 'slug' => 'event', 'description' => 'Event'],
];

foreach ($types as $t) {
    $stmt = $db->prepare("SELECT id FROM post_types WHERE slug = ?");
    $stmt->execute([$t['slug']]);
    if ($stmt->fetch()) {
        continue;
    }
    $stmt = $db->prepare("
        INSERT INTO post_types (name, slug, description, public, show_in_menu, supports, created_by, updated_by, created_at, updated_at)
        VALUES (?, ?, ?, 1, 1, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$t['name'], $t['slug'], $t['description'], $supports, $userId, $userId]);
}

$migration->recordMigration($version, $name);
