<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load required files if not already loaded
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once CORE_PATH . '/functions/admin.php';
require_once CORE_PATH . '/functions/database.php';

// Check database installation status (only if not on install page)
$db_install_needed = false;
if (strpos($_SERVER['REQUEST_URI'], '/install.php') === false && isset($GLOBALS['db'])) {
    try {
        $tableCheck = check_database_tables();
        // Only show install banner if critical tables are missing (users, post_types)
        $criticalTables = ['users', 'post_types'];
        $criticalMissing = array_intersect($criticalTables, $tableCheck['missing']);
        $db_install_needed = !empty($criticalMissing);
    } catch (Exception $e) {
        // If check fails, don't show banner (might be a temporary issue)
        $db_install_needed = false;
        error_log("Database check error: " . $e->getMessage());
    }
}

// Ensure $page_title is set
$page_title = $page_title ?? 'Modular CMS';

// Get current path for active navigation (without query string)
$current_path = strtok($_SERVER['REQUEST_URI'], '?');

// Get active states for navigation
// Determine which top-level menu item should be active (only ONE can be active)
// Priority order: posts > post_types > dashboard > media > forms > users > settings > profile > actions

// First check if we're on a posts page (highest priority)
// Respect if $posts_active is already set, otherwise detect it
if (!isset($posts_active)) {
    $posts_active = is_posts_page();
} else {
    // If already set, ensure it's boolean
    $posts_active = (bool)$posts_active;
}

// Only check other pages if NOT on posts page
if (!$posts_active) {
    // Check post types (but not if we're on posts page)
    // Also exclude if we're on a posts page (double check)
    $post_types_active = !$posts_active && (is_post_types_page() || strpos($current_path, '/modular/admin/taxonomies') !== false);
    
    // Only check dashboard if not on post types or posts
    if (!$post_types_active) {
        // Dashboard only when path is exactly /modular/admin or starts with /modular/admin/dashboard
        // (Do NOT use check_active_path('/modular/admin') - it matches every admin page)
        $dashboard_active = ($current_path === '/modular/admin' || $current_path === '/modular/admin/' || strpos($current_path, '/modular/admin/dashboard') === 0);
    } else {
        $dashboard_active = false;
    }
    
    // Check other pages only if none of the above are active
    if (!$post_types_active && !$dashboard_active) {
        $media_active = is_media_page();
        $users_active = is_users_page();
        $settings_active = is_settings_page();
        $profile_active = is_profile_page();
        $forms_active = is_forms_page();
        $actions_active = is_actions_page();
        $modules_active = is_modules_page();
    } else {
        $media_active = false;
        $users_active = false;
        $settings_active = false;
        $profile_active = false;
        $forms_active = false;
        $actions_active = false;
        $modules_active = false;
    }
} else {
    // On posts page - deactivate all other top-level items
    $post_types_active = false;
    if (!isset($dashboard_active)) {
        $dashboard_active = false;
    }
    $media_active = false;
    $users_active = false;
    $settings_active = false;
    $profile_active = false;
    $forms_active = false;
    $actions_active = false;
    $modules_active = false;
}

// Check if this is an auth page
$is_auth_page = strpos($current_path, '/modular/admin/auth/') !== false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo isset($page_title) ? $page_title . ' - Modular CMS' : 'Modular CMS'; ?></title>
    <link rel="icon" href="/modular/ontwerp/favicon_modular.svg" type="image/svg+xml">
    
    <!-- Base CSS -->
    <link rel="stylesheet" href="/modular/admin/assets/css/base.css">
    <link rel="stylesheet" href="/modular/admin/assets/css/form.css">
    <?php if (!$is_auth_page): ?>
    <link rel="stylesheet" href="/modular/admin/assets/css/layout.css">
    <link rel="stylesheet" href="/modular/admin/assets/css/components.css">
    <?php endif; ?>
    <?php if ($is_auth_page): ?>
    <link rel="stylesheet" href="/modular/admin/assets/css/auth.css">
    <?php endif; ?>
    <link rel="stylesheet" href="/modular/admin/assets/css/responsive.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <?php if (!$is_auth_page): ?>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Vue.js -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    
    <!-- Admin Scripts -->
    <script src="/modular/admin/assets/js/base.js"></script>
    <script src="/modular/admin/assets/js/components.js"></script>
    <script src="/modular/admin/assets/js/forms.js"></script>
    <script src="/modular/admin/assets/js/tables.js"></script>
    <script src="/modular/admin/assets/js/charts.js"></script>
    <?php endif; ?>
</head>
<body class="<?php echo $is_auth_page ? 'auth-page' : ''; ?>">
    <?php if (!$is_auth_page): ?>
    <?php require_once __DIR__ . '/toolbar.php'; ?>
    <?php endif; ?>

    <div class="main-container">
        <div class="main-body">
            <?php if (!$is_auth_page): ?>
            <?php require_once __DIR__ . '/sidebar.php'; ?>
            <?php endif; ?>

            <main class="main-content">
                <?php if (!$is_auth_page && $db_install_needed): ?>
                <div class="alert alert-error" style="margin: var(--spacing-md); border-left: 4px solid var(--error-border);">
                    <div style="display: flex; align-items: start; gap: var(--spacing-md);">
                        <div style="flex: 1;">
                            <strong style="display: block; margin-bottom: var(--spacing-sm);">
                                <i class="fas fa-exclamation-triangle"></i> Database Installation Required
                            </strong>
                            <p style="margin: 0; font-size: 0.9rem;">
                                The database tables have not been created yet. Please install the database to continue using the CMS.
                            </p>
                        </div>
                        <div>
                            <a href="/modular/admin/install.php" class="btn btn-primary" style="white-space: nowrap;">
                                <i class="fas fa-database"></i> Install Database
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>