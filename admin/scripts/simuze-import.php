<?php
/**
 * Simuze → Modular data import (run from CLI).
 *
 * Usage: php admin/scripts/simuze-import.php
 *
 * Set SIMUZE_SOURCE_DSN in config or env (e.g. mysql:host=127.0.0.1;dbname=simuze;charset=utf8mb4)
 * and SIMUZE_SOURCE_USER / SIMUZE_SOURCE_PASS. Target = current Modular DB from config.
 *
 * Order: 1) users, 2) media (music uploads), 3) post types resolve, 4) posts (album, track, playlist, event, page), 5) post_terms, 6) simuze_* tables.
 * Uses ID maps to rewrite foreign keys.
 */
if (php_sapi_name() !== 'cli') {
    die('CLI only.');
}

$base = dirname(__DIR__, 2);
require_once $base . '/admin/config/config.php';
if (!isset($GLOBALS['db'])) {
    die("Modular DB not configured.\n");
}

$sourceDsn = getenv('SIMUZE_SOURCE_DSN') ?: (defined('SIMUZE_SOURCE_DSN') ? SIMUZE_SOURCE_DSN : '');
$sourceUser = getenv('SIMUZE_SOURCE_USER') ?: (defined('SIMUZE_SOURCE_USER') ? SIMUZE_SOURCE_USER : '');
$sourcePass = getenv('SIMUZE_SOURCE_PASS') ?: (defined('SIMUZE_SOURCE_PASS') ? SIMUZE_SOURCE_PASS : '');

if ($sourceDsn === '' || $sourceUser === '') {
    echo "Set SIMUZE_SOURCE_DSN and SIMUZE_SOURCE_USER (and SIMUZE_SOURCE_PASS) in env or config.\n";
    echo "Example: SIMUZE_SOURCE_DSN='mysql:host=127.0.0.1;dbname=simuze;charset=utf8mb4' php admin/scripts/simuze-import.php\n";
    exit(1);
}

try {
    $source = new PDO($sourceDsn, $sourceUser, $sourcePass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) {
    die("Source DB connection failed: " . $e->getMessage() . "\n");
}

$target = $GLOBALS['db'];
$userMap = [];
$postMap = [];
$mediaMap = [];

echo "1. Users (wp_users → users)\n";
$stmt = $source->query("SELECT ID, user_login, user_pass, user_email, user_registered FROM wp_users");
$targetUserStmt = $target->prepare("INSERT INTO users (username, email, password_hash, created_at, updated_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE updated_at = NOW()");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $check = $target->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$row['user_email']]);
    $existing = $check->fetchColumn();
    if ($existing) {
        $userMap[(int)$row['ID']] = (int) $existing;
        continue;
    }
    $targetUserStmt->execute([
        $row['user_login'] ?: $row['user_email'],
        $row['user_email'],
        $row['user_pass'] ?: password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
        $row['user_registered'] ?: date('Y-m-d H:i:s'),
    ]);
    $userMap[(int)$row['ID']] = (int) $target->lastInsertId();
}
echo "   Mapped " . count($userMap) . " users.\n";

echo "2. Media (wp_music_uploads if present → media)\n";
$hasMusicUploads = false;
try {
    $source->query("SELECT 1 FROM wp_music_uploads LIMIT 1");
    $hasMusicUploads = true;
} catch (Throwable $e) {}
if ($hasMusicUploads) {
    $stmt = $source->query("SELECT * FROM wp_music_uploads");
    $ins = $target->prepare("INSERT INTO media (filename, file_path, file_type, mime_type, file_size, uploaded_by, created_by, updated_by, created_at, updated_at) VALUES (?, ?, 'audio', ?, ?, 1, 1, 1, NOW(), NOW())");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ins->execute([
            $row['filename'] ?? 'audio',
            $row['file_path'] ?? '',
            $row['mime_type'] ?? 'audio/mpeg',
            (int)($row['file_size'] ?? 0),
        ]);
        $mediaMap[(int)$row['id']] = (int) $target->lastInsertId();
    }
    echo "   Mapped " . count($mediaMap) . " media.\n";
} else {
    echo "   wp_music_uploads not found; skip.\n";
}

