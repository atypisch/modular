<?php
/**
 * Group Settings Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$is_admin = $current_user && $group->can_manage($current_user->id);

if (!$is_admin) {
    echo '<p>Access denied.</p>';
    return;
}

?>
<!-- Cropper.js for image cropping -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.css">
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.js"></script>

<div class="group-settings" style="background: #1a1a1a; color: #fff; padding: 30px; border-radius: 8px;">
    <h2 style="color: #a8d5ba; margin-bottom: 30px; border-bottom: 2px solid #a8d5ba; padding-bottom: 10px;">Group Settings</h2>
    
    <form id="group-settings-form" class="simuze-form">
        <div class="form-group">
            <label for="group_name" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">Group Name</label>
            <input type="text" id="group_name" name="name" value="<?php echo esc_attr($group->name); ?>" required 
                   style="width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">
        </div>
        
        <div class="form-group">
            <label for="group_unique_handle" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">
                Unique Handle (@handle) <small style="color: #999; font-weight: normal;">(Used for @mentions, URLs, and PMs)</small>
            </label>
            <input type="text" id="group_unique_handle" name="unique_handle" value="<?php echo esc_attr($group->unique_handle ?: ''); ?>" 
                   placeholder="<?php echo esc_attr(Simuze_Group::slugify_handle($group->name)); ?>"
                   pattern="[a-z0-9-]{3,64}" 
                   minlength="3"
                   maxlength="64"
                   style="width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">
            <small style="display: block; margin-top: 8px; color: #999;">
                3-64 characters, lowercase letters, numbers, and hyphens only. This will be used for your URL: <strong style="color: #a8d5ba;"><?php echo md_home_url('/'); ?><span id="unique-handle-preview"><?php echo esc_html($group->unique_handle ?: Simuze_Group::slugify_handle($group->name)); ?></span>/</strong>
            </small>
            <?php if ($group->unique_handle && $group->unique_handle !== Simuze_Group::slugify_handle($group->name)): ?>
                <div style="margin-top: 8px; padding: 8px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 4px; color: #ffa500;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> Changing your handle will create a redirect from the old handle, but old links may break.
                </div>
            <?php endif; ?>
            <div id="unique-handle-status" style="margin-top: 8px; padding: 8px; border-radius: 4px; display: none;"></div>
        </div>
        
        <!-- Legacy unique_name field (hidden, for backwards compatibility) -->
        <input type="hidden" id="group_unique_name" name="unique_name" value="<?php echo esc_attr($group->unique_name ?: ''); ?>">
        
        <div class="form-group">
            <label for="group_description" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">Description</label>
            <textarea id="group_description" name="description" rows="5" 
                      style="width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;"><?php echo esc_textarea($group->description); ?></textarea>
        </div>
        
        <?php
        // Genre field - only for Artist groups
        $group_type_data = $group->get_group_type();
        $group_type_slug = $group_type_data ? $group_type_data['slug'] : null;
        $current_settings = is_array($group->settings) ? $group->settings : (is_string($group->settings) ? json_decode($group->settings, true) : array());
        $current_genre = $current_settings['genre'] ?? '';
        
        if ($group_type_slug === 'artist'):
            // Get all genres from taxonomy
            $all_genres = get_terms([
                'taxonomy' => 'genre',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ]);
        ?>
            <div class="form-group">
                <label for="group_genre" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">Genre</label>
                <select id="group_genre" name="genre" 
                        style="width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">
                    <option value=""><?php _e('Select a genre...', 'simuze'); ?></option>
                    <?php if (!is_wp_error($all_genres) && !empty($all_genres)): ?>
                        <?php foreach ($all_genres as $genre): ?>
                            <option value="<?php echo esc_attr($genre->name); ?>" <?php selected($current_genre, $genre->name); ?>>
                                <?php echo esc_html($genre->name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <small style="display: block; margin-top: 8px; color: #999;">Select the primary genre for this artist group.</small>
            </div>
        <?php endif; ?>
        
        <!-- Avatar Upload Section -->
        <div class="form-group" style="margin-top: 30px;">
            <label style="color: #fff; font-weight: 600; margin-bottom: 15px; display: block; border-bottom: 2px solid #a8d5ba; padding-bottom: 8px;">
                <i class="fas fa-user-circle"></i> Group Avatar
            </label>
            <div id="avatar-preview-container" style="margin-bottom: 15px;">
                <?php if ($group->avatar_url): ?>
                    <div class="current-avatar" style="margin-bottom: 15px;">
                        <img src="<?php echo esc_url($group->avatar_url); ?>" alt="Current Avatar" 
                             style="max-width: 200px; max-height: 200px; border-radius: 50%; border: 2px solid #4a4a4a; display: block;">
                    </div>
                <?php else: ?>
                    <div class="no-avatar-placeholder" style="width: 200px; height: 200px; border-radius: 50%; background: #2a2a2a; border: 2px dashed #4a4a4a; display: flex; align-items: center; justify-content: center; color: #999;">
                        <i class="fas fa-user-circle" style="font-size: 80px;"></i>
                    </div>
                <?php endif; ?>
            </div>
            <input type="file" id="group-avatar-input" name="avatar" accept="image/*" style="display: none;">
            <button type="button" id="browse-avatar-btn" class="button" 
                    style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                <i class="fas fa-upload"></i> <?php echo $group->avatar_url ? 'Change Avatar' : 'Upload Avatar'; ?>
            </button>
            <small style="display: block; margin-top: 8px; color: #999;">Recommended: 200x200px (square)</small>
        </div>
        
        <!-- Header Upload Section -->
        <div class="form-group" style="margin-top: 30px;">
            <label style="color: #fff; font-weight: 600; margin-bottom: 15px; display: block; border-bottom: 2px solid #a8d5ba; padding-bottom: 8px;">
                <i class="fas fa-image"></i> Header Image
            </label>
            <div id="header-preview-container" style="margin-bottom: 15px;">
                <?php if ($group->header_url): ?>
                    <div class="current-header" style="margin-bottom: 15px;">
                        <img src="<?php echo esc_url($group->header_url); ?>" alt="Current Header" 
                             style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 2px solid #4a4a4a; display: block;">
                    </div>
                <?php else: ?>
                    <div class="no-header-placeholder" style="width: 100%; height: 200px; background: #2a2a2a; border: 2px dashed #4a4a4a; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999;">
                        <i class="fas fa-image" style="font-size: 60px;"></i>
                    </div>
                <?php endif; ?>
            </div>
            <input type="file" id="group-header-input" name="header" accept="image/*" style="display: none;">
            <button type="button" id="browse-header-btn" class="button" 
                    style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                <i class="fas fa-upload"></i> <?php echo $group->header_url ? 'Change Header' : 'Upload Header'; ?>
            </button>
            <small style="display: block; margin-top: 8px; color: #999;">Recommended: 1200x300px</small>
        </div>
        
        <div class="form-group" style="margin-top: 30px;">
            <label for="group_status" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">Status</label>
            <select id="group_status" name="status" 
                    style="width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">
                <option value="public" <?php selected($group->status, 'public'); ?>>Public</option>
                <option value="private" <?php selected($group->status, 'private'); ?>>Private</option>
                <option value="hidden" <?php selected($group->status, 'hidden'); ?>>Hidden</option>
            </select>
        </div>
        
        <!-- Payment Recipient Selection -->
        <div class="form-group" style="margin-top: 30px;">
            <label for="payment_recipient" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">
                <i class="fas fa-money-bill-wave"></i> Payment Recipient
            </label>
            <p style="color: #999; font-size: 13px; margin-bottom: 12px;">
                Select a group member who will receive payments for this group's sales. This information will be passed to SIMEZU when processing payments.
            </p>
            <select id="payment_recipient" name="payment_recipient" 
                    style="width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">
                <option value="">-- Select a member --</option>
                <?php
                $members_result = $group->get_members(array('per_page' => 100, 'status' => 'active'));
                $members = $members_result['members'];
                $current_settings = is_array($group->settings) ? $group->settings : array();
                $current_recipient = $current_settings['payment_recipient_user_id'] ?? '';
                
                foreach ($members as $member):
                    $member_user = Simuze_User::get($member['id']);
                    if ($member_user):
                        $display_name = $member_user->display_name ?: $member_user->user_login;
                        $role_label = ucfirst($member['role'] ?? 'member');
                ?>
                    <option value="<?php echo esc_attr($member['id']); ?>" <?php selected($current_recipient, $member['id']); ?>>
                        <?php echo esc_html($display_name); ?> (<?php echo esc_html($role_label); ?>)
                    </option>
                <?php
                    endif;
                endforeach;
                ?>
            </select>
            <small style="display: block; margin-top: 8px; color: #999;">
                Payments for this group's music sales will be sent to the selected member's payment account.
            </small>
        </div>
        
        <!-- Payment Settings Section -->
        <div class="form-group" style="margin-top: 30px; padding-top: 30px; border-top: 2px solid #4a4a4a;">
            <label style="color: #fff; font-weight: 600; margin-bottom: 15px; display: block; border-bottom: 2px solid #a8d5ba; padding-bottom: 8px;">
                <i class="fas fa-credit-card"></i> Payment Settings
            </label>
            <p style="color: #999; font-size: 13px; margin-bottom: 20px;">
                Configure payment methods for this group. Payments for group music sales will be sent to these accounts.
            </p>
            
            <!-- PayPal Settings Section -->
            <div class="form-section payment-method-section" style="background: #2a2a2a; padding: 25px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #4a4a4a;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fab fa-paypal" style="font-size: 28px; color: #0070ba;"></i>
                        <h3 style="margin: 0; color: #fff; font-size: 18px; font-weight: 600;">PayPal</h3>
                    </div>
                    <div id="group-paypal-connection-status" style="display: none;">
                        <span class="connection-badge" style="background: #2a4a2a; color: #a8d5ba; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                            <i class="fas fa-check"></i> Connected
                        </span>
                    </div>
                </div>
                <p style="margin: 0 0 20px 0; color: #999; font-size: 14px;">Connect your PayPal account to receive payments</p>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="group-paypal-email" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">PayPal Email Address</label>
                    <input type="email" id="group-paypal-email" name="group_paypal_email" 
                           placeholder="your.email@example.com"
                           style="width: 100%; padding: 12px 14px; background: #1a1a1a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff; box-sizing: border-box;">
                    <small style="display: block; margin-top: 6px; color: #999; font-size: 12px;">Enter the email address associated with your PayPal account</small>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="button" id="test-group-paypal-btn" class="button" 
                            style="background: #4a4a4a; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px;">
                        <i class="fas fa-plug"></i> Test Connection
                    </button>
                    <button type="button" id="save-group-paypal-btn" class="button" 
                            style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 14px;">
                        <i class="fas fa-save"></i> Save PayPal Settings
                    </button>
                </div>
                <div id="group-paypal-status-message" style="margin-top: 10px; font-size: 13px;"></div>
            </div>
            
            <!-- Stripe Settings Section -->
            <div class="form-section payment-method-section" style="background: #2a2a2a; padding: 25px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #4a4a4a;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fab fa-cc-stripe" style="font-size: 28px; color: #635bff;"></i>
                        <h3 style="margin: 0; color: #fff; font-size: 18px; font-weight: 600;">Stripe</h3>
                    </div>
                    <div id="group-stripe-connection-status" style="display: none;">
                        <span class="connection-badge" style="background: #2a4a2a; color: #a8d5ba; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                            <i class="fas fa-check"></i> Connected
                        </span>
                    </div>
                </div>
                <p style="margin: 0 0 20px 0; color: #999; font-size: 14px;">Connect your Stripe account using API keys</p>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="group-stripe-publishable-key" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">Publishable Key</label>
                    <input type="text" id="group-stripe-publishable-key" name="group_stripe_publishable_key" 
                           placeholder="pk_test_..."
                           style="width: 100%; padding: 12px 14px; background: #1a1a1a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff; box-sizing: border-box; font-family: monospace;">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="group-stripe-secret-key" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">Secret Key (will be encrypted)</label>
                    <input type="password" id="group-stripe-secret-key" name="group_stripe_secret_key" 
                           placeholder="sk_test_..."
                           style="width: 100%; padding: 12px 14px; background: #1a1a1a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff; box-sizing: border-box; font-family: monospace;">
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="button" id="test-group-stripe-btn" class="button" 
                            style="background: #4a4a4a; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px;">
                        <i class="fas fa-plug"></i> Test Connection
                    </button>
                    <button type="button" id="save-group-stripe-btn" class="button" 
                            style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 14px;">
                        <i class="fas fa-save"></i> Save Stripe Settings
                    </button>
                </div>
                <div id="group-stripe-status-message" style="margin-top: 10px; font-size: 13px;"></div>
            </div>
            
            <!-- Credit Card Settings Section -->
            <div class="form-section payment-method-section" style="background: #2a2a2a; padding: 25px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #4a4a4a;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <i class="fas fa-credit-card" style="font-size: 20px; color: #a8d5ba;"></i>
                    <h3 style="margin: 0; color: #fff; font-size: 18px; font-weight: 600;">Credit Card</h3>
                </div>
                <p style="margin: 0 0 20px 0; color: #999; font-size: 14px;">Manage your credit card preferences</p>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="group-enable-credit-card" name="group_enable_credit_card" 
                               style="width: 18px; height: 18px; cursor: pointer;">
                        <span style="color: #fff; font-weight: 600; font-size: 14px;">Enable credit card payments</span>
                    </label>
                    <small style="display: block; margin-top: 6px; margin-left: 28px; color: #999; font-size: 12px;">Allow credit card payments for this group</small>
                </div>
                
                <button type="button" id="save-group-credit-card-btn" class="button" 
                        style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 14px;">
                    <i class="fas fa-save"></i> Save Credit Card Settings
                </button>
                <div id="group-credit-card-status-message" style="margin-top: 10px; font-size: 13px;"></div>
            </div>
            
            <!-- Default Payment Method Section -->
            <div class="form-section payment-method-section" style="background: #2a2a2a; padding: 25px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #4a4a4a;">
                <h3 style="margin: 0 0 10px 0; color: #fff; font-size: 18px; font-weight: 600;">Default Payment Method</h3>
                <p style="margin: 0 0 20px 0; color: #999; font-size: 14px;">Select Default Payment Method</p>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="group-default-payment-method" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block; font-size: 14px;">Payment Method</label>
                    <select id="group-default-payment-method" name="group_default_payment_method" 
                            style="width: 100%; padding: 12px 14px; background: #1a1a1a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff; box-sizing: border-box; cursor: pointer;">
                        <option value="paypal">PayPal</option>
                        <option value="stripe">Credit Card (Stripe)</option>
                    </select>
                    <small style="display: block; margin-top: 6px; color: #999; font-size: 12px;">This will be used as the default payment method for group transactions</small>
                </div>
                
                <button type="button" id="save-group-default-payment-btn" class="button" 
                        style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 14px;">
                    <i class="fas fa-save"></i> Save Default Payment Method
                </button>
                <div id="group-default-payment-status-message" style="margin-top: 10px; font-size: 13px;"></div>
            </div>
        </div>
        
        <div class="form-actions" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #4a4a4a;">
            <button type="submit" class="button" 
                    style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; margin-right: 10px;">
                <i class="fas fa-save"></i> Save Settings
            </button>
            <button type="button" class="button secondary delete-group" data-group-id="<?php echo esc_attr($group->id); ?>" 
                    style="background: #4a4a4a; color: #fff; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                <i class="fas fa-trash"></i> Delete Group
            </button>
        </div>
    </form>
</div>

<!-- Delete Group Modal -->
<div id="delete-group-modal">
    <div>
        <h3 style="color: #ff6b6b; margin: 0 0 15px 0; font-size: 20px; font-weight: 600;">
            <i class="fas fa-exclamation-triangle"></i> Delete Group
        </h3>
        <p style="color: #fff; margin: 0 0 20px 0; line-height: 1.6;">
            Are you sure you want to delete "<strong style="color: #a8d5ba;"><?php echo esc_js($group->name); ?></strong>"? This action cannot be undone. All group data, members, and content will be permanently removed.
        </p>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" id="cancel-delete-group" class="button" 
                    style="background: #4a4a4a; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                Cancel
            </button>
            <button type="button" id="confirm-delete-group" class="button" 
                    style="background: #ff6b6b; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                <i class="fas fa-trash"></i> Delete Group
            </button>
        </div>
    </div>
</div>

<style>
/* Crop Container Styling */
.avatar-crop-container,
.header-crop-container {
    background: #2a2a2a;
    border: 1px solid #4a4a4a;
    border-radius: 8px;
    padding: 20px;
    margin-top: 15px;
}

