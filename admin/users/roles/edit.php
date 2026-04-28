<?php
/**
 * Edit Role - display name, description, capabilities
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(dirname(__DIR__)) . '/config/config.php';
}
require_once dirname(dirname(__DIR__)) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';
require_once CORE_PATH . '/functions/database.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

$page_title = 'Edit Role';
$users_active = true;

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['error'] = 'Invalid role.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users/roles.php');
    exit;
}

$db = $GLOBALS['db'];
$role = null;
$allCapabilities = [];
$roleCapabilityIds = [];

$tableCheck = check_database_tables();
if ($tableCheck['all_exist'] && !in_array('roles', $tableCheck['missing'])) {
    try {
        $stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($role) {
            $stmt = $db->query("SELECT * FROM capabilities ORDER BY name");
            $allCapabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT capability_id FROM role_capabilities WHERE role_id = ?");
            $stmt->execute([$id]);
            $roleCapabilityIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (PDOException $e) {
        error_log('Roles edit: ' . $e->getMessage());
    }
}

if (!$role) {
    $_SESSION['error'] = 'Role not found.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/users/roles.php');
    exit;
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1>Edit Role: <?php echo htmlspecialchars($role['display_name'] ?? $role['name']); ?></h1>
            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/users/roles.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Roles
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form action="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/users/roles/save.php" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo (int) $role['id']; ?>">

            <div class="form-group">
                <label for="name">Role name (system)</label>
                <input type="text" id="name" class="form-control" value="<?php echo htmlspecialchars($role['name']); ?>" disabled>
                <p class="help-text">System identifier; cannot be changed.</p>
            </div>

            <div class="form-group">
                <label for="display_name">Display name <span style="color: var(--error-text);">*</span></label>
                <input type="text" name="display_name" id="display_name" class="form-control" value="<?php echo htmlspecialchars($role['display_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($role['description'] ?? ''); ?></textarea>
            </div>

            <?php if (!empty($allCapabilities)): ?>
                <div class="form-group">
                    <label>Capabilities</label>
                    <div class="checkbox-group" style="max-height: 320px; overflow-y: auto;">
                        <?php foreach ($allCapabilities as $cap): ?>
                            <div class="checkbox-group" style="margin-bottom: 0.5rem;">
                                <input type="checkbox" name="capabilities[]" id="cap_<?php echo (int) $cap['id']; ?>" value="<?php echo (int) $cap['id']; ?>"
                                    <?php echo in_array($cap['id'], $roleCapabilityIds) ? ' checked' : ''; ?>>
                                <label for="cap_<?php echo (int) $cap['id']; ?>">
                                    <strong><?php echo htmlspecialchars($cap['name']); ?></strong>
                                    <?php if (!empty($cap['description'])): ?>
                                        <span class="help-text">— <?php echo htmlspecialchars($cap['description']); ?></span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/users/roles.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Role
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
