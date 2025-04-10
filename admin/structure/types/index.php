<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../data/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

// Set page title
$page_title = 'Types';

// Set content
$content = '
<div class="dashboard">
    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <h1>Content Types</h1>
            <p>Manage your content types and fields</p>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Types List -->
            <div class="col-span-1">
                <div class="card">
                    <div class="flex justify-between items-center p-4">
                        <h2>Content Types</h2>
                        <a href="/modular/admin/types/new" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Type
                        </a>
                    </div>
                    <div class="p-4">
                        <!-- Add types list here -->
                    </div>
                </div>
            </div>
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
