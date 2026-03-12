<?php
/**
 * Settings Admin Page – Modular styling with cards
 */
if (!defined('ABSPATH') && !defined('SIMUZE_MODULAR_RUN')) {
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

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();

$admin_css_url = defined('SIMUZE_COMMUNITY_PLUGIN_URL') ? rtrim(SIMUZE_COMMUNITY_PLUGIN_URL, '/') . '/admin/assets/admin.css' : '';
?>
<?php if ($admin_css_url): ?>
<link rel="stylesheet" href="<?php echo esc_url($admin_css_url); ?>">
<?php endif; ?>

<div class="simuze-admin-page">
    <div class="page-header">
        <h1>Simuze Community Settings</h1>
    </div>

    <div class="card">
        <div class="card-header">Connection Status</div>
        <div class="card-body" style="padding: 1rem;">
            <?php if ($current_user): ?>
                <p style="margin: 0; color: var(--success, #0a3622);">✓ Connected as: <strong><?php echo esc_html($current_user->display_name); ?></strong> (<?php echo esc_html($current_user->email); ?>)</p>
            <?php else: ?>
                <p style="margin: 0; color: var(--warning, #664d03);">○ Not logged in. <a href="<?php echo esc_url($auth->get_login_url()); ?>">Login via Simezu</a></p>
            <?php endif; ?>
        </div>
    </div>

    <form method="post">
        <div class="card">
            <div class="card-header">Simezu Authentication</div>
            <div class="card-body" style="padding: 1rem;">
                <table class="simuze-table" style="max-width: 640px;">
                    <tbody>
                        <tr>
                            <th style="width: 180px; padding: 0.5rem 0.75rem 0.5rem 0;"><label for="simuze_auth_api_url">Simezu Auth API URL</label></th>
                            <td style="padding: 0.5rem 0.75rem;">
                                <input type="text" id="simuze_auth_api_url" name="simuze_auth_api_url" value="<?php echo esc_attr($auth_api_url); ?>" class="regular-text" style="width: 100%; max-width: 400px; padding: 0.35rem 0.6rem; border-radius: 6px; border: 1px solid var(--border-color, #dee2e6);">
                                <p class="description" style="margin: 0.25rem 0 0; font-size: 0.8125rem; color: var(--text-secondary, #6c757d);">The URL for the Simezu Auth API (e.g. http://localhost/simezu/api.php/auth)</p>
                            </td>
                        </tr>
                        <tr>
                            <th style="padding: 0.5rem 0.75rem 0.5rem 0;"><label for="simuze_login_url">Simezu Login URL</label></th>
                            <td style="padding: 0.5rem 0.75rem;">
                                <input type="text" id="simuze_login_url" name="simuze_login_url" value="<?php echo esc_attr($login_url); ?>" class="regular-text" style="width: 100%; max-width: 400px; padding: 0.35rem 0.6rem; border-radius: 6px; border: 1px solid var(--border-color, #dee2e6);">
                                <p class="description" style="margin: 0.25rem 0 0; font-size: 0.8125rem; color: var(--text-secondary, #6c757d);">The URL for the Simezu login page</p>
                            </td>
                        </tr>
                        <tr>
                            <th style="padding: 0.5rem 0.75rem 0.5rem 0;"><label for="simuze_register_url">Simezu Register URL</label></th>
                            <td style="padding: 0.5rem 0.75rem;">
                                <input type="text" id="simuze_register_url" name="simuze_register_url" value="<?php echo esc_attr($register_url); ?>" class="regular-text" style="width: 100%; max-width: 400px; padding: 0.35rem 0.6rem; border-radius: 6px; border: 1px solid var(--border-color, #dee2e6);">
                                <p class="description" style="margin: 0.25rem 0 0; font-size: 0.8125rem; color: var(--text-secondary, #6c757d);">The URL for the Simezu registration page</p>
                            </td>
                        </tr>
                        <tr>
                            <th style="padding: 0.5rem 0.75rem 0.5rem 0;"><label for="simuze_jwt_secret">JWT Secret</label></th>
                            <td style="padding: 0.5rem 0.75rem;">
                                <input type="password" id="simuze_jwt_secret" name="simuze_jwt_secret" value="<?php echo esc_attr($jwt_secret); ?>" class="regular-text" style="width: 100%; max-width: 400px; padding: 0.35rem 0.6rem; border-radius: 6px; border: 1px solid var(--border-color, #dee2e6);">
                                <p class="description" style="margin: 0.25rem 0 0; font-size: 0.8125rem; color: var(--text-secondary, #6c757d);">The shared JWT secret (must match Simezu's JWT_SECRET). Keep this secure!</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Other APIs</div>
            <div class="card-body" style="padding: 1rem;">
                <table class="simuze-table" style="max-width: 640px;">
                    <tbody>
                        <tr>
                            <th style="width: 180px; padding: 0.5rem 0.75rem 0.5rem 0;"><label for="simuze_geo_api_url">GEO API URL</label></th>
                            <td style="padding: 0.5rem 0.75rem;">
                                <input type="text" id="simuze_geo_api_url" name="simuze_geo_api_url" value="<?php echo esc_attr($geo_api_url); ?>" class="regular-text" style="width: 100%; max-width: 400px; padding: 0.35rem 0.6rem; border-radius: 6px; border: 1px solid var(--border-color, #dee2e6);">
                                <p class="description" style="margin: 0.25rem 0 0; font-size: 0.8125rem; color: var(--text-secondary, #6c757d);">The URL for the GEO API (city/province/country lookup)</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="simuze-form-toolbar" style="margin-top: 0.5rem;">
            <button type="submit" name="submit" class="button button-primary">Save Settings</button>
        </div>
    </form>
</div>
