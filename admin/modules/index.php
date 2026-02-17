<?php
/**
 * Modules Management Page
 * Manage and activate/deactivate modules (WordPress-style plugins)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';

if (!is_logged_in()) {
    header('Location: /modular/admin/auth/login.php');
    exit;
}

$page_title = 'Modules';
$modules_active = true;

$db = $GLOBALS['db'];

// Check if modules table exists
$modulesTableExists = false;
try {
    $stmt = $db->query("SHOW TABLES LIKE 'modules'");
    $modulesTableExists = $stmt->rowCount() > 0;
} catch (\PDOException $e) {
    $modulesTableExists = false;
}

if (!$modulesTableExists) {
    $message = "The modules table does not exist. Please <a href='/modular/admin/install-modules-table.php'>install it first</a>.";
    $messageType = 'error';
}

$moduleManager = null;
if ($modulesTableExists) {
    try {
        $moduleManager = $GLOBALS['module_manager'] ?? new \Modular\ModuleManager($db);
    } catch (\Exception $e) {
        $message = "Error initializing module manager: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle activation/deactivation
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!function_exists('csrf_verify') || !csrf_verify()) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } elseif (isset($_POST['action']) && isset($_POST['module_slug'])) {
        $slug = $_POST['module_slug'];
        
        try {
            if ($_POST['action'] === 'activate') {
                $moduleManager->activateModule($slug);
                $message = "Module '{$slug}' activated successfully.";
                $messageType = 'success';
            } elseif ($_POST['action'] === 'deactivate') {
                $moduleManager->deactivateModule($slug);
                $message = "Module '{$slug}' deactivated successfully.";
                $messageType = 'success';
            }
        } catch (\Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Get all modules
$modules = $moduleManager ? $moduleManager->getModules() : [];
$activeModules = $moduleManager ? $moduleManager->getActiveModules() : [];

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Modules</h1>
        <p>Manage and activate modules to extend your CMS functionality.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $messageType === 'error' ? $message : htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (!$modulesTableExists): ?>
        <div class="card">
            <div class="card-body">
                <p class="error">The modules table is not installed. Please install it first.</p>
                <p><a href="/modular/admin/install-modules-table.php" class="btn btn-primary">Install Modules Table</a></p>
            </div>
        </div>
    <?php elseif (!$moduleManager): ?>
        <div class="card">
            <div class="card-body">
                <p class="error">Error initializing module manager.</p>
            </div>
        </div>
    <?php else: ?>
    <div class="card">
        <div class="card-header">
            <h2>Installed Modules</h2>
        </div>
        <div class="card-body">
            <?php if (empty($modules)): ?>
                <p>No modules found. Place modules in the <code>/public/modules/</code> directory.</p>
                <p>Each module should have a <code>module.php</code> file with a header like:</p>
                <pre><code>/**
 * Module Name: My Module
 * Version: 1.0.0
 * Description: Module description
 * Author: Your Name
 */</code></pre>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Description</th>
                            <th>Version</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $module): ?>
                            <?php
                            $isActive = $moduleManager ? $moduleManager->isModuleActive($module->slug) : false;
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($module->name); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($module->slug); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($module->description); ?></td>
                                <td><?php echo htmlspecialchars($module->version); ?></td>
                                <td>
                                    <?php if ($module->author_url): ?>
                                        <a href="<?php echo htmlspecialchars($module->author_url); ?>" target="_blank">
                                            <?php echo htmlspecialchars($module->author); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($module->author); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isActive): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="module_slug" value="<?php echo htmlspecialchars($module->slug); ?>">
                                        <?php if ($isActive): ?>
                                            <button type="submit" name="action" value="deactivate" class="btn btn-sm btn-warning">
                                                Deactivate
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="activate" class="btn btn-sm btn-success">
                                                Activate
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h2>Module Development</h2>
        </div>
        <div class="card-body">
            <h3>Creating a Module</h3>
            <p>To create a new module:</p>
            <ol>
                <li>Create a new folder in <code>/public/modules/</code> (e.g., <code>/public/modules/my-module/</code>)</li>
                <li>Create a <code>module.php</code> file with a header:</li>
            </ol>
            <pre><code><?php echo htmlspecialchars('<?php
/**
 * Module Name: My Module
 * Version: 1.0.0
 * Description: This is my custom module
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: my-module
 * Requires PHP: 8.1
 * Requires Modular: 1.0
 */

// Your module code here
add_action(\'init\', function() {
    // Module initialization
});

add_filter(\'the_title\', function($title) {
    return $title . \' - Modified\';
});'); ?></code></pre>
            
            <h3>Available Hooks</h3>
            <p>Modules can use the following hooks:</p>
            <ul>
                <li><strong>Actions:</strong> <code>init</code>, <code>module_activate</code>, <code>module_deactivate</code>, <code>admin_init</code>, <code>admin_menu</code></li>
                <li><strong>Filters:</strong> <code>the_title</code>, <code>the_content</code>, <code>admin_menu_items</code></li>
            </ul>
            
            <h3>Example Module</h3>
            <p>See <code>/public/modules/</code> for example modules.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
