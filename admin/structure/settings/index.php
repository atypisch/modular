<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../data/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

// Check if user has admin privileges
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /modular/admin');
    exit;
}

// Set page title
$page_title = 'Settings';

// Set content
$content = '
<div class="dashboard">
    <div class="grid grid-cols-1 gap-6">
        <!-- General Settings -->
        <div class="col-span-1">
            <div class="card">
                <h2>General Settings</h2>
                <div class="mt-4">
                    <form action="/modular/admin/settings/general" method="POST" class="space-y-6">
                        <div class="form-group">
                            <label for="site_name" class="form-label">Site Name</label>
                            <input type="text" id="site_name" name="site_name" class="form-input" value="' . htmlspecialchars($settings['site_name'] ?? '') . '" required>
                        </div>
                        <div class="form-group">
                            <label for="site_description" class="form-label">Site Description</label>
                            <textarea id="site_description" name="site_description" class="form-input" rows="3" required>' . htmlspecialchars($settings['site_description'] ?? '') . '</textarea>
                        </div>
                        <div class="form-group">
                            <label for="admin_email" class="form-label">Admin Email</label>
                            <input type="email" id="admin_email" name="admin_email" class="form-input" value="' . htmlspecialchars($settings['admin_email'] ?? '') . '" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Save General Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="col-span-1">
            <div class="card">
                <h2>Security</h2>
                <div class="mt-4">
                    <form action="/modular/admin/settings/security" method="POST" class="space-y-6">
                        <div class="form-group">
                            <label for="enable_2fa" class="form-label">Two-Factor Authentication</label>
                            <div class="form-toggle">
                                <input type="checkbox" id="enable_2fa" name="enable_2fa" class="toggle-input" ' . (($settings['enable_2fa'] ?? false) ? 'checked' : '') . '>
                                <label for="enable_2fa" class="toggle-label"></label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                            <input type="number" id="session_timeout" name="session_timeout" class="form-input" value="' . htmlspecialchars($settings['session_timeout'] ?? '30') . '" min="5" max="1440" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Save Security Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
';

// Include header
require_once __DIR__ . '/../../templates/header.php';

// Output content
echo $content;

// Include footer
require_once __DIR__ . '/../../templates/footer.php';
?>
