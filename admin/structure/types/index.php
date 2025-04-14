<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/data/init.php';

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
    <div class="content">
        <!-- Success Message -->
        ' . (isset($_GET["success"]) && $_GET["success"] == 1 ? '<div class="alert alert-success">Type saved successfully!</div>' : '') . '
        
        <div class="card">
            <div class="card-header">
                <h2>Content Types</h2>
                <a href="/modular/admin/types/new" class="btn btn-primary">Add New Type</a>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Fields</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Types will be loaded here via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>';

// Include header and footer
require_once dirname(__DIR__) . '/templates/header.php';
echo $content;
require_once dirname(__DIR__) . '/templates/footer.php';
?>
