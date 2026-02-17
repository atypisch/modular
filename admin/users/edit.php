<?php
/**
 * Users Edit Page - Japanese Style
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

$page_title = isset($_GET['id']) ? 'Edit User' : 'Add New User';
$users_active = true;

$userData = new userdata();
$user = null;
$userData_array = [
    'username' => '',
    'email' => '',
    'first_name' => '',
    'last_name' => '',
    'role' => 'editor',
    'status' => 'active'
];

if (isset($_GET['id'])) {
    $user = $userData->getUserById($_GET['id']);
    if (!$user) {
        $_SESSION['error'] = 'User not found.';
        header('Location: /modular/admin/users');
        exit;
    }
    $userData_array = [
        'username' => $user['username'] ?? '',
        'email' => $user['email'] ?? '',
        'first_name' => $user['first_name'] ?? '',
        'last_name' => $user['last_name'] ?? '',
        'role' => $user['role'] ?? 'editor',
        'status' => $user['status'] ?? 'active'
    ];
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <div class="flex justify-between items-center">
            <h1><?php echo $page_title; ?></h1>
            <a href="/modular/admin/users" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
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
        <form method="POST" action="/modular/admin/users/save.php">
            <?php echo csrf_field(); ?>
            <?php if (isset($_GET['id'])): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Username <span style="color: var(--error-text);">*</span></label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?php echo htmlspecialchars($userData_array['username']); ?>" required>
                <div class="help-text">A unique username for this user.</div>
            </div>
            
            <div class="form-group">
                <label for="email">Email <span style="color: var(--error-text);">*</span></label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($userData_array['email']); ?>" required>
                <div class="help-text">The user's email address.</div>
            </div>
            
            <div class="grid grid-cols-2">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" 
                           value="<?php echo htmlspecialchars($userData_array['first_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" 
                           value="<?php echo htmlspecialchars($userData_array['last_name']); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password<?php echo isset($_GET['id']) ? ' (leave blank to keep current)' : ''; ?> <?php echo !isset($_GET['id']) ? '<span style="color: var(--error-text);">*</span>' : ''; ?></label>
                <input type="password" id="password" name="password" class="form-control" 
                       <?php echo !isset($_GET['id']) ? 'required' : ''; ?>>
                <div class="help-text"><?php echo isset($_GET['id']) ? 'Only enter a password if you want to change it.' : 'Minimum 8 characters.'; ?></div>
            </div>
            
            <div class="grid grid-cols-2">
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" class="form-control">
                        <option value="user" <?php echo $userData_array['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="editor" <?php echo $userData_array['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                        <option value="admin" <?php echo $userData_array['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <div class="help-text">User role determines permissions.</div>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="active" <?php echo $userData_array['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $userData_array['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <div class="help-text">Account status.</div>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="/modular/admin/users" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo isset($_GET['id']) ? 'Update' : 'Create'; ?> User
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
