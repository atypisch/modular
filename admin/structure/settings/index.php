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
    <!-- Top Navigation Bar -->
    <nav class="top-nav">
        <div class="nav-left">
            <a href="/modular" target="_blank" class="nav-link">
                <i class="fas fa-home"></i> Visit Site
            </a>
        </div>
        <div class="nav-right">
            <a href="/modular/admin/auth/logout" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <h1>Settings</h1>
            <p>Configure your website settings</p>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- General Settings -->
            <div class="col-span-1">
                <div class="card">
                    <h2>General Settings</h2>
                    <div class="mt-4">
                        <form action="/modular/admin/settings/save" method="POST" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Site Title</label>
                                <input type="text" name="site_title" value="Modular CMS" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Site Description</label>
                                <textarea name="site_description" rows="3" 
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    Welcome to Modular CMS
                                </textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Timezone</label>
                                <select name="timezone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="Europe/Amsterdam">Europe/Amsterdam</option>
                                    <option value="UTC">UTC</option>
                                </select>
                            </div>

                            <div>
                                <button type="submit" class="btn btn-primary w-full">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Appearance Settings -->
            <div class="col-span-1">
                <div class="card">
                    <h2>Appearance</h2>
                    <div class="mt-4">
                        <form action="/modular/admin/settings/appearance" method="POST" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Theme</label>
                                <select name="theme" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="light">Light</option>
                                    <option value="dark">Dark</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Layout</label>
                                <select name="layout" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="boxed">Boxed</option>
                                    <option value="full-width">Full Width</option>
                                </select>
                            </div>

                            <div>
                                <button type="submit" class="btn btn-primary w-full">
                                    <i class="fas fa-paint-brush"></i> Update Appearance
                                </button>
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
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Password Requirements</label>
                                <div class="mt-2 space-y-2">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="require_uppercase" id="require_uppercase" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="require_uppercase" class="ml-2 block text-sm text-gray-700">
                                            Require uppercase letters
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" name="require_special_chars" id="require_special_chars" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="require_special_chars" class="ml-2 block text-sm text-gray-700">
                                            Require special characters
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Login Attempts</label>
                                <input type="number" name="max_login_attempts" value="3" min="1" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <button type="submit" class="btn btn-primary w-full">
                                    <i class="fas fa-shield-alt"></i> Update Security
                                </button>
                            </div>
                        </form>
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
