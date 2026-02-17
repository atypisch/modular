<?php
/**
 * Edit Term Page
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
require_once CORE_PATH . '/functions/database.php';
require_once CORE_PATH . '/classes/Taxonomy.php';

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$db = $GLOBALS['db'];
$taxonomyObj = new Modular\Taxonomy($db);

$taxonomy_id = filter_input(INPUT_GET, 'taxonomy_id', FILTER_SANITIZE_NUMBER_INT);
$term_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$taxonomy_id) {
    $_SESSION['error'] = 'No taxonomy ID provided.';
    header('Location: /modular/admin/taxonomies');
    exit;
}

$taxonomy = $taxonomyObj->getById($taxonomy_id);
if (!$taxonomy) {
    $_SESSION['error'] = 'Taxonomy not found.';
    header('Location: /modular/admin/taxonomies');
    exit;
}

$termData = [
    'name' => '',
    'slug' => '',
    'description' => '',
    'parent_id' => null
];

$allTerms = [];
if ($term_id) {
    try {
        $termData = $taxonomyObj->getTermById($term_id);
        if (!$termData || $termData['taxonomy_id'] != $taxonomy_id) {
            $_SESSION['error'] = 'Term not found.';
            header('Location: /modular/admin/taxonomies/terms.php?taxonomy_id=' . $taxonomy_id);
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error loading term: ' . $e->getMessage();
        header('Location: /modular/admin/taxonomies/terms.php?taxonomy_id=' . $taxonomy_id);
        exit;
    }
}

// Get all terms for parent selection (if hierarchical)
if ($taxonomy['hierarchical']) {
    try {
        $allTerms = $taxonomyObj->getTermsByTaxonomyId($taxonomy_id);
        // Remove current term from parent options
        if ($term_id) {
            $allTerms = array_filter($allTerms, function($t) use ($term_id) {
                return $t['id'] != $term_id;
            });
        }
    } catch (Exception $e) {
        error_log("Error loading terms: " . $e->getMessage());
    }
}

$page_title = $term_id ? 'Edit Term' : 'Add New Term';
$post_types_active = true;

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1><?php echo $page_title; ?> - <?php echo htmlspecialchars($taxonomy['name']); ?></h1>
            <a href="/modular/admin/taxonomies/terms.php?taxonomy_id=<?php echo $taxonomy_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Terms
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
        <form action="/modular/admin/taxonomies/terms/save.php" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="taxonomy_id" value="<?php echo $taxonomy_id; ?>">
            <?php if ($term_id): ?>
            <input type="hidden" name="id" value="<?php echo $term_id; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Name <span style="color: var(--error-text);">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($termData['name'] ?? ''); ?>" required>
                <div class="help-text">The display name for this term.</div>
            </div>

            <div class="form-group">
                <label for="slug">Slug <span style="color: var(--error-text);">*</span></label>
                <input type="text" name="slug" id="slug" class="form-control" value="<?php echo htmlspecialchars($termData['slug'] ?? ''); ?>" required>
                <div class="help-text">The URL-friendly version of the name. Must be unique within this taxonomy.</div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="4"><?php echo htmlspecialchars($termData['description'] ?? ''); ?></textarea>
                <div class="help-text">A brief description of this term.</div>
            </div>

            <?php if ($taxonomy['hierarchical'] && !empty($allTerms)): ?>
            <div class="form-group">
                <label for="parent_id">Parent Term</label>
                <select name="parent_id" id="parent_id" class="form-control">
                    <option value="">None</option>
                    <?php foreach ($allTerms as $term): ?>
                        <option value="<?php echo $term['id']; ?>" <?php echo ($termData['parent_id'] == $term['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($term['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text">Select a parent term if this is a hierarchical taxonomy.</div>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <a href="/modular/admin/taxonomies/terms.php?taxonomy_id=<?php echo $taxonomy_id; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Term
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


