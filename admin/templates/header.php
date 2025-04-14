<?php
// Ensure $page_title is set
$page_title = $page_title ?? 'Modular CMS';

// Get current path for active navigation
$current_path = $_SERVER['REQUEST_URI'];

// Function to check if a link should be active
function is_active($path) {
    global $current_path;
    // Check if we're on the exact path
    return $current_path === $path;
}

// Set active states for navigation
$dashboard_active = is_active('/modular/admin');
$types_active = is_active('/modular/admin/types');
$media_active = is_active('/modular/admin/media');
$users_active = is_active('/modular/admin/users');
$settings_active = is_active('/modular/admin/settings');
$profile_active = is_active('/modular/admin/profile');

// Check if this is an auth page
$is_auth_page = strpos($current_path, '/modular/admin/auth/') !== false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Modular CMS' : 'Modular CMS'; ?></title>
    
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
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    
    <!-- Admin Scripts -->
    <script src="/modular/admin/assets/js/base.js"></script>
    <script src="/modular/admin/assets/js/components.js"></script>
    <script src="/modular/admin/assets/js/forms.js"></script>
    <script src="/modular/admin/assets/js/tables.js"></script>
    <script src="/modular/admin/assets/js/charts.js"></script>
    <?php endif; ?>
</head>
<body>
    <?php if (!$is_auth_page): ?>
    <!-- Toolbar -->
    <?php require_once __DIR__ . '/toolbar.php'; ?>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/sidebar.php'; ?>


    <?php endif; ?>

