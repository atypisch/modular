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
            <a href="<?php echo SITE_URL; ?>/public/" class="toolbar-favicon" title="Modular front page" aria-label="Modular front page">
                <img src="<?php echo SITE_URL; ?>/ontwerp/favicon_modular.svg" alt="" width="24" height="24">
            </a>
            <a href="<?php echo SITE_URL; ?>/public/" class="toolbar-link">
                <i class="fas fa-home"></i>
                <span>Frontpage</span>
            </a>
        </div>
        <div class="toolbar-right">
            <?php if (!isInstalled() || (function_exists('dev_mode_enabled') && dev_mode_enabled())): ?>
                <span class="toolbar-devbadge">
                    <?php if (!isInstalled()): ?>
                        <span class="toolbar-devbadge-label">Setup</span>
                        <span class="toolbar-devbadge-text">Modular CMS is not fully installed</span>
                    <?php else: ?>
                        <span class="toolbar-devbadge-label">DEV MODE ACTIVE</span>
                        <span class="toolbar-devbadge-text">Tier: <?php echo htmlspecialchars(subscription_effective_tier()); ?></span>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
            <?php if ($current_user): ?>
                <a href="<?php echo ADMIN_URL; ?>/profile" class="toolbar-link">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($current_user['name'] ?? $current_user['username'] ?? $current_user['email'] ?? 'User'); ?></span>
                </a>
                <a href="<?php echo ADMIN_URL; ?>/auth/logout.php" class="toolbar-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            <?php else: ?>
                <a href="<?php echo ADMIN_URL; ?>/auth/login.php" class="toolbar-link">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
