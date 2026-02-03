<?php
/**
 * Forms List Page
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

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$page_title = 'Forms';
$forms_active = true;

$db = $GLOBALS['db'];

// Check if database tables exist
require_once CORE_PATH . '/functions/database.php';
$tableCheck = check_database_tables();

// Get forms from database
$forms = [];
$message = '';

if (!$tableCheck['all_exist'] || in_array('forms', $tableCheck['missing'])) {
    $message = "The database tables have not been created yet. Please install the database to continue.";
} else {
    try {
        $stmt = $db->query("
            SELECT f.*, 
                   COALESCE(u1.username, u1.name) as created_by_name,
                   COALESCE(u2.username, u2.name) as updated_by_name
            FROM forms f
            LEFT JOIN users u1 ON f.created_by = u1.id
            LEFT JOIN users u2 ON f.updated_by = u2.id
            ORDER BY f.name
        ");
        $forms = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Decode JSON fields
        foreach ($forms as &$form) {
            $form['fields'] = json_decode($form['fields'] ?? '[]', true);
            $form['settings'] = json_decode($form['settings'] ?? '{}', true);
            $form['spam_protection'] = json_decode($form['spam_protection'] ?? '{}', true);
        }
    } catch (PDOException $e) {
        // Check if it's a "table doesn't exist" error
        if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Base table or view not found") !== false) {
            $message = "The database tables have not been created yet. Please install the database to continue.";
        } else {
            $message = "Error loading forms: " . $e->getMessage();
        }
        error_log("Forms error: " . $e->getMessage());
    } catch (Exception $e) {
        $message = "Error loading forms: " . $e->getMessage();
        error_log("Forms error: " . $e->getMessage());
    }
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1>Forms</h1>
            <a href="/modular/admin/forms/builder.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Form
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-error" style="margin-bottom: var(--spacing-lg);">
            <div style="display: flex; align-items: start; gap: var(--spacing-md);">
                <div style="flex: 1;">
                    <strong>Database Setup Required</strong>
                    <p style="margin: var(--spacing-sm) 0 0 0;"><?php echo htmlspecialchars($message); ?></p>
                    <?php if (!$tableCheck['all_exist']): ?>
                        <p style="margin: var(--spacing-sm) 0 0 0; font-size: 0.875rem;">
                            <strong><?php echo count($tableCheck['missing']); ?> table(s) missing.</strong>
                        </p>
                    <?php endif; ?>
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
        <?php if (empty($forms)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>No Forms Found</h3>
                <p>Create your first form to get started.</p>
                <a href="/modular/admin/forms/builder.php" class="btn btn-primary mt-4">
                    <i class="fas fa-plus"></i> Create Form
                </a>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Fields</th>
                        <th>Created</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($form['name'] ?? 'Untitled'); ?></strong></td>
                            <td><code style="background: var(--background-subtle); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 13px;"><?php echo htmlspecialchars($form['slug'] ?? ''); ?></code></td>
                            <td><?php echo is_array($form['fields']) ? count($form['fields']) : 0; ?> fields</td>
                            <td><?php echo $form['created_at'] ? date('Y-m-d', strtotime($form['created_at'])) : 'N/A'; ?></td>
                            <td style="text-align: right;">
                                <div class="flex gap-2 justify-end">
                                    <a href="/modular/admin/forms/builder.php?id=<?php echo $form['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="/modular/admin/forms/delete.php?id=<?php echo $form['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this form?');">
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


