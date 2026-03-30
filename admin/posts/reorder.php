<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/functions/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

$token = isset($payload['_token']) ? (string)$payload['_token'] : '';
$expectedToken = $_SESSION['_csrf_token'] ?? '';
if ($expectedToken === '' || !hash_equals($expectedToken, $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$moves = isset($payload['moves']) && is_array($payload['moves']) ? $payload['moves'] : [];
if (empty($moves)) {
    echo json_encode(['success' => true]);
    exit;
}

$db = $GLOBALS['db'] ?? null;
if (!$db instanceof PDO) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database unavailable']);
    exit;
}

try {
    $db->beginTransaction();

    $postTypeId = null;
    foreach ($moves as $move) {
        $id = isset($move['id']) ? (int)$move['id'] : 0;
        $stmt = $db->prepare("SELECT p.id, p.post_type_id, pt.hierarchical FROM posts p INNER JOIN post_types pt ON p.post_type_id = pt.id WHERE p.id = ? AND p.deleted_at IS NULL");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['hierarchical'])) {
            throw new RuntimeException('Invalid hierarchical post move.');
        }
        if ($postTypeId === null) {
            $postTypeId = (int)$row['post_type_id'];
        } elseif ($postTypeId !== (int)$row['post_type_id']) {
            throw new RuntimeException('Cannot reorder across post types.');
        }
    }

    foreach ($moves as $move) {
        $id = (int)($move['id'] ?? 0);
        $parentId = array_key_exists('parent_id', $move) && $move['parent_id'] !== null ? (int)$move['parent_id'] : null;
        $order = (int)($move['order'] ?? 0);

        if ($parentId !== null) {
            $stmtParent = $db->prepare("SELECT id, post_type_id, parent_id FROM posts WHERE id = ? AND deleted_at IS NULL");
            $stmtParent->execute([$parentId]);
            $parent = $stmtParent->fetch(PDO::FETCH_ASSOC);
            if (!$parent || (int)$parent['post_type_id'] !== (int)$postTypeId) {
                throw new RuntimeException('Invalid parent.');
            }
            // Depth <= 3
            $depth = 1;
            $cursor = $parent['parent_id'] !== null ? (int)$parent['parent_id'] : null;
            while ($cursor !== null) {
                $depth++;
                if ($depth >= 3) {
                    throw new RuntimeException('Maximum depth is 3.');
                }
                $stmtDepth = $db->prepare("SELECT parent_id FROM posts WHERE id = ? AND deleted_at IS NULL");
                $stmtDepth->execute([$cursor]);
                $depthRow = $stmtDepth->fetch(PDO::FETCH_ASSOC);
                $cursor = $depthRow && $depthRow['parent_id'] !== null ? (int)$depthRow['parent_id'] : null;
            }
            // No cycle
            $cycle = $parentId;
            while ($cycle !== null) {
                if ($cycle === $id) {
                    throw new RuntimeException('Cycle detected.');
                }
                $stmtCycle = $db->prepare("SELECT parent_id FROM posts WHERE id = ? AND deleted_at IS NULL");
                $stmtCycle->execute([$cycle]);
                $cycleRow = $stmtCycle->fetch(PDO::FETCH_ASSOC);
                $cycle = $cycleRow && $cycleRow['parent_id'] !== null ? (int)$cycleRow['parent_id'] : null;
            }
        }

        $stmtUpdate = $db->prepare("UPDATE posts SET parent_id = ?, `order` = ?, updated_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$parentId, $order, $id]);
    }

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
