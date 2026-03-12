<?php
/**
 * Payment Settings Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Payment Settings</h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('simuze_commerce_settings'); ?>
        
        <!-- General Commerce Settings -->
        <h2>General Settings</h2>
        <table class="form-table">
            <tr>
                <th><label for="simuze_commerce_fee_percentage">Simuze Fee (%)</label></th>
                <td>
                    <input type="number" name="simuze_commerce_fee_percentage" id="simuze_commerce_fee_percentage" 
                           value="<?php echo esc_attr(get_option('simuze_commerce_fee_percentage', '15')); ?>" 
                           class="small-text" step="0.1" min="0" max="100">
                    <p class="description">Percentage fee Simuze takes from each sale (e.g., 15 = 15%)</p>
                </td>
            </tr>
            <tr>
                <th><label for="simuze_commerce_max_downloads">Max Downloads per Purchase</label></th>
                <td>
                    <input type="number" name="simuze_commerce_max_downloads" id="simuze_commerce_max_downloads" 
                           value="<?php echo esc_attr(get_option('simuze_commerce_max_downloads', '5')); ?>" 
                           class="small-text" min="1">
                    <p class="description">How many times a buyer can download their purchased files</p>
                </td>
            </tr>
        </table>
        
        <!-- SIMEZU API Settings -->
        <h2>SIMEZU API Settings</h2>
        <table class="form-table">
            <tr>
                <th><label for="simezu_api_url">SIMEZU API URL</label></th>
                <td>
                    <input type="url" name="simezu_api_url" id="simezu_api_url" 
                           value="<?php echo esc_attr(get_option('simezu_api_url', 'http://localhost/simezu/api')); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="simuze_simezu_api_key">SIMEZU API Key</label></th>
                <td>
                    <input type="text" name="simuze_simezu_api_key" id="simuze_simezu_api_key" 
                           value="<?php echo esc_attr(get_option('simuze_simezu_api_key', '')); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="simuze_simezu_app_id">SIMEZU App ID</label></th>
                <td>
                    <input type="text" name="simuze_simezu_app_id" id="simuze_simezu_app_id" 
                           value="<?php echo esc_attr(get_option('simuze_simezu_app_id', 'simuze')); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="simuze_simezu_webhook_secret">SIMEZU Webhook Secret</label></th>
                <td>
                    <input type="text" name="simuze_simezu_webhook_secret" id="simuze_simezu_webhook_secret" 
                           value="<?php echo esc_attr(get_option('simuze_simezu_webhook_secret', '')); ?>" 
                           class="regular-text">
                    <p class="description">Used to verify webhook callbacks from SIMEZU</p>
                </td>
            </tr>
        </table>
        
        <!-- PayPal Settings -->
        <h2>PayPal Settings</h2>
        <table class="form-table">
            <tr>
                <th><label for="simuze_paypal_sandbox">Mode</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="simuze_paypal_sandbox" id="simuze_paypal_sandbox" value="1" 
                               <?php checked(get_option('simuze_paypal_sandbox', true)); ?>>
                        Sandbox Mode (for testing)
                    </label>
                    <p class="description">Uncheck for live/production payments</p>
                </td>
            </tr>
            <tr>
                <th><label for="simuze_paypal_client_id">PayPal Client ID</label></th>
                <td>
                    <input type="text" name="simuze_paypal_client_id" id="simuze_paypal_client_id" 
                           value="<?php echo esc_attr(get_option('simuze_paypal_client_id', '')); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="simuze_paypal_client_secret">PayPal Client Secret</label></th>
                <td>
                    <input type="password" name="simuze_paypal_client_secret" id="simuze_paypal_client_secret" 
                           value="<?php echo esc_attr(get_option('simuze_paypal_client_secret', '')); ?>" 
                           class="regular-text">
                </td>
            </tr>
        </table>
        
        <!-- Stripe Settings -->
        <h2>Stripe Settings</h2>
        <table class="form-table">
            <tr>
                <th><label for="simuze_stripe_enabled">Enable Stripe</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="simuze_stripe_enabled" id="simuze_stripe_enabled" value="1" 
                               <?php checked(get_option('simuze_stripe_enabled', false)); ?>>
                        Enable Stripe payments
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="simuze_stripe_publishable_key">Stripe Publishable Key</label></th>
                <td>
                    <input type="text" name="simuze_stripe_publishable_key" id="simuze_stripe_publishable_key" 
                           value="<?php echo esc_attr(get_option('simuze_stripe_publishable_key', '')); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="simuze_stripe_secret_key">Stripe Secret Key</label></th>
                <td>
                    <input type="password" name="simuze_stripe_secret_key" id="simuze_stripe_secret_key" 
                           value="<?php echo esc_attr(get_option('simuze_stripe_secret_key', '')); ?>" 
                           class="regular-text">
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <!-- Test Connection -->
    <h2>Test Connections</h2>
    <div class="connection-tests">
        <button type="button" class="button" id="test-simezu">Test SIMEZU Connection</button>
        <button type="button" class="button" id="test-paypal">Test PayPal Connection</button>
        <span id="test-result"></span>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#test-simezu').on('click', function() {
        $('#test-result').text('Testing SIMEZU...');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'simuze_test_simezu_connection'
            },
            success: function(response) {
                $('#test-result').text(response.success ? 'SIMEZU: Connected!' : 'SIMEZU: ' + response.data);
            },
            error: function() {
                $('#test-result').text('SIMEZU: Connection failed');
            }
        });
    });
    
    $('#test-paypal').on('click', function() {
        $('#test-result').text('Testing PayPal...');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'simuze_test_paypal_connection'
            },
            success: function(response) {
                $('#test-result').text(response.success ? 'PayPal: Connected!' : 'PayPal: ' + response.data);
            },
            error: function() {
                $('#test-result').text('PayPal: Connection failed');
            }
        });
    });
});
</script>

<style>
.connection-tests {
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    margin-top: 20px;
}
.connection-tests .button {
    margin-right: 10px;
}
#test-result {
    margin-left: 20px;
    font-weight: bold;
}
</style>











