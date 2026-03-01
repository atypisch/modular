<?php
/**
 * Dashboard Reports - Japanese Style
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

$page_title = 'Reports';
$dashboard_active = true;

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Reports</h1>
    </div>
    
    <div class="card">
        <h3>Reporting</h3>
        <p class="text-muted">Reporting functionality coming soon. This will include:</p>
        <ul style="margin-top: var(--spacing-md); padding-left: var(--spacing-lg);">
            <li>Content performance reports</li>
            <li>User activity reports</li>
            <li>Media usage reports</li>
            <li>Export capabilities</li>
        </ul>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
