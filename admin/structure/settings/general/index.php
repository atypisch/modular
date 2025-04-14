<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/data/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

// Set page title
$page_title = 'General Settings';

// Set content
$content = '
<div class="dashboard">
    <div class="content">
        <div class="card">
            <div class="card-header">
                <h2>General Settings</h2>
            </div>
            <div class="card-body">
                <form action="/modular/admin/settings/general/save" method="POST" class="form">
                    <div class="form-group">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" id="site_name" name="site_name" class="form-input" value="' . htmlspecialchars($settings['site_name'] ?? '') . '" required>
                    </div>
                    <div class="form-group">
                        <label for="site_description" class="form-label">Site Description</label>
                        <textarea id="site_description" name="site_description" class="form-input" rows="3">' . htmlspecialchars($settings['site_description'] ?? '') . '</textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>';

// Include header and footer
require_once dirname(dirname(dirname(__FILE__))) . '/templates/header.php';
echo $content;
require_once dirname(dirname(dirname(__FILE__))) . '/templates/footer.php';
?>
