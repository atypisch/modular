<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../data/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

// Get user data
$userData = new userdata();
$totalUsers = count($userData->getAllUsers());

// Set page title
$page_title = 'Dashboard';

// Set content
$content = '
<div class="dashboard">
    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <h1>Dashboard</h1>
            <p>Welcome to your Modular CMS dashboard</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Statistics Cards -->
            <div class="stat-card">
                <h3>Total Users</h3>
                <p class="stat-number">' . $totalUsers . '</p>
                <i class="fas fa-users"></i>
            </div>
            
            <!-- Add more statistics cards as needed -->
        </div>
    </main>
</div>
';

// Include header
require_once __DIR__ . '/../../templates/header.php';
?>

<?php echo $content; ?>

<?php
// Include footer
require_once __DIR__ . '/../../templates/footer.php';
?>
