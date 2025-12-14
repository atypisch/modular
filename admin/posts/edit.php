<?php
/**
 * Posts Edit Page - Japanese Style
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

$page_title = isset($_GET['id']) ? 'Edit Post' : 'Add New Post';
$posts_active = true;

$db = $GLOBALS['db'];
$postId = $_GET['id'] ?? null;
$postTypeId = $_GET['post_type_id'] ?? null;

// Get post types
$postTypes = [];
try {
    $stmt = $db->query("SELECT id, name, slug FROM post_types WHERE deleted_at IS NULL ORDER BY name");
    $postTypes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error loading post types: " . $e->getMessage());
}

// Load existing post if editing
$postData = [
    'post_type_id' => $postTypeId ?? '',
    'title' => '',
    'slug' => '',
    'excerpt' => '',
    'status' => 'draft',
    'component_data' => []
];

if ($postId) {
    try {
        $stmt = $db->prepare("
            SELECT p.*, pt.name as post_type_name
            FROM posts p
            INNER JOIN post_types pt ON p.post_type_id = pt.id
            WHERE p.id = ? AND p.deleted_at IS NULL
        ");
        $stmt->execute([$postId]);
        $post = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($post) {
            $postData = $post;
            $postData['component_data'] = json_decode($post['component_data'] ?? '[]', true);
            $postTypeId = $post['post_type_id'];
        } else {
            $_SESSION['error'] = 'Post not found.';
            header('Location: /modular/admin/posts');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error loading post: ' . $e->getMessage();
        header('Location: /modular/admin/posts');
        exit;
    }
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1><?php echo $page_title; ?></h1>
            <a href="/modular/admin/posts<?php echo $postTypeId ? '?post_type_id=' . $postTypeId : ''; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Posts
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form action="/modular/admin/posts/save.php" method="POST">
            <?php if ($postId): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($postId); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="post_type_id">Post Type <span style="color: var(--error-text);">*</span></label>
                <select name="post_type_id" id="post_type_id" class="form-control" required>
                    <option value="">Select Post Type...</option>
                    <?php foreach ($postTypes as $pt): ?>
                        <option value="<?php echo $pt['id']; ?>" 
                                <?php echo ($postData['post_type_id'] == $pt['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pt['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text">Select the post type for this content.</div>
            </div>

            <div class="form-group">
                <label for="title">Title <span style="color: var(--error-text);">*</span></label>
                <input type="text" name="title" id="title" class="form-control" value="<?php echo htmlspecialchars($postData['title'] ?? ''); ?>" required>
                <div class="help-text">The title of your post.</div>
            </div>

            <div class="form-group">
                <label for="slug">Slug <span style="color: var(--error-text);">*</span></label>
                <input type="text" name="slug" id="slug" class="form-control" value="<?php echo htmlspecialchars($postData['slug'] ?? ''); ?>" required>
                <div class="help-text">The URL-friendly version of the title.</div>
            </div>

            <div class="form-group">
                <label for="excerpt">Excerpt</label>
                <textarea name="excerpt" id="excerpt" class="form-control" rows="3"><?php echo htmlspecialchars($postData['excerpt'] ?? ''); ?></textarea>
                <div class="help-text">A brief description of your post.</div>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="draft" <?php echo ($postData['status'] ?? 'draft') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo ($postData['status'] ?? '') == 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="archived" <?php echo ($postData['status'] ?? '') == 'archived' ? 'selected' : ''; ?>>Archived</option>
                </select>
                <div class="help-text">The publication status of this post.</div>
            </div>

            <hr class="section-divider">

            <h3 style="margin-top: 0;">Component Data</h3>
            <div class="form-group">
                <label for="component_data">Component Data (JSON)</label>
                <textarea name="component_data" id="component_data" class="form-control" rows="8" style="font-family: 'Courier New', monospace; font-size: 13px;"><?php echo htmlspecialchars(json_encode($postData['component_data'] ?? [], JSON_PRETTY_PRINT)); ?></textarea>
                <div class="help-text">Component data in JSON format. Use the Component Builder for visual editing.</div>
            </div>

            <div class="form-actions">
                <a href="/modular/admin/posts<?php echo $postTypeId ? '?post_type_id=' . $postTypeId : ''; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Post
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById("title").addEventListener("input", function() {
    var slugInput = document.getElementById("slug");
    if (!slugInput.value || slugInput.value === this.value.toLowerCase().replace(/[^a-z0-9]+/g, "-")) {
        slugInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, "-");
    }
});
</script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
