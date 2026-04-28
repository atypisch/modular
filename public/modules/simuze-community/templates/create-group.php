<?php
/**
 * Create Group Page Template
 * Full page group creation wizard with image cropping
 */

if (!defined('ABSPATH') && !defined('SIMUZE_MODULAR_RUN')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();

if (!$current_user) {
    header('Location: ' . md_home_url('/login/?redirect=' . urlencode(md_home_url('/groups/create/'))), true, 302);
    exit;
}

// Get group types
$group_types = Simuze_Group::get_group_types();

// Ensure simuzeCommunity for create-group.js (Modular)
if (defined('SIMUZE_MODULAR_RUN') && (empty($GLOBALS['simuze_community_script_emitted']) || !$GLOBALS['simuze_community_script_emitted'])) {
    $api_url      = md_home_url('/api/simuze/v1/');
    $home_url     = md_home_url('/');
    $nonce        = function_exists('md_csrf_token') ? md_csrf_token() : '';
    $default_avatar = function_exists('theme_uri') ? theme_uri('/images/profile_default.jpg') : '';
    $current_user_arr = $current_user && method_exists($current_user, 'to_array') ? $current_user->to_array() : null;
    echo '<script>var simuzeCommunity = { apiUrl: "' . esc_js($api_url) . '", ajaxUrl: "' . esc_js($home_url . 'api/simezu-ajax.php') . '", homeUrl: "' . esc_js($home_url) . '", nonce: "' . esc_js($nonce) . '", currentUser: ' . json_encode($current_user_arr) . ', defaultAvatar: "' . esc_js($default_avatar) . '" };</script>' . "\n";
    $GLOBALS['simuze_community_script_emitted'] = true;
}

if (function_exists('get_header')) {
    get_header();
}
?>

<div class="col-fullwidth">
    <div class="header-image">
        <div class="header-image-bg"></div>
        <div class="header-text">
            <h1>Create New Group</h1>
        </div>
    </div>
</div>

<section class="fullwidth">
    <div class="row">
        <div class="col col-12">
            <div class="create-group-page">
                
                <!-- Progress Steps -->
                <div class="wizard-progress">
                    <div class="wizard-step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-label">Basic Info</div>
                    </div>
                    <div class="wizard-step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-label">Location</div>
                    </div>
                    <div class="wizard-step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-label">Images</div>
                    </div>
                    <div class="wizard-step" data-step="4">
                        <div class="step-number">4</div>
                        <div class="step-label">Members</div>
                    </div>
                </div>
                
                <form id="create-group-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('simuze_create_group', 'group_nonce'); ?>
                    
                    <!-- Step 1: Basic Info -->
                    <div class="wizard-panel active" data-step="1">
                        <h2>Basic Information</h2>
                        <p class="panel-description">Start by giving your group a name and choosing its type.</p>
                        
                        <div class="form-group">
                            <label for="group-name">Group Name <span class="required">*</span></label>
                            <input type="text" id="group-name" name="name" required placeholder="Enter group name">
                            <p class="field-hint">Choose a unique, memorable name for your group.</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="group-type">Group Type <span class="required">*</span></label>
                            <select id="group-type" name="group_type_id" required>
                                <option value="">Select a type...</option>
                                <?php foreach ($group_types as $type): ?>
                                    <option value="<?php echo esc_attr($type['id']); ?>">
                                        <?php echo esc_html($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="field-hint">Only Artist and Label groups can upload music.</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="group-description">Description</label>
                            <textarea id="group-description" name="description" rows="4" placeholder="Tell people what your group is about..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Step 2: Location -->
                    <div class="wizard-panel" data-step="2">
                        <h2>Location</h2>
                        <p class="panel-description">Set your group's location. This helps people find local groups.</p>
                        
                        <div class="form-group">
                            <label for="group-city">Search City</label>
                            <div class="location-search-container">
                                <input type="text" id="group-city" placeholder="Start typing a city name..." autocomplete="off">
                                <div id="city-dropdown" class="location-dropdown"></div>
                            </div>
                        </div>
                        
                        <div id="selected-location" class="selected-location" style="display: none;">
                            <div class="location-display">
                                <i class="fas fa-map-marker-alt"></i>
                                <span id="location-display"></span>
                                <button type="button" id="clear-location" class="btn-clear">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Hidden fields for location data -->
                        <input type="hidden" id="group-city-name" name="city">
                        <input type="hidden" id="group-province" name="province">
                        <input type="hidden" id="group-country" name="country">
                        <input type="hidden" id="group-geo-id" name="geo_id">
                        <input type="hidden" id="group-lat" name="lat">
                        <input type="hidden" id="group-lng" name="lng">
                        
                        <p class="field-hint">City, Province/State, and Country groups will be automatically created if they don't exist.</p>
                    </div>
                    
                    <!-- Step 3: Images -->
                    <div class="wizard-panel" data-step="3">
                        <h2>Group Images</h2>
                        <p class="panel-description">Upload an avatar and header image for your group.</p>
                        
                        <!-- Avatar Upload -->
                        <div class="image-upload-section">
                            <h3>Avatar</h3>
                            <p class="size-requirements">Minimum size: 200x200 pixels. Will be cropped to square.</p>
                            
                            <div class="image-upload-container" id="avatar-upload-container">
                                <div class="upload-area" id="avatar-upload-area">
                                    <div class="upload-placeholder">
                                        <i class="fas fa-user-circle"></i>
                                        <span>Click to upload avatar</span>
                                    </div>
                                    <input type="file" id="group-avatar" name="avatar" accept="image/*" class="file-input">
                                </div>
                                
                                <div class="crop-container" id="avatar-crop-container" style="display: none;">
                                    <div class="crop-preview">
                                        <img id="avatar-crop-image" src="">
                                    </div>
                                    <div class="crop-actions">
                                        <button type="button" class="btn-secondary" id="avatar-cancel-crop">Cancel</button>
                                        <button type="button" class="btn-primary" id="avatar-apply-crop">Apply Crop</button>
                                    </div>
                                </div>
                                
                                <div class="cropped-preview" id="avatar-preview" style="display: none;">
                                    <img id="avatar-preview-img" src="">
                                    <button type="button" class="btn-change" id="avatar-change">Change</button>
                                </div>
                            </div>
                            <input type="hidden" id="avatar-cropped-data" name="avatar_cropped">
                        </div>
                        
                        <!-- Header Upload -->
                        <div class="image-upload-section">
                            <h3>Header Image</h3>
                            <p class="size-requirements">Minimum size: 1200x300 pixels. Recommended ratio 4:1.</p>
                            
                            <div class="image-upload-container header-upload" id="header-upload-container">
                                <div class="upload-area" id="header-upload-area">
                                    <div class="upload-placeholder">
                                        <i class="fas fa-image"></i>
                                        <span>Click to upload header image</span>
                                    </div>
                                    <input type="file" id="group-header" name="header" accept="image/*" class="file-input">
                                </div>
                                
                                <div class="crop-container" id="header-crop-container" style="display: none;">
                                    <div class="crop-preview header-crop">
                                        <img id="header-crop-image" src="">
                                    </div>
                                    <div class="crop-actions">
                                        <button type="button" class="btn-secondary" id="header-cancel-crop">Cancel</button>
                                        <button type="button" class="btn-primary" id="header-apply-crop">Apply Crop</button>
                                    </div>
                                </div>
                                
                                <div class="cropped-preview header-preview" id="header-preview" style="display: none;">
                                    <img id="header-preview-img" src="">
                                    <button type="button" class="btn-change" id="header-change">Change</button>
                                </div>
                            </div>
                            <input type="hidden" id="header-cropped-data" name="header_cropped">
                        </div>
                    </div>
                    
                    <!-- Step 4: Invite Members -->
                    <div class="wizard-panel" data-step="4">
                        <h2>Invite Members</h2>
                        <p class="panel-description">Invite people to join your group. You can always add more later.</p>
                        
                        <div class="form-group">
                            <label for="member-search">Search Members</label>
                            <div class="member-search-container">
                                <input type="text" id="member-search" placeholder="Search by name or @username..." autocomplete="off">
                                <div id="member-dropdown" class="member-dropdown"></div>
                            </div>
                        </div>
                        
                        <div class="invited-members">
                            <h4>Members to invite:</h4>
                            <div id="members-list" class="members-chips">
                                <!-- Member chips will be added here -->
                            </div>
                            <p class="no-members-text" id="no-members-text">No members invited yet. You can skip this step.</p>
                        </div>
                        
                        <input type="hidden" id="invited-member-ids" name="invited_members">
                    </div>
                    
                    <!-- Navigation -->
                    <div class="wizard-navigation">
                        <button type="button" class="btn-secondary" id="wizard-prev" style="display: none;">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn-primary" id="wizard-next">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                        <button type="submit" class="btn-success" id="wizard-submit" style="display: none;">
                            <i class="fas fa-check"></i> Create Group
                        </button>
                    </div>
                </form>
                
            </div>
        </div>
    </div>
</section>

<style>
.create-group-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 20px;
}

.wizard-progress {
    display: flex;
    justify-content: space-between;
    margin-bottom: 40px;
    position: relative;
}

.wizard-progress::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 10%;
    right: 10%;
    height: 2px;
    background: rgba(255,255,255,0.1);
}

