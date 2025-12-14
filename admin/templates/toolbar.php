<?php
/**
 * Toolbar - Japanese Style
 */
if (!isset($current_user)) {
    $current_user = get_current_user();
}
?>
<div class="toolbar">
    <div class="toolbar-content">
        <div class="toolbar-left">
            <a href="/modular/admin/dashboard" class="toolbar-link">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </div>
        <div class="toolbar-right">
            <?php if ($current_user): ?>
                <a href="/modular/admin/profile" class="toolbar-link">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($current_user['name'] ?? $current_user['email'] ?? 'User'); ?></span>
                </a>
                <a href="/modular/admin/auth/logout.php" class="toolbar-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            <?php else: ?>
                <a href="/modular/admin/auth/login.php" class="toolbar-link">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
