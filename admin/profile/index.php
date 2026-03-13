<?php
/**
 * Profile Page - Japanese Style
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

$page_title = 'My Profile';
$profile_active = true;

$userData = new userdata();
$user = $userData->getUserById($_SESSION['user_id']);

if (!$user) {
    $_SESSION['error'] = 'User not found.';
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/dashboard');
    exit;
}

$full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if (empty($full_name)) {
    $full_name = $user['username'] ?? 'N/A';
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>My Profile</h1>
    </div>

    <div class="card">
        <div class="grid grid-cols-2" style="gap: var(--spacing-xl);">
            <div>
                <h3>Account Information</h3>
                <div class="form-group">
                    <label>Username</label>
                    <div style="padding: var(--spacing-md) var(--spacing-lg); background: var(--background-subtle); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <?php echo htmlspecialchars($user['username'] ?? ''); ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <div style="padding: var(--spacing-md) var(--spacing-lg); background: var(--background-subtle); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <?php echo htmlspecialchars($user['email'] ?? ''); ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Full Name</label>
                    <div style="padding: var(--spacing-md) var(--spacing-lg); background: var(--background-subtle); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <?php echo htmlspecialchars($full_name); ?>
                    </div>
                </div>
            </div>
            
            <div>
                <h3>Account Details</h3>
                <div class="form-group">
                    <label>Role</label>
                    <div style="padding: var(--spacing-md) var(--spacing-lg); background: var(--background-subtle); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <span class="badge badge-success"><?php echo htmlspecialchars(ucfirst($user['role'] ?? 'user')); ?></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <div style="padding: var(--spacing-md) var(--spacing-lg); background: var(--background-subtle); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <span class="badge <?php echo ($user['status'] ?? 'active') === 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                            <?php echo htmlspecialchars(ucfirst($user['status'] ?? 'active')); ?>
                        </span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Member Since</label>
                    <div style="padding: var(--spacing-md) var(--spacing-lg); background: var(--background-subtle); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <?php echo date('F j, Y', strtotime($user['created_at'] ?? 'now')); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <hr class="section-divider">
        
        <div class="flex gap-2">
            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
            <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/auth/logout.php" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