.wizard-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    border: 2px solid rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: rgba(255,255,255,0.5);
    margin-bottom: 8px;
    transition: all 0.3s;
}

.wizard-step.active .step-number {
    background: #4CAF50;
    border-color: #4CAF50;
    color: #fff;
}

.wizard-step.completed .step-number {
    background: #4CAF50;
    border-color: #4CAF50;
    color: #fff;
}

.step-label {
    font-size: 13px;
    color: rgba(255,255,255,0.5);
}

.wizard-step.active .step-label,
.wizard-step.completed .step-label {
    color: #fff;
}

.wizard-panel {
    display: none;
    animation: fadeIn 0.3s;
}

.wizard-panel.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.wizard-panel h2 {
    margin-bottom: 10px;
}

.panel-description {
    color: rgba(255,255,255,0.6);
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-group label .required {
    color: #f44336;
}

.form-group input[type="text"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px 15px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 8px;
    color: #fff;
    font-size: 15px;
    transition: all 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #4CAF50;
    background: rgba(255,255,255,0.08);
}

.field-hint {
    margin-top: 6px;
    font-size: 13px;
    color: rgba(255,255,255,0.5);
}

/* Location Search */
.location-search-container {
    position: relative;
}

.location-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #1a1a1a;
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 8px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 100;
    display: none;
}

