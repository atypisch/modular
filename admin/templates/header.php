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
    <link rel="stylesheet" href="/modular/admin/assets/css/layout.css">
    <link rel="stylesheet" href="/modular/admin/assets/css/components.css">
    <link rel="stylesheet" href="/modular/admin/assets/css/responsive.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
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
</head>
<body>
    <!-- Toolbar -->
    <?php require_once __DIR__ . '/toolbar.php'; ?>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>Modular CMS</h1>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="/modular/admin" class="<?php echo $dashboard_active ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                        <ul class="sub-nav <?php echo $dashboard_active ? 'active' : ''; ?>">
                            <li><a href="/modular/admin" class="<?php echo $dashboard_active ? 'active' : ''; ?>">Overview</a></li>
                            <li><a href="/modular/admin/analytics" class="<?php echo is_active('/modular/admin/analytics') ? 'active' : ''; ?>">Analytics</a></li>
                            <li><a href="/modular/admin/reports" class="<?php echo is_active('/modular/admin/reports') ? 'active' : ''; ?>">Reports</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="/modular/admin/types" class="<?php echo $types_active ? 'active' : ''; ?>">
                            <i class="fas fa-file-alt"></i>
                            Types
                        </a>
                        <ul class="sub-nav <?php echo $types_active ? 'active' : ''; ?>">
                            <li><a href="/modular/admin/types" class="<?php echo $types_active ? 'active' : ''; ?>">Manage Types</a></li>
                            <li><a href="/modular/admin/types/new" class="<?php echo is_active('/modular/admin/types/new') ? 'active' : ''; ?>">New Type</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="/modular/admin/media" class="<?php echo $media_active ? 'active' : ''; ?>">
                            <i class="fas fa-images"></i>
                            Media
                        </a>
                        <ul class="sub-nav <?php echo $media_active ? 'active' : ''; ?>">
                            <li><a href="/modular/admin/media" class="<?php echo $media_active ? 'active' : ''; ?>">Library</a></li>
                            <li><a href="/modular/admin/media/upload" class="<?php echo is_active('/modular/admin/media/upload') ? 'active' : ''; ?>">Upload</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="/modular/admin/users" class="<?php echo $users_active ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            Users
                        </a>
                        <ul class="sub-nav <?php echo $users_active ? 'active' : ''; ?>">
                            <li><a href="/modular/admin/users" class="<?php echo $users_active ? 'active' : ''; ?>">Manage Users</a></li>
                            <li><a href="/modular/admin/users/roles" class="<?php echo is_active('/modular/admin/users/roles') ? 'active' : ''; ?>">User Roles</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="/modular/admin/profile" class="<?php echo $profile_active ? 'active' : ''; ?>">
                            <i class="fas fa-user"></i>
                            Profile
                        </a>
                    </li>
                    <li>
                        <a href="/modular/admin/settings" class="<?php echo $settings_active ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                        <ul class="sub-nav <?php echo $settings_active ? 'active' : ''; ?>">
                            <li><a href="/modular/admin/settings" class="<?php echo $settings_active ? 'active' : ''; ?>">General</a></li>
                            <li><a href="/modular/admin/settings/appearance" class="<?php echo is_active('/modular/admin/settings/appearance') ? 'active' : ''; ?>">Appearance</a></li>
                            <li><a href="/modular/admin/settings/security" class="<?php echo is_active('/modular/admin/settings/security') ? 'active' : ''; ?>">Security</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
