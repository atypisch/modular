<?php
/**
 * Atelier admin SPA — server shell (Fase 0 scaffold).
 *
 * Single PHP entry that boots the same core stack as admin/index.php, requires
 * an authenticated session, then serves the Vue SPA (vendored global build).
 * It injects a small bootstrap (window.__MDLR) so the SPA knows the API base,
 * the CSRF token and the current user. The old admin UI is left untouched; the
 * `admin_spa` feature flag (settings table) governs whether /admin reroutes
 * here (wired in a later phase). Rollback = delete this file + assets/spa/.
 */

// Same boot order as admin/index.php.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/data/init.php';
// bootstrap.php does not load http.php; pull in the md_esc_* / URL helpers we use below.
require_once CORE_PATH . '/functions/http.php';

// Require authentication (mirror admin/index.php behaviour).
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    $loginUrl = defined('ADMIN_URL') ? ADMIN_URL . '/auth/login.php' : (rtrim(BASE_PATH ?? '', '/') . '/admin/auth/login.php');
    header('Location: ' . $loginUrl);
    exit;
}

/** Read the admin_spa feature flag from the settings table (default off). */
$adminSpaEnabled = false;
if (isset($GLOBALS['db'])) {
    try {
        $stmt = $GLOBALS['db']->prepare("SELECT option_value FROM settings WHERE option_name = 'admin_spa' LIMIT 1");
        $stmt->execute();
        $val = $stmt->fetchColumn();
        $adminSpaEnabled = in_array((string) $val, ['1', 'true', 'on', 'yes'], true);
    } catch (Throwable $e) {
        $adminSpaEnabled = false;
    }
}

$base       = rtrim(defined('BASE_PATH') ? BASE_PATH : '', '/');
$apiBase    = $base . '/admin/api/index.php/api/v1';
$assetsBase = $base . '/admin/assets/spa';
$vueSrc     = $base . '/admin/assets/vendor/vue/vue.global.prod.js';

$bootUser = [
    'id'    => $_SESSION['user_id'] ?? null,
    'name'  => $_SESSION['user_name'] ?? '',
    'email' => $_SESSION['user_email'] ?? '',
    'role'  => $_SESSION['user_role'] ?? '',
];

// Module-registered admin menu items (add_filter('admin_menu_items', ...)).
// bootstrap.php has already loaded active modules and fired `init`, so the
// filter is populated. Items: { label, url, icon, match, sub:[{label,url}] }.
$moduleMenu = function_exists('apply_filters') ? apply_filters('admin_menu_items', []) : [];
if (!is_array($moduleMenu)) { $moduleMenu = []; }

