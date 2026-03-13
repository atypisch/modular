<?php
/**
 * Simuze Community – Messages admin (full CRUD)
 * Conversations: create, edit, delete. Messages: create, edit, delete per conversation.
 * Tables: simuze_conversations, simuze_conversation_participants, simuze_messages.
 */
$db = $GLOBALS['db'] ?? null;
if (!defined('SIMUZE_DB_PREFIX')) {
    define('SIMUZE_DB_PREFIX', 'simuze_');
}
$prefix = SIMUZE_DB_PREFIX;
$tConv = $prefix . 'conversations';
$tPart = $prefix . 'conversation_participants';
$tMsg = $prefix . 'messages';
$baseUrl = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=messages';

if (!$db) {
    echo '<p>Database unavailable.</p>';
    return;
}

foreach ([$tConv, $tMsg] as $t) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($t));
        if ($stmt->rowCount() === 0) {
            echo '<div class="container"><div class="card"><p>Messages tables not found. Activate the Simuze Community module to run migrations.</p></div></div>';
            return;
        }
    } catch (Throwable $e) {
        echo '<div class="container"><div class="card"><p>Error: ' . htmlspecialchars($e->getMessage()) . '</p></div></div>';
        return;
    }
}

// --- POST: conversation add/edit/delete ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $redirectMsg = '';
    try {
        if ($action === 'conv_add') {
            $type = isset($_POST['type']) ? trim((string) $_POST['type']) : 'direct';
            $title = isset($_POST['title']) ? trim((string) $_POST['title']) : null;
            $ins = $db->prepare("INSERT INTO {$tConv} (type, title, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
            $ins->execute([$type, $title ?: null]);
            $redirectMsg = 'message=conv_added';
        } elseif ($action === 'conv_edit' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
            $type = isset($_POST['type']) ? trim((string) $_POST['type']) : 'direct';
            $title = isset($_POST['title']) ? trim((string) $_POST['title']) : null;
            if ($id > 0) {
                $upd = $db->prepare("UPDATE {$tConv} SET type = ?, title = ?, updated_at = NOW() WHERE id = ?");
                $upd->execute([$type, $title ?: null, $id]);
                $redirectMsg = 'message=conv_updated';
            }
        } elseif ($action === 'conv_delete' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
            if ($id > 0) {
                $db->prepare("DELETE FROM {$tMsg} WHERE conversation_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM {$tPart} WHERE conversation_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM {$tConv} WHERE id = ?")->execute([$id]);
                $redirectMsg = 'message=conv_deleted';
            }
        } elseif ($action === 'msg_add' && isset($_POST['conversation_id'])) {
            $cid = (int) $_POST['conversation_id'];
            $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
            $body = isset($_POST['body']) ? trim((string) $_POST['body']) : '';
            if ($cid > 0 && $body !== '') {
                $ins = $db->prepare("INSERT INTO {$tMsg} (conversation_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())");
                $ins->execute([$cid, $user_id, $body]);
                $db->prepare("UPDATE {$tConv} SET updated_at = NOW() WHERE id = ?")->execute([$cid]);
                $redirectMsg = 'view=conversation&id=' . $cid . '&message=msg_added';
            }
        } elseif ($action === 'msg_edit' && isset($_POST['id'], $_POST['conversation_id'])) {
            $id = (int) $_POST['id'];
            $cid = (int) $_POST['conversation_id'];
            $body = isset($_POST['body']) ? trim((string) $_POST['body']) : '';
            if ($id > 0 && $body !== '') {
                $upd = $db->prepare("UPDATE {$tMsg} SET body = ?, edited_at = NOW() WHERE id = ?");
                $upd->execute([$body, $id]);
                $db->prepare("UPDATE {$tConv} SET updated_at = NOW() WHERE id = ?")->execute([$cid]);
                $redirectMsg = 'view=conversation&id=' . $cid . '&message=msg_updated';
            }
        } elseif ($action === 'msg_delete' && isset($_POST['id'], $_POST['conversation_id'])) {
            $id = (int) $_POST['id'];
            $cid = (int) $_POST['conversation_id'];
            if ($id > 0) {
                $db->prepare("DELETE FROM {$tMsg} WHERE id = ?")->execute([$id]);
                $db->prepare("UPDATE {$tConv} SET updated_at = NOW() WHERE id = ?")->execute([$cid]);
                $redirectMsg = 'view=conversation&id=' . $cid . '&message=msg_deleted';
            }
        }
    } catch (Throwable $e) {
        $redirectMsg = 'error=failed';
    }
    if ($redirectMsg !== '') {
        header('Location: ' . $baseUrl . '&' . $redirectMsg);
        exit;
    }
}

