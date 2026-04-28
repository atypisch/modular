<?php
/**
 * Settings Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

if (isset($_POST['submit'])) {
    update_option('simuze_auth_api_url', sanitize_text_field($_POST['simuze_auth_api_url']));
    update_option('simuze_login_url', sanitize_text_field($_POST['simuze_login_url']));
    update_option('simuze_register_url', sanitize_text_field($_POST['simuze_register_url']));
    update_option('simuze_jwt_secret', sanitize_text_field($_POST['simuze_jwt_secret']));
    update_option('simuze_geo_api_url', sanitize_text_field($_POST['simuze_geo_api_url']));
    echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
}

$auth_api_url = get_option('simuze_auth_api_url', 'http://localhost/simezu/api.php/auth');
$login_url = get_option('simuze_login_url', 'http://localhost/simezu/login.php');
$register_url = get_option('simuze_register_url', 'http://localhost/simezu/register.php');
$jwt_secret = get_option('simuze_jwt_secret', 'simezu-auth-secret-key-change-in-production-2024');
$geo_api_url = get_option('simuze_geo_api_url', '');

// Test connection
$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
?>

<div class="wrap">
    <h1>Simuze Community Settings</h1>
    
    <div class="card" style="max-width: 100%; margin-bottom: 20px; padding: 15px;">
        <h2>Connection Status</h2>
        <?php if ($current_user): ?>
            <p style="color: green;">✓ Connected as: <strong><?php echo esc_html($current_user->display_name); ?></strong> (<?php echo esc_html($current_user->email); ?>)</p>
        <?php else: ?>
            <p style="color: orange;">○ Not logged in. <a href="<?php echo esc_url($auth->get_login_url()); ?>">Login via Simezu</a></p>
        <?php endif; ?>
    </div>
    
    <form method="post">
        <h2>Simezu Authentication</h2>
        <table class="form-table">
            <tr>
                <th><label for="simuze_auth_api_url">Simezu Auth API URL</label></th>
                <td>
                    <input type="text" id="simuze_auth_api_url" name="simuze_auth_api_url" value="<?php echo esc_attr($auth_api_url); ?>" class="regular-text">
                    <p class="description">The URL for the Simezu Auth API (e.g., http://localhost/simezu/api.php/auth)</p>
                </td>
            </tr>
            <tr>
                <th><label for="simuze_login_url">Simezu Login URL</label></th>
                <td>
                    <input type="text" id="simuze_login_url" name="simuze_login_url" value="<?php echo esc_attr($login_url); ?>" class="regular-text">
                    <p class="description">The URL for the Simezu login page (e.g., http://localhost/simezu/login.php)</p>
                </td>
            </tr>
            <tr>
                <th><label for="simuze_register_url">Simezu Register URL</label></th>
                <td>
                    <input type="text" id="simuze_register_url" name="simuze_register_url" value="<?php echo esc_attr($register_url); ?>" class="regular-text">
                    <p class="description">The URL for the Simezu registration page (e.g., http://localhost/simezu/register.php)</p>
                </td>
            </tr>
            <tr>
                <th><label for="simuze_jwt_secret">JWT Secret</label></th>
                <td>
                    <input type="password" id="simuze_jwt_secret" name="simuze_jwt_secret" value="<?php echo esc_attr($jwt_secret); ?>" class="regular-text">
                    <p class="description">The shared JWT secret (must match Simezu's JWT_SECRET). Keep this secure!</p>
                </td>
            </tr>
        </table>
        
        <h2>Other APIs</h2>
        <table class="form-table">
            <tr>
                <th><label for="simuze_geo_api_url">GEO API URL</label></th>
                <td>
                    <input type="text" id="simuze_geo_api_url" name="simuze_geo_api_url" value="<?php echo esc_attr($geo_api_url); ?>" class="regular-text">
                    <p class="description">The URL for the GEO API (city/province/country lookup)</p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" name="submit" class="button button-primary">Save Settings</button>
        </p>
    </form>
</div>

