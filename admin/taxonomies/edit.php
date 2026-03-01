<?php
/**
 * Taxonomy Edit/Create Page
 */

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/functions/database.php';
require_once CORE_PATH . '/classes/Taxonomy.php';
require_once CORE_PATH . '/classes/PostType.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

$page_title = isset($_GET['id']) ? 'Edit Taxonomy' : 'Add New Taxonomy';
$post_types_active = true;

$db = $GLOBALS['db'];
$taxonomy = new Taxonomy();
$postType = new PostType();

// Get taxonomy data if editing
$taxonomyData = [];
$linkedPostTypes = [];
if (isset($_GET['id'])) {
    $taxonomyData = $taxonomy->getById($_GET['id']);
    if (!$taxonomyData) {
        $_SESSION['error'] = 'Taxonomy not found.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/taxonomies');
        exit;
    }
    $linkedPostTypes = $taxonomy->getLinkedPostTypes($_GET['id']);
    $linkedPostTypeIds = array_column($linkedPostTypes, 'id');
} else {
    $linkedPostTypeIds = [];
}

// Get all post types for selection
$allPostTypes = [];
try {
    $allPostTypes = $postType->getAll();
} catch (Exception $e) {
    error_log("Error loading post types: " . $e->getMessage());
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1><?php echo $page_title; ?></h1>
            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/taxonomies" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Taxonomies
            </a>
        </div>
    </div>

    <div class="card">
        <form action="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/taxonomies/save.php" method="POST">
            <?php echo csrf_field(); ?>
            <?php if (isset($_GET['id'])): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
            <?php endif; ?>

            <div class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Basic Information</h3>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($taxonomyData['name'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <p class="mt-1 text-sm text-gray-500">The display name for this taxonomy (e.g., "Categories", "Tags").</p>
                        </div>

                        <div>
                            <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
                            <input type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($taxonomyData['slug'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <p class="mt-1 text-sm text-gray-500">The URL-friendly version of the name. Must be unique.</p>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php echo htmlspecialchars($taxonomyData['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Taxonomy Type -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Taxonomy Type</h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="hierarchical" id="hierarchical" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?php echo (!empty($taxonomyData['hierarchical'])) ? 'checked' : ''; ?>>
                            </div>
                            <div class="ml-3">
                                <label for="hierarchical" class="text-sm font-medium text-gray-700">Hierarchical</label>
                                <p class="text-sm text-gray-500">Enable if terms can have parent-child relationships (like Categories). Disable for flat taxonomies (like Tags).</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attach to Post Types -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Attach to Post Types</h3>
                    <p class="text-sm text-gray-500 mb-4">Select which post types this taxonomy should be available for.</p>
                    <div class="space-y-2">
                        <?php if (empty($allPostTypes)): ?>
                            <p class="text-sm text-gray-500">No post types available. <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/post-types/edit.php" class="text-blue-600">Create a post type first</a>.</p>
                        <?php else: ?>
                            <?php foreach ($allPostTypes as $pt): ?>
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox" name="post_types[]" id="post_type_<?php echo $pt['id']; ?>" value="<?php echo $pt['id']; ?>" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?php echo in_array($pt['id'], $linkedPostTypeIds) ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="ml-3">
                                        <label for="post_type_<?php echo $pt['id']; ?>" class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($pt['name']); ?></label>
                                        <?php if (!empty($pt['description'])): ?>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($pt['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Display Options -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Display Options</h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="public" id="public" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?php echo (!isset($taxonomyData['public']) || !empty($taxonomyData['public'])) ? 'checked' : ''; ?>>
                            </div>
                            <div class="ml-3">
                                <label for="public" class="text-sm font-medium text-gray-700">Public</label>
                                <p class="text-sm text-gray-500">Whether this taxonomy should be publicly queryable.</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="show_in_menu" id="show_in_menu" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?php echo (!isset($taxonomyData['show_in_menu']) || !empty($taxonomyData['show_in_menu'])) ? 'checked' : ''; ?>>
                            </div>
                            <div class="ml-3">
                                <label for="show_in_menu" class="text-sm font-medium text-gray-700">Show in Menu</label>
                                <p class="text-sm text-gray-500">Whether to show this taxonomy in the admin menu.</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="show_in_rest" id="show_in_rest" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?php echo (!isset($taxonomyData['show_in_rest']) || !empty($taxonomyData['show_in_rest'])) ? 'checked' : ''; ?>>
                            </div>
                            <div class="ml-3">
                                <label for="show_in_rest" class="text-sm font-medium text-gray-700">Show in REST API</label>
                                <p class="text-sm text-gray-500">Whether to expose this taxonomy in the REST API.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-4">
                <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/taxonomies" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Taxonomy
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-generate slug from name
document.getElementById('name').addEventListener('input', function() {
    const slugInput = document.getElementById('slug');
    if (!slugInput.value || slugInput.dataset.autoGenerated === 'true') {
        const slug = this.value.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        slugInput.value = slug;
        slugInput.dataset.autoGenerated = 'true';
    }
});

document.getElementById('slug').addEventListener('input', function() {
    this.dataset.autoGenerated = 'false';
});
</script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>