.simple-cropper {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}

.cropper-image {
    display: block;
    max-width: 100%;
    border-radius: 8px;
}

.crop-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.crop-box {
    position: absolute;
    border: 2px solid #a8d5ba;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
    cursor: move;
}

.crop-controls {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.crop-controls button {
    background: #a8d5ba;
    color: #1a1a1a;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}

.crop-controls button:hover {
    background: #8fc5a8;
}

.crop-preview {
    text-align: center;
    margin-top: 20px;
}

.crop-preview h4 {
    color: #a8d5ba;
    margin-bottom: 15px;
}

.crop-canvas {
    border: 2px solid #4a4a4a;
    border-radius: 8px;
    background: #1a1a1a;
    display: block;
    margin: 0 auto 15px;
}

.crop-preview .button {
    background: #a8d5ba;
    color: #1a1a1a;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    margin: 0 5px;
}

.crop-preview .button:hover {
    background: #8fc5a8;
}

.final-avatar,
.final-header {
    text-align: center;
}

.final-avatar img,
.final-header img {
    max-width: 200px;
    max-height: 200px;
    border-radius: 50%;
    border: 2px solid #4a4a4a;
    margin-bottom: 15px;
}

.final-header img {
    max-width: 100%;
    max-height: 300px;
    border-radius: 8px;
    border-radius: 50%;
}

.final-header img {
    border-radius: 8px;
}

/* Payment Settings Styles */
.payment-method-selection {
    margin-bottom: 20px;
}

.payment-method-selection label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: #fff;
    padding: 10px;
    border-radius: 6px;
    transition: background 0.2s;
}

.payment-method-selection label:hover {
    background: rgba(255, 255, 255, 0.05);
}

.payment-section {
    margin-bottom: 20px;
}

.payment-section input[type="email"],
.payment-section input[type="text"] {
    width: 100%;
    padding: 10px 12px;
    background: #2a2a2a;
    border: 1px solid #4a4a4a;
    border-radius: 6px;
    font-size: 14px;
    color: #fff;
    box-sizing: border-box;
}

.payment-section input:focus {
    outline: none;
    border-color: #a8d5ba;
}

.payment-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.payment-actions .button {
    flex: 1;
    min-width: 150px;
}

#payment-status-message {
    margin-top: 15px;
    padding: 12px;
    border-radius: 6px;
    font-size: 14px;
}

