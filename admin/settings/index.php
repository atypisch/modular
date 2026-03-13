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
require_once CORE_PATH . '/functions/subscription.php';

if (!is_logged_in()) {
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
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
    if (!csrf_verify()) {
        $_SESSION['error'] = 'Invalid request. Please try again.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/settings');
        exit;
    }
    $siteTitle = filter_input(INPUT_POST, 'site_title', FILTER_SANITIZE_STRING) ?? '';
    $siteTagline = filter_input(INPUT_POST, 'site_tagline', FILTER_SANITIZE_STRING) ?? '';
    $siteUrl = filter_input(INPUT_POST, 'site_url', FILTER_SANITIZE_URL) ?? '';
    $timezone = filter_input(INPUT_POST, 'timezone', FILTER_SANITIZE_STRING) ?? 'UTC';
    $language = filter_input(INPUT_POST, 'language', FILTER_SANITIZE_STRING) ?? 'en_US';

    // Mail & templates
    $mailFromName = filter_input(INPUT_POST, 'mail_from_name', FILTER_SANITIZE_STRING) ?? '';
    $mailFromAddress = filter_input(INPUT_POST, 'mail_from_address', FILTER_SANITIZE_EMAIL) ?? '';
    $passwordResetSubject = filter_input(INPUT_POST, 'email_template_password_reset_subject', FILTER_SANITIZE_STRING) ?? '';
    $passwordResetBody = $_POST['email_template_password_reset_body'] ?? '';

    // License & Dev Mode
    $licenseTier = filter_input(INPUT_POST, 'license_tier', FILTER_SANITIZE_STRING) ?? 'free';
    $licenseKey = filter_input(INPUT_POST, 'license_key', FILTER_SANITIZE_STRING) ?? '';
    $devModeEnabled = isset($_POST['dev_mode_enabled']) ? '1' : '0';
    $devModeTierOverride = filter_input(INPUT_POST, 'dev_mode_tier_override', FILTER_SANITIZE_STRING) ?? '';
    
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

        // Mail settings
        $updateStmt->execute(['mail_from_name', $mailFromName, $mailFromName]);
        $updateStmt->execute(['mail_from_address', $mailFromAddress, $mailFromAddress]);
        $updateStmt->execute(['email_template.password_reset.subject', $passwordResetSubject, $passwordResetSubject]);
        $updateStmt->execute(['email_template.password_reset.body', $passwordResetBody, $passwordResetBody]);

        // License + Dev Mode (autoloaded)
        $updateStmt->execute(['license_tier', strtolower($licenseTier), strtolower($licenseTier)]);
        $updateStmt->execute(['license_key', $licenseKey, $licenseKey]);
        $updateStmt->execute(['dev_mode_enabled', $devModeEnabled, $devModeEnabled]);
        $updateStmt->execute(['dev_mode_tier_override', strtolower($devModeTierOverride), strtolower($devModeTierOverride)]);
        
        $_SESSION['success'] = 'Settings saved successfully.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/settings');
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
            <?php echo csrf_field(); ?>
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

            <hr class="section-divider">

            <h2 style="margin-top: var(--spacing-md);">Email</h2>

            <div class="form-group">
                <label for="mail_from_name">From Name</label>
                <input type="text" name="mail_from_name" id="mail_from_name" class="form-control"
                       value="<?php echo htmlspecialchars($settings['mail_from_name'] ?? 'Modular CMS'); ?>">
                <div class="help-text">Name shown in the "From" field of outgoing emails.</div>
            </div>

            <div class="form-group">
                <label for="mail_from_address">From Address</label>
                <input type="email" name="mail_from_address" id="mail_from_address" class="form-control"
                       value="<?php echo htmlspecialchars($settings['mail_from_address'] ?? 'noreply@example.com'); ?>">
                <div class="help-text">Email address used for outgoing system emails.</div>
            </div>

            <div class="form-group">
                <label for="email_template_password_reset_subject">Password Reset – Subject</label>
                <input type="text" name="email_template_password_reset_subject" id="email_template_password_reset_subject" class="form-control"
                       value="<?php echo htmlspecialchars($settings['email_template.password_reset.subject'] ?? 'Password Reset Request'); ?>">
                <div class="help-text">
                    You can use variables like <code>{{reset_link}}</code>.
                </div>
            </div>

            <div class="form-group">
                <label for="email_template_password_reset_body">Password Reset – Body</label>
                <textarea name="email_template_password_reset_body" id="email_template_password_reset_body" rows="6" class="form-control"><?php
                    echo htmlspecialchars($settings['email_template.password_reset.body'] ??
"Hello,

You have requested to reset your password. Please click the following link to reset it:

{{reset_link}}

This link will expire in 1 hour.

If you did not request this password reset, please ignore this email.

Best regards,
Modular CMS Team");
                ?></textarea>
                <div class="help-text">
                    Available variables: <code>{{reset_link}}</code>. Plain text only for now.
                </div>
            </div>

            <hr class="section-divider">

            <h2 style="margin-top: var(--spacing-md);">License &amp; Dev Mode</h2>

            <div class="form-group">
                <label for="license_tier">License Tier</label>
                <select name="license_tier" id="license_tier" class="form-control">
                    <?php
                    $currentTier = subscription_base_tier();
                    ?>
                    <option value="free" <?php echo $currentTier === 'free' ? 'selected' : ''; ?>>Free</option>
                    <option value="pro" <?php echo $currentTier === 'pro' ? 'selected' : ''; ?>>Pro</option>
                    <option value="agency" <?php echo $currentTier === 'agency' ? 'selected' : ''; ?>>Agency</option>
                </select>
                <div class="help-text">Sets the licensed tier for this installation.</div>
            </div>

            <div class="form-group">
                <label for="license_key">License Key</label>
                <input type="text" name="license_key" id="license_key" class="form-control"
                       value="<?php echo htmlspecialchars($settings['license_key'] ?? ''); ?>">
                <div class="help-text">Optional license key or contract reference. Not sent anywhere automatically.</div>
            </div>

            <?php if (function_exists('is_admin') && is_admin()): ?>
            <div class="form-group">
                <label for="dev_mode_enabled">Dev Mode</label>
                <div class="checkbox-group">
                    <input type="checkbox" name="dev_mode_enabled" id="dev_mode_enabled" value="1"
                           <?php echo dev_mode_enabled() ? 'checked' : ''; ?>>
                    <label for="dev_mode_enabled">
                        <strong>Enable Dev Mode (admins only)</strong>
                        <div class="help-text">
                            Allows simulating another tier for testing feature gating. Should be disabled in production.
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="dev_mode_tier_override">Dev Mode Tier Override</label>
                <?php $override = $settings['dev_mode_tier_override'] ?? ''; ?>
                <select name="dev_mode_tier_override" id="dev_mode_tier_override" class="form-control">
                    <option value="" <?php echo $override === '' ? 'selected' : ''; ?>>None (use licensed tier)</option>
                    <option value="free" <?php echo $override === 'free' ? 'selected' : ''; ?>>Free</option>
                    <option value="pro" <?php echo $override === 'pro' ? 'selected' : ''; ?>>Pro</option>
                    <option value="agency" <?php echo $override === 'agency' ? 'selected' : ''; ?>>Agency</option>
                </select>
                <div class="help-text">
                    When Dev Mode is enabled, use this tier for gating checks.
                </div>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once ADMIN_PATH . '/templates/footer.php'; ?>
