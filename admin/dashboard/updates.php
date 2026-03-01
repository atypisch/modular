<?php
/**
 * Dashboard – Updates
 * Current version, latest from GitHub, changelog, Update button
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('ADMIN_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
if (is_file(CORE_PATH . '/config/update.php')) {
    require_once CORE_PATH . '/config/update.php';
}
require_once ADMIN_PATH . '/bootstrap.php';
require_once CORE_PATH . '/functions/auth.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

$page_title = 'Updates';
$dashboard_active = true;

$db = $GLOBALS['db'];

require_once CORE_PATH . '/classes/Update/VersionService.php';
require_once CORE_PATH . '/classes/Update/MaintenanceModeService.php';
require_once CORE_PATH . '/classes/Update/UpdateLog.php';
require_once CORE_PATH . '/classes/Update/ThemeUpdateService.php';
require_once CORE_PATH . '/classes/Update/ModuleUpdateService.php';

$versionService = new \Modular\Update\VersionService();
$maintenance = new \Modular\Update\MaintenanceModeService();

$current = $versionService->getCurrentVersion();
$latest = $versionService->getLatestRelease();
$latestError = $latest === null ? $versionService->getLastError() : null;
$updateAvailable = $latest !== null && \Modular\Update\VersionService::compareSemver($current['version'], $latest['version']) < 0;
$isMaintenance = $maintenance->isEnabled();

$canUpdate = function_exists('is_admin') && is_admin();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_core') {
    if (!function_exists('csrf_verify') || !csrf_verify()) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } elseif (!$canUpdate) {
        $message = 'Only administrators can update the core.';
        $messageType = 'error';
    } elseif ($latest === null) {
        $message = $latestError ?: 'Could not fetch latest release.';
        $messageType = 'error';
    } else {
        $rateLimit = defined('UPDATE_RATE_LIMIT_PER_HOUR') ? (int) UPDATE_RATE_LIMIT_PER_HOUR : 3;
        $recentCount = 0;
        try {
            $stmt = $db->query("SHOW TABLES LIKE 'system_updates'");
            if ($stmt->rowCount() > 0) {
                $stmt = $db->query("SELECT COUNT(*) FROM system_updates WHERE started_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                $recentCount = (int) $stmt->fetchColumn();
            }
        } catch (\Throwable $e) {
            $recentCount = 0;
        }
        if ($recentCount >= $rateLimit) {
            $message = 'Rate limit reached. Try again later.';
            $messageType = 'error';
        } else {
            require_once CORE_PATH . '/classes/Update/UpdateService.php';
            $updateService = new \Modular\Update\UpdateService($db);
            $userId = $_SESSION['user_id'] ?? null;
            $result = $updateService->runUpdate($latest['version'], $userId);
            if ($result['success']) {
                $message = 'Update completed successfully. You are now on version ' . ($result['version_to'] ?? $latest['version']) . '.';
                $messageType = 'success';
                $current = $versionService->getCurrentVersion();
                $updateAvailable = false;
            } else {
                $message = 'Update failed: ' . htmlspecialchars($result['error'] ?? 'Unknown error');
                $messageType = 'error';
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_theme' && isset($_POST['theme_slug'])) {
    if (!function_exists('csrf_verify') || !csrf_verify()) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } elseif (!function_exists('is_admin') || !is_admin()) {
        $message = 'Only administrators can update themes.';
        $messageType = 'error';
    } else {
        $slug = trim($_POST['theme_slug']);
        $themeService = new \Modular\Update\ThemeUpdateService();
        $result = $themeService->run($slug);
        if (!empty($result['success'])) {
            $to = $result['version_to'] ?? 'latest';
            $message = "Theme '{$slug}' updated to version {$to}.";
            $messageType = 'success';
        } else {
            $err = $result['error'] ?? 'Unknown error';
            $message = "Failed to update theme '{$slug}': " . $err;
            $messageType = 'error';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_module' && isset($_POST['module_slug'])) {
    if (!function_exists('csrf_verify') || !csrf_verify()) {
        $message = 'Invalid request.';
        $messageType = 'error';
    } elseif (!function_exists('is_admin') || !is_admin()) {
        $message = 'Only administrators can update modules.';
        $messageType = 'error';
    } else {
        $slug = trim($_POST['module_slug']);
        $moduleService = new \Modular\Update\ModuleUpdateService($db);
        $result = $moduleService->run($slug);
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
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Updates</h1>
        <p>Manage core and module updates.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($isMaintenance): ?>
        <div class="alert alert-warning">
            <strong>Maintenance mode</strong> is currently enabled. The site may be in read-only state.
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h2>Core (Modular CMS)</h2>
        </div>
        <div class="card-body">
            <table class="table">
                <tr>
                    <th style="width: 180px;">Current version</th>
                    <td><strong><?php echo htmlspecialchars($current['version']); ?></strong></td>
                </tr>
                <tr>
                    <th>Latest version</th>
                    <td>
                        <?php if ($latest !== null): ?>
                            <strong><?php echo htmlspecialchars($latest['version']); ?></strong>
                            <?php if (!empty($latest['published_at'])): ?>
                                <span class="text-muted">(<?php echo date('M j, Y', strtotime($latest['published_at'])); ?>)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">
                                <?php echo htmlspecialchars($latestError ?: 'Could not fetch latest release. Check UPDATE_CORE_GITHUB_REPO in config.'); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <?php if ($updateAvailable): ?>
                            <span class="badge badge-warning">Update available</span>
                        <?php else: ?>
                            <span class="badge badge-success">Up to date</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php if ($latest !== null && !empty($latest['body'])): ?>
                <h3 class="h5 mt-3">Changelog (latest)</h3>
                <div class="bg-light p-3 rounded mb-3" style="max-height: 200px; overflow-y: auto;">
                    <?php echo nl2br(htmlspecialchars($latest['body'])); ?>
                </div>
            <?php endif; ?>

            <?php if ($canUpdate && $updateAvailable): ?>
                <form method="post">
                    <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>
                    <input type="hidden" name="action" value="update_core">
                    <button type="submit" class="btn btn-primary">Update to latest version</button>
                </form>
            <?php elseif ($updateAvailable && !$canUpdate): ?>
                <p class="text-muted">Only administrators can run core updates.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // Themes + Modules: build data for collapsible section
    $themesDir = defined('PUBLIC_PATH') ? PUBLIC_PATH . '/themes' : (defined('MODULAR_PATH') ? MODULAR_PATH . '/public/themes' : null);
    $modulesDir = defined('PUBLIC_PATH') ? PUBLIC_PATH . '/modules' : (defined('MODULAR_PATH') ? MODULAR_PATH . '/public/modules' : null);
    $themes = [];
    $modules = [];
    if ($themesDir && is_dir($themesDir)) {
        $themeDirs = array_filter(glob($themesDir . '/*') ?: [], 'is_dir');
        foreach ($themeDirs as $dir) {
            $slug = basename($dir);
            $metaFile = $dir . '/theme.json';
            $meta = is_file($metaFile) ? @json_decode(@file_get_contents($metaFile), true) : null;
            $installedVersion = is_array($meta) && !empty($meta['version']) ? (string) $meta['version'] : '0.0.0';
            $name = is_array($meta) && !empty($meta['name']) ? (string) $meta['name'] : ucfirst($slug);
            $repo = is_array($meta) && !empty($meta['github_repo']) ? (string) $meta['github_repo'] : '';
            $latestThemeVersion = null;
            if ($repo !== '') {
                $vsTheme = new \Modular\Update\VersionService(null, $repo);
                $relTheme = $vsTheme->getLatestRelease();
                if ($relTheme !== null && !empty($relTheme['version'])) {
                    $latestThemeVersion = (string) $relTheme['version'];
                }
            }
            $updateThemeAvailable = $latestThemeVersion && \Modular\Update\VersionService::compareSemver($installedVersion, $latestThemeVersion) < 0;
            $themes[] = [
                'slug' => $slug,
                'name' => $name,
                'installed' => $installedVersion,
                'latest' => $latestThemeVersion,
                'repo' => $repo,
                'update_available' => $updateThemeAvailable,
            ];
        }
    }
    if ($modulesDir && is_dir($modulesDir)) {
        $moduleDirs = array_filter(glob($modulesDir . '/*') ?: [], 'is_dir');
        foreach ($moduleDirs as $dir) {
            $slug = basename($dir);
            $metaFile = $dir . '/module.json';
            if (!is_file($metaFile)) {
                continue;
            }
            $meta = @json_decode((string) @file_get_contents($metaFile), true);
            if (!is_array($meta)) {
                continue;
            }
            $installedVersion = !empty($meta['version']) ? (string) $meta['version'] : '0.0.0';
            $name = !empty($meta['name']) ? (string) $meta['name'] : ucfirst($slug);
            $repo = !empty($meta['github_repo']) ? trim((string) $meta['github_repo']) : '';
            $latestModuleVersion = null;
            if ($repo !== '') {
                $vsMod = new \Modular\Update\VersionService(null, $repo);
                $relMod = $vsMod->getLatestRelease();
                if ($relMod !== null && !empty($relMod['version'])) {
                    $latestModuleVersion = (string) $relMod['version'];
                }
            }
            $updateModuleAvailable = $latestModuleVersion && \Modular\Update\VersionService::compareSemver($installedVersion, $latestModuleVersion) < 0;
            $modules[] = [
                'slug' => $slug,
                'name' => $name,
                'installed' => $installedVersion,
                'latest' => $latestModuleVersion,
                'repo' => $repo,
                'update_available' => $updateModuleAvailable,
            ];
        }
    }
    $themesCount = count($themes);
    $modulesCount = count($modules);
    $hasThemesOrModules = $themesCount > 0 || $modulesCount > 0;
    ?>

    <?php if ($hasThemesOrModules): ?>
    <div class="card mb-4">
        <details class="updates-themes-modules-details">
            <summary class="card-header updates-collapsible-summary">
                Themes and modules (<?php echo $themesCount; ?> theme<?php echo $themesCount !== 1 ? 's' : ''; ?>, <?php echo $modulesCount; ?> module<?php echo $modulesCount !== 1 ? 's' : ''; ?>)
            </summary>
            <div class="card-body">
                <?php if (!empty($themes)): ?>
                <h3 class="h5 mb-3">Themes</h3>
                <p class="text-muted small">Update themes from GitHub when <code>theme.json</code> has <code>github_repo</code> set. Manage themes in <code>public/themes/</code>.</p>
                <table class="table table-hover mb-4">
                    <thead>
                        <tr>
                            <th>Theme</th>
                            <th>Installed</th>
                            <th>Latest</th>
                            <th>Update?</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($themes as $t): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($t['name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($t['slug']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($t['installed']); ?></td>
                                <td><?php echo $t['latest'] !== null ? htmlspecialchars($t['latest']) : '—'; ?></td>
                                <td><?php echo $t['update_available'] ? '<span class="badge badge-warning">Yes</span>' : '—'; ?></td>
                                <td>
                                    <?php if ($t['update_available']): ?>
                                        <form method="post" class="d-inline">
                                            <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>
                                            <input type="hidden" name="action" value="update_theme">
                                            <input type="hidden" name="theme_slug" value="<?php echo htmlspecialchars($t['slug']); ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <?php if (!empty($modules)): ?>
                <h3 class="h5 mb-3">Modules</h3>
                <p class="text-muted small">Update modules from GitHub when <code>module.json</code> has <code>github_repo</code> set. Manage and activate modules via <a href="<?php echo defined('ADMIN_URL') ? ADMIN_URL : ''; ?>/modules">Modules</a>.</p>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Installed</th>
                            <th>Latest</th>
                            <th>Update?</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $m): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($m['name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($m['slug']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($m['installed']); ?></td>
                                <td><?php echo $m['latest'] !== null ? htmlspecialchars($m['latest']) : '—'; ?></td>
                                <td><?php echo $m['update_available'] ? '<span class="badge badge-warning">Yes</span>' : '—'; ?></td>
                                <td>
                                    <?php if ($m['update_available']): ?>
                                        <form method="post" class="d-inline">
                                            <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>
                                            <input type="hidden" name="action" value="update_module">
                                            <input type="hidden" name="module_slug" value="<?php echo htmlspecialchars($m['slug']); ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </details>
    </div>
    <?php endif; ?>


    <div class="card">
        <div class="card-header">
            <h2>Update log</h2>
        </div>
        <div class="card-body">
            <p class="text-muted">All update actions are logged to <code>core/storage/logs/updates.log</code>.</p>
            <?php
            $updatesTableExists = false;
            try {
                $stmt = $db->query("SHOW TABLES LIKE 'system_updates'");
                $updatesTableExists = $stmt->rowCount() > 0;
            } catch (\Throwable $e) {}
            if ($updatesTableExists):
                $stmt = $db->query("SELECT * FROM system_updates ORDER BY id DESC LIMIT 10");
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            ?>
                <?php if (empty($rows)): ?>
                    <p class="text-muted">No update history yet.</p>
                <?php else: ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>To</th>
                                <th>Status</th>
                                <th>Started</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['version_from']); ?></td>
                                    <td><?php echo htmlspecialchars($r['version_to']); ?></td>
                                    <td><span class="badge badge-<?php echo $r['status'] === 'completed' ? 'success' : ($r['status'] === 'failed' ? 'danger' : 'secondary'); ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($r['started_at']); ?></td>
                                    <td><?php echo htmlspecialchars($r['notes'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
