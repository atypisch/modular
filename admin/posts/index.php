<?php
/**
 * Posts Index Page - Japanese Style
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/data/init.php';
require_once CORE_PATH . '/functions/auth.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

$page_title = 'Posts';
$posts_active = true;

$db = $GLOBALS['db'];

$postTypeId = null;
if (isset($_GET['post_type_id'])) {
    $rawPt = $_GET['post_type_id'];
    if ($rawPt === '' || $rawPt === 'all' || $rawPt === '0') {
        admin_posts_filter_remember(null);
    } else {
        $postTypeId = (int) $rawPt;
        if ($postTypeId > 0) {
            admin_posts_filter_remember($postTypeId);
        } else {
            $postTypeId = null;
            admin_posts_filter_remember(null);
        }
    }
}

$listStatus = admin_posts_list_status_from_request(isset($_GET['status']) ? (string) $_GET['status'] : null);
$isTrashView = $listStatus === 'trash';
$isFilteredView = $listStatus !== '';

// 'show' post type is managed via simuze_events — redirect to dedicated admin
if ($postTypeId) {
    try {
        $stmtSlug = $db->prepare("SELECT slug FROM post_types WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmtSlug->execute([$postTypeId]);
        $ptSlugRow = $stmtSlug->fetch(\PDO::FETCH_ASSOC);
        if ($ptSlugRow && ($ptSlugRow['slug'] ?? '') === 'show') {
            $showsAdminUrl = (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=simuze-community&page=shows';
            header('Location: ' . $showsAdminUrl);
            exit;
        }
    } catch (\Throwable $e) { /* ignore, continue normally */ }
}

// Get post types for filter
$postTypes = [];
// Map index voor hiërarchie-informatie
$postTypesById = [];
try {
    $stmt = $db->query("SELECT id, name, slug, hierarchical FROM post_types WHERE deleted_at IS NULL ORDER BY name");
    $postTypes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($postTypes as $pt) {
        $postTypesById[(int)$pt['id']] = $pt;
    }
} catch (Exception $e) {
    error_log("Error loading post types: " . $e->getMessage());
}

// Get posts (active or trash)
$posts = [];
$message = '';
$isHierarchicalListing = false;
$trashCount = 0;
$draftCount = 0;
$scheduledCount = 0;

try {
    $trashCount = admin_posts_list_count($db, $postTypeId, 'trash');
    $draftCount = admin_posts_list_count($db, $postTypeId, 'draft');
    $scheduledCount = admin_posts_list_count($db, $postTypeId, 'scheduled');
} catch (Exception $e) {
    error_log('Error loading post list counts: ' . $e->getMessage());
}

try {
    $sql = "
        SELECT p.*, pt.name as post_type_name, pt.slug as post_type_slug, pt.hierarchical as post_type_hierarchical,
               COALESCE(u.username, u.email, 'Unknown') as author_name
        FROM posts p
        INNER JOIN post_types pt ON p.post_type_id = pt.id
        LEFT JOIN users u ON p.author_id = u.id
        WHERE p.deleted_at " . ($isTrashView ? 'IS NOT NULL' : 'IS NULL') . "
    ";
    
    $params = [];
    if ($postTypeId) {
        $sql .= " AND p.post_type_id = ?";
        $params[] = $postTypeId;
    }
    if (!$isTrashView && $listStatus === 'draft') {
        $sql .= " AND p.status = 'draft'";
    } elseif (!$isTrashView && $listStatus === 'scheduled') {
        $sql .= " AND p.status = 'scheduled'";
    }
    if ($postTypeId && !$isFilteredView && isset($postTypesById[(int)$postTypeId]) && !empty($postTypesById[(int)$postTypeId]['hierarchical'])) {
        $isHierarchicalListing = true;
    }
    
    if ($isTrashView) {
        $sql .= " ORDER BY p.deleted_at DESC";
    } elseif ($listStatus === 'scheduled') {
        $sql .= " ORDER BY p.published_at ASC, p.created_at DESC";
    } elseif ($isHierarchicalListing) {
        $sql .= " ORDER BY p.`order`, p.title";
    } else {
        $sql .= " ORDER BY p.created_at DESC";
    }
    $sql .= " LIMIT 500";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    // Note: author_name is already set in the SQL query above using email
    // No need for post-processing since we're not using username column
    
} catch (Exception $e) {
    $message = "Error loading posts: " . $e->getMessage();
    error_log($message);
    
    // Fallback: join users without deleted_at and use only email for author (works with minimal users table)
    try {
        $sql = "
            SELECT p.*, pt.name as post_type_name, pt.slug as post_type_slug,
                   COALESCE(u.email, 'Unknown') as author_name
            FROM posts p
            INNER JOIN post_types pt ON p.post_type_id = pt.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.deleted_at " . ($isTrashView ? 'IS NOT NULL' : 'IS NULL') . "
        ";
        
        $params = [];
        if ($postTypeId) {
            $sql .= " AND p.post_type_id = ?";
            $params[] = $postTypeId;
        }
        if (!$isTrashView && $listStatus === 'draft') {
            $sql .= " AND p.status = 'draft'";
        } elseif (!$isTrashView && $listStatus === 'scheduled') {
            $sql .= " AND p.status = 'scheduled'";
        }
        
        $sql .= $isTrashView ? " ORDER BY p.deleted_at DESC LIMIT 100" : " ORDER BY p.created_at DESC LIMIT 100";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $message = "Posts loaded (author information unavailable)";
    } catch (Exception $e2) {
        error_log("Fallback query also failed: " . $e2->getMessage());
    }
}

