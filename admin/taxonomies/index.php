<?php
/**
 * Taxonomies List Page
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

$page_title = 'Taxonomies';
$post_types_active = true; // Show under post types section

$db = $GLOBALS['db'];

// Check if database tables exist
$tableCheck = check_database_tables();

$taxonomies = [];
$message = '';

if (!$tableCheck['all_exist'] || in_array('taxonomies', $tableCheck['missing'])) {
    $message = "The database tables have not been created yet. Please install the database to continue.";
} else {
    try {
        $taxonomy = new Taxonomy();
        $taxonomies = $taxonomy->getAll();
        
        // Get associated post types for each taxonomy
        foreach ($taxonomies as &$tax) {
            $stmt = $db->prepare("
                SELECT pt.id, pt.name, pt.slug
                FROM post_type_taxonomies ptt
                INNER JOIN post_types pt ON ptt.post_type_id = pt.id
                WHERE ptt.taxonomy_id = ?
            ");
            $stmt->execute([$tax['id']]);
            $tax['post_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Base table or view not found") !== false) {
            $message = "The database tables have not been created yet. Please install the database to continue.";
        } else {
            $message = "Error loading taxonomies: " . $e->getMessage();
        }
        error_log("Taxonomies error: " . $e->getMessage());
    } catch (Exception $e) {
        $message = "Error loading taxonomies: " . $e->getMessage();
        error_log("Taxonomies error: " . $e->getMessage());
    }
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1>Taxonomies</h1>
            <div class="flex gap-2">
                <a href="/modular/admin/post-types" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Post Types
                </a>
                <a href="/modular/admin/taxonomies/edit.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Taxonomy
                </a>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-error" style="margin-bottom: var(--spacing-lg);">
            <div style="display: flex; align-items: start; gap: var(--spacing-md);">
                <div style="flex: 1;">
                    <strong>Database Setup Required</strong>
                    <p style="margin: var(--spacing-sm) 0 0 0;"><?php echo htmlspecialchars($message); ?></p>
                </div>
                <div>
                    <a href="/modular/admin/install.php" class="btn btn-primary" style="white-space: nowrap;">
                        <i class="fas fa-database"></i> Install Database
                    </a>
                </div>
            </div>
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
        <?php if (empty($taxonomies)): ?>
            <div class="empty-state">
                <i class="fas fa-tags"></i>
                <h3>No Taxonomies Found</h3>
                <p>Create your first taxonomy to organize content. Taxonomies can be attached to post types.</p>
                <a href="/modular/admin/taxonomies/edit.php" class="btn btn-primary mt-4">
                    <i class="fas fa-plus"></i> Create Taxonomy
                </a>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Type</th>
                        <th>Attached to Post Types</th>
                        <th>Terms</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($taxonomies as $tax): ?>
                        <?php
                        // Get term count
                        $termCount = 0;
                        try {
                            $stmt = $db->prepare("SELECT COUNT(*) FROM terms WHERE taxonomy_id = ? AND deleted_at IS NULL");
                            $stmt->execute([$tax['id']]);
                            $termCount = $stmt->fetchColumn();
                        } catch (Exception $e) {
                            // Ignore
                        }
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($tax['name']); ?></strong></td>
                            <td><code style="background: var(--background-subtle); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 13px;"><?php echo htmlspecialchars($tax['slug']); ?></code></td>
                            <td>
                                <?php if ($tax['hierarchical']): ?>
                                    <span class="badge badge-success">Hierarchical</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Flat</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($tax['post_types'])): ?>
                                    <?php foreach ($tax['post_types'] as $pt): ?>
                                        <span class="badge badge-secondary" style="margin-right: 0.25rem;">
                                            <?php echo htmlspecialchars($pt['name']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">None</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $termCount; ?> terms</td>
                            <td style="text-align: right;">
                                <div class="flex gap-2 justify-end">
                                    <a href="/modular/admin/taxonomies/edit.php?id=<?php echo $tax['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="/modular/admin/taxonomies/terms.php?id=<?php echo $tax['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-list"></i> Terms
                                    </a>
                                    <a href="/modular/admin/taxonomies/delete.php?id=<?php echo $tax['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this taxonomy?');">
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


