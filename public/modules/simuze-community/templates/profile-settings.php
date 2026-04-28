<?php
/**
 * User Profile Settings Template
 * Mirrors Simezu Account Settings - all profile data stored in Simezu
 * Local storage: header image, bio, websites (repeater), Simezu ID
 */

if (!defined('ABSPATH') && !defined('SIMUZE_MODULAR_RUN')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();

if (!$current_user || $current_user->id !== $user->id) {
    echo '<p>Access denied.</p>';
    return;
}

// Get Simezu user ID for API calls
$simezu_user_id = $user->simezu_id ?? '';

// Get current avatar URL from Simezu (direct image endpoint for instant load)
$avatar_direct_url = '';
if (!empty($simezu_user_id)) {
    $avatar_direct_url = md_home_url('/simezu/api/auth/users/' . rawurlencode($simezu_user_id) . '/avatar/image?size=240');
}

// Get local header URL (stored in Simuze)
$current_header_url = $user->header_url ?? '';

// Get local bio and websites (stored in Simuze, not Simezu)
$local_bio = $user->profile['bio'] ?? '';
$local_websites = $user->profile['websites'] ?? [];
// Local fallbacks for Simezu-sourced fields (populated by JS on load, but pre-filled here as fallback)
$local_first_name = $user->profile['first_name'] ?? '';
$local_last_name  = $user->profile['last_name'] ?? '';
$local_location   = $user->profile['location'] ?? '';
if (!is_array($local_websites)) {
    $local_websites = !empty($local_websites) ? [$local_websites] : [];
}
?>

<div class="profile-settings" style="background: #1a1a1a; color: #fff; padding: 30px; border-radius: 8px;">
    <h2 style="color: #a8d5ba; margin-bottom: 30px; border-bottom: 2px solid #a8d5ba; padding-bottom: 10px;">Account Settings</h2>
    
    <form id="profile-settings-form" class="simuze-form">
        
        <!-- Avatar Upload Section -->
        <div class="form-group avatar-row">
            <div class="avatar-info">
                <label>
                <i class="fas fa-user-circle"></i> Profile Avatar
            </label>
                <small>Recommended: Square image, minimum 500x500px, max 2MB. Avatar is synced with Simezu.</small>
            </div>
            <div class="avatar-flex">
                <div id="avatar-preview-container">
                    <div class="current-avatar">
                        <img src="<?php echo esc_url($avatar_direct_url); ?>" alt="Current Avatar" 
                         id="avatar-preview-img"
                             data-simezu-user-id="<?php echo esc_attr($simezu_user_id); ?>"
                             onerror="this.onerror=null; this.style.display='none'; var fallback = document.getElementById('avatar-initials-fallback'); if (fallback) fallback.style.display='flex';">
                        <div class="avatar-initials-fallback" id="avatar-initials-fallback"></div>
                </div>
            </div>
                <div class="avatar-actions">
            <input type="file" id="user-avatar-input" name="avatar" accept="image/*" style="display: none;">
                    <button type="button" id="browse-avatar-btn" class="button">
                <i class="fas fa-upload"></i> Change Avatar
            </button>
                </div>
            </div>
        </div>
        
        <!-- Header Upload Section (Local to Simuze) -->
        <div class="form-group" style="margin-bottom: 30px;">
            <label style="color: #fff; font-weight: 600; margin-bottom: 15px; display: block; border-bottom: 2px solid #a8d5ba; padding-bottom: 8px;">
                <i class="fas fa-image"></i> Header Image
            </label>
            <div id="header-preview-container" style="margin-bottom: 15px;">
                <?php if ($current_header_url): ?>
                    <div class="current-header" style="margin-bottom: 15px;">
                        <img src="<?php echo esc_url($current_header_url); ?>" alt="Current Header" 
                             id="header-preview-img"
                             style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 2px solid #4a4a4a; display: block;">
                    </div>
                <?php else: ?>
                    <div class="no-header-placeholder" style="width: 100%; height: 200px; background: #2a2a2a; border: 2px dashed #4a4a4a; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999;">
                        <i class="fas fa-image" style="font-size: 60px;"></i>
                    </div>
                <?php endif; ?>
            </div>
            <input type="file" id="user-header-input" name="header" accept="image/*" style="display: none;">
            <button type="button" id="browse-header-btn" class="button" 
                    style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                <i class="fas fa-upload"></i> <?php echo $current_header_url ? 'Change Header' : 'Upload Header'; ?>
            </button>
            <small style="display: block; margin-top: 8px; color: #999;">Recommended: 1200x300px (4:1 aspect ratio). Stored locally in Simuze.</small>
        </div>
        
        <!-- Profile Fields (Synced with Simezu) -->
        <div class="form-row" style="display: flex; gap: 16px; margin-bottom: 20px;">
            <div class="form-group" style="flex: 1;">
                <label for="first_name" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($local_first_name); ?>"
                       style="width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="last_name" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($local_last_name); ?>"
                       style="width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label for="display_name" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">Display Name</label>
            <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" 
                   style="width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">
        </div>
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="email" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">Email</label>
            <input type="email" id="email" name="email" value="" readonly
                   style="width: 100%; padding: 10px 12px; background: #3a3a3a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #999; cursor: not-allowed;">
            <small style="display: block; margin-top: 8px; color: #999;">Email is managed in Simezu.</small>
            </div>
            
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="unique_id" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">Unique Identifier</label>
            <div style="display: flex; gap: 8px; align-items: flex-start;">
                <input type="text" id="unique_id" name="unique_id" value="" 
                       style="flex: 1; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">
                <button type="button" id="check-unique-id-btn" class="button" 
                        style="background: #4a4a4a; color: #fff; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; white-space: nowrap;">
                    Check
                </button>
            </div>
            <div id="unique-id-status" style="margin-top: 8px; font-size: 13px;"></div>
            <small style="display: block; margin-top: 8px; color: #999;">Letters, numbers, dash, underscore. Must be unique.</small>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
                <label for="location" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">Location</label>
            <input type="text" id="location" name="location" value="<?php echo esc_attr($local_location); ?>"
                       style="width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">
            </div>
            
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="simezu_id" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">Simezu ID</label>
            <input type="text" id="simezu_id" name="simezu_id" value="<?php echo esc_attr($simezu_user_id); ?>" 
                   readonly
                   style="width: 100%; padding: 10px 12px; background: #3a3a3a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #999; cursor: not-allowed;">
            <small style="display: block; margin-top: 8px; color: #999;">Your unique identifier in the Simezu network.</small>
        </div>

        <!-- Local Fields (Stored in Simuze, not Simezu) -->
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="bio" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">Bio</label>
            <textarea id="bio" name="bio" rows="4"
                      style="width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff; resize: vertical;"><?php echo esc_textarea($local_bio); ?></textarea>
            <small style="display: block; margin-top: 8px; color: #999;">Stored locally in Simuze.</small>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">Websites</label>
            <div id="websites-repeater-container" style="display: flex; flex-direction: column; gap: 10px;">
                <?php 
                if (empty($local_websites)) {
                    $local_websites = [''];
                }
                foreach ($local_websites as $index => $website_url): ?>
                    <div class="website-item" style="display: flex; gap: 8px; align-items: center;">
                        <input type="url" name="websites[]" value="<?php echo esc_attr($website_url); ?>" 
                               placeholder="https://yourwebsite.com"
                               style="flex: 1; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">
                        <button type="button" class="remove-website-btn button" style="background: #ff6b6b; color: #fff; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-website-btn" class="button" 
                    style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; margin-top: 10px;">
                <i class="fas fa-plus"></i> Add Website
            </button>
            <small style="display: block; margin-top: 8px; color: #999;">Stored locally in Simuze.</small>
        </div>

        <!-- Password Change Section -->
        <div class="form-group" style="margin-bottom: 20px; margin-top: 40px; padding-top: 20px; border-top: 2px solid #4a4a4a;">
            <label style="color: #fff; font-weight: 600; margin-bottom: 15px; display: block; border-bottom: 2px solid #a8d5ba; padding-bottom: 8px;">
                <i class="fas fa-lock"></i> Change Password
            </label>
            <div class="form-group" style="margin-bottom: 15px;">
                <label for="current_password" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">Current Password</label>
                <input type="password" id="current_password" name="current_password" 
                       style="width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label for="new_password" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">New Password</label>
                <input type="password" id="new_password" name="new_password" 
                       style="width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label for="confirm_password" style="color: #fff; font-weight: 600; margin-bottom: 8px; display: block;">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" 
                       style="width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">
            </div>
            <button type="button" id="change-password-btn" class="button" 
                    style="background: #4a4a4a; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                <i class="fas fa-key"></i> Change Password
            </button>
            <div id="password-change-status" style="margin-top: 10px; font-size: 13px;"></div>
        </div>
        
        <div class="form-actions" style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #4a4a4a;">
            <button type="submit" class="button primary" 
                    style="background: #a8d5ba; color: #1a1a1a; border: none; padding: 12px 25px; border-radius: 4px; cursor: pointer; font-weight: 700; font-size: 16px;">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<script src="<?php echo theme_uri('/js/complex-cropper.js'); ?>"></script>

<style>
/* Layout tweaks to prevent avatar overlapping other fields */
.profile-settings .avatar-row {
    margin-bottom: 30px;
    padding-bottom: 12px;
    border-bottom: 2px solid #4a4a4a;
}

.profile-settings .avatar-info label {
    color: #fff;
    font-weight: 600;
    display: block;
    margin-bottom: 6px;
    border-bottom: 2px solid #a8d5ba;
    padding-bottom: 6px;
}

.profile-settings .avatar-info small {
    color: #999;
}

.profile-settings .avatar-flex {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 12px;
}

.profile-settings .current-avatar {
    position: relative;
    width: 180px;
    height: 180px;
}

.profile-settings #avatar-preview-img,
.profile-settings .avatar-initials-fallback {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    border: 2px solid #4a4a4a;
    object-fit: cover;
    display: block;
    background: #2a2a2a;
}

.profile-settings .avatar-initials-fallback {
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 58px;
    font-weight: 700;
    color: #fff;
}

.profile-settings .avatar-actions .button {
    background: #a8d5ba;
    color: #1a1a1a;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}

.profile-settings .avatar-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.avatar-cropper-wrapper {
    background: #1f1f1f;
    border: 1px solid #4a4a4a;
    border-radius: 8px;
    padding: 12px;
    max-width: 360px;
}

.avatar-cropper-canvas img {
    border-radius: 8px;
}

.profile-settings .header-preview-img {
    max-width: 100%;
    max-height: 360px;
    border-radius: 10px;
    border: 2px solid #4a4a4a;
    object-fit: cover;
}

.profile-settings .final-header .button.change-header {
    background: #a8d5ba;
    color: #1a1a1a;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    margin-top: 10px;
}

/* ComplexCropper Styling */
.complex-cropper {
    background: #2a2a2a;
    border: 1px solid #4a4a4a;
    border-radius: 8px;
    padding: 20px;
    margin-top: 15px;
}

.crop-container {
    position: relative;
}

.crop-workspace {
    position: relative;
    overflow: hidden;
    background: #1a1a1a;
    border-radius: 4px;
}

.crop-area {
    position: relative;
    display: inline-block;
}

.crop-area img {
    display: block;
    max-width: 100%;
    height: auto;
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

.resize-handle {
    position: absolute;
    width: 10px;
    height: 10px;
    background: #a8d5ba;
    border: 2px solid #1a1a1a;
    border-radius: 50%;
    cursor: pointer;
}

.resize-handle.top-left { top: -5px; left: -5px; cursor: nw-resize; }
.resize-handle.top { top: -5px; left: 50%; transform: translateX(-50%); cursor: n-resize; }
.resize-handle.top-right { top: -5px; right: -5px; cursor: ne-resize; }
.resize-handle.right { right: -5px; top: 50%; transform: translateY(-50%); cursor: e-resize; }
.resize-handle.bottom-right { bottom: -5px; right: -5px; cursor: se-resize; }
.resize-handle.bottom { bottom: -5px; left: 50%; transform: translateX(-50%); cursor: s-resize; }
.resize-handle.bottom-left { bottom: -5px; left: -5px; cursor: sw-resize; }
.resize-handle.left { left: -5px; top: 50%; transform: translateY(-50%); cursor: w-resize; }

.crop-tools {
    margin-top: 15px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.tool-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.tool-group label {
    color: #fff;
    font-size: 14px;
    font-weight: 600;
}

.tool-group select,
.tool-group input[type="range"] {
    background: #2a2a2a;
    border: 1px solid #4a4a4a;
    color: #fff;
    padding: 6px 10px;
    border-radius: 4px;
}

.crop-button {
    background: #a8d5ba !important;
    color: #1a1a1a !important;
    border: none !important;
    padding: 10px 20px !important;
    border-radius: 4px !important;
    cursor: pointer !important;
    font-weight: 600 !important;
    font-size: 14px !important;
}

.crop-button:hover {
    background: #8fc5a8 !important;
}

.crop-workspace-wrapper {
    display: flex;
    gap: 20px;
    align-items: flex-start;
    margin-bottom: 20px;
}

.crop-workspace {
    flex: 1;
    min-width: 0;
}

.preview-sidebar {
    flex: 0 0 300px;
    background: #2a2a2a;
    border: 1px solid #4a4a4a;
    border-radius: 8px;
    padding: 15px;
}

.preview-sidebar.hidden {
    display: none;
}

.preview-container {
    margin-bottom: 15px;
}

.preview-canvas {
    max-width: 100%;
    width: 100%;
    height: auto;
    border-radius: 4px;
    border: 1px solid #4a4a4a;
}

.preview-info {
    color: #fff;
    font-size: 14px;
    line-height: 1.6;
}

.preview-info p {
    margin: 8px 0;
}

.preview-info strong {
    color: #a8d5ba;
}

.preview-controls {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 20px;
    padding: 15px;
    background: #2a2a2a;
    border-radius: 8px;
}

.preview-controls.hidden {
    display: none;
}

.preview-controls .button {
    background: #a8d5ba;
    color: #1a1a1a;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}

.preview-controls .button:hover {
    background: #8fc5a8;
}

.crop-workspace.hidden {
    display: none;
}

.final-avatar,
.final-header {
    text-align: center;
}

.final-avatar img {
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
    border: 2px solid #4a4a4a;
    margin-bottom: 15px;
}
</style>

<script>
// Simezu API Integration
(function() {
    var SIMEZU_APP_ID = '85912f38-cb0f-11f0-89cd-d6acdb43bded';
    var SIMEZU_API_KEY = '85912f42-cb0f-11f0-89cd-d6acdb43bded';
    var SIMEZU_BASE_URL = '/simezu';
    var SIMEZU_USER_ID = '<?php echo esc_js($simezu_user_id); ?>';

    let lastSimezuProfile = null;

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }

    function getAccessToken() {
        // Prefer SimezuAuth helper if available
        try {
            if (typeof SimezuAuth !== 'undefined') {
                if (typeof SimezuAuth.getToken === 'function') {
                    var t = SimezuAuth.getToken();
                    if (t) return t;
                }
                if (typeof SimezuAuth.getAccessToken === 'function') {
                    return SimezuAuth.getAccessToken();
                }
            }
        } catch (e) {
            console.warn('SimezuAuth token fetch failed', e);
        }
        // Cookie/localStorage fallbacks
        return getCookie('accessToken') ||
               getCookie('simuze_jwt_token') ||
               getCookie('simezu_token') ||
               window.localStorage.getItem('simezu_token') ||
               null;
    }

    function getCSRFToken() {
        // Try to get CSRF token from cookie or meta tag
        return getCookie('csrf_token') || getCookie('X-CSRF-Token') || 
               (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';
    }

    function simezuHeaders(token, includeCSRF) {
        var headers = {
            'X-App-ID': SIMEZU_APP_ID,
            'X-API-Key': SIMEZU_API_KEY,
            'Authorization': 'Bearer ' + token
        };
        if (includeCSRF) {
            var csrf = getCSRFToken();
            if (csrf) {
                headers['X-CSRF-Token'] = csrf;
            }
        }
        return headers;
    }

    function getInitials(name) {
        if (!name) return '';
        var parts = name.trim().split(/\s+/);
        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }
        return name.slice(0, 2).toUpperCase();
    }
    // Expose for other scopes
    window.getInitials = window.getInitials || getInitials;

    function fetchAvatarForUser(identifier) {
        // Public, no auth: GET /simezu/api/user/avatar?user_id=USER_ID
        if (!identifier) return Promise.resolve(null);
        var url = SIMEZU_BASE_URL + '/api/user/avatar?user_id=' + encodeURIComponent(identifier);
        return fetch(url)
            .then(function(r) {
                if (!r.ok) return null;
                return r.json().catch(function() { return null; });
            })
            .then(function(data) {
                if (data && data.status === 'ok' && data.url) {
                    return data.url;
                }
                return null;
            })
            .catch(function(err) {
                console.warn('Failed to fetch avatar for', identifier, ':', err);
                return null;
            });
    }
    // Expose for outside scope (loadCurrentAvatar)
    window.fetchAvatarForUser = window.fetchAvatarForUser || fetchAvatarForUser;

    function updateAvatarPreview(url, initials) {
        var img = document.getElementById('avatar-preview-img');
        var fallback = document.getElementById('avatar-initials-fallback');
        
        if (url && img) {
            img.src = url;
            img.style.display = 'block';
            if (fallback) fallback.style.display = 'none';
        } else if (initials && fallback) {
            fallback.textContent = initials;
            fallback.style.display = 'flex';
            if (img) img.style.display = 'none';
        }
    }

    // Batch SIMEZU settings loader with localStorage cache (1 hour TTL)
    window.getSimezuSettings = async function(userId) {
        if (!userId) {
            console.warn('[SIMEZU] No user ID provided to getSimezuSettings');
            return null;
        }

        var cacheKey = 'simezu_settings_cache_' + userId;
        var cacheTTL = 60 * 60 * 1000; // 1 hour in milliseconds

        // Check cache first
        try {
            var cached = localStorage.getItem(cacheKey);
            if (cached) {
                var cacheData = JSON.parse(cached);
                var cacheAge = Date.now() - (cacheData.timestamp || 0);
                
                if (cacheAge < cacheTTL && cacheData.data) {
                    console.log('[SIMEZU] Using cached data (age: ' + Math.round(cacheAge / 1000) + 's)');
                    return cacheData.data;
                } else {
                    console.log('[SIMEZU] Cache expired, fetching fresh data');
                    localStorage.removeItem(cacheKey);
                }
            }
        } catch (e) {
            console.warn('[SIMEZU] Cache read error:', e);
        }

        // Fetch from API
        var token = getAccessToken();
        if (!token) {
            console.warn('[SIMEZU] No access token, cannot fetch settings');
            return null;
        }

        console.log('[SIMEZU] Batch request executed for user:', userId);

        // Try batch endpoint first, fallback to standard profile endpoint
        var batchEndpoint = SIMEZU_BASE_URL + '/api/account/batch/' + encodeURIComponent(userId);
        var fallbackEndpoint = SIMEZU_BASE_URL + '/api/auth/users/' + encodeURIComponent(userId);
        if (!userId || userId === '') {
            batchEndpoint = SIMEZU_BASE_URL + '/api/account/batch/me';
            fallbackEndpoint = SIMEZU_BASE_URL + '/api/auth/users/me';
        }

        try {
            // Try batch endpoint first
            var response = await fetch(batchEndpoint, {
                headers: simezuHeaders(token, false),
                credentials: 'include'
            });

            // If batch endpoint doesn't exist (404), use fallback
            if (!response.ok && response.status === 404) {
                console.log('[SIMEZU] Batch endpoint not found, using fallback');
                response = await fetch(fallbackEndpoint, {
                    headers: simezuHeaders(token, false),
                    credentials: 'include'
                });
            }

            if (!response.ok) {
                var errorText = await response.text();
                console.error('[SIMEZU] API error:', response.status, errorText);
                throw new Error('Failed to fetch SIMEZU settings: ' + response.status);
            }

            var data = await response.json();
            
            // Normalize data structure (batch endpoint may return different format)
            var normalizedData = {
                id: data.id || userId,
                unique_identifier: data.unique_identifier || data.unique_id || '',
                first_name: data.first_name || '',
                last_name: data.last_name || '',
                location: data.location || '',
                email: data.email || '',
                display_name: data.display_name || '',
                timezone: data.timezone || '',
                updated_at: data.updated_at || data.updated || '',
                avatar_url: data.avatar_url || data.avatarUrl || data.avatar || '',
                avatarUrl: data.avatar_url || data.avatarUrl || data.avatar || ''
            };

            // Store in cache
            try {
                localStorage.setItem(cacheKey, JSON.stringify({
                    data: normalizedData,
                    timestamp: Date.now()
                }));
                console.log('[SIMEZU] Account settings data cached');
            } catch (e) {
                console.warn('[SIMEZU] Cache write error:', e);
            }

            console.log('[SIMEZU] Account settings data:', normalizedData);
            return normalizedData;

        } catch (err) {
            console.error('[SIMEZU] Batch request failed:', err);
            return null;
        }
    };

    // Prefill form from Simezu - Single batch request
    function prefillFromSimezu() {
        if (!SIMEZU_USER_ID) {
            console.warn('[SIMEZU] No user ID available for prefill');
            return;
        }

        // Show loading states
        showLoadingStates();

        // Use batch loader
        window.getSimezuSettings(SIMEZU_USER_ID).then(function(settings) {
            if (settings) {
                fillFormFromUser(settings);
            } else {
                console.error('[SIMEZU] Failed to load settings');
                hideLoadingStates();
            }
        }).catch(function(err) {
            console.error('[SIMEZU] Error loading settings:', err);
            hideLoadingStates();
        });
    }

    // Show loading skeletons for SIMEZU fields
    function showLoadingStates() {
        var fields = ['first_name', 'last_name', 'display_name', 'email', 'unique_id', 'location'];
        fields.forEach(function(fieldId) {
            var el = document.getElementById(fieldId);
            if (el) {
                el.style.opacity = '0.5';
                el.style.pointerEvents = 'none';
            }
        });
    }

    // Hide loading states
    function hideLoadingStates() {
        var fields = ['first_name', 'last_name', 'display_name', 'email', 'unique_id', 'location'];
        fields.forEach(function(fieldId) {
            var el = document.getElementById(fieldId);
            if (el) {
                el.style.opacity = '1';
                el.style.pointerEvents = '';
            }
        });
    }

    function fillFormFromUser(user) {
        lastSimezuProfile = user || lastSimezuProfile;
        console.log('[SIMEZU] Filling form from user data:', user);
        
        // Fill form fields - ALL from Simezu (as per documentation)
        setValue('display_name', user.display_name || '');
        setValue('first_name', user.first_name || '');
        setValue('last_name', user.last_name || '');
        setValue('email', user.email || '');
        setValue('location', user.location || '');
        setValue('unique_id', user.unique_identifier || user.unique_id || '');
        setValue('simezu_id', user.id || SIMEZU_USER_ID || '');

        // Hide loading states
        hideLoadingStates();

        // Update avatar preview - use avatar_url from batch response
        var displayName = user.display_name || (user.first_name && user.last_name ? (user.first_name + ' ' + user.last_name) : '') || user.email || '';
        var initials = getInitials(displayName);
        
        // Prefer avatar_url from batch response
        var avatarUrl = user.avatar_url || user.avatarUrl || user.avatar;
        if (avatarUrl) {
            var resolved = avatarUrl;
            if (resolved.indexOf('http') !== 0 && resolved.indexOf('/') === 0) {
                resolved = window.location.origin + resolved;
            }
            updateAvatarPreview(resolved, initials);
        } else {
            // Fallback: use direct image endpoint if no URL in response
            var imgEndpoint = SIMEZU_BASE_URL + '/api/auth/users/' + encodeURIComponent(user.id || SIMEZU_USER_ID) + '/avatar/image?size=240';
            updateAvatarPreview(imgEndpoint, initials);
        }
    }

    function setValue(id, value) {
        var el = document.getElementById(id);
        if (el && value !== undefined && value !== null) {
            el.value = value;
        }
    }

    // Check unique identifier availability
    window.checkUniqueIdentifier = function(identifier) {
        if (!identifier || identifier.trim() === '') {
            return Promise.resolve({ available: false, error: 'Identifier cannot be empty' });
        }

        // Validate pattern
        if (!/^[a-zA-Z0-9_-]+$/.test(identifier)) {
            return Promise.resolve({ available: false, error: 'Invalid characters. Use letters, numbers, dash, or underscore.' });
        }

        // GET /simezu/api/auth/users/check-unique-identifier?identifier={candidate}
        return fetch(SIMEZU_BASE_URL + '/api/auth/users/check-unique-identifier?identifier=' + encodeURIComponent(identifier), {
            credentials: 'include'
        })
        .then(function(r) {
            if (r.status === 200) {
                return r.json().then(function(data) {
                    return { available: data.available, identifier: data.identifier };
                });
            } else if (r.status === 409) {
                return r.json().then(function(data) {
                    return { available: false, suggestions: data.suggestions || [] };
                });
            }
            throw new Error('Failed to check identifier');
        })
        .catch(function(err) {
            console.error('Error checking unique identifier:', err);
            return { available: false, error: 'Failed to check availability' };
        });
    };

    // Upload avatar to Simezu
    window.uploadAvatarToSimezu = function(fileBlob, userId) {
        var token = getAccessToken();
        if (!token) {
            console.error('[Avatar Upload] No access token');
            return Promise.reject(new Error('No access token'));
        }

        var formData = new FormData();
        formData.append('file', fileBlob);

        // Simezu supports /api/avatar/user/upload (confirmed) and some stacks use /api/avatar/upload.
        // We try the user/upload endpoint first, then the generic one. Rebuild FormData per attempt.
        var endpoints = [
            SIMEZU_BASE_URL + '/api/avatar/user/upload',
            SIMEZU_BASE_URL + '/api/avatar/upload'
        ];

        var attempt = function(idx) {
            if (idx >= endpoints.length) {
                throw new Error('Failed to upload avatar: no endpoint succeeded');
            }
            var endpoint = endpoints[idx];
            console.log('[Avatar Upload] Sending to:', endpoint, 'attempt', idx + 1);

            // Rebuild formData to avoid body reuse issues across retries
            var fd = new FormData();
            fd.append('file', fileBlob);
            fd.append('user_id', userId || SIMEZU_USER_ID);

            return fetch(endpoint, {
                method: 'POST',
                headers: simezuHeaders(token, true),
                credentials: 'include',
                body: fd
            }).then(function(r) {
                console.log('[Avatar Upload] Response status:', r.status);
                if (r.ok) return r.json();
                return r.text().then(function(text) {
                    console.error('[Avatar Upload] Error response:', text);
                    // Retry next endpoint on 404
                    if (r.status === 404) {
                        console.warn('[Avatar Upload] Endpoint 404, trying fallback if available');
                        return attempt(idx + 1);
                    }
                    throw new Error('Failed to upload avatar: ' + r.status + ' - ' + text);
                });
            });
        };

        return attempt(0)
            .then(function(data) {
                // Expected: { thumb_url, medium_url, full_url, updated_at }
                if (data && (data.full_url || data.thumb_url || data.medium_url)) {
                    var avatarUrl = data.full_url || data.medium_url || data.thumb_url;
                    console.log('[Avatar Upload] Success, avatar URL:', avatarUrl);
                    
                    // Clear cache on successful avatar upload
                    var cacheKey = 'simezu_settings_cache_' + (userId || SIMEZU_USER_ID);
                    localStorage.removeItem(cacheKey);
                    console.log('[SIMEZU] Cache cleared after avatar upload');
                    
                    return avatarUrl;
                }
                console.error('[Avatar Upload] Invalid response format:', data);
                throw new Error('Invalid response format from Simezu');
            })
            .catch(function(err) {
                console.error('[Avatar Upload] Upload failed:', err);
                throw err;
            });
    };

    // Update profile in Simezu
    window.updateSimezuProfile = function(profileData) {
        var token = getAccessToken();
        if (!token) {
            return Promise.reject(new Error('No access token'));
        }

        // PUT /simezu/api/auth/users/{user_id}
        var payload = {
            display_name: profileData.display_name || '',
            first_name: profileData.first_name || '',
            last_name: profileData.last_name || '',
            email: profileData.email || '',
            bio: profileData.bio || '',
            location: profileData.location || '',
            unique_identifier: profileData.unique_id || ''
        };

        return fetch(SIMEZU_BASE_URL + '/api/auth/users/' + encodeURIComponent(SIMEZU_USER_ID), {
            method: 'PUT',
            headers: Object.assign(simezuHeaders(token, true), { 'Content-Type': 'application/json' }),
            credentials: 'include',
            body: JSON.stringify(payload)
        })
        .then(function(r) {
            if (r.ok) {
                // Clear cache on successful update
                var cacheKey = 'simezu_settings_cache_' + SIMEZU_USER_ID;
                localStorage.removeItem(cacheKey);
                console.log('[SIMEZU] Cache cleared after profile update');
                return r.json();
            }
            return r.text().then(function(txt) {
                var msg = 'Failed to update profile';
                if (txt) msg += ': ' + txt;
                throw new Error(msg);
            });
        });
    };

    // Change password in Simezu
    window.changeSimezuPassword = function(newPassword) {
        var token = getAccessToken();
        if (!token) {
            return Promise.reject(new Error('No access token'));
        }

        // PATCH /simezu/api/auth/me
        return fetch(SIMEZU_BASE_URL + '/api/auth/me', {
            method: 'PATCH',
            headers: Object.assign(simezuHeaders(token, true), { 'Content-Type': 'application/json' }),
            credentials: 'include',
            body: JSON.stringify({ password: newPassword })
        })
        .then(function(r) {
            if (r.ok) return r.json();
            throw new Error('Failed to change password');
        });
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', prefillFromSimezu);
        } else {
        prefillFromSimezu();
    }
})();

jQuery(document).ready(function($) {
    let avatarCropped = null;
    let headerCropped = null;
    let avatarCropper = null;
    let headerCropper = null;
    let avatarUploaded = false;
    const simezuUserId = '<?php echo esc_js($simezu_user_id); ?>';
    // Local fallback for initials if global not present
    function localInitials(name) {
        if (typeof getInitials === 'function') return getInitials(name);
        if (!name) return '';
        var parts = (name || '').trim().split(/\s+/);
        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }
        return (name || '').slice(0, 2).toUpperCase();
    }
    
    // Websites repeater handlers
    function addWebsiteField(url) {
        var html = '<div class="website-item" style="display: flex; gap: 8px; align-items: center; margin-bottom: 10px;">' +
                   '<input type="url" name="websites[]" value="' + (url || '') + '" placeholder="https://yourwebsite.com" ' +
                   'style="flex: 1; padding: 10px 12px; background: #2a2a2a; border: 1px solid #4a4a4a; border-radius: 6px; font-size: 14px; color: #fff;">' +
                   '<button type="button" class="remove-website-btn button" style="background: #ff6b6b; color: #fff; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600;">' +
                   '<i class="fas fa-times"></i></button></div>';
        $('#websites-repeater-container').append(html);
    }

    $('#add-website-btn').on('click', function() {
        addWebsiteField('');
    });

    $(document).on('click', '.remove-website-btn', function() {
        $(this).closest('.website-item').remove();
    });
    
    // Unique identifier check
    $('#check-unique-id-btn').on('click', function() {
        var identifier = $('#unique_id').val().trim();
        var statusEl = $('#unique-id-status');
        var btn = $(this);
        
        if (!identifier) {
            statusEl.html('<span style="color: #ff6b6b;">Please enter an identifier</span>');
            return;
        }

        btn.prop('disabled', true).html('Checking...');
        statusEl.html('<span style="color: #a8d5ba;"><i class="fas fa-spinner fa-spin"></i> Checking...</span>');

        window.checkUniqueIdentifier(identifier).then(function(result) {
            btn.prop('disabled', false).html('Check');
            
            if (result.error) {
                statusEl.html('<span style="color: #ff6b6b;">' + result.error + '</span>');
            } else if (result.available) {
                statusEl.html('<span style="color: #a8d5ba;"><i class="fas fa-check"></i> Available!</span>');
            } else {
                var msg = '<span style="color: #ff6b6b;"><i class="fas fa-times"></i> Not available';
                if (result.suggestions && result.suggestions.length > 0) {
                    msg += '. Suggestions: ' + result.suggestions.slice(0, 3).join(', ');
                }
                msg += '</span>';
                statusEl.html(msg);
            }
        }).catch(function(err) {
            btn.prop('disabled', false).html('Check');
            statusEl.html('<span style="color: #ff6b6b;">Error checking availability</span>');
        });
    });
    
    // Password change handler
    $('#change-password-btn').on('click', function() {
        var currentPwd = $('#current_password').val();
        var newPwd = $('#new_password').val();
        var confirmPwd = $('#confirm_password').val();
        var statusEl = $('#password-change-status');
        var btn = $(this);

        if (!currentPwd || !newPwd || !confirmPwd) {
            statusEl.html('<span style="color: #ff6b6b;">Please fill in all password fields</span>');
            return;
        }

        if (newPwd !== confirmPwd) {
            statusEl.html('<span style="color: #ff6b6b;">New passwords do not match</span>');
            return;
        }

        if (newPwd.length < 8) {
            statusEl.html('<span style="color: #ff6b6b;">Password must be at least 8 characters</span>');
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Changing...');
        statusEl.html('<span style="color: #a8d5ba;"><i class="fas fa-spinner fa-spin"></i> Changing password...</span>');

        window.changeSimezuPassword(newPwd).then(function() {
            btn.prop('disabled', false).html('<i class="fas fa-key"></i> Change Password');
            statusEl.html('<span style="color: #a8d5ba;"><i class="fas fa-check"></i> Password changed successfully!</span>');
            $('#current_password, #new_password, #confirm_password').val('');
            setTimeout(function() {
                statusEl.html('');
            }, 3000);
        }).catch(function(err) {
            btn.prop('disabled', false).html('<i class="fas fa-key"></i> Change Password');
            statusEl.html('<span style="color: #ff6b6b;"><i class="fas fa-times"></i> Failed to change password. Please try again.</span>');
        });
    });
    
    // Avatar upload handlers
    $('#browse-avatar-btn').on('click', function() {
        $('#user-avatar-input').trigger('click');
    });

    // Load avatar once DOM and scripts are ready
    loadCurrentAvatar();
    
    $('#user-avatar-input').on('change', function(e) {
        const file = e.target.files[0];
        if (!(file && file.type.startsWith('image/'))) return;
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB');
            return;
        }
            const reader = new FileReader();
        reader.onload = function(ev) {
            initAvatarCropper(ev.target.result);
            };
            reader.readAsDataURL(file);
    });

    // Force-load current avatar from Simezu on load (keeps middle avatar in sync)
    function loadCurrentAvatar() {
        const identifier = simezuUserId || $('#simezu_id').val() || $('#email').val();
        if (!identifier) return;
        // Direct image endpoint for instant paint
        var imgEndpoint = SIMEZU_BASE_URL + '/api/auth/users/' + encodeURIComponent(identifier) + '/avatar/image?size=240';
        updateAvatarPreview(imgEndpoint, null);
    }

    // Refresh all avatar imgs on the page (middle + large + any data-simezu-user-id)
    async function refreshAllAvatarImgs() {
        const identifier = simezuUserId || $('#simezu_id').val() || $('#email').val();
        if (!identifier) return;
        const ts = Date.now();

        // Try lightweight public fetch first to get the canonical URL
        let resolvedUrl = null;
        try {
            const resp = await fetch(SIMEZU_BASE_URL + '/api/user/avatar?user_id=' + encodeURIComponent(identifier), { credentials: 'include' });
            if (resp.ok) {
                const data = await resp.json();
                if (data && data.url) {
                    resolvedUrl = data.url;
                }
            }
        } catch (e) {
            console.warn('refreshAllAvatarImgs: public fetch failed, falling back to direct image endpoint', e);
        }

        const url240 = resolvedUrl ? resolvedUrl + (resolvedUrl.includes('?') ? '&' : '?') + 'size=240&t=' + ts
                                   : SIMEZU_BASE_URL + '/api/auth/users/' + encodeURIComponent(identifier) + '/avatar/image?size=240&t=' + ts;
        const url500 = resolvedUrl ? resolvedUrl + (resolvedUrl.includes('?') ? '&' : '?') + 'size=500&t=' + ts
                                   : SIMEZU_BASE_URL + '/api/auth/users/' + encodeURIComponent(identifier) + '/avatar/image?size=500&t=' + ts;

        // Primary preview (middle avatar)
        updateAvatarPreview(url240, null);

        // Any img with data-simezu-user-id
        $('img[data-simezu-user-id]').each(function() {
            const sizeAttr = $(this).data('avatar-size') || $(this).data('size');
            const size = sizeAttr || 240;
            this.src = (resolvedUrl ? resolvedUrl : SIMEZU_BASE_URL + '/api/auth/users/' + encodeURIComponent(identifier) + '/avatar/image') + '?size=' + size + '&t=' + ts;
        });

        // Large avatar (profile reference)
        $('.avatar-large, .dashboard-user-avatar-large img').each(function() {
            this.src = url500;
        });

        // Top-right dropdown (Simezu quick code)
        if (typeof Simezu !== 'undefined' && typeof Simezu.renderAvatar === 'function') {
            try {
                var opts = {
                    app: (typeof SIMEZU_APP !== 'undefined' ? SIMEZU_APP : 'simuze-mdlr')
                };
                Simezu.renderAvatar('#user-menu', opts);
            } catch (e) {
                console.warn('refreshAllAvatarImgs: Simezu.renderAvatar refresh failed', e);
            }
        }
    }
    
    // Header upload handlers
    $('#browse-header-btn').on('click', function() {
        $('#user-header-input').trigger('click');
    });
    
    $('#user-header-input').on('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                initHeaderCropper(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Initialize avatar cropper (using Cropper.js for simplicity)
    function initAvatarCropper(imageSrc) {
        const container = $('#avatar-preview-container');
        container.empty();
        
        const wrapper = $(`
            <div class="avatar-cropper-wrapper" style="position: relative;">
                <div class="avatar-cropper-canvas" style="max-width: 320px; max-height: 320px; margin-bottom: 10px;">
                    <img id="avatar-cropper-img" src="${imageSrc}" style="max-width: 100%; display: block;">
                </div>
                <div class="avatar-cropper-actions" style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="button" class="button" id="avatar-upload-btn" style="background:#a8d5ba; color:#1a1a1a; border:none; padding:10px 20px; border-radius:4px; font-weight:600;">Upload Avatar</button>
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
            return;
        }

        avatarCropper = new Cropper(imgEl, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 1,
        });

        $('#avatar-upload-btn').on('click', function() {
            if (!avatarCropper) {
                console.error('[Avatar Upload] Cropper not initialized');
                return;
            }
            const canvas = avatarCropper.getCroppedCanvas({ width: 500, height: 500 });
            if (!canvas) {
                console.error('[Avatar Upload] Failed to get cropped canvas');
                return;
            }
            $('#avatar-status').html('<span style="color:#a8d5ba;"><i class="fas fa-spinner fa-spin"></i> Uploading...</span>');
            $('#avatar-upload-btn').prop('disabled', true);
            
            canvas.toBlob(async function(blob) {
                if (!blob) {
                    console.error('[Avatar Upload] Failed to create blob');
                    $('#avatar-status').html('<span style="color:#ff6b6b;">Failed to process image</span>');
                    $('#avatar-upload-btn').prop('disabled', false);
                    return;
                }
                
                try {
                    console.log('[Avatar Upload] Uploading blob, size:', blob.size);
                    const avatarUrl = await window.uploadAvatarToSimezu(blob, simezuUserId);
                    
                    // Update cache via REST API
                    try {
                        var apiUrl = (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.apiUrl) 
                            ? simuzeCommunity.apiUrl 
                            : '/wp-json/simuze/v1/';
                        var nonce = (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.nonce) 
                            ? simuzeCommunity.nonce 
                            : '';
                        
                        await $.ajax({
                            url: apiUrl + 'avatar/refresh-cache',
                method: 'POST',
                            data: JSON.stringify({ simezu_user_id: simezuUserId }),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                                if (nonce) xhr.setRequestHeader('X-WP-Nonce', nonce);
                }
                        }).catch(function() {
                            console.warn('[Avatar Upload] Cache refresh failed, but upload succeeded');
            });
                    } catch (cacheErr) {
                        console.warn('[Avatar Upload] Cache refresh error:', cacheErr);
                    }
            
                avatarUploaded = true;
                    // Update preview with new avatar URL
                    var imgEndpoint = SIMEZU_BASE_URL + '/api/auth/users/' + encodeURIComponent(simezuUserId) + '/avatar/image?size=240&t=' + Date.now();
                    updateAvatarPreview(imgEndpoint, null);
                    
                    $('#avatar-status').html('<span style="color:#a8d5ba;"><i class="fas fa-check"></i> Uploaded successfully!</span>');
                    
                    // Reset UI after short delay
                    setTimeout(function() {
                        if (avatarCropper) {
                            avatarCropper.destroy();
                            avatarCropper = null;
                        }
                        container.empty();
                        refreshAllAvatarImgs();
                    }, 1500);
                } catch (err) {
                    console.error('[Avatar Upload] Upload error:', err);
                    $('#avatar-status').html('<span style="color:#ff6b6b;"><i class="fas fa-times"></i> Upload failed: ' + (err.message || 'Unknown error') + '</span>');
                    $('#avatar-upload-btn').prop('disabled', false);
                } finally {
                    // Keep cropper alive on failure so user can retry; destroy on success or cancel
                }
            }, 'image/jpeg', 0.9);
        });

        $('#avatar-cancel-btn').on('click', function() {
            if (avatarCropper) {
                avatarCropper.destroy();
                avatarCropper = null;
            }
            container.empty();
            loadCurrentAvatar();
        });
    }
    
    // Initialize header cropper (using Cropper.js, 16:9 ratio, 1600x900 output)
    function initHeaderCropper(imageSrc) {
        const container = $('#header-preview-container');
        container.empty();

        const wrapper = $(`
            <div class="header-cropper-wrapper" style="position: relative;">
                <div class="header-cropper-canvas" style="max-width: 100%; max-height: 400px; margin-bottom: 10px;">
                    <img id="header-cropper-img" src="${imageSrc}" style="max-width: 100%; display: block;">
                </div>
                <div class="header-cropper-actions" style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
                    <button type="button" class="button" id="header-upload-btn" style="background:#a8d5ba; color:#1a1a1a; border:none; padding:10px 20px; border-radius:4px; font-weight:600;">Upload Header</button>
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
            console.error('[Header Upload] Cropper.js not loaded');
            $('#header-status').html('<span style="color:#ff6b6b;">Cropper.js library not loaded</span>');
            return;
        }

        headerCropper = new Cropper(imgEl, {
            aspectRatio: 16 / 9,
            viewMode: 1,
            autoCropArea: 1,
        });

        $('#header-upload-btn').on('click', function() {
            if (!headerCropper) {
                console.error('[Header Upload] Cropper not initialized');
                return;
            }
            const canvas = headerCropper.getCroppedCanvas({ width: 1600, height: 900 });
            if (!canvas) {
                console.error('[Header Upload] Failed to get cropped canvas');
                return;
            }
            $('#header-status').html('<span style="color:#a8d5ba;"><i class="fas fa-spinner fa-spin"></i> Uploading...</span>');
            $('#header-upload-btn').prop('disabled', true);
            
            canvas.toBlob(async function(blob) {
                if (!blob) {
                    console.error('[Header Upload] Failed to create blob');
                    $('#header-status').html('<span style="color:#ff6b6b;">Failed to process image</span>');
                    $('#header-upload-btn').prop('disabled', false);
                    return;
                }
                
                try {
                    console.log('[Header Upload] Uploading blob, size:', blob.size);
                    var apiUrl = (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.apiUrl) 
                        ? simuzeCommunity.apiUrl 
                        : '/wp-json/simuze/v1/';
                    var nonce = (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.nonce) 
                        ? simuzeCommunity.nonce 
                        : '';
                    
                    // Convert blob to base64 for upload
                    const reader = new FileReader();
                    reader.onload = function() {
                        const base64 = reader.result;
                        $.ajax({
                            url: apiUrl + 'account/header-upload',
                            method: 'POST',
                            data: JSON.stringify({ header_cropped: base64 }),
                            contentType: 'application/json',
                            beforeSend: function(xhr) {
                                if (nonce) xhr.setRequestHeader('X-WP-Nonce', nonce);
                            }
                        })
                        .done(function(response) {
                            console.log('[Header Upload] Success:', response);
                            if (response && response.status === 'ok' && response.url) {
                                $('#header-status').html('<span style="color:#a8d5ba;"><i class="fas fa-check"></i> Uploaded successfully!</span>');
                                // Update header preview
                                container.empty();
                                container.html(`
                                    <div class="current-header" style="margin-bottom: 15px;">
                                        <img src="${response.url}" alt="Current Header" 
                                             id="header-preview-img"
                                             style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 2px solid #4a4a4a; display: block;">
                                    </div>
                                `);
                                // Update background if header is used as background
                                $('.header-image-bg').css('background-image', 'url(' + response.url + ')');
                            } else {
                                throw new Error('Invalid response format');
                            }
                        })
                        .fail(function(xhr, status, error) {
                            console.error('[Header Upload] Upload failed:', status, error, xhr.responseText);
                            var errorMsg = 'Upload failed';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                try {
                                    var errData = JSON.parse(xhr.responseText);
                                    errorMsg = errData.message || errorMsg;
                                } catch(e) {
                                    errorMsg = xhr.responseText.substring(0, 100);
                                }
                            }
                            $('#header-status').html('<span style="color:#ff6b6b;"><i class="fas fa-times"></i> ' + errorMsg + '</span>');
                            $('#header-upload-btn').prop('disabled', false);
                        });
                    };
                    reader.onerror = function() {
                        console.error('[Header Upload] Failed to read blob');
                        $('#header-status').html('<span style="color:#ff6b6b;">Failed to process image</span>');
                        $('#header-upload-btn').prop('disabled', false);
                    };
                    reader.readAsDataURL(blob);
                } catch (err) {
                    console.error('[Header Upload] Error:', err);
                    $('#header-status').html('<span style="color:#ff6b6b;"><i class="fas fa-times"></i> ' + (err.message || 'Upload failed') + '</span>');
                    $('#header-upload-btn').prop('disabled', false);
                } finally {
                    if (headerCropper) {
                        headerCropper.destroy();
                        headerCropper = null;
                    }
                }
            }, 'image/jpeg', 0.9);
        });

        $('#header-cancel-btn').on('click', function() {
            if (headerCropper) {
                headerCropper.destroy();
                headerCropper = null;
            }
            container.empty();
            // Reload original header
            <?php if ($current_header_url): ?>
        container.html(`
                <div class="current-header" style="margin-bottom: 15px;">
                    <img src="<?php echo esc_js($current_header_url); ?>" alt="Current Header" 
                         id="header-preview-img"
                         style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 2px solid #4a4a4a; display: block;">
                </div>
            `);
            <?php else: ?>
            container.html(`
                <div class="no-header-placeholder" style="width: 100%; height: 200px; background: #2a2a2a; border: 2px dashed #4a4a4a; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999;">
                    <i class="fas fa-image" style="font-size: 60px;"></i>
            </div>
        `);
            <?php endif; ?>
        });
    }
    
    // Form submission
    $('#profile-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            display_name: $('#display_name').val(),
            first_name: $('#first_name').val(),
            last_name: $('#last_name').val(),
            email: $('#email').val(),
            unique_id: ($('#unique_id').val() || '').trim(),
                location: $('#location').val(),
            bio: $('#bio').val() // Local to Simuze
        };
        
        // Websites repeater (local to Simuze)
        let websites = [];
        $('#websites-repeater-container .website-item input').each(function() {
            const v = $(this).val().trim();
            if (v) websites.push(v);
        });
        formData.websites = websites;
        
        // Header (local to Simuze)
        if (headerCropped) {
            formData.header_cropped = headerCropped;
        }
        
        // Show loading
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        // Remove any existing notices
        $('.notice').remove();
        
        // Update Simezu profile first (all profile fields except bio/websites/header)
        var simezuUpdate = window.updateSimezuProfile(formData).then(function(updatedUser) {
            if (updatedUser && updatedUser.unique_identifier) {
                lastSimezuProfile = updatedUser;
                setValue('unique_id', updatedUser.unique_identifier);
            }
            return updatedUser;
        }).catch(function(err) {
            // On failure, revert unique_id to last known Simezu value
            if (lastSimezuProfile && (lastSimezuProfile.unique_identifier || lastSimezuProfile.unique_id)) {
                setValue('unique_id', lastSimezuProfile.unique_identifier || lastSimezuProfile.unique_id);
            }
            throw err;
        });
        
        // Save local data (bio, websites, header) to Simuze
        var apiUrl = (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.apiUrl) 
            ? simuzeCommunity.apiUrl 
            : '/wp-json/simuze/v1/';
        var nonce = (typeof simuzeCommunity !== 'undefined' && simuzeCommunity.nonce) 
            ? simuzeCommunity.nonce 
            : '';
        
        var localUpdate = new Promise(function(resolve, reject) {
            $.ajax({
                url: apiUrl + 'users/' + <?php echo $user->id; ?>,
                method: 'PUT',
                data: JSON.stringify({
                    display_name: formData.display_name,
                    first_name: formData.first_name,
                    last_name: formData.last_name,
                    profile: {
                        bio: formData.bio,
                        websites: formData.websites,
                        location: formData.location
                    },
                    header_cropped: formData.header_cropped
                }),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    if (nonce) {
                        xhr.setRequestHeader('X-WP-Nonce', nonce);
                    }
                }
            }).done(function(data) {
                resolve(data);
            }).fail(function(jqXHR) {
                console.error('Local profile update error:', jqXHR);
                reject(jqXHR);
            });
        });

        // Run both saves independently — local save succeeds even if Simezu is down
        var simezuError = null;
        var localError  = null;

        var simezuSettled = simezuUpdate.catch(function(err) {
            simezuError = err && err.message ? err.message : 'Simezu sync failed';
            console.error('Simezu update error:', err);
        });
        var localSettled = localUpdate.catch(function(jqXHR) {
            var msg = (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.message)
                ? jqXHR.responseJSON.message
                : 'Local save failed';
            localError = msg;
            console.error('Local save error:', jqXHR);
        });

        Promise.all([simezuSettled, localSettled]).then(function() {
            submitBtn.prop('disabled', false).html(originalText);

            if (!localError) {
                // Local data saved — consider it a success regardless of Simezu
                avatarCropped = null;
                headerCropped = null;

                var msg = 'Settings saved successfully!';
                if (simezuError) {
                    msg += ' (Simezu sync failed — profile fields may not be updated remotely)';
                }
                var cls = simezuError ? 'notice-warning" style="background:#4a3a1a; border:1px solid #ffa500; color:#ffa500' : 'notice-success" style="background:#2a4a2a; border:1px solid #a8d5ba; color:#a8d5ba';
                $('<div class="notice ' + cls + '; padding:15px; border-radius:4px; margin-top:15px;"><p>' + msg + '</p></div>')
                    .insertBefore('#profile-settings-form')
                    .delay(3000).fadeOut();

                setTimeout(function() { window.location.reload(); }, 1500);
            } else {
                // Local save failed — show error
                var errorMessage = localError;
                if (simezuError) errorMessage += ' | Simezu: ' + simezuError;
                $('<div class="notice notice-error" style="background:#4a2a2a; border:1px solid #ff6b6b; color:#ff6b6b; padding:15px; border-radius:4px; margin-top:15px;"><p>' + errorMessage + '</p></div>')
                    .insertBefore('#profile-settings-form')
                    .delay(5000).fadeOut();
            }
        });
    });
});
</script>
