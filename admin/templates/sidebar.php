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
$export_import_active = $export_import_active ?? false;
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
                    <li><a href="<?php echo ADMIN_URL; ?>/dashboard" class="<?php echo $dashboard_active && !str_contains($_SERVER['REQUEST_URI'], 'analytics') && !str_contains($_SERVER['REQUEST_URI'], 'reports') && !str_contains($_SERVER['REQUEST_URI'], 'updates') ? 'active' : ''; ?>">Overview</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/dashboard/updates" class="<?php echo check_active_path($adminBase . '/dashboard/updates') ? 'active' : ''; ?>">Updates</a></li>
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
                    $_sidebarPtHelper = new PostType();
                    $allPostTypes = $_sidebarPtHelper->getAll();

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
            <?php
            // Menu items registered by modules via add_filter('admin_menu_items')
            $module_menu_items = function_exists('apply_filters') ? apply_filters('admin_menu_items', []) : [];
            // Track which module slugs already have a menu item (from filter)
            $module_slugs_in_menu = [];
            foreach ($module_menu_items as $mitem) {
                $u = $mitem['url'] ?? '';
                if (preg_match('/[?&]module=([a-z0-9\-]+)/', $u, $m)) {
                    $module_slugs_in_menu[$m[1]] = true;
                }
            }
            // Fallback: add sidebar entry for each active module that has admin pages but didn't register via filter
            $moduleManager = $GLOBALS['module_manager'] ?? null;
            $modulesDir = defined('PUBLIC_PATH') ? PUBLIC_PATH . '/modules' : (defined('MODULAR_PATH') ? MODULAR_PATH . '/public/modules' : '');
            if ($moduleManager && $modulesDir && is_dir($modulesDir)) {
                $activeModules = $moduleManager->getActiveModules();
                foreach ($activeModules as $slug => $mod) {
                    if (isset($module_slugs_in_menu[$slug])) {
                        continue;
                    }
                    $adminDir = $modulesDir . '/' . $slug . '/admin';
                    if (!is_dir($adminDir)) {
                        continue;
                    }
                    $pages = [];
                    foreach (glob($adminDir . '/*.php') as $f) {
                        $base = basename($f, '.php');
                        $pages[] = [
                            'label' => ucfirst(str_replace(['-', '_'], ' ', $base)),
                            'url' => (defined('ADMIN_URL') ? ADMIN_URL : '') . '/module-page.php?module=' . $slug . '&page=' . $base,
                        ];
                    }
                    if (!empty($pages)) {
                        $module_menu_items[] = [
                            'label' => ($mod->name ?? ucfirst(str_replace('-', ' ', $slug))),
                            'url' => $pages[0]['url'],
                            'icon' => 'fas fa-puzzle-piece',
                            'match' => $slug,
                            'sub' => $pages,
                        ];
                    }
                }
            }
            $current_module = isset($_GET['module']) ? $_GET['module'] : '';
            $current_page = isset($_GET['page']) ? $_GET['page'] : '';
            foreach ($module_menu_items as $mitem):
                $murl = $mitem['url'] ?? '#';
                $mlabel = $mitem['label'] ?? 'Item';
                $micon = $mitem['icon'] ?? 'fas fa-puzzle-piece';
                $mactive = !empty($mitem['match']) && strpos($_SERVER['REQUEST_URI'], 'module-page') !== false && strpos($_SERVER['REQUEST_URI'], $mitem['match']) !== false;
                if (!$mactive && !empty($mitem['url'])) {
                    $mactive = strpos($_SERVER['REQUEST_URI'], $murl) !== false;
                }
                $msub = $mitem['sub'] ?? [];
            ?>
            <li>
                <a href="<?php echo htmlspecialchars($murl); ?>" class="<?php echo $mactive ? 'active' : ''; ?>">
                    <i class="<?php echo htmlspecialchars($micon); ?>"></i>
                    <?php echo htmlspecialchars($mlabel); ?>
                </a>
                <?php if (!empty($msub)): ?>
                <ul class="sub-nav <?php echo $mactive ? 'active' : ''; ?>">
                    <?php foreach ($msub as $s):
                        $surl = $s['url'] ?? '#';
                        $sactive = ($mactive && $current_page && strpos($surl, 'page=' . $current_page) !== false) || ($current_module && strpos($surl, 'module=' . $current_module) !== false && $current_page && strpos($surl, 'page=' . $current_page) !== false);
                    ?>
                    <li><a href="<?php echo htmlspecialchars($surl); ?>" class="<?php echo $sactive ? 'active' : ''; ?>"><?php echo htmlspecialchars($s['label'] ?? ''); ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
            <li>
                <a href="<?php echo ADMIN_URL; ?>/modules" class="<?php echo $modules_active ? 'active' : ''; ?>">
                    <i class="fas fa-puzzle-piece"></i>
                    Modules
                </a>
                <ul class="sub-nav <?php echo $modules_active ? 'active' : ''; ?>">
                    <li><a href="<?php echo ADMIN_URL; ?>/modules" class="<?php echo $modules_active ? 'active' : ''; ?>">Manage modules</a></li>
                </ul>
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
                <a href="<?php echo ADMIN_URL; ?>/export-import" class="<?php echo $export_import_active ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i>
                    Export / Import
                </a>
                <ul class="sub-nav <?php echo $export_import_active ? 'active' : ''; ?>">
                    <li><a href="<?php echo ADMIN_URL; ?>/export-import?tab=export" class="<?php echo $export_import_active && (!isset($_GET['tab']) || $_GET['tab']==='export') ? 'active' : ''; ?>">Export</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/export-import?tab=import" class="<?php echo $export_import_active && (($_GET['tab'] ?? '')==='import') ? 'active' : ''; ?>">Import</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/export-import?tab=history" class="<?php echo $export_import_active && (($_GET['tab'] ?? '')==='history') ? 'active' : ''; ?>">History</a></li>
                </ul>
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
                    <li><a href="<?php echo ADMIN_URL; ?>/settings" class="<?php echo $settings_active && !check_active_path($adminBase . '/settings/appearance') && !check_active_path($adminBase . '/settings/security') && !check_active_path($adminBase . '/settings/navigation') && !check_active_path($adminBase . '/settings/permalinks') ? 'active' : ''; ?>">General</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/settings/appearance" class="<?php echo check_active_path($adminBase . '/settings/appearance') ? 'active' : ''; ?>">Appearance</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/settings/security" class="<?php echo check_active_path($adminBase . '/settings/security') ? 'active' : ''; ?>">Security</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/settings/media-images.php" class="<?php echo check_active_path($adminBase . '/settings/media-images.php') ? 'active' : ''; ?>">Media Images</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/settings/navigation.php" class="<?php echo check_active_path($adminBase . '/settings/navigation') ? 'active' : ''; ?>">Navigation</a></li>
                    <li><a href="<?php echo ADMIN_URL; ?>/settings/permalinks" class="<?php echo check_active_path($adminBase . '/settings/permalinks') ? 'active' : ''; ?>">Permalinks</a></li>
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
