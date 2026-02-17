<?php
/**
 * Settings Appearance - Japanese Style
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

$page_title = 'Appearance Settings';
$settings_active = true;

$db = $GLOBALS['db'];

// Load settings
$settings = [];
try {
    $stmt = $db->query("SELECT option_name, option_value FROM settings WHERE autoload = 1");
    $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        $settings[$row['option_name']] = $row['option_value'];
    }
} catch (Exception $e) {
    error_log("Error loading settings: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $_SESSION['error'] = 'Invalid request. Please try again.';
        header('Location: /modular/admin/settings/appearance.php');
        exit;
    }
    $activeTheme = filter_input(INPUT_POST, 'active_theme', FILTER_SANITIZE_STRING) ?? 'default';
    $headlessMode = isset($_POST['headless_mode']) ? 1 : 0;
    
    try {
        $updateStmt = $db->prepare("
            INSERT INTO settings (option_name, option_value, autoload, created_at, updated_at)
            VALUES (?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE option_value = ?, updated_at = NOW()
        ");
        
        $updateStmt->execute(['active_theme', $activeTheme, $activeTheme]);
        $updateStmt->execute(['headless_mode', $headlessMode, $headlessMode]);
        
        $_SESSION['success'] = 'Appearance settings saved successfully.';
        header('Location: /modular/admin/settings/appearance.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error saving settings: ' . $e->getMessage();
    }
}

// Get available themes
$themes = [];
$themesDir = PUBLIC_PATH . '/themes';
if (is_dir($themesDir)) {
    $dirs = array_filter(glob($themesDir . '/*'), 'is_dir');
    foreach ($dirs as $dir) {
        $themeName = basename($dir);
        $themes[] = $themeName;
    }
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Appearance Settings</h1>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="active_theme">Active Theme</label>
                <select name="active_theme" id="active_theme" class="form-control">
                    <?php foreach ($themes as $theme): ?>
                        <option value="<?php echo htmlspecialchars($theme); ?>" 
                                <?php echo ($settings['active_theme'] ?? 'default') === $theme ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($theme)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text">Select the active theme for your site.</div>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="headless_mode" id="headless_mode" value="1" 
                       <?php echo ($settings['headless_mode'] ?? '0') === '1' ? 'checked' : ''; ?>>
                <label for="headless_mode">
                    <strong>Headless Mode</strong>
                    <div class="help-text">Enable headless mode to return JSON only (no HTML rendering).</div>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
