<?php
// Sidebar navigation
// Ensure active states are set (from header.php)
$adminBase = defined('ADMIN_BASE_PATH') ? ADMIN_BASE_PATH : '/modular/admin';
$dashboard_active = $dashboard_active ?? false;
$post_types_active = $post_types_active ?? false;
$posts_active = $posts_active ?? false;
$media_active = $media_active ?? false;
$users_active = $users_active ?? false;
$settings_active = $settings_active ?? false;
$forms_active = $forms_active ?? false;
$actions_active = $actions_active ?? false;
$modules_active = $modules_active ?? false;
$profile_active = $profile_active ?? false;
?>
<aside class="sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="<?php echo ADMIN_URL; ?>/dashboard" class="<?php echo $dashboard_active ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <?php
                $dashboard_sub_active = $dashboard_active;
                ?>
                <ul class="sub-nav <?php echo $dashboard_sub_active ? 'active' : ''; ?>">
                    <li><a href="<?php echo ADMIN_URL; ?>/dashboard" class="<?php echo $dashboard_active && !str_contains($_SERVER['REQUEST_URI'], 'analytics') && !str_contains($_SERVER['REQUEST_URI'], 'reports') ? 'active' : ''; ?>">Overview</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/dashboard/analytics.php" class="<?php echo check_active_path($adminBase . '/dashboard/analytics.php') ? 'active' : ''; ?>">Analytics</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/dashboard/reports.php" class="<?php echo check_active_path($adminBase . '/dashboard/reports.php') ? 'active' : ''; ?>">Reports</a></li>
                </ul>
            </li>
            <li>
                <a href="<?php echo ADMIN_URL; ?>/media" class="<?php echo $media_active ? 'active' : ''; ?>">
                    <i class="fas fa-images"></i>
                    Media
                </a>
                <?php
                $media_sub_active = $media_active;
                ?>
                <ul class="sub-nav <?php echo $media_sub_active ? 'active' : ''; ?>">
                    <li><a href="<?php echo ADMIN_URL; ?>/media" class="<?php echo $media_active && !check_active_path($adminBase . '/media/upload') ? 'active' : ''; ?>">Library</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/media/upload" class="<?php echo check_active_path($adminBase . '/media/upload') ? 'active' : ''; ?>">Upload</a></li>
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
                        $showInMenu = isset($pt['show_in_menu']) ? (bool)$pt['show_in_menu'] : true;
                        if (!$showInMenu) continue;

                        $slug = $pt['slug'] ?? $pt['nicename'] ?? '';
                        $name = $pt['name'] ?? 'Untitled';
                        $ptId = $pt['id'];

                        $isActive = (str_contains($_SERVER['REQUEST_URI'], $adminBase . '/posts') && isset($_GET['post_type_id']) && $_GET['post_type_id'] == $ptId) ||
                                   (str_contains($_SERVER['REQUEST_URI'], $adminBase . '/posts/' . $slug));
                        ?>
                        <li>
                            <a href="<?php echo ADMIN_URL; ?>/posts?post_type_id=<?php echo $ptId; ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
                                <i class="fas fa-file-alt"></i>
                                <?php echo htmlspecialchars($name); ?>
                            </a>
                            <?php
                            $pt_sub_active = $isActive;
                            ?>
                            <ul class="sub-nav <?php echo $pt_sub_active ? 'active' : ''; ?>">
                                <li><a href="<?php echo ADMIN_URL; ?>/posts?post_type_id=<?php echo $ptId; ?>" class="<?php echo $isActive && !isset($_GET['id']) && !str_contains($_SERVER['REQUEST_URI'], $adminBase . '/posts/edit.php') ? 'active' : ''; ?>">All <?php echo htmlspecialchars($name); ?></a></li>
                                <li><a href="<?php echo ADMIN_URL; ?>/posts/edit.php?post_type_id=<?php echo $ptId; ?>" class="<?php echo str_contains($_SERVER['REQUEST_URI'], $adminBase . '/posts/edit.php') && isset($_GET['post_type_id']) && $_GET['post_type_id'] == $ptId ? 'active' : ''; ?>">Add New</a></li>
                            </ul>
                        </li>
                        <?php
                    }
                } catch (Exception $e) {
                }
            }
            ?>
            <li class="sidebar-divider" role="separator"></li>
            <li>
                <a href="<?php echo ADMIN_URL; ?>/post-types" class="<?php echo $post_types_active && !$posts_active ? 'active' : ''; ?>">
                    <i class="fas fa-cubes"></i>
                    Post Types
                </a>
                <?php
                $post_types_sub_active = $post_types_active && !$posts_active;
                ?>
                <ul class="sub-nav <?php echo $post_types_sub_active ? 'active' : ''; ?>">
                    <li><a href="<?php echo ADMIN_URL; ?>/post-types" class="<?php echo $post_types_active && !isset($_GET['id']) && !str_contains($_SERVER['REQUEST_URI'], 'edit.php') && !str_contains($_SERVER['REQUEST_URI'], 'taxonomies') ? 'active' : ''; ?>">All Post Types</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/post-types/edit.php" class="<?php echo str_contains($_SERVER['REQUEST_URI'], $adminBase . '/post-types/edit.php') && !str_contains($_SERVER['REQUEST_URI'], 'taxonomies') && !str_contains($_SERVER['REQUEST_URI'], $adminBase . '/posts') ? 'active' : ''; ?>">Add New</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/taxonomies" class="<?php echo str_contains($_SERVER['REQUEST_URI'], '/taxonomies') ? 'active' : ''; ?>">Taxonomies</a></li>
                </ul>
            </li>
            <li>
                <a href="<?php echo ADMIN_URL; ?>/modules" class="<?php echo $modules_active ? 'active' : ''; ?>">
                    <i class="fas fa-puzzle-piece"></i>
                    Modules
                </a>
            </li>
            <li>
                <a href="<?php echo ADMIN_URL; ?>/actions" class="<?php echo $actions_active ? 'active' : ''; ?>">
                    <i class="fas fa-bolt"></i>
                    Actions
                </a>
            </li>
            <li>
                <a href="<?php echo ADMIN_URL; ?>/forms" class="<?php echo $forms_active ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    Forms
                </a>
                <?php
                $forms_sub_active = $forms_active;
                ?>
                <ul class="sub-nav <?php echo $forms_sub_active ? 'active' : ''; ?>">
                    <li><a href="<?php echo ADMIN_URL; ?>/forms" class="<?php echo $forms_active && !check_active_path($adminBase . '/forms/builder') ? 'active' : ''; ?>">All Forms</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/forms/builder.php" class="<?php echo check_active_path($adminBase . '/forms/builder') ? 'active' : ''; ?>">Create Form</a></li>
                </ul>
            </li>
            <li>
                <a href="<?php echo ADMIN_URL; ?>/users" class="<?php echo $users_active ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    Users
                </a>
                <?php
                $users_sub_active = $users_active;
                ?>
                <ul class="sub-nav <?php echo $users_sub_active ? 'active' : ''; ?>">
                    <li><a href="<?php echo ADMIN_URL; ?>/users" class="<?php echo $users_active && !check_active_path($adminBase . '/users/roles.php') && !check_active_path($adminBase . '/users/roles/') && !check_active_path($adminBase . '/users/edit.php') ? 'active' : ''; ?>">Manage Users</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/users/roles.php" class="<?php echo (check_active_path($adminBase . '/users/roles.php') || check_active_path($adminBase . '/users/roles/')) ? 'active' : ''; ?>">User Roles</a></li>
                </ul>
            </li>
            <li>
                <a href="<?php echo ADMIN_URL; ?>/profile" class="<?php echo $profile_active ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    Profile
                </a>
            </li>
            <li>
                <a href="<?php echo ADMIN_URL; ?>/settings" class="<?php echo $settings_active ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
                <?php
                $settings_sub_active = $settings_active;
                ?>
                <ul class="sub-nav <?php echo $settings_sub_active ? 'active' : ''; ?>">
                    <li><a href="<?php echo ADMIN_URL; ?>/settings" class="<?php echo $settings_active && !check_active_path($adminBase . '/settings/appearance') && !check_active_path($adminBase . '/settings/security') ? 'active' : ''; ?>">General</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/settings/appearance" class="<?php echo check_active_path($adminBase . '/settings/appearance') ? 'active' : ''; ?>">Appearance</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/settings/security" class="<?php echo check_active_path($adminBase . '/settings/security') ? 'active' : ''; ?>">Security</a></li>
                </ul>
            </li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="Collapse sidebar">
            <span class="sidebar-toggle-collapse" aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
            <span class="sidebar-toggle-expand" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
        </button>
    </div>
</aside>
