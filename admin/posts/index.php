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
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$page_title = 'Posts';
$posts_active = true;

$db = $GLOBALS['db'];
$postTypeId = $_GET['post_type_id'] ?? null;

// Get post types for filter
$postTypes = [];
try {
    $stmt = $db->query("SELECT id, name, slug FROM post_types WHERE deleted_at IS NULL ORDER BY name");
    $postTypes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading post types: " . $e->getMessage());
}

// Get posts
$posts = [];
$message = '';

try {
    $sql = "
        SELECT p.*, pt.name as post_type_name, pt.slug as post_type_slug,
               COALESCE(u.username, u.name, u.email, 'Unknown') as author_name
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
                <a href="/modular/admin/posts/edit.php<?php echo $postTypeId ? '?post_type_id=' . $postTypeId : ''; ?>" class="btn btn-primary">
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

    <div class="card">
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <h3>No Posts Found</h3>
                <p>Create your first post to get started.</p>
                <a href="/modular/admin/posts/edit.php<?php echo $postTypeId ? '?post_type_id=' . $postTypeId : ''; ?>" class="btn btn-primary mt-4">
                    <i class="fas fa-plus"></i> Create Post
                </a>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Post Type</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($post['title'] ?? 'Untitled'); ?></strong>
                                <div class="text-muted" style="font-size: 13px; margin-top: 0.25rem;">
                                    <code><?php echo htmlspecialchars($post['slug'] ?? ''); ?></code>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($post['post_type_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></td>
                            <td>
                                <?php
                                $status = $post['status'] ?? 'draft';
                                $statusColors = [
                                    'published' => 'badge-success',
                                    'draft' => 'badge-secondary',
                                    'archived' => 'badge-warning'
                                ];
                                $statusColor = $statusColors[$status] ?? 'badge-secondary';
                                ?>
                                <span class="badge <?php echo $statusColor; ?>"><?php echo ucfirst($status); ?></span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($post['created_at'] ?? 'now')); ?></td>
                            <td style="text-align: right;">
                                <div class="flex gap-2 justify-end">
                                    <a href="/modular/admin/posts/edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="/modular/admin/posts/delete.php?id=<?php echo $post['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this post?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function filterByPostType(postTypeId) {
    if (postTypeId) {
        window.location.href = '/modular/admin/posts?post_type_id=' + postTypeId;
    } else {
        window.location.href = '/modular/admin/posts';
    }
}
</script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
