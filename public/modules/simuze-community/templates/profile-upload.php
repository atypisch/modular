<?php
/**
 * User Profile Upload Template
 * Uses existing Simuze upload functionality
 */

if (!defined('ABSPATH') && !defined('SIMUZE_MODULAR_RUN')) {
    exit;
}

$auth = Simuze_Auth::get_instance();
$current_user = $auth->get_current_user();
$is_own_profile = $current_user && $current_user->id === $user->id;

if (!$is_own_profile) {
    echo '<p>Access denied.</p>';
    return;
}

// Get user's groups for upload - only Artist and Label groups can upload
$user_groups = $user->get_groups();
$upload_groups = array();

foreach ($user_groups as $group) {
    // Check if this is an Artist or Label group
    if ($group->can_upload_music()) {
        $upload_groups[] = $group;
    }
}
?>

<div class="profile-upload">
    <h2>Upload Music</h2>
    
    <?php if (empty($upload_groups)): ?>
        <div class="upload-no-groups">
            <div class="notice-box">
                <i class="fas fa-info-circle"></i>
                <div>
                    <h4>No Artist or Label Groups</h4>
                    <p>Only <strong>Artist</strong> and <strong>Label</strong> groups can upload music.</p>
                    <p>You can <a href="<?php echo esc_url(add_query_arg('tab', 'create-group', $user->get_url())); ?>">create a new group</a> or join an existing Artist/Label group to start uploading.</p>
                    <?php if (!empty($user_groups)): ?>
                        <p class="note">You are a member of <?php echo count($user_groups); ?> group(s), but none of them are Artist or Label type.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="upload-group-selector">
            <label for="upload-group-select">Select Artist or Label:</label>
            <select id="upload-group-select" name="group_id">
                <option value="">-- Select a group --</option>
                <?php foreach ($upload_groups as $group): 
                    $type = $group->get_group_type();
                    $type_label = $type ? ucfirst($type['slug']) : '';
                ?>
                    <option value="<?php echo esc_attr($group->id); ?>">
                        <?php echo esc_html($group->name); ?>
                        <?php if ($type_label): ?>(<?php echo esc_html($type_label); ?>)<?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div id="simuze-upload-container" class="upload-wizard" style="display: none;">
            <div class="upload-header">
                <h3><?php _e('Upload Music', 'simuze'); ?></h3>
                <p><?php _e('Share your music with the group by uploading tracks or albums.', 'simuze'); ?></p>
            </div>

            <!-- Step Navigation -->
            <div class="upload-steps">
                <div class="step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-title"><?php _e('Choose Type', 'simuze'); ?></div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-title"><?php _e('Upload Files', 'simuze'); ?></div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-title"><?php _e('Add Details', 'simuze'); ?></div>
                </div>
            </div>

            <!-- Step 1: Choose Upload Type -->
            <div id="step-1" class="upload-step-content">
                <h4><?php _e('What would you like to upload?', 'simuze'); ?></h4>
                <div class="upload-type-choices">
                    <div class="upload-type" data-type="single">
                        <i class="fas fa-music"></i>
                        <h5><?php _e('Single Track', 'simuze'); ?></h5>
                        <p><?php _e('Upload one standalone song', 'simuze'); ?></p>
                    </div>
                    <div class="upload-type" data-type="album">
                        <i class="fas fa-compact-disc"></i>
                        <h5><?php _e('Album', 'simuze'); ?></h5>
                        <p><?php _e('Upload multiple songs as an album', 'simuze'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Step 2: Upload Files -->
            <div id="step-2" class="upload-step-content hidden">
                <h4><?php _e('Upload Your Files', 'simuze'); ?></h4>
                <div class="file-upload-area">
                    <input type="file" id="music-files" multiple accept=".mp3,.wav,.aiff,.flac,.m4a,.ogg" class="file-input-hidden">
                    <div id="drag-drop-area" class="upload-dropzone">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p class="upload-text"><?php _e('Drag and drop your files here, or click to browse', 'simuze'); ?></p>
                        <button type="button" class="button upload-button browse-files" id="browse-files">
                            <i class="fas fa-folder-open"></i> <?php _e('Browse Files', 'simuze'); ?>
                        </button>
                    </div>
                    <div class="uploaded-files"></div>
                </div>
            </div>

            <!-- Step 3: Add Metadata -->
            <div id="step-3" class="upload-step-content hidden">
                <h4><?php _e('Add Details', 'simuze'); ?></h4>
                <form id="metadata-form">
                    <!-- Album Details (only shown for albums) -->
                    <div id="album-details"></div>
                    
                    <!-- Track Details -->
                    <div id="tracks-container"></div>
                </form>
                <div id="single-track-form"></div>
            </div>

            <!-- Navigation Buttons -->
            <div class="upload-actions">
                <button type="button" class="button prev-step hidden">
                    <i class="fas fa-arrow-left"></i> <?php _e('Previous', 'simuze'); ?>
                </button>
                <button type="button" class="button cancel-upload hidden">
                    <i class="fas fa-times"></i> <?php _e('Cancel', 'simuze'); ?>
                </button>
                <button type="button" class="button next-step">
                    <?php _e('Next', 'simuze'); ?> <i class="fas fa-arrow-right"></i>
                </button>
                <button type="button" class="button submit-upload hidden" id="finish-upload">
                    <i class="fas fa-check"></i> <?php _e('Publish', 'simuze'); ?>
                </button>
                <button type="button" class="button save-draft hidden" id="save-draft">
                    <i class="fas fa-save"></i> <?php _e('Save as Draft', 'simuze'); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize upload when group is selected
    var selectedGroupId = null;
    
    function initializeUploadForGroup(groupId) {
        selectedGroupId = groupId;
        
        // Set group_id in the localized script if available
        if (typeof simuze_upload !== 'undefined') {
            simuze_upload.group_id = groupId;
        }
        
        // Initialize upload if function exists
        if (typeof initializeUpload === 'function') {
            initializeUpload(groupId);
        } else {
            console.warn('Upload initialization function not found. Make sure music-upload.js is loaded.');
        }
    }
    
    // Show upload container when group is selected
    $('#upload-group-select').on('change', function() {
        var groupId = $(this).val();
        if (groupId) {
            $('#simuze-upload-container').show();
            initializeUploadForGroup(groupId);
        } else {
            $('#simuze-upload-container').hide();
        }
    });
});
</script>

