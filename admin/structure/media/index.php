<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/data/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

// Set page title
$page_title = 'Media Library';

// Set content
$content = '
<div class="dashboard">
    <div class="content">
        <div class="card">
            <div class="card-header">
                <h2>Media Library</h2>
                <a href="/modular/admin/media/upload" class="btn btn-primary">Upload New Media</a>
            </div>
            <div class="card-body">
                <div class="media-grid">
                    <!-- Media items will be loaded here via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>';

// Include header and footer
require_once dirname(__DIR__) . '/templates/header.php';
echo $content;
require_once dirname(__DIR__) . '/templates/footer.php';
?>
