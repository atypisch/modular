<?php
// Start session and check if user is logged in
session_start();
require_once 'config.php';
require_once 'auth/data/UserData.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$userData = new UserData();
$totalUsers = count($userData->getAllUsers());

$page_title = 'Dashboard';
$content = '
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold">Dashboard</h1>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">Welcome, ' . htmlspecialchars($_SESSION['user_name']) . '</span>
                <a href="/modular/admin/auth/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="dashboard-card">
                <h3 class="dashboard-label">Total Users</h3>
                <p class="dashboard-stat">' . $totalUsers . '</p>
            </div>
            <div class="dashboard-card">
                <h3 class="dashboard-label">Total Pages</h3>
                <p class="dashboard-stat">0</p>
            </div>
            <div class="dashboard-card">
                <h3 class="dashboard-label">Total Posts</h3>
                <p class="dashboard-stat">0</p>
            </div>
        </div>
    </div>
';

$additional_styles = '
    .dashboard {
        padding: 2rem;
    }
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    .user-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .user-role {
        background: #e9ecef;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.9rem;
        color: #495057;
    }
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }
    .dashboard-card {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-top: 1rem;
    }
    .stat-item {
        text-align: center;
    }
    .stat-value {
        display: block;
        font-size: 1.5rem;
        font-weight: bold;
        color: #007bff;
    }
    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.9rem;
    }
    .activity-list {
        margin-top: 1rem;
    }
';

// Load the theme with additional styles
require THEMES_PATH . '/' . DEFAULT_THEME . '/index.php'; 