echo "3. Post types (resolve album, track, playlist, event, page)\n";
$typeSlugToId = [];
foreach (['album', 'track', 'playlist', 'event', 'page', 'post'] as $slug) {
    $q = $target->prepare("SELECT id FROM post_types WHERE slug = ?");
    $q->execute([$slug]);
    $id = $q->fetchColumn();
    if ($id) {
        $typeSlugToId[$slug] = (int) $id;
    }
}
if (!isset($typeSlugToId['post'])) {
    $q = $target->query("SELECT id FROM post_types LIMIT 1");
    $typeSlugToId['post'] = (int) $q->fetchColumn();
}

echo "4. Posts (wp_posts → posts)\n";
$wpTypeToSlug = ['album' => 'album', 'track' => 'track', 'playlist' => 'playlist', 'event' => 'event', 'page' => 'page', 'post' => 'post'];
$stmt = $source->query("SELECT ID, post_author, post_date, post_content, post_title, post_name, post_status, post_type FROM wp_posts WHERE post_type IN ('album','track','playlist','event','page','post') AND post_status IN ('publish','draft','pending')");
$insPost = $target->prepare("
    INSERT INTO posts (post_type_id, slug, title, excerpt, status, component_data, author_id, created_by, updated_by, created_at, updated_at, published_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $typeSlug = $wpTypeToSlug[$row['post_type']] ?? 'post';
    $postTypeId = $typeSlugToId[$typeSlug] ?? $typeSlugToId['post'];
    $authorId = $userMap[(int)$row['post_author']] ?? 1;
    $status = $row['post_status'] === 'publish' ? 'published' : ($row['post_status'] === 'pending' ? 'scheduled' : 'draft');
    $publishedAt = $status === 'published' && !empty($row['post_date']) ? $row['post_date'] : null;
    $insPost->execute([
        $postTypeId,
        $row['post_name'] ?: ('post-' . $row['ID']),
        $row['post_title'] ?: 'Untitled',
        substr($row['post_content'], 0, 500),
        $status,
        json_encode(['data' => []]),
        $authorId,
        $authorId,
        $authorId,
        $publishedAt,
    ]);
    $postMap[(int)$row['ID']] = (int) $target->lastInsertId();
}
echo "   Mapped " . count($postMap) . " posts.\n";

echo "5. Simuze module tables (copy if structure matches)\n";
$simuzeTables = ['groups', 'group_types', 'group_members', 'conversations', 'conversation_participants', 'messages', 'reviews', 'reports', 'activity'];
foreach ($simuzeTables as $t) {
    $srcTable = 'simuze_' . $t;
    $targetTable = 'simuze_' . $t;
    try {
        $source->query("SELECT 1 FROM {$srcTable} LIMIT 1");
    } catch (Throwable $e) {
        continue;
    }
    try {
        $target->query("SELECT 1 FROM {$targetTable} LIMIT 1");
    } catch (Throwable $e) {
        echo "   Skip {$targetTable} (missing in target).\n";
        continue;
    }
    $cols = $source->query("SHOW COLUMNS FROM {$srcTable}")->fetchAll(PDO::FETCH_COLUMN);
    $stmt = $source->query("SELECT * FROM {$srcTable}");
    $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        // Simple insert: map user IDs if column names contain user/author/reviewer/reporter
        foreach (['owner_id', 'user_id', 'reporter_id', 'reviewer_id', 'author_id'] as $key) {
            if (isset($row[$key]) && $row[$key] && isset($userMap[(int)$row[$key]])) {
                $row[$key] = $userMap[(int)$row[$key]];
            }
        }
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $target->prepare("INSERT IGNORE INTO {$targetTable} (" . implode(',', $cols) . ") VALUES ({$placeholders})")->execute(array_values($row));
    }
    echo "   {$targetTable}: {$count} rows.\n";
}

echo "Done. Review ID maps (userMap, postMap, mediaMap) if you need to fix relation data in component_data.\n";
