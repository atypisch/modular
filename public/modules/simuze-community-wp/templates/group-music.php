<?php
/**
 * Group Music Template - Full Discography Functionality
 * Based on the original discography.php with all features:
 * - Multi-file upload with progressbars
 * - Artwork upload and crop
 * - Mood sliders
 * - Scheduled, review, trash and overview tabs
 * - Multi-step upload flow
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue necessary scripts and styles
wp_enqueue_style('simuze-components', get_template_directory_uri() . '/css/components.css', array(), '1.0.0');
wp_enqueue_style('simuze-layout', get_template_directory_uri() . '/css/layout.css', array(), '1.0.0');
wp_enqueue_style('simuze-forms', get_template_directory_uri() . '/css/forms.css', array(), '1.0.0');
wp_enqueue_style('simuze-menu', get_template_directory_uri() . '/css/menu.css', array(), '1.0.0');

wp_enqueue_script('simuze-modal-script', get_template_directory_uri() . '/js/simuze-modal.js', array('jquery'), '1.0.1', true);
// Use consistent script handle to match functions.php
wp_enqueue_script('simuze-music-upload', get_template_directory_uri() . '/js/music-upload.js', array('jquery'), '2.2.4', true);
wp_enqueue_script('simuze-discography-script', get_template_directory_uri() . '/js/discography.js', array('jquery', 'simuze-modal-script'), '1.0.3', true);

$group_id = $group->id;
$current_user_id = $current_user ? $current_user->id : 0;

// Check if user is a member of the group
$is_member = $current_user && $group->is_member($current_user_id);

// Check if user is group admin/creator or WordPress superuser
$is_group_admin = false;
$is_group_mod = false;
$is_group_creator = false;

// Check if user is SIMEZU Super Admin
$is_simezu_super_admin = false;
if (class_exists('Simuze_Auth')) {
    $auth = Simuze_Auth::get_instance();
    $is_simezu_super_admin = $auth->is_admin();
}

// Check if user is group creator
if ($group->creator_id == $current_user_id) {
    $is_group_creator = true;
    $is_group_admin = true; // Creator is always admin
}

// Check if user is group admin or mod
if (!$is_group_admin) {
    $is_group_admin = $group->is_member($current_user_id, 'admin');
}
$is_group_mod = $group->is_member($current_user_id, 'mod');

// User has admin rights if they are group creator, admin, or mod
$has_admin_rights = $is_group_creator || $is_group_admin || $is_group_mod;

// User can manage if they are SIMEZU Super Admin, group creator, group admin, or group mod
$can_manage_group = $is_simezu_super_admin || $has_admin_rights;

// Check permissions for tabs and buttons:
// - Super Admin: Always sees all tabs and buttons
// - Member + Admin: All tabs + edit/delete buttons
// - Member + No Admin: All tabs, no edit/delete buttons
// - Not Member: Only overview tab, no edit/delete buttons

// Show all tabs if: SIMEZU Super Admin OR Member (regardless of admin status)
// Use SIMEZU authentication ONLY
$show_all_tabs = $is_simezu_super_admin || $is_member;

// Show edit/delete buttons if: SIMEZU Super Admin OR (Member AND Admin)
// Use SIMEZU authentication ONLY
$show_edit_delete_buttons = $is_simezu_super_admin || ($is_member && $has_admin_rights);

// Debug logging for permissions
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('GROUP MUSIC PERMISSIONS: is_simezu_super_admin=' . ($is_simezu_super_admin ? 'true' : 'false') . ', is_member=' . ($is_member ? 'true' : 'false') . ', has_admin_rights=' . ($has_admin_rights ? 'true' : 'false') . ', show_edit_delete_buttons=' . ($show_edit_delete_buttons ? 'true' : 'false') . ', current_user_id=' . $current_user_id . ', group_id=' . $group_id);
}

// Check permissions
$can_upload = $is_member && ($is_simezu_super_admin || $has_admin_rights);

// If edit=true and album_id present, load album edit view directly
if (isset($_GET['edit']) && $_GET['edit'] === 'true' && !empty($_GET['album_id'])) {
    $album_id = intval($_GET['album_id']);
    // album_id in URLs is sometimes the WP album post ID; the editor expects the DB album id (wp_music_albums.id).
    $album_db_id = $album_id;
    $maybe_album_post = get_post($album_id);
    
    error_log('SIMUZE DEBUG [group-music.php]: Processing edit request - album_id from URL: ' . $album_id);
    
    if ($maybe_album_post && $maybe_album_post->post_type === 'album') {
        error_log('SIMUZE DEBUG [group-music.php]: Found WordPress post - ID: ' . $maybe_album_post->ID . ', post_status: ' . $maybe_album_post->post_status);
        
        // Try multiple meta keys to find the database ID
        $meta_db_id = get_post_meta($maybe_album_post->ID, '_simuze_album_db_id', true);
        if (!$meta_db_id) {
            // Backward-compatible meta key used in parts of the codebase.
            $meta_db_id = get_post_meta($maybe_album_post->ID, '_simuze_album_id', true);
        }
        
        if ($meta_db_id) {
            $album_db_id = intval($meta_db_id);
            error_log('SIMUZE DEBUG [group-music.php]: Found database ID from meta: ' . $album_db_id);
        } else {
            // If no meta found, try reverse lookup: find database album by searching for posts with this ID
            global $wpdb;
            $albums_table = $wpdb->prefix . 'music_albums';
            $reverse_lookup = $wpdb->get_var($wpdb->prepare(
                "SELECT a.id FROM {$albums_table} a
                 INNER JOIN {$wpdb->postmeta} pm ON pm.meta_value = a.id
                 WHERE pm.post_id = %d
                 AND (pm.meta_key = '_simuze_album_id' OR pm.meta_key = '_simuze_album_db_id')
                 AND pm.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'album')
                 LIMIT 1",
                $maybe_album_post->ID
            ));
            
            if ($reverse_lookup) {
                $album_db_id = intval($reverse_lookup);
                error_log('SIMUZE DEBUG [group-music.php]: Found database ID via reverse lookup: ' . $album_db_id);
            } else {
                error_log('SIMUZE DEBUG [group-music.php]: WARNING - No database ID found in meta or via reverse lookup. Using post ID as database ID: ' . $album_id);
            }
        }
    } else {
        error_log('SIMUZE DEBUG [group-music.php]: No WordPress post found for ID: ' . $album_id . ', assuming it is a database ID');
    }

    error_log('SIMUZE DEBUG [group-music.php]: Final album_db_id being passed to simuze_get_album_for_editing: ' . $album_db_id);

    // Fetch full album data for editing
    if (function_exists('simuze_get_album_for_editing')) {
        $album_data = simuze_get_album_for_editing($album_db_id, $group_id, $current_user_id);
    } else {
        $album_data = false;
    }
    
    if (!$album_data) {
        error_log('SIMUZE DEBUG [group-music.php]: ERROR - simuze_get_album_for_editing returned false for album_db_id: ' . $album_db_id);
        echo '<div class="no-music"><i class="fas fa-exclamation-circle"></i><h3>' . __('Album not found or no permission to edit.', 'simuze') . '</h3></div>';
        return;
    }
    
    error_log('SIMUZE DEBUG [group-music.php]: Album data retrieved - Status: ' . ($album_data['status'] ?? 'not set'));
    // Enqueue dedicated album edit styles (no inline CSS in template)
    wp_enqueue_style('simuze-album-edit', get_template_directory_uri() . '/css/album-edit.css', array('simuze-components', 'simuze-layout', 'simuze-forms'), '1.0.0');
    // Include the album edit template and stop rendering the music dashboard
    include(locate_template('template-parts/album-edit.php'));
    return;
}

// Get statistics for all statuses
$published_albums = simuze_get_albums_by_status($group_id, 'published');
$draft_albums = simuze_get_albums_by_status($group_id, 'draft');
$scheduled_albums = simuze_get_albums_by_status($group_id, 'scheduled');
$under_review_albums = simuze_get_albums_by_status($group_id, 'under_review');

// Count albums for each status
$published_count = !empty($published_albums) ? count($published_albums) : 0;
$draft_count = !empty($draft_albums) ? count($draft_albums) : 0;
$scheduled_count = !empty($scheduled_albums) ? count($scheduled_albums) : 0;
$under_review_count = !empty($under_review_albums) ? count($under_review_albums) : 0;

// Localize script with comprehensive data - use consistent handle
wp_localize_script('simuze-music-upload', 'simuze_upload', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'ajaxurl' => admin_url('admin-ajax.php'), // Also provide as ajaxurl for compatibility
    'nonce' => wp_create_nonce('simuze_upload_single_file'),
    'upload_nonce' => wp_create_nonce('simuze_upload'),
    'delete_nonce' => wp_create_nonce('simuze_delete_album_nonce'),
    'rename_nonce' => wp_create_nonce('simuze_rename_album'),
    'group_id' => $group_id,
    'group_name' => $group->name,
    'max_file_size' => 1000 * 1024 * 1024, // 1000MB
    'can_upload' => $can_upload,
    'is_admin' => $is_simezu_super_admin,
    'is_mod' => $is_group_mod,
    'current_user_id' => $current_user_id,
    'i18n' => array(
        'save_error' => __('Error saving changes.', 'simuze'),
        'ajax_error' => __('An error occurred while saving. Please try again.', 'simuze'),
        'upload_error' => __('Upload failed. Please try again.', 'simuze'),
        'invalid_type' => __('Invalid file type. Please upload only supported audio files.', 'simuze'),
        'file_too_large' => __('File is too large. Maximum size allowed is %s.', 'simuze')
    )
));

// Get current tab from query string
$current_tab = isset($_GET['music_tab']) ? sanitize_text_field($_GET['music_tab']) : 'overview';
?>

<div class="group-discography">
    <?php if ($show_all_tabs) : ?>
    <div class="tabs">
        <ul class="tab-list clearfix">
            <li class="tab-item <?php echo $current_tab === 'overview' ? 'active' : ''; ?>" data-tab="overview-tab" id="tab-overview">
                <i class="fas fa-music"></i> <?php _e('Overview', 'simuze'); ?>
                <span class="tab-count" id="overview-count"><?php echo $published_count; ?></span>
            </li>
            <li class="tab-item <?php echo $current_tab === 'in-progress' ? 'active' : ''; ?>" data-tab="in-progress-tab" id="tab-in-progress">
                <i class="fas fa-clock"></i> <?php _e('In Progress', 'simuze'); ?>
                <span class="tab-count" id="in-progress-count"><?php echo $draft_count; ?></span>
            </li>
            <li class="tab-item <?php echo $current_tab === 'scheduled' ? 'active' : ''; ?>" data-tab="scheduled-tab" id="tab-scheduled">
                <i class="fas fa-calendar-alt"></i> <?php _e('Scheduled', 'simuze'); ?>
                <span class="tab-count" id="scheduled-count"><?php echo $scheduled_count; ?></span>
            </li>
            <li class="tab-item <?php echo $current_tab === 'under-review' ? 'active' : ''; ?>" data-tab="under-review-tab" id="tab-under-review">
                <i class="fas fa-eye"></i> <?php _e('Under Review', 'simuze'); ?>
                <span class="tab-count" id="under-review-count"><?php echo $under_review_count; ?></span>
            </li>
            <li class="tab-item <?php echo $current_tab === 'upload' ? 'active' : ''; ?>" data-tab="upload-tab" id="tab-upload">
                <i class="fas fa-upload"></i> <?php _e('Upload', 'simuze'); ?>
            </li>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Overview Tab Content - Always visible, but only show tabs if show_all_tabs -->
    <div class="tab-content <?php echo ($current_tab === 'overview' || !$show_all_tabs) ? 'active' : ''; ?>" id="overview-tab">
        <?php if (!empty($published_albums)) : ?>
            <div class="music-collection">
                <h3><?php _e('Published Music', 'simuze'); ?></h3>
                
                
                <?php
                $albums = $published_albums;
                $show_actions = $show_edit_delete_buttons; // Only show actions if user has permission
                $empty_message = __('No published music found.', 'simuze');
                $grid_class = 'albums-grid';
                // Pass group_id to template context
                $template_group_id = $group_id;
                
                // Debug logging
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('GROUP MUSIC DEBUG: show_edit_delete_buttons=' . ($show_edit_delete_buttons ? 'true' : 'false') . ', is_simezu_super_admin=' . ($is_simezu_super_admin ? 'true' : 'false') . ', is_member=' . ($is_member ? 'true' : 'false') . ', has_admin_rights=' . ($has_admin_rights ? 'true' : 'false') . ', current_user_id=' . $current_user_id . ', albums_count=' . count($albums));
                }
                
                // Debug: Show albums count
                
                include(locate_template('template-parts/album-grid.php'));
                ?>
            </div>
        <?php else : ?>
            <div class="no-music">
                <i class="fas fa-music"></i>
                <h3><?php _e('No music uploads yet!', 'simuze'); ?></h3>
                <?php if ($can_upload) : ?>
                    <p><?php _e('Start by uploading your first track or album.', 'simuze'); ?></p>
                    <button id="show-upload-empty" class="button">
                        <i class="fas fa-upload"></i> <?php _e('Upload Music', 'simuze'); ?>
                    </button>
                <?php else : ?>
                    <p><?php _e('No music has been uploaded to this group yet.', 'simuze'); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($show_all_tabs) : ?>
    <!-- In Progress Tab Content -->
    <div class="tab-content <?php echo $current_tab === 'in-progress' ? 'active' : ''; ?>" id="in-progress-tab">
        <h3><?php _e('In Progress', 'simuze'); ?></h3>
        <p><?php _e('Drafts and uploads that are not yet completed.', 'simuze'); ?></p>
        
        <?php 
        $user_id = null;
        if (!$has_admin_rights && !$is_wordpress_admin) {
            $user_id = $current_user_id;
        }
        
        $draft_albums = simuze_get_albums_by_status($group_id, 'draft', $user_id);
        ?>
        
        <?php if (!empty($draft_albums)) : ?>
                        <?php
            $albums = $draft_albums;
            $show_actions = $show_edit_delete_buttons; // Only show actions if user has permission
            $empty_message = __('No drafts found.', 'simuze');
            $grid_class = 'albums-grid draft-albums';
            $template_group_id = $group_id; // Pass group_id to album-grid.php
            
            include(locate_template('template-parts/album-grid.php'));
            ?>
        <?php else : ?>
            <div class="no-albums-message">
                <i class="fas fa-clock"></i>
                <p><?php _e('No drafts found. Start a new upload to create drafts.', 'simuze'); ?></p>
                <button class="button show-upload-tab">
                    <i class="fas fa-upload"></i> <?php _e('Start Upload', 'simuze'); ?>
                </button>
                                </div>
        <?php endif; ?>
    </div>
    
    <!-- Scheduled Tab Content -->
    <div class="tab-content <?php echo $current_tab === 'scheduled' ? 'active' : ''; ?>" id="scheduled-tab">
        <h3><?php _e('Scheduled', 'simuze'); ?></h3>
        <p><?php _e('Items scheduled for future release.', 'simuze'); ?></p>
        
        <?php 
        $user_id = null;
        if (!$has_admin_rights && !$is_wordpress_admin) {
            $user_id = $current_user_id;
        }
        
        $scheduled_albums = simuze_get_albums_by_status($group_id, 'scheduled', $user_id);
        ?>
        
        <?php if (!empty($scheduled_albums)) : ?>
            <?php 
            $albums = $scheduled_albums;
            $show_actions = $show_edit_delete_buttons; // Only show actions if user has permission
            $empty_message = __('No scheduled releases found.', 'simuze');
            $grid_class = 'albums-grid scheduled-albums';
            $template_group_id = $group_id; // Pass group_id to album-grid.php
            
            include(locate_template('template-parts/album-grid.php'));
            ?>
        <?php else : ?>
            <div class="no-albums-message">
                <i class="fas fa-calendar-alt"></i>
                <p><?php _e('No scheduled releases found.', 'simuze'); ?></p>
                <p><small><?php _e('Albums with future release dates will appear here.', 'simuze'); ?></small></p>
                                </div>
        <?php endif; ?>
                            </div>
                            
    <!-- Under Review Tab Content -->
    <div class="tab-content <?php echo $current_tab === 'under-review' ? 'active' : ''; ?>" id="under-review-tab">
        <h3><?php _e('Under Review', 'simuze'); ?></h3>
        <p><?php _e('Items that require editorial review before publication.', 'simuze'); ?></p>
        
        <?php 
        $user_id = null;
        if (!$has_admin_rights && !$is_wordpress_admin) {
            $user_id = $current_user_id;
        }
        
        $review_albums = simuze_get_albums_by_status($group_id, 'under_review', $user_id);
        ?>
        
        <?php if (!empty($review_albums)) : ?>
            <?php 
            $albums = $review_albums;
            $show_actions = $show_edit_delete_buttons; // Only show actions if user has permission
            $empty_message = __('No items under review.', 'simuze');
            $grid_class = 'albums-grid review-albums';
            $template_group_id = $group_id; // Pass group_id to album-grid.php
            
            include(locate_template('template-parts/album-grid.php'));
            ?>
        <?php else : ?>
            <div class="no-albums-message">
                <i class="fas fa-eye"></i>
                <p><?php _e('No items currently under review.', 'simuze'); ?></p>
            </div>
        <?php endif; ?>
                                </div>
                                
    <!-- Upload Tab Content - FULL UPLOAD WIZARD -->
    <div class="tab-content <?php echo $current_tab === 'upload' ? 'active' : ''; ?>" id="upload-tab">
        <div id="simuze-upload-container" class="upload-wizard">
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
    </div>
</div>

<!-- Review Modal -->
<div id="review-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">Put Item Under Review</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="review-item-info"></div>
            
            <div class="review-form">
                <div class="form-group">
                    <label for="review-reason">Reason for Review:</label>
                    <textarea id="review-reason" placeholder="Please explain why this item needs to be reviewed..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="review-priority">Priority:</label>
                    <select id="review-priority">
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button id="submit-review-btn" class="button-danger">Put Under Review</button>
                    <button id="cancel-review-btn" class="button-secondary">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let currentContentId = null;
    let currentContentType = null;
    let currentContentName = null;
    
    // Tab functionality
    $('.tab-item').on('click', function() {
        var targetTab = $(this).data('tab');
        
        // Remove active class from all tabs and content
        $('.tab-item').removeClass('active');
        $('.tab-content').removeClass('active');
        
        // Add active class to clicked tab and its content
        $(this).addClass('active');
        $('#' + targetTab).addClass('active');
        
        // If switching to upload tab, initialize the upload wizard
        if (targetTab === 'upload-tab') {
            console.log('Initializing upload tab');
            // Give it a moment to become visible, then initialize
            setTimeout(function() {
                if (typeof window.initializeUpload === 'function') {
                    console.log('Calling global initializeUpload function');
                    window.initializeUpload();
                } else {
                    console.log('Manual upload initialization');
                    // Manual reset if function not available
                    $('.upload-step-content').addClass('hidden');
                    $('#step-1').removeClass('hidden');
                    $('.step').removeClass('active completed');
                    $('.step[data-step="1"]').addClass('active');
                    $('.next-step').removeClass('hidden').prop('disabled', true).addClass('disabled');
                    $('.prev-step, .submit-upload, .save-draft').addClass('hidden');
                    $('.upload-type').removeClass('selected');
                    $('.uploaded-files').empty();
                    window.uploadType = null;
                    window.uploadedFiles = [];
                    window.currentStep = 1;
                }
            }, 100);
        }
        
        // Update tab counts when switching tabs
        updateTabCounts();
    });
    
    // Function to update tab counts
    function updateTabCounts() {
        const groupId = <?php echo $group_id; ?>;
        const nonce = '<?php echo wp_create_nonce('simuze_upload_single_file'); ?>';
        
        // Silently return if invalid group ID or nonce
        if (!groupId || groupId <= 0 || !nonce) {
            // Set counts to 0 as fallback
            $('#overview-count').text('0');
            $('#in-progress-count').text('0');
            $('#scheduled-count').text('0');
            $('#under-review-count').text('0');
            return;
        }
        
        const ajaxData = {
            action: 'simuze_get_tab_counts',
            group_id: groupId,
            _ajax_nonce: nonce
        };
        
        // Use localized ajax_url if available, otherwise fallback
        const ajaxUrl = (typeof simuze_upload !== 'undefined' && simuze_upload.ajax_url) 
            ? simuze_upload.ajax_url 
            : (typeof window.ajaxurl !== 'undefined' ? window.ajaxurl : '<?php echo admin_url('admin-ajax.php'); ?>');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            timeout: 5000, // 5 second timeout
            success: function(response) {
                if (response && response.success && response.data && response.data.counts) {
                    const counts = response.data.counts;
                    $('#overview-count').text(counts.publish || 0);
                    $('#in-progress-count').text(counts.draft || 0);
                    $('#scheduled-count').text(counts.scheduled || 0);
                    $('#under-review-count').text(counts.under_review || 0);
                } else {
                    // Silently set to 0 on invalid response
                    $('#overview-count').text('0');
                    $('#in-progress-count').text('0');
                    $('#scheduled-count').text('0');
                    $('#under-review-count').text('0');
                }
            },
            error: function(xhr, status, error) {
                // Silently handle errors - set counts to 0 as fallback
                // Only log in debug mode
                if (typeof console !== 'undefined' && console.debug && window.location.search.indexOf('debug=1') > -1) {
                    console.debug('Tab counts AJAX error (silent):', {
                        status: xhr.status,
                        statusText: xhr.statusText
                    });
                }
                
                // Set counts to 0 as fallback
                $('#overview-count').text('0');
                $('#in-progress-count').text('0');
                $('#scheduled-count').text('0');
                $('#under-review-count').text('0');
            }
        });
    }
    
    // Update counts on page load
    updateTabCounts();
    
    // Show upload tab when buttons are clicked
    $('.show-upload-tab, #show-upload-empty').on('click', function() {
        $('.tab-item').removeClass('active');
        $('.tab-content').removeClass('active');
        
        $('#tab-upload').addClass('active');
        $('#upload-tab').addClass('active');
    });
    
    // Open review modal
    $('.put-under-review-btn').on('click', function() {
        currentContentId = $(this).data('content-id');
        currentContentType = $(this).data('content-type');
        currentContentName = $(this).data('content-name');
        
        $('#review-item-info').html('<p><strong>' + currentContentType.charAt(0).toUpperCase() + currentContentType.slice(1) + ':</strong> ' + currentContentName + '</p>');
        $('#review-modal').show();
    });
    
    // Close modal
    $('.close, #cancel-review-btn').on('click', function() {
        $('#review-modal').hide();
        currentContentId = null;
        currentContentType = null;
        currentContentName = null;
    });
    
    // Close modal when clicking outside
    $('.modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Submit review
    $('#submit-review-btn').on('click', function() {
        const reason = $('#review-reason').val();
        const priority = $('#review-priority').val();
        
        if (!reason.trim()) {
            if (typeof simuzeAlertError === 'function') {
                simuzeAlertError('Please provide a reason for putting this item under review.');
            } else {
                alert('Please provide a reason for putting this item under review.');
            }
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'simuze_put_under_review',
                content_id: currentContentId,
                content_type: currentContentType,
                reason: reason,
                priority: priority,
                nonce: '<?php echo wp_create_nonce("review_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    if (typeof simuzeAlertSuccess === 'function') {
                        simuzeAlertSuccess('Item has been put under review successfully!', function() {
                            $('#review-modal').hide();
                            location.reload();
                        });
                    } else {
                        alert('Item has been put under review successfully!');
                        $('#review-modal').hide();
                        location.reload();
                    }
                } else {
                    const errorMsg = 'Error: ' + response.data;
                    if (typeof simuzeAlertError === 'function') {
                        simuzeAlertError(errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                }
            },
            error: function() {
                if (typeof simuzeAlertError === 'function') {
                    simuzeAlertError('An error occurred. Please try again.');
                } else {
                    alert('An error occurred. Please try again.');
                }
            }
        });
    });
});
</script>
<?php endif; // End show_all_tabs check for Review Modal ?>