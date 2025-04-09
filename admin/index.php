<?php
// Start session and check if user is logged in
session_start();
require_once 'config/config.php';
require_once dirname(__FILE__) . '/../data/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$userData = new userdata();
$totalUsers = count($userData->getAllUsers());

// Set page title and additional styles
$page_title = 'Dashboard';
$additional_styles = '
    <link rel="stylesheet" href="' . ADMIN_CSS_PATH . '/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="' . ADMIN_CSS_PATH . '/profile.css">
';

// Set additional scripts
$additional_scripts = '
    <script src="' . ADMIN_URL . '/js/profile.js"></script>
';

// Set content
$content = '
<div class="dashboard">
    <!-- Top Navigation Bar -->
    <nav class="top-nav">
        <div class="nav-left">
            <a href="/modular" target="_blank" class="nav-link">
                <i class="fas fa-external-link-alt"></i> View Homepage
            </a>
        </div>
        <div class="nav-right">
            <a href="/modular/admin/auth/logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Modular CMS</h2>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li class="active">
                    <a href="/modular/admin/">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Type</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-puzzle-piece"></i>
                        <span>Modules</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li>
                    <a href="/modular/admin/profile.php">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <h1>Dashboard</h1>
            <p>Welcome back, ' . htmlspecialchars($_SESSION['user_name'] ?? '') . '</p>
        </div>

        <!-- Widgets Grid -->
        <div class="widgets-grid">
            <!-- Users Widget -->
            <div class="widget">
                <div class="widget-header">
                    <h3>Users</h3>
                    <i class="fas fa-users"></i>
                </div>
                <div class="widget-content">
                    <div class="stat">
                        <span class="stat-value">' . $totalUsers . '</span>
                        <span class="stat-label">Total Users</span>
                    </div>
                    <div class="widget-footer">
                        <a href="#" class="widget-link">View All Users</a>
                    </div>
                </div>
            </div>

            <!-- Content Types Widget -->
            <div class="widget">
                <div class="widget-header">
                    <h3>Content Types</h3>
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="widget-content">
                    <div class="stat">
                        <span class="stat-value">0</span>
                        <span class="stat-label">Total Types</span>
                    </div>
                    <div class="widget-footer">
                        <a href="#" class="widget-link">Manage Types</a>
                    </div>
                </div>
            </div>

            <!-- Modules Widget -->
            <div class="widget">
                <div class="widget-header">
                    <h3>Modules</h3>
                    <i class="fas fa-puzzle-piece"></i>
                </div>
                <div class="widget-content">
                    <div class="stat">
                        <span class="stat-value">0</span>
                        <span class="stat-label">Active Modules</span>
                    </div>
                    <div class="widget-footer">
                        <a href="#" class="widget-link">Configure Modules</a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Widget -->
            <div class="widget widget-wide">
                <div class="widget-header">
                    <h3>Recent Activity</h3>
                    <i class="fas fa-clock"></i>
                </div>
                <div class="widget-content">
                    <ul class="activity-list">
                        <li class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="activity-details">
                                <p>New user registered</p>
                                <span class="activity-time">2 minutes ago</span>
                            </div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-file-edit"></i>
                            </div>
                            <div class="activity-details">
                                <p>Content type updated</p>
                                <span class="activity-time">15 minutes ago</span>
                            </div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="activity-details">
                                <p>Settings updated</p>
                                <span class="activity-time">1 hour ago</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</div>
';

// Load the theme
require_once dirname(dirname(__FILE__)) . '/public/themes/default/index.php';

// Include additional scripts
if (isset($additional_scripts)) {
    echo $additional_scripts;
}
?>
