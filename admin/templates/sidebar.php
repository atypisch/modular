<?php
// Sidebar navigation
// Ensure active states are set (from header.php)
$dashboard_active = $dashboard_active ?? false;
$post_types_active = $post_types_active ?? false;
$posts_active = $posts_active ?? false;
$media_active = $media_active ?? false;
$users_active = $users_active ?? false;
$settings_active = $settings_active ?? false;
$forms_active = $forms_active ?? false;
$actions_active = $actions_active ?? false;
$profile_active = $profile_active ?? false;
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h1>Modular CMS</h1>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="/modular/admin/dashboard" class="<?php echo $dashboard_active ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <?php 
                // Only show sub-nav if dashboard is the ONLY active main menu item
                $dashboard_sub_active = $dashboard_active;
                ?>
                <ul class="sub-nav <?php echo $dashboard_sub_active ? 'active' : ''; ?>">
                    <li><a href="/modular/admin/dashboard" class="<?php echo $dashboard_active && !str_contains($_SERVER['REQUEST_URI'], 'analytics') && !str_contains($_SERVER['REQUEST_URI'], 'reports') ? 'active' : ''; ?>">Overview</a></li>
                    <li><a href="/modular/admin/dashboard/analytics.php" class="<?php echo check_active_path('/modular/admin/dashboard/analytics.php') ? 'active' : ''; ?>">Analytics</a></li>
                    <li><a href="/modular/admin/dashboard/reports.php" class="<?php echo check_active_path('/modular/admin/dashboard/reports.php') ? 'active' : ''; ?>">Reports</a></li>
                </ul>
            </li>
            <li>
                <a href="/modular/admin/post-types" class="<?php echo $post_types_active && !$posts_active ? 'active' : ''; ?>">
                    <i class="fas fa-cubes"></i>
                    Post Types
                </a>
                <?php 
                // Only show sub-nav if post-types is the ONLY active main menu item AND we're NOT on a posts page
                $post_types_sub_active = $post_types_active && !$posts_active;
                ?>
                <ul class="sub-nav <?php echo $post_types_sub_active ? 'active' : ''; ?>">
                    <li><a href="/modular/admin/post-types" class="<?php echo $post_types_active && !isset($_GET['id']) && !str_contains($_SERVER['REQUEST_URI'], 'edit.php') && !str_contains($_SERVER['REQUEST_URI'], 'taxonomies') ? 'active' : ''; ?>">All Post Types</a></li>
                    <li><a href="/modular/admin/post-types/edit.php" class="<?php echo str_contains($_SERVER['REQUEST_URI'], '/modular/admin/post-types/edit.php') && !str_contains($_SERVER['REQUEST_URI'], 'taxonomies') && !str_contains($_SERVER['REQUEST_URI'], '/modular/admin/posts') ? 'active' : ''; ?>">Add New</a></li>
                    <li><a href="/modular/admin/taxonomies" class="<?php echo str_contains($_SERVER['REQUEST_URI'], '/taxonomies') ? 'active' : ''; ?>">Taxonomies</a></li>
                </ul>
            </li>
            <?php
            // Dynamically add menu items for each post type that has show_in_menu = 1
            if (isset($GLOBALS['db'])) {
                try {
                    require_once CORE_PATH . '/classes/PostType.php';
                    $postType = new PostType();
                    $allPostTypes = $postType->getAll();
                    
                    foreach ($allPostTypes as $pt) {
                        // Only show in menu if show_in_menu is true
                        $showInMenu = isset($pt['show_in_menu']) ? (bool)$pt['show_in_menu'] : true;
                        if (!$showInMenu) continue;
                        
                        $slug = $pt['slug'] ?? $pt['nicename'] ?? '';
                        $name = $pt['name'] ?? 'Untitled';
                        $ptId = $pt['id'];
                        
                        // Check if current page is for this post type
                        $isActive = (str_contains($_SERVER['REQUEST_URI'], '/modular/admin/posts') && isset($_GET['post_type_id']) && $_GET['post_type_id'] == $ptId) ||
                                   (str_contains($_SERVER['REQUEST_URI'], '/modular/admin/posts/' . $slug));
                        ?>
                        <li>
                            <a href="/modular/admin/posts?post_type_id=<?php echo $ptId; ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
                                <i class="fas fa-file-alt"></i>
                                <?php echo htmlspecialchars($name); ?>
                            </a>
                            <?php 
                            // Only show sub-nav if this post type is the active menu item
                            // When on a posts page, only this post type should be active (not Post Types or Dashboard)
                            $pt_sub_active = $isActive;
                            ?>
                            <ul class="sub-nav <?php echo $pt_sub_active ? 'active' : ''; ?>">
                                <li><a href="/modular/admin/posts?post_type_id=<?php echo $ptId; ?>" class="<?php echo $isActive && !isset($_GET['id']) && !str_contains($_SERVER['REQUEST_URI'], '/modular/admin/posts/edit.php') ? 'active' : ''; ?>">All <?php echo htmlspecialchars($name); ?></a></li>
                                <li><a href="/modular/admin/posts/edit.php?post_type_id=<?php echo $ptId; ?>" class="<?php echo str_contains($_SERVER['REQUEST_URI'], '/modular/admin/posts/edit.php') && isset($_GET['post_type_id']) && $_GET['post_type_id'] == $ptId ? 'active' : ''; ?>">Add New</a></li>
                            </ul>
                        </li>
                        <?php
                    }
                } catch (Exception $e) {
                    // Silently fail if post types can't be loaded (e.g., tables don't exist yet)
                }
            }
            ?>
            <li>
                <a href="/modular/admin/media" class="<?php echo $media_active ? 'active' : ''; ?>">
                    <i class="fas fa-images"></i>
                    Media
                </a>
                <?php 
                // Only show sub-nav if media is the ONLY active main menu item
                $media_sub_active = $media_active;
                ?>
                <ul class="sub-nav <?php echo $media_sub_active ? 'active' : ''; ?>">
                    <li><a href="/modular/admin/media" class="<?php echo $media_active && !check_active_path('/modular/admin/media/upload') ? 'active' : ''; ?>">Library</a></li>
                    <li><a href="/modular/admin/media/upload" class="<?php echo check_active_path('/modular/admin/media/upload') ? 'active' : ''; ?>">Upload</a></li>
                </ul>
            </li>
            <li>
                <a href="/modular/admin/forms" class="<?php echo $forms_active ? 'active' : ''; ?>">
                    <i class="fas fa-wpforms"></i>
                    Forms
                </a>
                <?php 
                // Only show sub-nav if forms is the ONLY active main menu item
                $forms_sub_active = $forms_active;
                ?>
                <ul class="sub-nav <?php echo $forms_sub_active ? 'active' : ''; ?>">
                    <li><a href="/modular/admin/forms" class="<?php echo $forms_active && !check_active_path('/modular/admin/forms/builder') ? 'active' : ''; ?>">All Forms</a></li>
                    <li><a href="/modular/admin/forms/builder.php" class="<?php echo check_active_path('/modular/admin/forms/builder') ? 'active' : ''; ?>">Create Form</a></li>
                </ul>
            </li>
            <li>
                <a href="/modular/admin/actions" class="<?php echo $actions_active ? 'active' : ''; ?>">
                    <i class="fas fa-bolt"></i>
                    Actions
                </a>
            </li>
            <li>
                <a href="/modular/admin/users" class="<?php echo $users_active ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    Users
                </a>
                <?php 
                // Only show sub-nav if users is the ONLY active main menu item
                $users_sub_active = $users_active;
                ?>
                <ul class="sub-nav <?php echo $users_sub_active ? 'active' : ''; ?>">
                    <li><a href="/modular/admin/users" class="<?php echo $users_active && !check_active_path('/modular/admin/users/roles') && !check_active_path('/modular/admin/users/edit.php') ? 'active' : ''; ?>">Manage Users</a></li>
                    <li><a href="/modular/admin/users/roles" class="<?php echo check_active_path('/modular/admin/users/roles') ? 'active' : ''; ?>">User Roles</a></li>
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
                <?php 
                // Only show sub-nav if settings is the ONLY active main menu item
                $settings_sub_active = $settings_active;
                ?>
                <ul class="sub-nav <?php echo $settings_sub_active ? 'active' : ''; ?>">
                    <li><a href="/modular/admin/settings" class="<?php echo $settings_active && !check_active_path('/modular/admin/settings/appearance') && !check_active_path('/modular/admin/settings/security') ? 'active' : ''; ?>">General</a></li>
                    <li><a href="/modular/admin/settings/appearance" class="<?php echo check_active_path('/modular/admin/settings/appearance') ? 'active' : ''; ?>">Appearance</a></li>
                    <li><a href="/modular/admin/settings/security" class="<?php echo check_active_path('/modular/admin/settings/security') ? 'active' : ''; ?>">Security</a></li>
                </ul>
            </li>
        </ul>
    </nav>
</aside>
