<?php
/**
 * Post Types Edit Page - Japanese Style
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
require_once CORE_PATH . '/classes/PostType.php';
require_once CORE_PATH . '/classes/Taxonomy.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

// Quick check if post_types table exists (don't block if other tables are missing)
$post_types_table_exists = false;
if (isset($GLOBALS['db'])) {
    try {
        // Method 1: Try SHOW TABLES LIKE
        $stmt = $GLOBALS['db']->query("SHOW TABLES LIKE 'post_types'");
        if ($stmt->rowCount() > 0) {
            $post_types_table_exists = true;
        } else {
            // Method 2: Try information_schema
            if (defined('DB_NAME')) {
                $stmt = $GLOBALS['db']->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'post_types'");
                $stmt->execute([DB_NAME]);
                $result = $stmt->fetch(PDO::FETCH_NUM);
                $post_types_table_exists = ($result[0] > 0);
            }
        }
    } catch (Exception $e) {
        // Method 3: Try direct query as last resort
        try {
            $GLOBALS['db']->query("SELECT 1 FROM post_types LIMIT 1");
            $post_types_table_exists = true;
        } catch (Exception $e2) {
            // Table doesn't exist - this is fine, we'll show error below
            $post_types_table_exists = false;
        }
    }
}

if (!$post_types_table_exists) {
    $_SESSION['error'] = 'The post_types table does not exist. Please install the database first.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/post-types');
    exit;
}

$page_title = isset($_GET['id']) ? 'Edit Post Type' : 'Add New Post Type';
$post_types_active = true;

// Load existing post type if editing
$postType = null;
$postTypeData = [
    'name' => '',
    'slug' => '',
    'description' => '',
    'public' => 1,
    'show_in_menu' => 1
];

$linkedTaxonomyIds = [];
if (isset($_GET['id'])) {
    try {
        $postType = new PostType();
        $postTypeData = $postType->getById($_GET['id']);
        if (!$postTypeData) {
            $_SESSION['error'] = 'Post type not found.';
            header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/post-types');
            exit;
        }
        $postTypeData['public'] = isset($postTypeData['public']) ? (int)$postTypeData['public'] : 1;
        $postTypeData['show_in_menu'] = isset($postTypeData['show_in_menu']) ? (int)$postTypeData['show_in_menu'] : 1;
        
        // Get linked taxonomies
        $db = $GLOBALS['db'];
        $stmt = $db->prepare("SELECT taxonomy_id FROM post_type_taxonomies WHERE post_type_id = ?");
        $stmt->execute([$_GET['id']]);
        $linkedTaxonomyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error loading post type: ' . $e->getMessage();
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/post-types');
        exit;
    }
}

// Get all taxonomies for selection
$allTaxonomies = [];
try {
    $taxonomy = new Taxonomy();
    $allTaxonomies = $taxonomy->getAll();
} catch (Exception $e) {
    error_log("Error loading taxonomies: " . $e->getMessage());
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1><?php echo $page_title; ?></h1>
            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/post-types" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Post Types
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form action="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/post-types/save.php" method="POST">
            <?php echo csrf_field(); ?>
            <?php if (isset($_GET['id'])): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Name <span style="color: var(--error-text);">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($postTypeData['name'] ?? ''); ?>" required>
                <div class="help-text">The display name for this post type.</div>
            </div>

            <div class="form-group">
                <label for="slug">Slug <span style="color: var(--error-text);">*</span></label>
                <input type="text" name="slug" id="slug" class="form-control" value="<?php echo htmlspecialchars($postTypeData['slug'] ?? $postTypeData['nicename'] ?? ''); ?>" required>
                <div class="help-text">The URL-friendly version of the name. Must be unique.</div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="4"><?php echo htmlspecialchars($postTypeData['description'] ?? ''); ?></textarea>
                <div class="help-text">A brief description of this post type.</div>
            </div>

            <hr class="section-divider">

            <h3 style="margin-top: 0;">Display Options</h3>

            <div class="checkbox-group">
                <input type="checkbox" name="public" id="public" value="1" <?php echo (!empty($postTypeData['public'])) ? 'checked' : ''; ?>>
                <label for="public">
                    <strong>Public</strong>
                    <div class="help-text">Whether this post type should be publicly queryable.</div>
                </label>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="show_in_menu" id="show_in_menu" value="1" <?php echo (!empty($postTypeData['show_in_menu'])) ? 'checked' : ''; ?>>
                <label for="show_in_menu">
                    <strong>Show in Menu</strong>
                    <div class="help-text">Whether to show this post type in the admin menu.</div>
                </label>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="hierarchical" id="hierarchical" value="1" <?php echo (!empty($postTypeData['hierarchical'])) ? 'checked' : ''; ?>>
                <label for="hierarchical">
                    <strong>Hierarchical (pages)</strong>
                    <div class="help-text">Maakt dit post type hiërarchisch; items kunnen een parent hebben (zoals Pages in WordPress). Maximaal 3 niveaus.</div>
                </label>
            </div>

            <hr class="section-divider">

            <h3 style="margin-top: 0;">Taxonomies</h3>
            <p class="help-text" style="margin-bottom: var(--spacing-md);">Select which taxonomies should be available for this post type. <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/taxonomies/edit.php" target="_blank">Create new taxonomy</a></p>
            
            <?php if (empty($allTaxonomies)): ?>
                <div class="alert alert-info">
                    <p>No taxonomies available. <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/taxonomies/edit.php">Create a taxonomy first</a>.</p>
                </div>
            <?php else: ?>
                <div class="checkbox-group" style="max-height: 300px; overflow-y: auto; border: 1px solid var(--border-color); padding: var(--spacing-md); border-radius: var(--radius-md);">
                    <?php foreach ($allTaxonomies as $tax): ?>
                        <div class="checkbox-group" style="margin-bottom: var(--spacing-sm);">
                            <input type="checkbox" name="taxonomies[]" id="taxonomy_<?php echo $tax['id']; ?>" value="<?php echo $tax['id']; ?>" <?php echo in_array($tax['id'], $linkedTaxonomyIds) ? 'checked' : ''; ?>>
                            <label for="taxonomy_<?php echo $tax['id']; ?>">
                                <strong><?php echo htmlspecialchars($tax['name']); ?></strong>
                                <span class="badge badge-secondary" style="margin-left: var(--spacing-sm); font-size: 0.75rem;">
                                    <?php echo $tax['hierarchical'] ? 'Hierarchical' : 'Flat'; ?>
                                </span>
                                <?php if (!empty($tax['description'])): ?>
                                    <div class="help-text" style="margin-top: 0.25rem;"><?php echo htmlspecialchars($tax['description']); ?></div>
                                <?php endif; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['id'])): ?>
            <hr class="section-divider">
            <h3 style="margin-top: 0;">Field Schema</h3>
            <p class="help-text" style="margin-bottom: var(--spacing-md);">Define the structured fields for this post type. Fixed = locked schema; Modular = editors can add/reorder/remove blocks per post.</p>
            <div class="configure-fields-wrap" style="display: inline-block; align-self: flex-start;">
                <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/field-builder/?id=<?php echo (int)$_GET['id']; ?>" class="btn btn-secondary btn-inline">
                    <i class="fas fa-th-list"></i> Configure fields
                </a>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/post-types" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Post Type
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById("name").addEventListener("input", function() {
    var slugInput = document.getElementById("slug");
    if (!slugInput.value || slugInput.value === this.value.toLowerCase().replace(/[^a-z0-9]+/g, "-")) {
        slugInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, "-");
    }
});
</script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
