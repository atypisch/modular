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
$postTypeId = $_GET['post_type_id'] ?? null;

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

// Get posts
$posts = [];
$message = '';
$isHierarchicalListing = false;

try {
    $sql = "
        SELECT p.*, pt.name as post_type_name, pt.slug as post_type_slug, pt.hierarchical as post_type_hierarchical,
               COALESCE(u.username, u.email, 'Unknown') as author_name
        FROM posts p
        INNER JOIN post_types pt ON p.post_type_id = pt.id
        LEFT JOIN users u ON p.author_id = u.id
        WHERE p.deleted_at IS NULL
    ";
    
    $params = [];
    if ($postTypeId) {
        $sql .= " AND p.post_type_id = ?";
        $params[] = $postTypeId;
        if (isset($postTypesById[(int)$postTypeId]) && !empty($postTypesById[(int)$postTypeId]['hierarchical'])) {
            $isHierarchicalListing = true;
        }
    }
    
    if ($isHierarchicalListing) {
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
            WHERE p.deleted_at IS NULL
        ";
        
        $params = [];
        if ($postTypeId) {
            $sql .= " AND p.post_type_id = ?";
            $params[] = $postTypeId;
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT 100";
        
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

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1>Posts</h1>
            <div class="flex gap-2">
                <?php if (!empty($postTypes)): ?>
                    <select id="post-type-filter" class="form-control" style="width: auto; min-width: 200px;" onchange="filterByPostType(this.value)">
                        <option value="">All Post Types</option>
                        <?php foreach ($postTypes as $pt): ?>
                            <option value="<?php echo $pt['id']; ?>" <?php echo $postTypeId == $pt['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pt['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/posts/edit.php<?php echo $postTypeId ? '?post_type_id=' . $postTypeId : ''; ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Post
                </a>
            </div>
        </div>
    </div>

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

    <div class="card" id="posts-admin-page" data-posts-base-url="<?php echo htmlspecialchars(admin_url('posts'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <h3>No Posts Found</h3>
                <p>Create your first post to get started.</p>
                <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/posts/edit.php<?php echo $postTypeId ? '?post_type_id=' . $postTypeId : ''; ?>" class="btn btn-primary mt-4">
                    <i class="fas fa-plus"></i> Create Post
                </a>
            </div>
        <?php else: ?>
            <?php
            $postsTableColspan = 6 + 1 + ($isHierarchicalListing ? 1 : 0);
            ?>
            <form id="posts-bulk-form" class="posts-bulk-form" method="post" action="<?php echo htmlspecialchars(admin_url('posts/bulk-actions.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>
                <input type="hidden" name="redirect_post_type_id" value="<?php echo htmlspecialchars((string) ($postTypeId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="posts-bulk-toolbar flex flex-wrap items-center gap-2" style="padding:0.75rem 1rem;border-bottom:1px solid var(--border-color,#dee2e6);">
                    <label class="flex items-center gap-2" style="margin:0;">
                        <span class="text-muted" style="font-size:0.875rem;">Bulk actions</span>
                        <select name="bulk_action" id="posts-bulk-action" class="form-control" style="width:auto;min-width:12rem;">
                            <option value="">Choose action…</option>
                            <option value="trash">Move to trash</option>
                            <option value="draft">Set status to Draft</option>
                            <option value="published">Set status to Published</option>
                            <option value="archived">Set status to Archived</option>
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
                        <th>Status</th>
                        <th>Created</th>
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
                            </td>
                            <td><?php echo htmlspecialchars($post['post_type_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></td>
                            <td class="post-status-cell">
                                <span class="badge <?php echo $statusColor; ?> post-status-badge"><?php echo htmlspecialchars(ucfirst($status)); ?></span>
                            </td>
                            <td class="post-created-cell"><?php echo date('Y-m-d H:i', strtotime($post['created_at'] ?? 'now')); ?></td>
                            <td style="text-align: right;">
                                <div class="flex gap-2 justify-end">
                                    <a href="<?php echo htmlspecialchars(admin_url('posts/edit.php?id=' . $pid), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="<?php echo htmlspecialchars(admin_url('posts/delete.php?id=' . $pid), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-sm btn-danger"
                                       data-confirm="Are you sure you want to delete this post?">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
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
    if (!u) { u = '<?php echo htmlspecialchars(admin_url('posts'), ENT_QUOTES, 'UTF-8'); ?>'; }
    if (postTypeId) {
        window.location.href = u + (u.indexOf('?') >= 0 ? '&' : '?') + 'post_type_id=' + encodeURIComponent(postTypeId);
    } else {
        window.location.href = u.split('?')[0];
    }
}
</script>
<script src="<?php echo htmlspecialchars(admin_url('assets/js/posts-list.js'), ENT_QUOTES, 'UTF-8'); ?>?v=1"></script>
<script src="<?php echo htmlspecialchars(admin_url('assets/js/post-tree-reorder.js'), ENT_QUOTES, 'UTF-8'); ?>?v=2"></script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
