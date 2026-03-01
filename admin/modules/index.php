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
require_once CORE_PATH . '/classes/Update/VersionService.php';
require_once CORE_PATH . '/classes/Update/QueueService.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
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

/**
 * Recursively remove a module directory (used when removing a module).
 */
function _modules_remove_directory(string $dir): bool {
    if (!is_dir($dir)) {
        return true;
    }
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            _modules_remove_directory($path);
        } else {
            @unlink($path);
        }
    }
    return @rmdir($dir);
}

// Handle activation/deactivation and update actions
$message = $message ?? $_SESSION['modules_message'] ?? '';
$messageType = $messageType ?? $_SESSION['modules_message_type'] ?? '';
if (isset($_SESSION['modules_message'])) {
    unset($_SESSION['modules_message'], $_SESSION['modules_message_type']);
}

if ($modulesTableExists && $moduleManager && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!function_exists('csrf_verify') || !csrf_verify()) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } else {
        $bulk = $_POST['bulk_action'] ?? '';
        $selected = $_POST['module_slugs'] ?? [];
        if (!is_array($selected)) $selected = [];

        if (isset($_POST['action']) && isset($_POST['module_slug'])) {
            $slug = $_POST['module_slug'];
            try {
                if ($_POST['action'] === 'activate') {
                    $moduleManager->activateModule($slug);
                    $message = "Module '{$slug}' activated.";
                    $messageType = 'success';
                } elseif ($_POST['action'] === 'deactivate') {
                    $moduleManager->deactivateModule($slug);
                    $message = "Module '{$slug}' deactivated.";
                    $messageType = 'success';
                } elseif ($_POST['action'] === 'update_now') {
                    if (function_exists('is_admin') && !is_admin()) {
                        $message = 'Only administrators can update modules.';
                        $messageType = 'error';
                    } else {
                        require_once CORE_PATH . '/classes/Update/ModuleUpdateService.php';
                        $service = new \Modular\Update\ModuleUpdateService($db);
                        $result = $service->run($slug);
                        if (!empty($result['success'])) {
                            $to = $result['version_to'] ?? 'latest';
                            $message = "Module '{$slug}' updated to version {$to}.";
                            $messageType = 'success';
                        } else {
                            $err = $result['error'] ?? 'Unknown error';
                            $message = "Failed to update module '{$slug}': " . $err;
                            $messageType = 'error';
                        }
                    }
                } elseif ($_POST['action'] === 'enable_auto_update' || $_POST['action'] === 'disable_auto_update') {
                    $enable = $_POST['action'] === 'enable_auto_update';
                    $stmt = $db->prepare("SELECT settings FROM modules WHERE slug = ? LIMIT 1");
                    $stmt->execute([$slug]);
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $settings = [];
                    if ($row && !empty($row['settings'])) {
                        $decoded = json_decode($row['settings'], true);
                        if (is_array($decoded)) {
                            $settings = $decoded;
                        }
                    }
                    $settings['auto_update'] = $enable ? 1 : 0;
                    $stmt = $db->prepare("UPDATE modules SET settings = ?, updated_at = NOW() WHERE slug = ?");
                    $stmt->execute([json_encode($settings), $slug]);
                    if ($enable) {
                        $message = "Auto-updates enabled for module '{$slug}'.";
                    } else {
                        $message = "Auto-updates disabled for module '{$slug}'.";
                    }
                    $messageType = 'success';
                } elseif ($_POST['action'] === 'remove_module') {
                    $mod = $moduleManager->getModule($slug);
                    if (!$mod) {
                        $message = "Module '{$slug}' not found.";
                        $messageType = 'error';
                    } else {
                        $modulePath = $mod->path ?? '';
                        $modulesBase = defined('PUBLIC_PATH') ? (PUBLIC_PATH . '/modules') : (dirname(__DIR__) . '/public/modules');
                        $realBase = realpath($modulesBase);
                        $realPath = $modulePath ? realpath($modulePath) : '';
                        if ($realPath && $realBase && strpos($realPath . DIRECTORY_SEPARATOR, $realBase . DIRECTORY_SEPARATOR) === 0) {
                            if ($moduleManager->isModuleActive($slug)) {
                                $moduleManager->deactivateModule($slug);
                            }
                            $stmt = $db->prepare("DELETE FROM modules WHERE slug = ?");
                            $stmt->execute([$slug]);
                            _modules_remove_directory($modulePath);
                            $_SESSION['modules_message'] = "Module '{$slug}' has been removed.";
                            $_SESSION['modules_message_type'] = 'success';
                            header('Location: ' . (isset($_SERVER['REQUEST_URI']) ? preg_replace('/[?&].*$/', '', $_SERVER['REQUEST_URI']) : '/modular/admin/modules/'));
                            exit;
                        } else {
                            $message = "Cannot remove module: path is outside modules directory.";
                            $messageType = 'error';
                        }
                    }
                }
            } catch (\Exception $e) {
                $message = $e->getMessage();
                $messageType = 'error';
            }
        } elseif ($bulk === 'deactivate' && !empty($selected)) {
            foreach ($selected as $slug) {
                if ($moduleManager && $moduleManager->isModuleActive($slug)) {
                    try {
                        $moduleManager->deactivateModule($slug);
                    } catch (\Throwable $e) {
                        $message .= ' ' . $slug . ': ' . $e->getMessage();
                    }
                }
            }
            if ($message === '') {
                $message = count($selected) . ' module(s) deactivated.';
                $messageType = 'success';
            } else {
                $messageType = 'error';
            }
        } elseif ($bulk === 'update_selected' && !empty($selected)) {
            if (function_exists('is_admin') && !is_admin()) {
                $message = 'Only administrators can update modules.';
                $messageType = 'error';
            } else {
                $queue = new \Modular\Update\QueueService($db);
                foreach ($selected as $slug) {
                    $queue->enqueue('module', $slug, 'update');
                }

                $enqueued = count($selected);
                $processed = 0;
                require_once CORE_PATH . '/classes/Update/ModuleUpdateService.php';
                $service = new \Modular\Update\ModuleUpdateService($db);

                if ($queue->acquireLock()) {
                    try {
                        while ($job = $queue->getNextPending()) {
                            $queue->markRunning((int) $job['id']);
                            if ($job['type'] === 'module' && $job['action'] === 'update') {
                                $result = $service->run($job['target']);
                                if (!empty($result['success'])) {
                                    $queue->markCompleted((int) $job['id'], json_encode($result));
                                } else {
                                    $queue->markFailed((int) $job['id'], (string) ($result['error'] ?? 'Unknown error'));
                                }
                            } else {
                                $queue->markFailed((int) $job['id'], 'Unsupported job type/action.');
                            }
                            $processed++;
                        }
                    } finally {
                        $queue->releaseLock();
                    }
                    $message = $enqueued . ' module(s) queued and ' . $processed . ' job(s) processed.';
                    $messageType = 'success';
                } else {
                    $message = $enqueued . ' module(s) queued for update. Queue is currently being processed by another worker.';
                    $messageType = 'success';
                }
            }
        } elseif ($bulk === 'remove_selected' && !empty($selected)) {
            $message = 'Remove is not yet implemented (safety). Deactivate modules instead.';
            $messageType = 'error';
        }
    }
}