// Build a depth-first tree view for hierarchical post types
$displayPosts = $posts;
if ($isHierarchicalListing && !empty($posts)) {
    $byParent = [];
    foreach ($posts as $row) {
        $parentKey = $row['parent_id'] !== null ? (int)$row['parent_id'] : 0;
        if (!isset($byParent[$parentKey])) {
            $byParent[$parentKey] = [];
        }
        $byParent[$parentKey][] = $row;
    }
    $displayPosts = [];
    $walkTree = function ($parentId, $depth) use (&$walkTree, &$displayPosts, $byParent) {
        $bucketKey = $parentId !== null ? (int)$parentId : 0;
        if (empty($byParent[$bucketKey])) {
            return;
        }
        foreach ($byParent[$bucketKey] as $child) {
            $child['_depth'] = $depth;
            $displayPosts[] = $child;
            $walkTree((int)$child['id'], $depth + 1);
        }
    };
    $walkTree(0, 0);
}

// Detect the front page post ID so we can label it in the list
$frontPageId = 0;
if (function_exists('get_setting')) {
    $frontPageId = (get_setting('front_page_type', 'index') === 'page') ? (int) get_setting('front_page_id', 0) : 0;
} else {
    try {
        $fpStmt = $db->prepare("SELECT option_value FROM settings WHERE option_name = 'front_page_type' LIMIT 1");
        $fpStmt->execute();
        $fpRow = $fpStmt->fetch(\PDO::FETCH_ASSOC);
        if ($fpRow && $fpRow['option_value'] === 'page') {
            $fpStmt2 = $db->prepare("SELECT option_value FROM settings WHERE option_name = 'front_page_id' LIMIT 1");
            $fpStmt2->execute();
            $fpRow2 = $fpStmt2->fetch(\PDO::FETCH_ASSOC);
            $frontPageId = $fpRow2 ? (int) $fpRow2['option_value'] : 0;
        }
    } catch (\Throwable $e) { /* ignore */ }
}

require_once ADMIN_PATH . '/templates/header.php';

$postsListAllUrl = admin_posts_index_url($postTypeId);
$postsListDraftUrl = admin_posts_index_url($postTypeId, 'draft');
$postsListScheduledUrl = admin_posts_index_url($postTypeId, 'scheduled');
$postsListTrashUrl = admin_posts_index_url($postTypeId, 'trash');

$listPageTitles = [
    '' => 'Posts',
    'draft' => 'Drafts',
    'scheduled' => 'Scheduled',
    'trash' => 'Trash',
];
$pageHeading = $listPageTitles[$listStatus] ?? 'Posts';
?>