#payment-status-message.success {
    background: rgba(168, 213, 186, 0.2);
    border: 1px solid #a8d5ba;
    color: #a8d5ba;
}

#payment-status-message.error {
    background: rgba(255, 107, 107, 0.2);
    border: 1px solid #ff6b6b;
    color: #ff6b6b;
}

/* Delete Group Modal */
#delete-group-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

#delete-group-modal.show {
    display: flex;
}

#delete-group-modal > div {
    background: #2a2a2a;
    border-radius: 8px;
    padding: 30px;
    max-width: 500px;
    width: 90%;
    border: 1px solid #4a4a4a;
    position: relative;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .payment-method-selection {
        flex-direction: column;
        gap: 10px;
    }
    
    .payment-method-selection label {
        width: 100%;
    }
    
    .payment-actions {
        flex-direction: column;
    }
    
    .payment-actions .button {
        width: 100%;
        min-width: auto;
    }
    
    .payment-section input[type="email"],
    .payment-section input[type="text"],
    .payment-section select {
        width: 100%;
        padding-left: 20px;
    }
    
    #delete-group-modal > div {
        width: 95%;
        padding: 20px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    let avatarCropped = null;
    let headerCropped = null;
    let avatarCropper = null;
    let headerCropper = null;
    
    // Avatar upload handlers
    $('#browse-avatar-btn').on('click', function() {
        $('#group-avatar-input').trigger('click');
    });
    
    $('#group-avatar-input').on('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                initAvatarCropper(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Header upload handlers
    $('#browse-header-btn').on('click', function() {
        $('#group-header-input').trigger('click');
    });
    
    $('#group-header-input').on('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                initHeaderCropper(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Initialize avatar cropper
    function initAvatarCropper(imageSrc) {
        const container = $('#avatar-preview-container');
        container.empty();

        const wrapper = $(`
            <div class="avatar-cropper-wrapper" style="position: relative;">
                <div class="avatar-cropper-canvas" style="max-width: 320px; max-height: 320px; margin-bottom: 10px;">
                    <img id="avatar-cropper-img" src="${imageSrc}" style="max-width: 100%; display: block;">
                </div>
                <div class="avatar-cropper-actions" style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="button" class="button" id="avatar-apply-btn" style="background:#a8d5ba; color:#1a1a1a; border:none; padding:10px 20px; border-radius:4px; font-weight:600;">Use Avatar</button>
                    <button type="button" class="button" id="avatar-cancel-btn" style="background:#4a4a4a; color:#fff; border:none; padding:10px 20px; border-radius:4px; font-weight:600;">Cancel</button>
                </div>
                <div id="avatar-status" style="margin-top:8px; font-size:13px; color:#a8d5ba;"></div>
            </div>
        `);

        container.append(wrapper);

        const imgEl = document.getElementById('avatar-cropper-img');
        if (avatarCropper) {
            avatarCropper.destroy();
            avatarCropper = null;
        }

        if (typeof Cropper === 'undefined') {
            console.error('Cropper.js not loaded');
            $('#avatar-status').html('<span style="color:#ff6b6b;">Cropper.js not loaded</span>');
            return;
        }

        avatarCropper = new Cropper(imgEl, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.8,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
        });

        $('#avatar-apply-btn').on('click', function() {
            if (!avatarCropper) return;
            const canvas = avatarCropper.getCroppedCanvas({ width: 500, height: 500 });
            if (!canvas) return;
            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            avatarCropped = dataUrl;
            showFinalAvatar(dataUrl);
            avatarCropper.destroy();
            avatarCropper = null;
        });

        $('#avatar-cancel-btn').on('click', function() {
            if (avatarCropper) {
                avatarCropper.destroy();
                avatarCropper = null;
            }
            container.empty();
            // restore previous preview if any
            if (avatarCropped) {
                showFinalAvatar(avatarCropped);
            }
        });
    }
    
    // Initialize header cropper
    function initHeaderCropper(imageSrc) {
        const container = $('#header-preview-container');
        container.empty();
        
        const wrapper = $(`
            <div class="header-cropper-wrapper" style="position: relative;">
                <div class="header-cropper-canvas" style="max-width: 100%; max-height: 400px; margin-bottom: 10px;">
                    <img id="header-cropper-img" src="${imageSrc}" style="max-width: 100%; display: block;">
                </div>
                <div class="header-cropper-actions" style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
                    <button type="button" class="button" id="header-apply-btn" style="background:#a8d5ba; color:#1a1a1a; border:none; padding:10px 20px; border-radius:4px; font-weight:600;">Use Header</button>
                    <button type="button" class="button" id="header-cancel-btn" style="background:#4a4a4a; color:#fff; border:none; padding:10px 20px; border-radius:4px; font-weight:600;">Cancel</button>
                </div>
                <div id="header-status" style="margin-top:8px; font-size:13px; color:#a8d5ba;"></div>
            </div>
        `);

        container.append(wrapper);

        const imgEl = document.getElementById('header-cropper-img');
        if (headerCropper) {
            headerCropper.destroy();
            headerCropper = null;
        }

        if (typeof Cropper === 'undefined') {
            console.error('Cropper.js not loaded');
            $('#header-status').html('<span style="color:#ff6b6b;">Cropper.js not loaded</span>');
            return;
        }

        headerCropper = new Cropper(imgEl, {
            aspectRatio: 4 / 1, // 4:1 ratio for headers
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.8,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            minCropBoxWidth: 400,
            minCropBoxHeight: 100,
        });

        $('#header-apply-btn').on('click', function() {
            if (!headerCropper) return;
            // Crop to recommended header size: 1200x300 (4:1 ratio)
            const canvas = headerCropper.getCroppedCanvas({ 
                width: 1200, 
                height: 300,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });
            if (!canvas) {
                $('#header-status').html('<span style="color:#ff6b6b;">Failed to crop image</span>');
                return;
            }
            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            headerCropped = dataUrl;
            showFinalHeader(dataUrl);
            headerCropper.destroy();
            headerCropper = null;
        });

        $('#header-cancel-btn').on('click', function() {
            if (headerCropper) {
                headerCropper.destroy();
                headerCropper = null;
            }
            container.empty();
            if (headerCropped) {
                showFinalHeader(headerCropped);
            }
        });
    }
    
    // Show final avatar preview
    function showFinalAvatar(dataUrl) {
        const container = $('#avatar-preview-container');
        container.html(`
            <div class="final-avatar">
                <img src="${dataUrl}" alt="Avatar Preview" style="max-width: 200px; max-height: 200px; border-radius: 50%; border: 2px solid #4a4a4a;">
                <div>
                    <button type="button" class="button change-avatar" style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600;">Change Avatar</button>
                </div>
            </div>
        `);
        
        container.find('.change-avatar').on('click', function() {
            $('#group-avatar-input').trigger('click');
        });
    }
    
    // Show final header preview
    function showFinalHeader(dataUrl) {
        const container = $('#header-preview-container');
        container.html(`
            <div class="final-header">
                <img src="${dataUrl}" alt="Header Preview" style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 2px solid #4a4a4a;">
                <div>
                    <button type="button" class="button change-header" style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600;">Change Header</button>
                </div>
            </div>
        `);
        
        container.find('.change-header').on('click', function() {
            $('#group-header-input').trigger('click');
        });
    }
    
    // Unique name validation and preview
    // Unique Handle validation and preview
    $('#group_unique_handle').on('input', function() {
        let handle = $(this).val().toLowerCase().replace(/[^a-z0-9-]/g, '');
        $(this).val(handle);
        $('#unique-handle-preview').text(handle || '<?php echo esc_js(Simuze_Group::slugify_handle($group->name)); ?>');
        
        const statusDiv = $('#unique-handle-status');
        
        if (handle.length < 3) {
            statusDiv.html('<span style="color: #ff6b6b;"><i class="fas fa-exclamation-circle"></i> Handle must be at least 3 characters</span>').show();
            return;
        }
        
        if (handle.length > 64) {
            statusDiv.html('<span style="color: #ff6b6b;"><i class="fas fa-exclamation-circle"></i> Handle must be 64 characters or less</span>').show();
            return;
        }
        
        // Check if handle is available
        $.ajax({
            url: simuzeCommunity.apiUrl + 'groups/check-unique-handle',
            method: 'POST',
            data: JSON.stringify({
                unique_handle: handle,
                group_id: <?php echo $group->id; ?>
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function(response) {
                if (response.is_unique) {
                    statusDiv.html('<span style="color: #a8d5ba;"><i class="fas fa-check-circle"></i> Available!</span>').show();
                } else {
                    statusDiv.html('<span style="color: #ff6b6b;"><i class="fas fa-times-circle"></i> Already taken</span>').show();
                }
            },
            error: function() {
                statusDiv.html('<span style="color: #ff6b6b;">Error checking availability</span>').show();
            }
        });
    }).trigger('input'); // Trigger on load to show initial status
    
    // Auto-generate handle from name if empty
    $('#group_name').on('input', function() {
        const handleInput = $('#group_unique_handle');
        if (!handleInput.val() || handleInput.data('auto-generated')) {
            const handle = $(this).val().toLowerCase().replace(/[^a-z0-9-]/g, '').replace(/\s+/g, '-');
            handleInput.val(handle).data('auto-generated', true);
            handleInput.trigger('input');
        }
    });
    
    // Form submission
    $('#group-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            name: $('#group_name').val(),
            unique_handle: $('#group_unique_handle').val() || null,
            unique_name: $('#group_unique_name').val() || null, // Legacy support
            description: $('#group_description').val(),
            status: $('#group_status').val(),
            settings: {
                payment_recipient_user_id: $('#payment_recipient').val() || null,
                genre: $('#group_genre').length ? $('#group_genre').val() || null : null
            }
        };
        
        // Add cropped images if available
        if (avatarCropped) {
            formData.avatar_cropped = avatarCropped;
        }
        
        if (headerCropped) {
            formData.header_cropped = headerCropped;
        }
        
        // Show loading
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'groups/' + <?php echo $group->id; ?>,
            method: 'PUT',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function(response) {
                submitBtn.prop('disabled', false).html(originalText);
                
                // Show success message
                $('<div class="notice notice-success" style="background: #2a4a2a; border: 1px solid #a8d5ba; color: #a8d5ba; padding: 15px; border-radius: 4px; margin-top: 15px;"><p>Settings saved successfully!</p></div>')
                    .insertBefore('#group-settings-form')
                    .delay(3000)
                    .fadeOut();
                
                // Reload page to show updated images
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).html(originalText);
                
                let errorMessage = 'Failed to save settings';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                $('<div class="notice notice-error" style="background: #4a2a2a; border: 1px solid #ff6b6b; color: #ff6b6b; padding: 15px; border-radius: 4px; margin-top: 15px;"><p>' + errorMessage + '</p></div>')
                    .insertBefore('#group-settings-form')
                    .delay(5000)
                    .fadeOut();
            }
        });
    });
    
    // Group Payment Settings Functionality (matching Account Settings behavior)
    const groupId = <?php echo $group->id; ?>;
    
    function showGroupPaymentStatusMessage(elementId, message, type) {
        const el = document.getElementById(elementId);
        if (el) {
            const color = type === 'success' ? '#a8d5ba' : type === 'error' ? '#ff6b6b' : '#999';
            el.innerHTML = '<span style="color: ' + color + ';">' + message + '</span>';
            setTimeout(function() {
                el.innerHTML = '';
            }, 5000);
        }
    }
    
    function populateGroupPaymentSettings(data) {
        // Populate PayPal settings
        if (data.paypal) {
            const paypalEmail = data.paypal.email || '';
            if (paypalEmail) {
                document.getElementById('group-paypal-email').value = paypalEmail;
            }
            
            const paypalConnected = data.paypal.connected === true || 
                                   data.paypal.status === 'connected' ||
                                   data.paypal.status === 'active' ||
                                   (paypalEmail && paypalEmail.length > 0);
            
            if (paypalConnected) {
                document.getElementById('group-paypal-connection-status').style.display = 'block';
            } else {
                document.getElementById('group-paypal-connection-status').style.display = 'none';
            }
        }
        
        // Populate Stripe settings
        if (data.stripe) {
            const stripeKey = data.stripe.publishable_key || '';
            if (stripeKey) {
                document.getElementById('group-stripe-publishable-key').value = stripeKey;
            }
            if (data.stripe.secret_key) {
                document.getElementById('group-stripe-secret-key').placeholder = '•••••••• (encrypted)';
            }
            
            const stripeConnected = data.stripe.connected === true ||
                                   data.stripe.status === 'connected' ||
                                   data.stripe.status === 'active' ||
                                   (stripeKey && stripeKey.length > 0);
            
            if (stripeConnected) {
                document.getElementById('group-stripe-connection-status').style.display = 'block';
            } else {
                document.getElementById('group-stripe-connection-status').style.display = 'none';
            }
        }
        
        // Populate Credit Card settings
        if (data.credit_card !== undefined) {
            document.getElementById('group-enable-credit-card').checked = data.credit_card.enabled || false;
        }
        
        // Populate Default Payment Method
        if (data.default_payment_method) {
            document.getElementById('group-default-payment-method').value = data.default_payment_method;
        }
    }
    
    // Load group payment settings
    function loadGroupPaymentSettings() {
        // Get payment settings from group_payments table
        $.ajax({
            url: simuzeCommunity.apiUrl + 'groups/' + groupId + '/payment-settings',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            success: function(response) {
                const paymentData = response.data || response;
                if (paymentData) {
                    populateGroupPaymentSettings(paymentData);
                }
            },
            error: function(err) {
                console.error('Error loading group payment settings:', err);
            }
        });
    }
    
    // Save PayPal settings
    function saveGroupPayPalSettings() {
        const email = document.getElementById('group-paypal-email').value.trim();
        if (!email || !email.includes('@')) {
            showGroupPaymentStatusMessage('group-paypal-status-message', 'Please enter a valid email address', 'error');
            return;
        }
        
        const btn = document.getElementById('save-group-paypal-btn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'groups/' + groupId + '/payment-settings',
            method: 'POST',
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            data: JSON.stringify({
                payment_method_type: 'new',
                paypal_email: email
            }),
            success: function(data) {
                if (data.success) {
                    showGroupPaymentStatusMessage('group-paypal-status-message', 'PayPal settings saved successfully', 'success');
                    document.getElementById('group-paypal-connection-status').style.display = 'block';
                    loadGroupPaymentSettings();
                } else {
                    showGroupPaymentStatusMessage('group-paypal-status-message', data.message || 'Failed to save PayPal settings', 'error');
                }
            },
            error: function(err) {
                console.error('Save PayPal error:', err);
                showGroupPaymentStatusMessage('group-paypal-status-message', 'Failed to save PayPal settings', 'error');
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
    
    // Save Stripe settings
    function saveGroupStripeSettings() {
        const publishableKey = document.getElementById('group-stripe-publishable-key').value.trim();
        const secretKey = document.getElementById('group-stripe-secret-key').value.trim();
        
        if (!publishableKey) {
            showGroupPaymentStatusMessage('group-stripe-status-message', 'Please enter a publishable key', 'error');
            return;
        }
        
        const btn = document.getElementById('save-group-stripe-btn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        const payload = {
            payment_method_type: 'new',
            stripe_connect_id: publishableKey
        };
        if (secretKey) {
            payload.stripe_secret_key = secretKey;
        }
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'groups/' + groupId + '/payment-settings',
            method: 'POST',
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            data: JSON.stringify(payload),
            success: function(data) {
                if (data.success) {
                    showGroupPaymentStatusMessage('group-stripe-status-message', 'Stripe settings saved successfully', 'success');
                    document.getElementById('group-stripe-connection-status').style.display = 'block';
                    document.getElementById('group-stripe-secret-key').value = '';
                    document.getElementById('group-stripe-secret-key').placeholder = '•••••••• (encrypted)';
                    loadGroupPaymentSettings();
                } else {
                    showGroupPaymentStatusMessage('group-stripe-status-message', data.message || 'Failed to save Stripe settings', 'error');
                }
            },
            error: function(err) {
                console.error('Save Stripe error:', err);
                showGroupPaymentStatusMessage('group-stripe-status-message', 'Failed to save Stripe settings', 'error');
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
    
    // Save Credit Card settings
    function saveGroupCreditCardSettings() {
        const enabled = document.getElementById('group-enable-credit-card').checked;
        
        const btn = document.getElementById('save-group-credit-card-btn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'groups/' + groupId + '/payment-settings',
            method: 'POST',
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            data: JSON.stringify({
                credit_card: {
                    enabled: enabled
                }
            }),
            success: function(data) {
                if (data.success) {
                    showGroupPaymentStatusMessage('group-credit-card-status-message', 'Credit card settings saved successfully', 'success');
                    loadGroupPaymentSettings();
                } else {
                    showGroupPaymentStatusMessage('group-credit-card-status-message', data.message || 'Failed to save credit card settings', 'error');
                }
            },
            error: function(err) {
                console.error('Save Credit Card error:', err);
                showGroupPaymentStatusMessage('group-credit-card-status-message', 'Failed to save credit card settings', 'error');
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
    
    // Save Default Payment Method
    function saveGroupDefaultPaymentMethod() {
        const method = document.getElementById('group-default-payment-method').value;
        
        const btn = document.getElementById('save-group-default-payment-btn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'groups/' + groupId + '/payment-settings',
            method: 'POST',
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            data: JSON.stringify({
                default_payment_method: method
            }),
            success: function(data) {
                if (data.success) {
                    showGroupPaymentStatusMessage('group-default-payment-status-message', 'Default payment method saved successfully', 'success');
                    loadGroupPaymentSettings();
                } else {
                    showGroupPaymentStatusMessage('group-default-payment-status-message', data.message || 'Failed to save default payment method', 'error');
                }
            },
            error: function(err) {
                console.error('Save Default Payment error:', err);
                showGroupPaymentStatusMessage('group-default-payment-status-message', 'Failed to save default payment method', 'error');
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
    
    // Test connection functions
    function testGroupPayPalConnection() {
        const btn = document.getElementById('test-group-paypal-btn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'groups/' + groupId + '/payment-settings/test',
            method: 'POST',
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            data: JSON.stringify({
                payment_method_type: 'new',
                paypal_email: document.getElementById('group-paypal-email').value.trim()
            }),
            success: function(data) {
                if (data.success) {
                    showGroupPaymentStatusMessage('group-paypal-status-message', 'PayPal connection test successful', 'success');
                } else {
                    showGroupPaymentStatusMessage('group-paypal-status-message', data.message || 'PayPal connection test failed', 'error');
                }
            },
            error: function(err) {
                console.error('Test PayPal error:', err);
                showGroupPaymentStatusMessage('group-paypal-status-message', 'Connection test failed', 'error');
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
    
    function testGroupStripeConnection() {
        const btn = document.getElementById('test-group-stripe-btn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
        
        $.ajax({
            url: simuzeCommunity.apiUrl + 'groups/' + groupId + '/payment-settings/test',
            method: 'POST',
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
            },
            data: JSON.stringify({
                payment_method_type: 'new',
                stripe_connect_id: document.getElementById('group-stripe-publishable-key').value.trim()
            }),
            success: function(data) {
                if (data.success) {
                    showGroupPaymentStatusMessage('group-stripe-status-message', 'Stripe connection test successful', 'success');
                } else {
                    showGroupPaymentStatusMessage('group-stripe-status-message', data.message || 'Stripe connection test failed', 'error');
                }
            },
            error: function(err) {
                console.error('Test Stripe error:', err);
                showGroupPaymentStatusMessage('group-stripe-status-message', 'Connection test failed', 'error');
            },
            complete: function() {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
    
    // Initialize group payment settings
    loadGroupPaymentSettings();
    
    // Bind event handlers
    document.getElementById('save-group-paypal-btn').addEventListener('click', saveGroupPayPalSettings);
    document.getElementById('save-group-stripe-btn').addEventListener('click', saveGroupStripeSettings);
    document.getElementById('save-group-credit-card-btn').addEventListener('click', saveGroupCreditCardSettings);
    document.getElementById('save-group-default-payment-btn').addEventListener('click', saveGroupDefaultPaymentMethod);
    document.getElementById('test-group-paypal-btn').addEventListener('click', testGroupPayPalConnection);
    document.getElementById('test-group-stripe-btn').addEventListener('click', testGroupStripeConnection);
    
    // Delete Group Modal Functionality
    const deleteGroupModal = document.getElementById('delete-group-modal');
    const deleteGroupBtn = document.querySelector('.delete-group');
    const cancelDeleteBtn = document.getElementById('cancel-delete-group');
    const confirmDeleteBtn = document.getElementById('confirm-delete-group');
    
    // Show modal when delete button is clicked
    if (deleteGroupBtn) {
        deleteGroupBtn.addEventListener('click', function(e) {
            e.preventDefault();
            deleteGroupModal.classList.add('show');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        });
    }
    
    // Hide modal when cancel is clicked
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            deleteGroupModal.classList.remove('show');
            document.body.style.overflow = ''; // Restore scrolling
        });
    }
    
    // Close modal when clicking outside
    deleteGroupModal.addEventListener('click', function(e) {
        if (e.target === deleteGroupModal) {
            deleteGroupModal.classList.remove('show');
            document.body.style.overflow = ''; // Restore scrolling
        }
    });
    
    // Confirm delete
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            const groupId = deleteGroupBtn.getAttribute('data-group-id');
            const originalText = confirmDeleteBtn.innerHTML;
            
            confirmDeleteBtn.disabled = true;
            confirmDeleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
            
            $.ajax({
                url: simuzeCommunity.apiUrl + 'groups/' + groupId,
                method: 'DELETE',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', simuzeCommunity.nonce);
                },
                success: function(response) {
                    // Show success message
                    $('<div class="notice notice-success" style="background: #2a4a2a; border: 1px solid #a8d5ba; color: #a8d5ba; padding: 15px; border-radius: 4px; margin-top: 15px; position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 10001;"><p>Group deleted successfully. Redirecting...</p></div>')
                        .appendTo('body')
                        .delay(2000)
                        .fadeOut();
                    
                    // Redirect to user dashboard (front-end, not WordPress admin)
                    setTimeout(function() {
                        // Get current user's dashboard URL - ensure it's front-end, not admin
                        const dashboardUrl = simuzeCommunity.homeUrl.replace(/\/wp-admin\/?$/, '') + '/dashboard/';
                        window.location.href = dashboardUrl;
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.innerHTML = originalText;
                    
                    let errorMessage = 'Failed to delete group';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    $('<div class="notice notice-error" style="background: #4a2a2a; border: 1px solid #ff6b6b; color: #ff6b6b; padding: 15px; border-radius: 4px; margin-top: 15px; position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 10001;"><p>' + errorMessage + '</p></div>')
                        .appendTo('body')
                        .delay(5000)
                        .fadeOut();
                    
                    deleteGroupModal.classList.remove('show');
                    document.body.style.overflow = ''; // Restore scrolling
                }
            });
        });
    }
});
</script>