// Get all modules
$modules = $moduleManager ? $moduleManager->getModules() : [];
$activeModules = $moduleManager ? $moduleManager->getActiveModules() : [];

// Load auto-update settings per module
$autoUpdateMap = [];
if ($modulesTableExists) {
    try {
        $stmt = $db->query("SELECT slug, settings FROM modules");
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $s = [];
            if (!empty($row['settings'])) {
                $decoded = json_decode($row['settings'], true);
                if (is_array($decoded)) {
                    $s = $decoded;
                }
            }
            $autoUpdateMap[$row['slug']] = !empty($s['auto_update']);
        }
    } catch (\Throwable $e) {
        // ignore, UI will just show auto-updates as disabled
    }
}

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
                <p><a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin'; ?>/install-modules-table.php" class="btn btn-primary">Install Modules Table</a></p>
            </div>
        </div>
    <?php elseif (!$moduleManager): ?>
        <div class="card">
            <div class="card-body">
                <p class="error">Error initializing module manager.</p>
            </div>
        </div>
    <?php else: ?>
    <form method="post">
        <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>

        <div class="card">
            <div class="card-header">
                <h2>Installed Modules</h2>
            </div>
            <div class="card-body p-0">
                <div class="p-3 modules-bulk-bar">
                    <label class="mb-0 modules-bulk-label">Bulk action:</label>
                    <select name="bulk_action" class="form-control form-control-sm modules-bulk-select">
                        <option value="">— Select —</option>
                        <option value="update_selected">Update selected</option>
                        <option value="deactivate">Deactivate selected</option>
                        <option value="remove_selected">Remove selected</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary modules-bulk-apply">Apply</button>
                </div>

                <?php if (empty($modules)): ?>
                    <div class="p-3">
                        <p>No modules found. Place modules in the <code>/public/modules/</code> directory.</p>
                        <p>Each module should have a <code>module.php</code> file with a header like:</p>
                        <pre><code>/**
 * Module Name: My Module
 * Version: 1.0.0
 * Description: Module description
 * Author: Your Name
 */</code></pre>
                    </div>
                <?php else: ?>
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" id="select-all" aria-label="Select all"></th>
                                <th>Module</th>
                                <th style="width: 200px;">Auto-updates</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $module): ?>
                                <?php
                                $isActive = $moduleManager ? $moduleManager->isModuleActive($module->slug) : false;
                                $modulePath = $module->path ?? '';
                                $installedVersion = $module->version ?? '0.0.0';
                                $latestVersion = null;
                                if ($modulePath && is_file($modulePath . '/module.json')) {
                                    $meta = @json_decode(@file_get_contents($modulePath . '/module.json'), true);
                                    $repo = is_array($meta) && !empty($meta['github_repo']) ? (string) $meta['github_repo'] : '';
                                    if ($repo !== '') {
                                        $vs = new \Modular\Update\VersionService(null, $repo);
                                        $rel = $vs->getLatestRelease();
                                        if ($rel !== null && !empty($rel['version'])) {
                                            $latestVersion = (string) $rel['version'];
                                        }
                                    }
                                }
                                $updateAvailable = $latestVersion && \Modular\Update\VersionService::compareSemver($installedVersion, $latestVersion) < 0;
                                $autoOn = !empty($autoUpdateMap[$module->slug]);
                                ?>
                                <tr class="module-row <?php echo $isActive ? 'module-row-active' : ''; ?>">
                                    <td class="align-top">
                                        <input type="checkbox" name="module_slugs[]" value="<?php echo htmlspecialchars($module->slug); ?>" class="module-cb">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($module->name); ?></strong>
                                        <?php if ($isActive): ?>
                                            <span class="badge badge-success ml-2">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary ml-2">Inactive</span>
                                        <?php endif; ?>
                                        <div class="mt-2 mb-1">
                                            <form method="POST" style="display:inline;">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="module_slug" value="<?php echo htmlspecialchars($module->slug); ?>">
                                                <?php if ($isActive): ?>
                                                    <button type="submit" name="action" value="deactivate" class="btn btn-sm btn-secondary module-action-btn">Deactivate</button>
                                                <?php else: ?>
                                                    <button type="submit" name="action" value="activate" class="btn btn-sm btn-primary module-action-btn">Activate</button>
                                                <?php endif; ?>
                                            </form>
                                            <?php if ($updateAvailable): ?>
                                                <form method="POST" style="display:inline; margin-left: .5rem;">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="module_slug" value="<?php echo htmlspecialchars($module->slug); ?>">
                                                    <button type="submit" name="action" value="update_now" class="btn btn-sm btn-outline-primary module-action-btn">Update now</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" style="display:inline; margin-left: .5rem;" class="module-remove-form" data-confirm-message="<?php echo htmlspecialchars(json_encode('Weet u zeker dat u de module "' . $module->name . '" wilt verwijderen? Alle bestanden worden permanent verwijderd.', JSON_UNESCAPED_UNICODE)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="module_slug" value="<?php echo htmlspecialchars($module->slug); ?>">
                                                <button type="submit" name="action" value="remove_module" class="btn btn-sm btn-danger module-action-btn">Remove</button>
                                            </form>
                                        </div>
                                        <p class="mt-2 mb-1"><?php echo htmlspecialchars($module->description); ?></p>
                                        <div class="text-muted small">
                                            Version <?php echo htmlspecialchars($installedVersion); ?>
                                            <?php if ($latestVersion !== null): ?>
                                                | Latest: <?php echo htmlspecialchars($latestVersion); ?>
                                            <?php endif; ?>
                                            <?php if ($module->author): ?>
                                                | By
                                                <?php if ($module->author_url): ?>
                                                    <a href="<?php echo htmlspecialchars($module->author_url); ?>" target="_blank"><?php echo htmlspecialchars($module->author); ?></a>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($module->author); ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="align-top">
                                        <div class="text-right">
                                            <form method="POST" style="display:inline;">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="module_slug" value="<?php echo htmlspecialchars($module->slug); ?>">
                                                <?php if ($autoOn): ?>
                                                    <button type="submit" name="action" value="disable_auto_update" class="btn btn-sm btn-success module-action-btn">Disable auto-updates</button>
                                                <?php else: ?>
                                                    <button type="submit" name="action" value="enable_auto_update" class="btn btn-sm btn-secondary module-action-btn">Enable auto-updates</button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php if ($updateAvailable): ?>
                                    <tr class="table-warning">
                                        <td></td>
                                        <td colspan="2">
                                            <div class="small">
                                                There is a new version of <strong><?php echo htmlspecialchars($module->name); ?></strong> available.
                                                <?php if ($latestVersion !== null): ?>
                                                    <span>View version <?php echo htmlspecialchars($latestVersion); ?> details or</span>
                                                <?php endif; ?>
                                                <form method="POST" style="display:inline;">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="module_slug" value="<?php echo htmlspecialchars($module->slug); ?>">
                                                    <button type="submit" name="action" value="update_now" class="btn btn-link btn-link-sm p-0 align-baseline">update now</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </form>

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

// Optional: add a top-level admin menu item like \"Community\"
add_filter(\'admin_menu_items\', function ($items) {
    $base = (defined(\'ADMIN_URL\') ? ADMIN_URL : \'\') . \'/module-page.php?module=my-module&page=\';
    $items[] = [
        \'label\' => \'My Module\',
        \'url\'   => $base . \'settings\',
        \'icon\'  => \'fas fa-puzzle-piece\',
        \'match\' => \'my-module\',
        \'sub\'   => [
            [\'label\' => \'Settings\', \'url\' => $base . \'settings\'],
        ],
    ];
    return $items;
});
'); ?></code></pre>

            <p>Then create admin pages under <code>/public/modules/my-module/admin/</code>, for example:</p>
            <pre><code>public/modules/my-module/admin/settings.php</code></pre>
            <p>These will be loaded via <code>/modular/admin/module-page.php?module=my-module&amp;page=settings</code> and show up as their own menu item in the sidebar.</p>
            
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

<script>
document.getElementById('select-all')?.addEventListener('change', function() {
    document.querySelectorAll('.module-cb').forEach(function(cb) { cb.checked = this.checked; }, this);
});
document.querySelectorAll('.module-remove-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        var msg = this.getAttribute('data-confirm-message');
        if (!msg) return;
        e.preventDefault();
        var f = this;
        (window.ModularModal && window.ModularModal.confirm(JSON.parse(msg)) || Promise.resolve(false)).then(function(yes) {
            if (yes) f.submit();
        });
    });
});
</script>

<style>
.module-row-active > td {
    background-color: #f0f6ff;
}
.module-action-btn {
    font-size: 0.8rem;
}
.modules-bulk-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    border-bottom: 1px solid #eee;
    margin-bottom: 20px;
}
.modules-bulk-label {
    font-size: 0.9rem;
    color: #6b7280;
    margin-right: 4px;
}
.modules-bulk-bar .modules-bulk-select {
    flex: 0 0 auto;
    width: auto !important;
    min-width: 160px;
    max-width: 220px;
    border-radius: 9999px;
    border: 1px solid #d1d5db;
    padding: 3px 12px;
    background-color: #ffffff;
}
.modules-bulk-bar .modules-bulk-select:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    border-color: #2563eb;
}
.modules-bulk-apply {
    flex: 0 0 auto;
    border-radius: 9999px;
    padding-inline: 14px;
}
</style>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