$message = '';
$messageType = 'success';
if (isset($_GET['message'])) {
    $m = $_GET['message'];
    if (in_array($m, ['conv_added', 'conv_updated', 'conv_deleted', 'msg_added', 'msg_updated', 'msg_deleted'], true)) {
        $message = $m === 'conv_added' ? 'Conversation added.' : ($m === 'conv_updated' ? 'Conversation updated.' : ($m === 'conv_deleted' ? 'Conversation deleted.' : ($m === 'msg_added' ? 'Message added.' : ($m === 'msg_updated' ? 'Message updated.' : 'Message deleted.'))));
    }
}
if (isset($_GET['error']) && $_GET['error'] === 'failed') {
    $message = 'An error occurred.';
    $messageType = 'danger';
}

$viewConvId = isset($_GET['view']) && $_GET['view'] === 'conversation' && isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Single conversation view: messages list + CRUD
if ($viewConvId > 0) {
    $conv = null;
    try {
        $stmt = $db->prepare("SELECT id, type, title, created_at, updated_at FROM {$tConv} WHERE id = ?");
        $stmt->execute([$viewConvId]);
        $conv = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { }
    if (!$conv) {
        header('Location: ' . $baseUrl);
        exit;
    }
    $messages = [];
    try {
        $stmt = $db->prepare("SELECT id, user_id, body, created_at, edited_at FROM {$tMsg} WHERE conversation_id = ? ORDER BY created_at ASC");
        $stmt->execute([$viewConvId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { }
    $editMsgId = isset($_GET['edit_msg']) ? (int) $_GET['edit_msg'] : 0;
    $editMsgRow = null;
    if ($editMsgId > 0) {
        foreach ($messages as $m) {
            if ((int) $m['id'] === $editMsgId) {
                $editMsgRow = $m;
                break;
            }
        }
    }
    ?>
    <div class="container">
        <div class="page-header">
            <h1>Conversation #<?php echo (int) $conv['id']; ?></h1>
            <p><a href="<?php echo htmlspecialchars($baseUrl); ?>">← Back to conversations</a></p>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <div class="card mb-4">
            <div class="card-body">
                <p><strong>Type:</strong> <?php echo htmlspecialchars($conv['type'] ?? 'direct'); ?>
                <strong>Title:</strong> <?php echo htmlspecialchars($conv['title'] ?? '—'); ?>
                <strong>Updated:</strong> <?php echo htmlspecialchars($conv['updated_at'] ?? ''); ?></p>
                <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>" class="d-inline">
                    <input type="hidden" name="action" value="conv_edit">
                    <input type="hidden" name="id" value="<?php echo (int) $conv['id']; ?>">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($conv['type'] ?? 'direct'); ?>">
                    <input type="text" name="title" value="<?php echo htmlspecialchars($conv['title'] ?? ''); ?>" placeholder="Title">
                    <button type="submit" class="btn btn-sm btn-secondary">Update title</button>
                </form>
                <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>" class="d-inline" onsubmit="return confirm('Delete this conversation and all messages?');">
                    <input type="hidden" name="action" value="conv_delete">
                    <input type="hidden" name="id" value="<?php echo (int) $conv['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete conversation</button>
                </form>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5"><?php echo $editMsgRow ? 'Edit Message' : 'Add Message'; ?></h2>
                <form method="post" action="<?php echo htmlspecialchars($baseUrl . '&view=conversation&id=' . $viewConvId); ?>">
                    <input type="hidden" name="action" value="<?php echo $editMsgRow ? 'msg_edit' : 'msg_add'; ?>">
                    <input type="hidden" name="conversation_id" value="<?php echo (int) $viewConvId; ?>">
                    <?php if ($editMsgRow): ?>
                        <input type="hidden" name="id" value="<?php echo (int) $editMsgRow['id']; ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Author (user id)</label>
                        <input type="number" name="user_id" class="form-control" min="0" value="<?php echo $editMsgRow ? (int)($editMsgRow['user_id'] ?? 0) : 0; ?>">
                    </div>
                    <div class="form-group">
                        <label>Body</label>
                        <textarea name="body" class="form-control" rows="2" required><?php echo $editMsgRow ? htmlspecialchars($editMsgRow['body'] ?? '') : ''; ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php echo $editMsgRow ? 'Update message' : 'Add message'; ?></button>
                    <?php if ($editMsgRow): ?>
                        <a href="<?php echo htmlspecialchars($baseUrl . '&view=conversation&id=' . $viewConvId); ?>" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h2 class="h5">Messages</h2>
                <?php if (empty($messages)): ?>
                    <p>No messages yet.</p>
                <?php else: ?>
                    <table class="table table-sm">
                        <thead>
                            <tr><th>ID</th><th>User</th><th>Body</th><th>Created</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $m): ?>
                                <tr>
                                    <td><?php echo (int) $m['id']; ?></td>
                                    <td><?php echo (int) ($m['user_id'] ?? 0); ?></td>
                                    <td><?php echo htmlspecialchars(mb_substr($m['body'] ?? '', 0, 80)); ?><?php echo mb_strlen($m['body'] ?? '') > 80 ? '…' : ''; ?></td>
                                    <td><?php echo htmlspecialchars($m['created_at'] ?? ''); ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($baseUrl . '&view=conversation&id=' . $viewConvId . '&edit_msg=' . (int) $m['id']); ?>">Edit</a>
                                        |
                                        <form method="post" action="<?php echo htmlspecialchars($baseUrl . '&view=conversation&id=' . $viewConvId); ?>" class="d-inline" onsubmit="return confirm('Delete this message?');">
                                            <input type="hidden" name="action" value="msg_delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $m['id']; ?>">
                                            <input type="hidden" name="conversation_id" value="<?php echo (int) $viewConvId; ?>">
                                            <button type="submit" class="btn btn-link p-0 text-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Conversations list + add/edit
$conversations = [];
try {
    $stmt = $db->query("SELECT id, type, title, created_at, updated_at FROM {$tConv} ORDER BY updated_at DESC LIMIT 100");
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { }
$editConvId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editConvRow = null;
if ($editConvId > 0) {
    foreach ($conversations as $c) {
        if ((int) $c['id'] === $editConvId) {
            $editConvRow = $c;
            break;
        }
    }
}
?>
<div class="container">
    <div class="page-header">
        <h1>Messages</h1>
        <p>Conversations and direct messages. Create or edit conversations; open one to manage messages.</p>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5"><?php echo $editConvRow ? 'Edit Conversation' : 'Add Conversation'; ?></h2>
            <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>">
                <input type="hidden" name="action" value="<?php echo $editConvRow ? 'conv_edit' : 'conv_add'; ?>">
                <?php if ($editConvRow): ?><input type="hidden" name="id" value="<?php echo (int) $editConvRow['id']; ?>"><?php endif; ?>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" class="form-control">
                        <option value="direct" <?php echo ($editConvRow && ($editConvRow['type'] ?? '') === 'direct') ? 'selected' : ''; ?>>Direct</option>
                        <option value="group" <?php echo ($editConvRow && ($editConvRow['type'] ?? '') === 'group') ? 'selected' : ''; ?>>Group</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title (optional)</label>
                    <input type="text" name="title" class="form-control" value="<?php echo $editConvRow ? htmlspecialchars($editConvRow['title'] ?? '') : ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $editConvRow ? 'Update conversation' : 'Add conversation'; ?></button>
                <?php if ($editConvRow): ?>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h2 class="h5">Conversations</h2>
            <?php if (empty($conversations)): ?>
                <p>No conversations yet. Add one above.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr><th>ID</th><th>Type</th><th>Title</th><th>Updated</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conversations as $c): ?>
                            <tr>
                                <td><?php echo (int) $c['id']; ?></td>
                                <td><?php echo htmlspecialchars($c['type'] ?? 'direct'); ?></td>
                                <td><?php echo htmlspecialchars($c['title'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($c['updated_at'] ?? ''); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($baseUrl . '&view=conversation&id=' . (int) $c['id']); ?>">View / Messages</a>
                                    |
                                    <a href="<?php echo htmlspecialchars($baseUrl . '&edit=' . (int) $c['id']); ?>">Edit</a>
                                    |
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this conversation and all messages?');">
                                        <input type="hidden" name="action" value="conv_delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
                                        <button type="submit" class="btn btn-link p-0 text-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
