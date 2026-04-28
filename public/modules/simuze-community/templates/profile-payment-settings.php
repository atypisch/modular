<?php
/**
 * User Payment Settings Template
 * All payments run via SIMEZU; no local payment provider configuration.
 */

if (!defined('ABSPATH') && !defined('SIMUZE_MODULAR_RUN')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();

if (!$current_user || (isset($user) && $current_user->id !== $user->id)) {
    echo '<p>Access denied.</p>';
    return;
}
?>

<div class="payment-settings" style="background: #1a1a1a; color: #fff; padding: 30px; border-radius: 8px;">
    <h2 style="color: #a8d5ba; margin-bottom: 30px; border-bottom: 2px solid #a8d5ba; padding-bottom: 10px;">
        <i class="fas fa-credit-card"></i> Payment Settings
    </h2>
    <div class="payment-settings-info" style="background: #2a2a2a; padding: 20px; border-radius: 8px; border-left: 4px solid #a8d5ba;">
        <p style="margin: 0; color: #fff;">
            <i class="fas fa-info-circle"></i> All payments, subscriptions, and payouts are managed via SIMEZU. Configure your payment methods in your SIMEZU account.
        </p>
    </div>
</div>
