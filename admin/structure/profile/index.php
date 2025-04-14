<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/data/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

// Get user data
$userData = new userdata();
$user = $userData->getUserById($_SESSION['user_id']);

// Set page title
$page_title = 'Profile';

// Set content
$content = '
<div class="dashboard">
    <div class="content">
        <div class="card">
            <div class="card-header">
                <h2>Your Profile</h2>
            </div>
            <div class="card-body">
                <form action="/modular/admin/profile/update" method="POST" class="form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" id="name" name="name" class="form-input" value="' . htmlspecialchars($user['name'] ?? '') . '" required>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-input" value="' . htmlspecialchars($user['email'] ?? '') . '" required>
                    </div>
                    <div class="form-group">
                        <label for="avatar" class="form-label">Profile Picture</label>
                        <input type="file" id="avatar" name="avatar" class="form-input" accept="image/*">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>';

// Include header and footer
require_once dirname(__DIR__) . '/templates/header.php';
echo $content;
require_once dirname(__DIR__) . '/templates/footer.php';
?>
