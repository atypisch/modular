<?php
// Sidebar navigation
?>
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
