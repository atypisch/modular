<?php
/**
 * Terms Management Page for a Taxonomy
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

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$taxonomyId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$taxonomyId) {
    $_SESSION['error'] = 'Invalid taxonomy ID.';
    header('Location: /modular/admin/taxonomies');
    exit;
}

$taxonomy = new Taxonomy();
$taxonomyData = $taxonomy->getById($taxonomyId);

if (!$taxonomyData) {
    $_SESSION['error'] = 'Taxonomy not found.';
    header('Location: /modular/admin/taxonomies');
    exit;
}

$page_title = 'Terms: ' . $taxonomyData['name'];
$post_types_active = true;

$db = $GLOBALS['db'];

// Get terms for this taxonomy
$terms = [];
$message = '';

try {
    $stmt = $db->prepare("
        SELECT t.*, 
               COALESCE(u1.username, u1.email, 'Unknown') as created_by_name,
               pt.name as parent_name
        FROM terms t
        LEFT JOIN users u1 ON t.created_by = u1.id
        LEFT JOIN terms pt ON t.parent_id = pt.id
        WHERE t.taxonomy_id = ? AND t.deleted_at IS NULL
        ORDER BY t.order, t.name
    ");
    $stmt->execute([$taxonomyId]);
    $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error loading terms: " . $e->getMessage();
    error_log($message);
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <div>
                <h1>Terms: <?php echo htmlspecialchars($taxonomyData['name']); ?></h1>
                <p class="text-muted" style="margin-top: 0.5rem;">Manage terms for this taxonomy</p>
            </div>
            <div class="flex gap-2">
                <a href="/modular/admin/taxonomies" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Taxonomies
                </a>
                <a href="/modular/admin/taxonomies/terms/edit.php?taxonomy_id=<?php echo $taxonomyId; ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Term
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
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <?php if (empty($terms)): ?>
            <div class="empty-state">
                <i class="fas fa-tags"></i>
                <h3>No Terms Found</h3>
                <p>Create your first term for this taxonomy.</p>
                <a href="/modular/admin/taxonomies/terms/edit.php?taxonomy_id=<?php echo $taxonomyId; ?>" class="btn btn-primary mt-4">
                    <i class="fas fa-plus"></i> Create Term
                </a>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <?php if ($taxonomyData['hierarchical']): ?>
                            <th>Parent</th>
                        <?php endif; ?>
                        <th>Description</th>
                        <th>Created</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($terms as $term): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($term['name']); ?></strong></td>
                            <td><code style="background: var(--background-subtle); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 13px;"><?php echo htmlspecialchars($term['slug']); ?></code></td>
                            <?php if ($taxonomyData['hierarchical']): ?>
                                <td>
                                    <?php if (!empty($term['parent_name'])): ?>
                                        <?php echo htmlspecialchars($term['parent_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars(substr($term['description'] ?? '', 0, 50)); ?><?php echo strlen($term['description'] ?? '') > 50 ? '...' : ''; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($term['created_at'] ?? 'now')); ?></td>
                            <td style="text-align: right;">
                                <div class="flex gap-2 justify-end">
                                    <a href="/modular/admin/taxonomies/terms/edit.php?taxonomy_id=<?php echo $taxonomyId; ?>&id=<?php echo $term['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="/modular/admin/taxonomies/terms/delete.php?id=<?php echo $term['id']; ?>&taxonomy_id=<?php echo $taxonomyId; ?>" 
                                       class="btn btn-sm btn-danger"
                                       data-confirm="Are you sure you want to delete this term?">
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

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>