<style>
.posts-status-nav { display:flex; gap:0.5rem; margin:0 0 1rem; padding:0; list-style:none; border-bottom:1px solid var(--border-color,#dee2e6); }
.posts-status-nav a { display:inline-block; padding:0.5rem 0.75rem; text-decoration:none; color:var(--text-muted,#64748b); border-bottom:2px solid transparent; margin-bottom:-1px; }
.posts-status-nav a.is-active { color:var(--text-primary,#0f172a); border-bottom-color:var(--primary-color,#0ea5e9); font-weight:600; }
.posts-status-nav .posts-view-count { font-size:0.75rem; opacity:0.85; margin-left:0.15rem; }
</style>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1><?php echo htmlspecialchars($pageHeading); ?></h1>
            <div class="flex gap-2">
                <?php if (!empty($postTypes)): ?>
                    <select id="post-type-filter" class="form-control" style="width: auto; min-width: 200px;" onchange="filterByPostType(this.value)">
                        <option value="" <?php echo !$postTypeId ? 'selected' : ''; ?>>All Post Types</option>
                        <?php foreach ($postTypes as $pt): ?>
                            <option value="<?php echo $pt['id']; ?>" <?php echo $postTypeId == $pt['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pt['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <?php if (!$isTrashView): ?>
                <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/posts/edit.php<?php echo $postTypeId ? '?post_type_id=' . $postTypeId : ''; ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Post
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <ul class="posts-status-nav" aria-label="Post list views">
        <li><a href="<?php echo htmlspecialchars($postsListAllUrl, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $listStatus === '' ? 'is-active' : ''; ?>">All</a></li>
        <li><a href="<?php echo htmlspecialchars($postsListDraftUrl, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $listStatus === 'draft' ? 'is-active' : ''; ?>">Draft<?php if ($draftCount > 0): ?><span class="posts-view-count">(<?php echo (int) $draftCount; ?>)</span><?php endif; ?></a></li>
        <li><a href="<?php echo htmlspecialchars($postsListScheduledUrl, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $listStatus === 'scheduled' ? 'is-active' : ''; ?>">Scheduled<?php if ($scheduledCount > 0): ?><span class="posts-view-count">(<?php echo (int) $scheduledCount; ?>)</span><?php endif; ?></a></li>
        <li><a href="<?php echo htmlspecialchars($postsListTrashUrl, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $listStatus === 'trash' ? 'is-active' : ''; ?>">Trash<?php if ($trashCount > 0): ?><span class="posts-view-count">(<?php echo (int) $trashCount; ?>)</span><?php endif; ?></a></li>
    </ul>

    <?php if ($message): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars((string) $_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card" id="posts-admin-page" data-posts-base-url="<?php echo htmlspecialchars(admin_url('posts'), ENT_QUOTES, 'UTF-8'); ?>" data-list-status="<?php echo htmlspecialchars($listStatus, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <i class="fas fa-<?php echo $isTrashView ? 'trash' : ($listStatus === 'scheduled' ? 'clock' : 'file-alt'); ?>"></i>
                <h3><?php
                    if ($isTrashView) {
                        echo 'Trash is empty';
                    } elseif ($listStatus === 'draft') {
                        echo 'No drafts found';
                    } elseif ($listStatus === 'scheduled') {
                        echo 'No scheduled posts found';
                    } else {
                        echo 'No Posts Found';
                    }
                ?></h3>
                <p><?php
                    if ($isTrashView) {
                        echo 'Trashed items from all post types appear here.';
                    } elseif ($listStatus === 'draft') {
                        echo 'Draft posts from all post types appear here when filtered.';
                    } elseif ($listStatus === 'scheduled') {
                        echo 'Scheduled posts from all post types appear here when filtered.';
                    } else {
                        echo 'Create your first post to get started.';
                    }
                ?></p>
                <?php if (!$isTrashView): ?>
                <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/posts/edit.php<?php echo $postTypeId ? '?post_type_id=' . $postTypeId : ''; ?>" class="btn btn-primary mt-4">
                    <i class="fas fa-plus"></i> Create Post
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php
            $postsTableColspan = 6 + ($isTrashView ? 0 : 1) + ($isHierarchicalListing ? 1 : 0);
            ?>
            <form id="posts-bulk-form" class="posts-bulk-form" method="post" action="<?php echo htmlspecialchars(admin_url('posts/bulk-actions.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>
                <input type="hidden" name="redirect_post_type_id" value="<?php echo htmlspecialchars((string) ($postTypeId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="redirect_status" value="<?php echo htmlspecialchars($listStatus, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="posts-bulk-toolbar flex flex-wrap items-center gap-2" style="padding:0.75rem 1rem;border-bottom:1px solid var(--border-color,#dee2e6);">
                    <label class="flex items-center gap-2" style="margin:0;">
                        <span class="text-muted" style="font-size:0.875rem;">Bulk actions</span>
                        <select name="bulk_action" id="posts-bulk-action" class="form-control" style="width:auto;min-width:12rem;">
                            <option value="">Choose action…</option>
                            <?php if ($isTrashView): ?>
                            <option value="delete_forever">Delete permanently</option>
                            <?php else: ?>
                            <option value="trash">Move to trash</option>
                            <option value="draft">Set status to Draft</option>
                            <option value="published">Set status to Published</option>
                            <option value="archived">Set status to Archived</option>
                            <?php endif; ?>
                        </select>
                    </label>
                    <button type="submit" class="btn btn-secondary" id="posts-bulk-apply">Apply</button>
                </div>
                <table class="table" id="posts-table" data-hierarchical="<?php echo $isHierarchicalListing ? '1' : '0'; ?>" data-csrf="<?php echo function_exists('csrf_token') ? htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') : ''; ?>" data-reorder-url="<?php echo htmlspecialchars(admin_url('posts/reorder.php'), ENT_QUOTES, 'UTF-8'); ?>" data-quick-save-url="<?php echo htmlspecialchars(admin_url('posts/quick-save.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <thead>
                    <tr>
                        <th style="width: 2.5rem;">
                            <input type="checkbox" id="posts-select-all" title="Select all" aria-label="Select all">
                        </th>
                        <?php if ($isHierarchicalListing): ?>
                            <th style="width: 44px;"></th>
                        <?php endif; ?>
                        <th>Title</th>
                        <th>Post Type</th>
                        <th>Author</th>
                        <?php if ($isTrashView): ?>
                        <th>Trashed</th>
                        <?php else: ?>
                        <th>Status</th>
                        <th>Created</th>
                        <?php endif; ?>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($displayPosts as $post): ?>
                        <?php
                        $depth = isset($post['_depth']) ? (int)$post['_depth'] : 0;
                        $titlePrefix = $depth > 0 ? str_repeat('— ', $depth) : '';
                        $pid = (int) $post['id'];
                        $row_view = '';
                        $row_preview = '';
                        if (trim((string) ($post['slug'] ?? '')) !== '') {
                            $row_view = function_exists('get_permalink') ? get_permalink($pid) : '';
                            $row_preview = function_exists('get_post_preview_url') ? get_post_preview_url($pid) : '';
                        }
                        $status = $post['status'] ?? 'draft';
                        $statusColors = [
                            'published' => 'badge-success',
                            'draft' => 'badge-secondary',
                            'archived' => 'badge-warning',
                            'scheduled' => 'badge-secondary',
                        ];
                        $statusColor = $statusColors[$status] ?? 'badge-secondary';
                        $publishedRaw = $post['published_at'] ?? '';
                        $dtLocal = '';
                        if ($publishedRaw !== '' && $publishedRaw !== null) {
                            $ts = strtotime((string) $publishedRaw);
                            if ($ts !== false) {
                                $dtLocal = date('Y-m-d\TH:i', $ts);
                            }
                        }
                        ?>
                        <tr class="post-main-row" data-post-id="<?php echo $pid; ?>" data-parent-id="<?php echo $post['parent_id'] !== null ? (int)$post['parent_id'] : ''; ?>" data-depth="<?php echo $depth; ?>">
                            <td>
                                <input type="checkbox" class="post-select-checkbox" name="post_ids[]" value="<?php echo $pid; ?>">
                            </td>
                            <?php if ($isHierarchicalListing): ?>
                                <td style="text-align:center;">
                                    <button type="button" class="btn btn-sm btn-secondary post-drag-handle" draggable="true" title="Sleep om te ordenen" aria-label="Sleep om te ordenen">
                                        <i class="fas fa-grip-vertical"></i>
                                    </button>
                                </td>
                            <?php endif; ?>
                            <td class="post-title-cell">
                                <strong class="post-title-label" style="display: inline-block; padding-left: <?php echo $depth * 14; ?>px;">
                                    <?php echo htmlspecialchars($titlePrefix . ($post['title'] ?? 'Untitled')); ?>
                                </strong>
                                <?php if ($frontPageId > 0 && $pid === $frontPageId): ?>
                                    <span class="badge badge-info" style="font-size:0.7rem;vertical-align:middle;margin-left:0.35rem;">Homepage</span>
                                <?php endif; ?>
                                <?php if (!$isTrashView): ?>
                                <div class="post-row-actions">
                                    <a href="<?php echo htmlspecialchars(admin_url('posts/edit.php?id=' . $pid), ENT_QUOTES, 'UTF-8'); ?>">Edit</a>
                                    <span class="post-row-actions-sep">|</span>
                                    <button type="button" class="post-row-quick-edit-btn">Quick edit</button>
                                    <?php if ($row_view !== ''): ?>
                                        <span class="post-row-actions-sep">|</span>
                                        <a class="post-row-view-link" href="<?php echo htmlspecialchars($row_view, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">View</a>
                                    <?php endif; ?>
                                    <?php if ($row_preview !== ''): ?>
                                        <span class="post-row-actions-sep">|</span>
                                        <a class="post-row-preview-link" href="<?php echo htmlspecialchars($row_preview, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Preview</a>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($post['post_type_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></td>
                            <?php if ($isTrashView): ?>
                            <td><?php echo !empty($post['deleted_at']) ? date('Y-m-d H:i', strtotime($post['deleted_at'])) : '—'; ?></td>
                            <?php else: ?>
                            <td class="post-status-cell">
                                <span class="badge <?php echo $statusColor; ?> post-status-badge"><?php echo htmlspecialchars(ucfirst($status)); ?></span>
                            </td>
                            <td class="post-created-cell"><?php echo date('Y-m-d H:i', strtotime($post['created_at'] ?? 'now')); ?></td>
                            <?php endif; ?>
                            <td style="text-align: right;">
                                <div class="flex gap-2 justify-end">
                                    <?php if ($isTrashView): ?>
                                    <a href="<?php echo htmlspecialchars(admin_url('posts/permanent-delete.php?id=' . $pid . ($postTypeId ? '&post_type_id=' . (int) $postTypeId : '')), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-sm btn-danger"
                                       data-confirm="Permanently delete this post? This cannot be undone.">
                                        <i class="fas fa-trash"></i> Delete permanently
                                    </a>
                                    <?php else: ?>
                                    <a href="<?php echo htmlspecialchars(admin_url('posts/edit.php?id=' . $pid), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="<?php echo htmlspecialchars(admin_url('posts/delete.php?id=' . $pid . ($postTypeId ? '&post_type_id=' . (int) $postTypeId : '')), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-sm btn-danger"
                                       data-confirm="Move this post to trash?">
                                        <i class="fas fa-trash"></i> Trash
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php if (!$isTrashView): ?>
                        <tr class="post-quick-edit-row" hidden>
                            <td colspan="<?php echo (int) $postsTableColspan; ?>" class="post-quick-edit-cell">
                                <div class="post-quick-edit-inner">
                                    <div class="post-quick-edit-grid">
                                        <div class="form-group">
                                            <label>Title</label>
                                            <input type="text" class="form-control qe-title" value="<?php echo htmlspecialchars($post['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Slug</label>
                                            <input type="text" class="form-control qe-slug" value="<?php echo htmlspecialchars($post['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select class="form-control qe-status">
                                                <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                                                <option value="scheduled" <?php echo $status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                                <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Published</label>
                                            <input type="datetime-local" class="form-control qe-published" value="<?php echo htmlspecialchars($dtLocal, ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-group qe-excerpt-wrap">
                                            <label>Excerpt</label>
                                            <textarea class="form-control qe-excerpt" rows="3"><?php echo htmlspecialchars($post['excerpt'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="post-quick-edit-actions flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-primary qe-save">Update</button>
                                        <button type="button" class="btn btn-secondary qe-cancel">Cancel</button>
                                        <span class="qe-message text-muted" style="font-size:0.875rem;"></span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function filterByPostType(postTypeId) {
    var base = document.getElementById('posts-admin-page');
    var u = (base && base.getAttribute('data-posts-base-url')) ? base.getAttribute('data-posts-base-url') : '';
    var listStatus = (base && base.getAttribute('data-list-status')) ? base.getAttribute('data-list-status') : '';
    if (!u) { u = '<?php echo htmlspecialchars(admin_url('posts'), ENT_QUOTES, 'UTF-8'); ?>'; }
    var qs = [];
    if (postTypeId) {
        qs.push('post_type_id=' + encodeURIComponent(postTypeId));
    } else {
        qs.push('post_type_id=all');
    }
    if (listStatus) {
        qs.push('status=' + encodeURIComponent(listStatus));
    }
    window.location.href = u + '?' + qs.join('&');
}
</script>
<script src="<?php echo htmlspecialchars(admin_url('assets/js/posts-list.js'), ENT_QUOTES, 'UTF-8'); ?>?v=1"></script>
<script src="<?php echo htmlspecialchars(admin_url('assets/js/post-tree-reorder.js'), ENT_QUOTES, 'UTF-8'); ?>?v=2"></script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
