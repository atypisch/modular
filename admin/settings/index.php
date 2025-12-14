<?php
/**
 * Settings Index - Japanese Style
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

$page_title = 'General Settings';
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
    $siteTitle = filter_input(INPUT_POST, 'site_title', FILTER_SANITIZE_STRING) ?? '';
    $siteTagline = filter_input(INPUT_POST, 'site_tagline', FILTER_SANITIZE_STRING) ?? '';
    $siteUrl = filter_input(INPUT_POST, 'site_url', FILTER_SANITIZE_URL) ?? '';
    $timezone = filter_input(INPUT_POST, 'timezone', FILTER_SANITIZE_STRING) ?? 'UTC';
    $language = filter_input(INPUT_POST, 'language', FILTER_SANITIZE_STRING) ?? 'en_US';
    
    try {
        $updateStmt = $db->prepare("
            INSERT INTO settings (option_name, option_value, autoload, created_at, updated_at)
            VALUES (?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE option_value = ?, updated_at = NOW()
        ");
        
        $updateStmt->execute(['site_title', $siteTitle, $siteTitle]);
        $updateStmt->execute(['site_tagline', $siteTagline, $siteTagline]);
        $updateStmt->execute(['site_url', $siteUrl, $siteUrl]);
        $updateStmt->execute(['timezone', $timezone, $timezone]);
        $updateStmt->execute(['language', $language, $language]);
        
        $_SESSION['success'] = 'Settings saved successfully.';
        header('Location: /modular/admin/settings');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error saving settings: ' . $e->getMessage();
    }
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>General Settings</h1>
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
            <div class="form-group">
                <label for="site_title">Site Title</label>
                <input type="text" name="site_title" id="site_title" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['site_title'] ?? 'Modular CMS'); ?>">
                <div class="help-text">The name of your site.</div>
            </div>

            <div class="form-group">
                <label for="site_tagline">Site Tagline</label>
                <input type="text" name="site_tagline" id="site_tagline" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['site_tagline'] ?? ''); ?>">
                <div class="help-text">A brief description of your site.</div>
            </div>

            <div class="form-group">
                <label for="site_url">Site URL</label>
                <input type="url" name="site_url" id="site_url" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['site_url'] ?? SITE_URL ?? ''); ?>">
                <div class="help-text">The URL of your site.</div>
            </div>

            <div class="form-group">
                <label for="timezone">Timezone</label>
                <select name="timezone" id="timezone" class="form-control">
                    <option value="UTC" <?php echo ($settings['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                    <option value="Europe/Amsterdam" <?php echo ($settings['timezone'] ?? '') === 'Europe/Amsterdam' ? 'selected' : ''; ?>>Europe/Amsterdam</option>
                    <option value="America/New_York" <?php echo ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
                    <option value="Asia/Tokyo" <?php echo ($settings['timezone'] ?? '') === 'Asia/Tokyo' ? 'selected' : ''; ?>>Asia/Tokyo</option>
                </select>
                <div class="help-text">The timezone for your site.</div>
            </div>

            <div class="form-group">
                <label for="language">Language</label>
                <select name="language" id="language" class="form-control">
                    <option value="en_US" <?php echo ($settings['language'] ?? 'en_US') === 'en_US' ? 'selected' : ''; ?>>English (US)</option>
                    <option value="nl_NL" <?php echo ($settings['language'] ?? '') === 'nl_NL' ? 'selected' : ''; ?>>Nederlands</option>
                    <option value="ja_JP" <?php echo ($settings['language'] ?? '') === 'ja_JP' ? 'selected' : ''; ?>>日本語</option>
                </select>
                <div class="help-text">The default language for your site.</div>
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