.location-dropdown.show {
    display: block;
}

.location-item {
    padding: 12px 15px;
    cursor: pointer;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.location-item:hover {
    background: rgba(255,255,255,0.05);
}

.location-item .city-name {
    font-weight: 500;
}

.location-item .region-info {
    font-size: 13px;
    color: rgba(255,255,255,0.5);
}

.selected-location {
    margin-top: 15px;
}

.location-display {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    background: rgba(76, 175, 80, 0.1);
    border: 1px solid rgba(76, 175, 80, 0.3);
    border-radius: 8px;
    color: #4CAF50;
}

.btn-clear {
    background: none;
    border: none;
    color: inherit;
    cursor: pointer;
    opacity: 0.7;
}

.btn-clear:hover {
    opacity: 1;
}

/* Image Upload */
.image-upload-section {
    margin-bottom: 30px;
}

.image-upload-section h3 {
    margin-bottom: 5px;
}

.size-requirements {
    font-size: 13px;
    color: rgba(255,255,255,0.5);
    margin-bottom: 15px;
}

.image-upload-container {
    position: relative;
}

.upload-area {
    border: 2px dashed rgba(255,255,255,0.2);
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.upload-area:hover {
    border-color: rgba(255,255,255,0.4);
    background: rgba(255,255,255,0.02);
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    color: rgba(255,255,255,0.5);
}

.upload-placeholder i {
    font-size: 48px;
}

.file-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.header-upload .upload-area {
    padding: 60px 40px;
}

/* Crop Container */
.crop-container {
    background: rgba(0,0,0,0.5);
    border-radius: 12px;
    padding: 20px;
}

.crop-preview {
    max-width: 400px;
    margin: 0 auto 20px;
}

.crop-preview.header-crop {
    max-width: 100%;
}

.crop-preview img {
    max-width: 100%;
    display: block;
}

.crop-actions {
    display: flex;
    justify-content: center;
    gap: 10px;
}

/* Cropped Preview */
.cropped-preview {
    position: relative;
    display: inline-block;
}

.cropped-preview img {
    border-radius: 12px;
    max-width: 200px;
}

.cropped-preview.header-preview img {
    max-width: 100%;
    max-height: 200px;
    object-fit: cover;
}

.btn-change {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 5px 10px;
    background: rgba(0,0,0,0.7);
    border: none;
    border-radius: 4px;
    color: #fff;
    cursor: pointer;
    font-size: 12px;
}

/* Member Search */
.member-search-container {
    position: relative;
}

.member-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #1a1a1a;
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 8px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 100;
    display: none;
}

.member-dropdown.show {
    display: block;
}

.member-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    cursor: pointer;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.member-item:hover {
    background: rgba(255,255,255,0.05);
}

.member-item img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.member-name {
    font-weight: 500;
}

.member-username {
    font-size: 13px;
    color: rgba(255,255,255,0.5);
}

.invited-members {
    margin-top: 30px;
}

.invited-members h4 {
    margin-bottom: 15px;
}

.members-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.member-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: rgba(255,255,255,0.1);
    border-radius: 20px;
}

.member-chip img {
    width: 24px;
    height: 24px;
    border-radius: 50%;
}

.remove-member {
    background: none;
    border: none;
    color: rgba(255,255,255,0.5);
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
}

.remove-member:hover {
    color: #f44336;
}

.no-members-text {
    color: rgba(255,255,255,0.5);
    font-style: italic;
}

/* Navigation */
.wizard-navigation {
    display: flex;
    justify-content: space-between;
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.btn-primary,
.btn-secondary,
.btn-success {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #4CAF50;
    color: #fff;
    margin-left: auto;
}

.btn-primary:hover {
    background: #45a049;
}

.btn-secondary {
    background: rgba(255,255,255,0.1);
    color: #fff;
}

.btn-secondary:hover {
    background: rgba(255,255,255,0.15);
}

.btn-success {
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: #fff;
    margin-left: auto;
}

.btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
}

.btn-success:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

@media (max-width: 768px) {
    .wizard-progress {
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .wizard-progress::before {
        display: none;
    }
    
    .wizard-step {
        width: 45%;
    }
}
</style>

<?php get_footer(); ?>



