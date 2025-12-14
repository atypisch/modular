<?php
/**
 * Users Index - Japanese Style
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
require_once ADMIN_PATH . '/data/userdata.php';

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$page_title = 'Users';
$users_active = true;

// Check if database tables exist
require_once CORE_PATH . '/functions/database.php';
$tableCheck = check_database_tables();

$users = [];
$error_message = '';

if (!$tableCheck['all_exist'] || in_array('users', $tableCheck['missing'])) {
    $error_message = "The database tables have not been created yet. Please install the database to continue.";
} else {
    try {
        $userData = new userdata();
        $users = $userData->getAllUsers();
        
        if ($users === false) {
            // Try direct database query to get better error message
            $db = $GLOBALS['db'];
            try {
                $stmt = $db->query("SELECT * FROM users WHERE deleted_at IS NULL ORDER BY username");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Base table or view not found") !== false) {
                    $error_message = "The database tables have not been created yet. Please install the database to continue.";
                } else {
                    $error_message = "Error loading users: " . $e->getMessage();
                }
                error_log("Users error: " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        $error_message = "Error loading users: " . $e->getMessage();
        error_log("Users error: " . $e->getMessage());
    }
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1>Manage Users</h1>
            <a href="/modular/admin/users/edit.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New User
            </a>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-error" style="margin-bottom: var(--spacing-lg);">
            <div style="display: flex; align-items: start; gap: var(--spacing-md);">
                <div style="flex: 1;">
                    <strong>Database Setup Required</strong>
                    <p style="margin: var(--spacing-sm) 0 0 0;"><?php echo htmlspecialchars($error_message); ?></p>
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
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No Users Found</h3>
                <p>Create your first user to get started.</p>
                <a href="/modular/admin/users/edit.php" class="btn btn-primary mt-4">
                    <i class="fas fa-plus"></i> Create User
                </a>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Email Verified</th>
                        <th>Created</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                        if (empty($full_name)) {
                            $full_name = $user['username'] ?? 'N/A';
                        }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id'] ?? ''); ?></td>
                            <td><strong><?php echo htmlspecialchars($user['username'] ?? ''); ?></strong></td>
                            <td><?php echo htmlspecialchars($full_name); ?></td>
                            <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                            <td>
                                <span class="badge badge-success"><?php echo htmlspecialchars(ucfirst($user['role'] ?? 'user')); ?></span>
                            </td>
                            <td>
                                <?php if (($user['status'] ?? 'active') === 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['email_verified'] ?? 0): ?>
                                    <span class="badge badge-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($user['created_at'] ?? 'now')); ?></td>
                            <td style="text-align: right;">
                                <div class="flex gap-2 justify-end">
                                    <a href="/modular/admin/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="/modular/admin/users/delete.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this user?');">
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