$bootstrap = [
    'apiBase'    => $apiBase,
    'baseUrl'    => $base,
    'assetsBase' => $assetsBase,
    'csrf'       => md_csrf_token(),
    'user'       => $bootUser,
    'flags'      => ['admin_spa' => $adminSpaEnabled],
    'menu'       => array_values($moduleMenu),
];
$bootJson = json_encode($bootstrap, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

// Never cache the SPA shell HTML itself — assets are ?v=filemtime-versioned,
// so a plain reload always re-renders the shell with fresh asset URLs.
if (!headers_sent()) {
    header('Cache-Control: no-store, must-revalidate');
    header('Pragma: no-cache');
}

/** Cache-busted SPA asset URL: appends ?v=<filemtime> so reloads always pick up edits. */
$spaAsset = function (string $file) use ($assetsBase) {
    $abs = __DIR__ . '/assets/spa/' . $file;
    $v = is_file($abs) ? filemtime($abs) : '1';
    return md_esc_attr($assetsBase . '/' . $file . '?v=' . $v);
};
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Modular — Admin</title>
  <link rel="stylesheet" href="<?= $spaAsset('styles.css') ?>">
  <link rel="stylesheet" href="<?= $spaAsset('fieldbuilder.css') ?>">
  <link rel="stylesheet" href="<?= $spaAsset('content.css') ?>">
  <link rel="stylesheet" href="<?= $spaAsset('field-renderer.css') ?>">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 86 78'%3E%3Cdefs%3E%3ClinearGradient id='m' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0' stop-color='%23221F1A'/%3E%3Cstop offset='1' stop-color='%23C0552F'/%3E%3C/linearGradient%3E%3C/defs%3E%3Cpath d='M1.44,75.812l8.08,-48.479l7.927,15.092l-5.336,33.386l-10.671,0Zm69.516,-74.852l2.744,16.617l-31.252,59.912l-31.404,-60.065l2.744,-16.464l28.66,55.186l28.508,-55.186Zm4.269,26.069l8.08,48.783l-10.671,0l-5.336,-33.539l7.927,-15.245Z' fill='url(%23m)'/%3E%3C/svg%3E">
</head>
<body>
  <div id="app"></div>

  <script>window.__MDLR = <?= $bootJson ?>;</script>

  <!-- Vendored Vue 3 global build (no bundler) -->
  <script src="<?= md_esc_attr($vueSrc) ?>"></script>

  <!-- Chart.js (analytics) — vendored locally, no external deps -->
  <script src="<?= md_esc_attr($base) ?>/admin/assets/vendor/chartjs/chart.umd.min.js"></script>

  <!-- SPA foundation (load order matters: Vue → data → screens → boot) -->
  <script src="<?= $spaAsset('icons.js') ?>"></script>
  <script src="<?= $spaAsset('ui.js') ?>"></script>
  <script src="<?= $spaAsset('code-output.js') ?>"></script>
  <script src="<?= $spaAsset('data.js') ?>"></script>
  <script src="<?= $spaAsset('theme.js') ?>"></script>
  <script src="<?= $spaAsset('mode-help.js') ?>"></script>
  <script src="<?= $spaAsset('cmdk.js') ?>"></script>
  <script src="<?= $spaAsset('shell.js') ?>"></script>

  <!-- field architecture (§3): primitives → registry → recursive renderer -->
  <script src="<?= $spaAsset('primitives.js') ?>"></script>
  <script src="<?= $spaAsset('field-registry.js') ?>"></script>
  <script src="<?= $spaAsset('field-settings.js') ?>"></script>
  <script src="<?= $spaAsset('field-renderer.js') ?>"></script>
  <script src="<?= $spaAsset('store.js') ?>"></script>

  <!-- screens (registered as built; unbuilt routes fall back to ScreenStub) -->
  <script src="<?= $spaAsset('screens-basic.js') ?>"></script>
  <script src="<?= $spaAsset('screen-dashboard.js') ?>"></script>
  <script src="<?= $spaAsset('screen-posttypes.js') ?>"></script>
  <script src="<?= $spaAsset('screen-taxonomies.js') ?>"></script>
  <script src="<?= $spaAsset('screen-forms.js') ?>"></script>
  <script src="<?= $spaAsset('screen-actions.js') ?>"></script>
  <script src="<?= $spaAsset('screen-modules.js') ?>"></script>
  <script src="<?= $spaAsset('screen-roles.js') ?>"></script>
  <script src="<?= $spaAsset('screen-profile.js') ?>"></script>
  <script src="<?= $spaAsset('screen-permalinks.js') ?>"></script>
  <script src="<?= $spaAsset('screen-menus.js') ?>"></script>
  <script src="<?= $spaAsset('screen-notifications.js') ?>"></script>
  <script src="<?= $spaAsset('screen-exportimport.js') ?>"></script>
  <script src="<?= $spaAsset('screen-library.js') ?>"></script>
  <script src="<?= $spaAsset('screen-design.js') ?>"></script>
  <script src="<?= $spaAsset('screen-module-page.js') ?>"></script>
  <script src="<?= $spaAsset('screen-system-update.js') ?>"></script>
  <script src="<?= $spaAsset('screen-insights.js') ?>"></script>
  <script src="<?= $spaAsset('screen-analytics.js') ?>"></script>
  <script src="<?= $spaAsset('screen-field-demo.js') ?>"></script>
  <script src="<?= $spaAsset('visual-editor.js') ?>"></script>
  <script src="<?= $spaAsset('screen-fieldbuilder.js') ?>"></script>
  <script src="<?= $spaAsset('screen-components.js') ?>"></script>
  <script src="<?= $spaAsset('screen-binding.js') ?>"></script>
  <script src="<?= $spaAsset('screen-content.js') ?>"></script>
  <script src="<?= $spaAsset('screen-content-editor.js') ?>"></script>
  <script src="<?= $spaAsset('screen-media.js') ?>"></script>

  <!-- boot -->
  <script src="<?= $spaAsset('app.js') ?>"></script>
</body>
</html>
