<?php
/**
 * Create Group Template - Within Profile
 * Multi-step group creation with image cropping
 */

if (!defined('ABSPATH')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();

if (!$current_user) {
    echo '<div class="access-denied"><p>You must be logged in to create a group.</p></div>';
    return;
}

// Get group types
$group_types = Simuze_Group::get_group_types();
?>

<div class="profile-create-group">
    
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
        
        <!-- Step 3: Images with Crop -->
        <div class="wizard-panel" data-step="3">
            <h2>Group Images</h2>
            <p class="panel-description">Upload an avatar and header image for your group. Images will be cropped to the correct size.</p>
            
            <!-- Avatar Upload -->
            <div class="image-upload-section">
                <h3>Avatar</h3>
                <p class="size-requirements">Minimum size: 200x200 pixels. Will be cropped to square (1:1 ratio).</p>
                
                <div class="image-upload-container" id="avatar-upload-container">
                    <div class="upload-area" id="avatar-upload-area">
                        <div class="upload-placeholder">
                            <i class="fas fa-user-circle"></i>
                            <span>Click to upload avatar</span>
                            <small>Min: 200x200px</small>
                        </div>
                        <input type="file" id="group-avatar" name="avatar" accept="image/*" class="file-input">
                    </div>
                    
                    <div class="crop-container" id="avatar-crop-container" style="display: none;">
                        <div class="crop-preview">
                            <img id="avatar-crop-image" src="">
                        </div>
                        <div class="crop-actions">
                            <button type="button" class="btn-secondary" id="avatar-cancel-crop">Cancel</button>
                            <button type="button" class="btn-primary" id="avatar-apply-crop">Crop & Save</button>
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
                <p class="size-requirements">Minimum size: 1200x300 pixels. Recommended ratio 4:1 (width:height).</p>
                
                <div class="image-upload-container header-upload" id="header-upload-container">
                    <div class="upload-area" id="header-upload-area">
                        <div class="upload-placeholder">
                            <i class="fas fa-image"></i>
                            <span>Click to upload header image</span>
                            <small>Min: 1200x300px</small>
                        </div>
                        <input type="file" id="group-header" name="header" accept="image/*" class="file-input">
                    </div>
                    
                    <div class="crop-container" id="header-crop-container" style="display: none;">
                        <div class="crop-preview header-crop">
                            <img id="header-crop-image" src="">
                        </div>
                        <div class="crop-actions">
                            <button type="button" class="btn-secondary" id="header-cancel-crop">Cancel</button>
                            <button type="button" class="btn-primary" id="header-apply-crop">Crop & Save</button>
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
        
        <div id="group-creation-message" class="creation-message" style="display: none;"></div>
    </form>
    
</div>



