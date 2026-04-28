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
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
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
            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/users/edit.php" class="btn btn-primary">
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
                    <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/install.php" class="btn btn-primary" style="white-space: nowrap;">
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
                <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/users/edit.php" class="btn btn-primary mt-4">
                    <i class="fas fa-plus"></i> Create User
                </a>
            </div>
        <?php else: ?>
            <form id="users-bulk-form" action="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/users/bulk.php" method="POST">
                <div class="users-toolbar">
                    <div class="users-search">
                        <input
                            type="search"
                            id="user-search"
                            class="form-control"
                            placeholder="Search by username or email">
                    </div>
                    <div class="users-bulk-actions">
                        <select name="bulk_action" id="bulk-action" class="form-control" style="max-width: 200px;">
                            <option value="">Bulk actions…</option>
                            <option value="activate">Set active</option>
                            <option value="deactivate">Set inactive</option>
                            <option value="role_admin">Set role: Admin</option>
                            <option value="role_editor">Set role: Editor</option>
                            <option value="role_user">Set role: User</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm">
                            Apply
                        </button>
                    </div>
                </div>

                <table class="table table-compact" id="users-table">
                    <thead>
                        <tr>
                            <th style="width: 32px; text-align: center;">
                                <input type="checkbox" id="bulk-select-all">
                            </th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr data-user-row data-search="<?php echo htmlspecialchars(strtolower(($user['username'] ?? '') . ' ' . ($user['email'] ?? ''))); ?>">
                                <td style="text-align: center;">
                                    <input
                                        type="checkbox"
                                        class="user-select"
                                        name="ids[]"
                                        value="<?php echo (int)($user['id'] ?? 0); ?>">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username'] ?? ''); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo htmlspecialchars(ucfirst($user['role'] ?? 'user')); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="users-actions">
                                        <a
                                            href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/users/edit.php?id=<?php echo (int)($user['id'] ?? 0); ?>"
                                            class="action-icon"
                                            title="Edit user">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a
                                            href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/users/delete.php?id=<?php echo (int)($user['id'] ?? 0); ?>"
                                            class="action-icon action-icon-danger"
                                            data-confirm="Are you sure you want to delete this user?"
                                            title="Delete user">
                                            <i class="fas fa-trash"></i>
                                        </a>
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
(function() {
    var searchInput = document.getElementById('user-search');
    var table = document.getElementById('users-table');
    if (searchInput && table) {
        var rows = table.querySelectorAll('tbody tr[data-user-row]');
        searchInput.addEventListener('input', function() {
            var q = this.value.toLowerCase();
            rows.forEach(function(row) {
                var haystack = row.getAttribute('data-search') || '';
                row.style.display = (!q || haystack.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    var selectAll = document.getElementById('bulk-select-all');
    if (selectAll) {
        var checkboxes = document.querySelectorAll('.user-select');
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
        });
    }

    var bulkForm = document.getElementById('users-bulk-form');
    var bulkAction = document.getElementById('bulk-action');
    if (bulkForm && bulkAction) {
        bulkForm.addEventListener('submit', function(e) {
            if (!bulkAction.value) {
                e.preventDefault();
                (window.ModularModal && window.ModularModal.alert('Kies eerst een bulkactie.')) || alert('Kies eerst een bulkactie.');
                return;
            }
            var anyChecked = false;
            var checkboxes = bulkForm.querySelectorAll('.user-select');
            checkboxes.forEach(function(cb) {
                if (cb.checked) anyChecked = true;
            });
            if (!anyChecked) {
                e.preventDefault();
                (window.ModularModal && window.ModularModal.alert('Selecteer minimaal één gebruiker.')) || alert('Selecteer minimaal één gebruiker.');
                return;
            }
            if (bulkAction.value === 'delete') {
                e.preventDefault();
                var form = bulkForm;
                var p = (window.ModularModal && window.ModularModal.confirm('Weet u zeker dat u de geselecteerde gebruikers wilt verwijderen?')) || Promise.resolve(false);
                p.then(function(yes) { if (yes) form.submit(); });
            }
        });
    }
})();
</script>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
