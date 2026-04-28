<?php
/**
 * Settings Security - Japanese Style
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
    header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/auth/login.php');
    exit;
}

$page_title = 'Security Settings';
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
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/settings/security.php');
        exit;
    }
    $enable2FA = isset($_POST['enable_2fa']) ? 1 : 0;
    $passwordMinLength = filter_input(INPUT_POST, 'password_min_length', FILTER_SANITIZE_NUMBER_INT) ?? 8;
    $maxLoginAttempts = filter_input(INPUT_POST, 'max_login_attempts', FILTER_SANITIZE_NUMBER_INT) ?? 5;
    $lockoutDuration = filter_input(INPUT_POST, 'lockout_duration', FILTER_SANITIZE_NUMBER_INT) ?? 15;
    
    try {
        $updateStmt = $db->prepare("
            INSERT INTO settings (option_name, option_value, autoload, created_at, updated_at)
            VALUES (?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE option_value = ?, updated_at = NOW()
        ");
        
        $updateStmt->execute(['enable_2fa', $enable2FA, $enable2FA]);
        $updateStmt->execute(['password_min_length', $passwordMinLength, $passwordMinLength]);
        $updateStmt->execute(['max_login_attempts', $maxLoginAttempts, $maxLoginAttempts]);
        $updateStmt->execute(['lockout_duration', $lockoutDuration, $lockoutDuration]);
        
        $_SESSION['success'] = 'Security settings saved successfully.';
        header('Location: ' . (defined('ADMIN_URL') ? ADMIN_URL : '/modular/admin') . '/settings/security.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error saving settings: ' . $e->getMessage();
    }
}

require_once ADMIN_PATH . '/templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Security Settings</h1>
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
            <h3 style="margin-top: 0;">Authentication</h3>
            
            <div class="checkbox-group">
                <input type="checkbox" name="enable_2fa" id="enable_2fa" value="1" 
                       <?php echo ($settings['enable_2fa'] ?? '0') === '1' ? 'checked' : ''; ?>>
                <label for="enable_2fa">
                    <strong>Enable Two-Factor Authentication</strong>
                    <div class="help-text">Require users to use 2FA for additional security.</div>
                </label>
            </div>

            <div class="form-group">
                <label for="password_min_length">Minimum Password Length</label>
                <input type="number" name="password_min_length" id="password_min_length" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['password_min_length'] ?? '8'); ?>" min="6" max="32">
                <div class="help-text">Minimum number of characters required for passwords.</div>
            </div>

            <hr class="section-divider">

            <h3>Brute Force Protection</h3>

            <div class="form-group">
                <label for="max_login_attempts">Maximum Login Attempts</label>
                <input type="number" name="max_login_attempts" id="max_login_attempts" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['max_login_attempts'] ?? '5'); ?>" min="3" max="10">
                <div class="help-text">Number of failed login attempts before lockout.</div>
            </div>

            <div class="form-group">
                <label for="lockout_duration">Lockout Duration (minutes)</label>
                <input type="number" name="lockout_duration" id="lockout_duration" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['lockout_duration'] ?? '15'); ?>" min="5" max="60">
                <div class="help-text">How long to lock out users after max attempts.</div>
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
