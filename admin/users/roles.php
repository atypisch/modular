<?php
/**
 * User Roles Management Page
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

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

$page_title = 'User Roles';
$users_active = true;

$db = $GLOBALS['db'];

// Check if database tables exist
$tableCheck = check_database_tables();

$roles = [];
$capabilities = [];
$message = '';

if (!$tableCheck['all_exist'] || in_array('roles', $tableCheck['missing']) || in_array('capabilities', $tableCheck['missing'])) {
    $message = "The database tables have not been created yet. Please install the database to continue.";
} else {
    try {
        // Get all roles with capabilities
        $stmt = $db->query("
            SELECT r.*, 
                   GROUP_CONCAT(c.name) as capabilities
            FROM roles r
            LEFT JOIN role_capabilities rc ON r.id = rc.role_id
            LEFT JOIN capabilities c ON rc.capability_id = c.id
            GROUP BY r.id
            ORDER BY r.name
        ");
        $roles = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Get all capabilities
        $stmt = $db->query("SELECT * FROM capabilities ORDER BY name");
        $capabilities = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Base table or view not found") !== false) {
            $message = "The database tables have not been created yet. Please install the database to continue.";
        } else {
            $message = "Error loading roles: " . $e->getMessage();
        }
        error_log("Roles error: " . $e->getMessage());
    } catch (Exception $e) {
        $message = "Error loading roles: " . $e->getMessage();
        error_log("Roles error: " . $e->getMessage());
    }
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1>User Roles</h1>
            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/users" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
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
        <?php if (empty($roles)): ?>
            <div class="empty-state">
                <i class="fas fa-user-shield"></i>
                <h3>No Roles Found</h3>
                <p>Roles will be created automatically when you install the database.</p>
                <?php if ($tableCheck['all_exist']): ?>
                    <p class="text-muted">Default roles (admin, editor, author, user) should be created during installation.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Display Name</th>
                        <th>Description</th>
                        <th>Capabilities</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($role['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($role['display_name'] ?? $role['name']); ?></td>
                        <td><?php echo htmlspecialchars($role['description'] ?? ''); ?></td>
                        <td>
                            <?php if (!empty($role['capabilities'])): ?>
                                <?php 
                                $caps = explode(',', $role['capabilities']);
                                foreach (array_slice($caps, 0, 3) as $cap): 
                                ?>
                                    <span class="badge badge-secondary" style="margin-right: 0.25rem; font-size: 0.75rem;">
                                        <?php echo htmlspecialchars(trim($cap)); ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($caps) > 3): ?>
                                    <span class="text-muted" style="font-size: 0.875rem;">+<?php echo count($caps) - 3; ?> more</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">None</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/users/roles/edit.php?id=<?php echo $role['id']; ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (!empty($capabilities)): ?>
                <div style="margin-top: var(--spacing-lg); padding-top: var(--spacing-lg); border-top: 1px solid var(--border-color);">
                    <h3>All Capabilities</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: var(--spacing-sm); margin-top: var(--spacing-md);">
                        <?php foreach ($capabilities as $cap): ?>
                            <span class="badge badge-secondary">
                                <?php echo htmlspecialchars($cap['name']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
