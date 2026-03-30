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

if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

// CSS and JS are loaded globally by the simuze theme header.php (music routes).
// No wp_enqueue needed in Modular.

$group_id = $group->id;
$current_user_id = $current_user ? $current_user->id : 0;
$is_wordpress_admin = false; // No WP admin in Modular

// Treat group creator as implicit member (matches group.php logic)
$is_owner = $current_user && ($group->creator_id == $current_user_id);
// Check if user is a member of the group
$is_member = $current_user && ($group->is_member($current_user_id) || $is_owner);

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
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_log('GROUP MUSIC PERMISSIONS: is_simezu_super_admin=' . ($is_simezu_super_admin ? 'true' : 'false') . ', is_member=' . ($is_member ? 'true' : 'false') . ', has_admin_rights=' . ($has_admin_rights ? 'true' : 'false') . ', show_edit_delete_buttons=' . ($show_edit_delete_buttons ? 'true' : 'false') . ', current_user_id=' . $current_user_id . ', group_id=' . $group_id);
}

// Check permissions
$can_upload = $is_member && ($is_simezu_super_admin || $has_admin_rights);

// Output CSS + JS config now so it's always available, including in edit mode.
// (The edit handler below returns early, skipping the rest of this template.)
{
    $_gm_base_url = function_exists('md_home_url') ? rtrim(md_home_url(''), '/') : (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '');
    $_gm_theme_js = $_gm_base_url . '/public/themes/simuze/js';
    $_gm_mod_css  = $_gm_base_url . '/public/modules/simuze-community/assets/css';
    echo '<link rel="stylesheet" href="' . md_esc_url($_gm_mod_css . '/community.css') . '?v=1" id="simuze-group-music-community-css">';
    echo '<script>';
    echo '(function(){var cfg=' . json_encode([
        'ajax_url'       => md_url('/api/simezu-ajax.php'),
        'ajaxurl'        => md_url('/api/simezu-ajax.php'),
        'nonce'          => md_csrf_token(),
        'upload_nonce'   => md_csrf_token(),
        'delete_nonce'   => md_csrf_token(),
        'rename_nonce'   => md_csrf_token(),
        'playlist_nonce' => md_csrf_token(),
        'favorite_nonce' => md_csrf_token(),
        'group_id'       => $group_id,
        'group_name'     => $group->name,
        'max_file_size'  => 1000 * 1024 * 1024,
        'can_upload'     => $can_upload,
        'is_admin'       => $is_simezu_super_admin,
        'is_mod'         => $is_group_mod,
        'current_user_id'=> $current_user_id,
    ]) . ';';
    echo 'window.simuze_upload=window.simuze_upload||{};Object.assign(window.simuze_upload,cfg);';
    echo 'window.simuze_ajax=window.simuze_ajax||{};';
    echo 'window.simuze_ajax.ajaxurl=cfg.ajax_url;';
    echo 'window.simuze_ajax.nonce=cfg.playlist_nonce;';
    echo 'window.simuze_ajax.playlist_nonce=cfg.playlist_nonce;';
    echo 'window.simuze_ajax.favorite_nonce=cfg.favorite_nonce;';
    echo 'window.simuze_ajax.current_user_id=cfg.current_user_id;';
    echo '})();</script>';
    if ($_gm_base_url !== '') {
        echo '<script src="' . md_esc_url($_gm_theme_js . '/music-upload.js') . '?v=2.2.4" id="simuze-music-upload-js-edit"></script>';
    }
    unset($_gm_base_url, $_gm_theme_js, $_gm_mod_css);
}

// If edit=true and album_id present, load album edit view directly.
// In Modular, album_id in the URL is always the md_music_albums.id (no WP post mapping needed).
if (isset($_GET['edit']) && $_GET['edit'] === 'true' && !empty($_GET['album_id'])) {
    $album_db_id = intval($_GET['album_id']);
    $album_data  = simuze_get_album_for_editing($album_db_id, $group_id, $current_user_id);
    if (!$album_data) {
        echo '<div class="no-music"><i class="fas fa-exclamation-circle"></i><h3>' . 'Album not found or no permission to edit.' . '</h3></div>';
        return;
    }
    $album_edit_tpl = md_locate_template('template-parts/album-edit.php');
    if ($album_edit_tpl) {
        include $album_edit_tpl;
    }
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

// simuze_upload config is set inline below (window.simuze_upload) — no wp_localize_script needed.
// Kept as comment for reference:
// wp_localize_script('simuze-music-upload', 'simuze_upload', array(
//     'ajax_url' => md_url('/api/simezu-ajax.php'), ...
// ));

// Get current tab from query string
$current_tab = isset($_GET['music_tab']) ? md_sanitize_text($_GET['music_tab']) : 'overview';
?>
<?php
$base_url = (function_exists('md_home_url') ? rtrim(md_home_url(''), '/') : (defined('SITE_URL') ? rtrim(SITE_URL, '/') : ''));
$base_path = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
if ($base_url === '' && $base_path !== '') {
    $base_url = $base_path;
}
$theme_js_url = $base_url . '/public/themes/simuze/js';
$module_js_url = $base_url . '/public/modules/simuze-community/assets/js';
$module_css_url = $base_url . '/public/modules/simuze-community/assets/css';
?>
<link rel="stylesheet" href="<?php echo md_esc_url($module_css_url . '/community.css'); ?>?v=1" id="simuze-group-music-community-css">
<style id="simuze-group-music-inline-css">
/* Span full width of parent .row.scroll-area.full-width-content without exceeding it */
.row.scroll-area.full-width-content { width: 100%; max-width: 100%; box-sizing: border-box; }
.row.scroll-area.full-width-content .group-discography { width: 100%; max-width: 100%; box-sizing: border-box; }
/* Group music / discography — original-style layout and upload type (single/album) selection */
.group-discography { padding: 20px 24px; }
.group-discography .tabs { margin-bottom: 24px; }
.group-discography .tab-list { display: flex; flex-wrap: wrap; gap: 8px; list-style: none; margin: 0; padding: 0; border-bottom: 1px solid rgba(255,255,255,0.15); }
.group-discography .tab-item { padding: 10px 16px; cursor: pointer; color: rgba(255,255,255,0.8); border-bottom: 2px solid transparent; margin-bottom: -1px; }
.group-discography .tab-item:hover { color: #fff; }
.group-discography .tab-item.active { color: #4CAF50; border-bottom-color: #4CAF50; }
.group-discography .tab-count { margin-left: 6px; font-size: 0.9em; opacity: 0.9; }
/* Tab content visibility — show only active tab */
.group-discography .tab-content { display: none !important; }
.group-discography .tab-content.active { display: block !important; }
/* Upload wizard: only show the current step so single/album choice disappears on step 2 and 3 */
#simuze-upload-container[data-current-step="1"] #step-2,
#simuze-upload-container[data-current-step="1"] #step-3 { display: none !important; }
#simuze-upload-container[data-current-step="2"] #step-1,
#simuze-upload-container[data-current-step="2"] #step-3 { display: none !important; }
#simuze-upload-container[data-current-step="3"] #step-1,
#simuze-upload-container[data-current-step="3"] #step-2 { display: none !important; }
#simuze-upload-container .upload-step-content.hidden { display: none !important; }
.upload-type-choices { display: flex !important; gap: 20px; margin: 20px 0; flex-wrap: wrap; }
.upload-type { flex: 1; min-width: 180px; max-width: 280px; padding: 24px 20px; border: 2px solid rgba(255,255,255,0.2); border-radius: 12px; cursor: pointer; text-align: center; transition: border-color .2s, background .2s, box-shadow .2s; background: rgba(255,255,255,0.03); display: block !important; visibility: visible !important; opacity: 1 !important; pointer-events: auto !important; }
.upload-type:hover { border-color: rgba(255,255,255,0.4); background: rgba(255,255,255,0.06); }
.upload-type.selected { border-color: #4CAF50; background: rgba(76,175,80,0.12); box-shadow: 0 0 0 1px #4CAF50; }
.upload-type i { display: block; font-size: 2rem; margin-bottom: 12px; color: rgba(255,255,255,0.9); }
.upload-type.selected i { color: #4CAF50; }
.upload-type h5 { margin: 0 0 8px 0; font-size: 1.1rem; color: #fff; }
.upload-type p { margin: 0; font-size: 0.9rem; color: rgba(255,255,255,0.7); }
.group-discography .upload-actions { margin-top: 24px; display: flex; gap: 12px; flex-wrap: wrap; }
.group-discography .upload-actions .button:disabled,
.group-discography .upload-actions .button.disabled { opacity: 0.6; cursor: not-allowed; }
</style>
<script>
(function() {
    // Always refresh security values on page load; don't keep stale/null values from previous pages.
    var cfg = <?php echo json_encode([
        'ajax_url' => md_url('/api/simezu-ajax.php'),
        'ajaxurl'  => md_url('/api/simezu-ajax.php'),
        'nonce' => md_csrf_token(),
        'upload_nonce' => md_csrf_token(),
        'delete_nonce' => md_csrf_token(),
        'rename_nonce' => md_csrf_token(),
        'playlist_nonce' => md_csrf_token(),
        'favorite_nonce' => md_csrf_token(),
        'group_id' => $group_id,
        'group_name' => $group->name,
        'max_file_size' => 1000 * 1024 * 1024,
        'can_upload' => $can_upload,
        'is_admin' => $is_simezu_super_admin,
        'is_mod' => $is_group_mod,
        'current_user_id' => $current_user_id,
    ]); ?>;

    window.simuze_upload = window.simuze_upload || {};
    Object.assign(window.simuze_upload, cfg);

    // audio-player.js reads window.simuze_ajax for playlist actions.
    window.simuze_ajax = window.simuze_ajax || {};
    window.simuze_ajax.ajaxurl = cfg.ajax_url;
    window.simuze_ajax.nonce = cfg.playlist_nonce;
    window.simuze_ajax.playlist_nonce = cfg.playlist_nonce;
    window.simuze_ajax.favorite_nonce = cfg.favorite_nonce;
    window.simuze_ajax.current_user_id = cfg.current_user_id;
})();
</script>
<?php if ($base_url !== ''): ?>
<script src="<?php echo md_esc_url($theme_js_url . '/music-upload.js'); ?>?v=2.2.4" id="simuze-music-upload-js"></script>
<script src="<?php echo md_esc_url($theme_js_url . '/discography.js'); ?>?v=1.0.3" id="simuze-discography-js"></script>
<?php endif; ?>
<script id="simuze-upload-type-fallback">
(function() {
    function initUploadTypeSelection() {
        if (typeof jQuery === 'undefined') return;
        var $ = jQuery;
        $(document).off('click.simuzeUploadType', '.upload-type').on('click.simuzeUploadType', '.upload-type', function() {
            var type = $(this).data('type');
            $('.upload-type').removeClass('selected');
            $(this).addClass('selected');
            window.uploadType = type;
            $('.next-step').prop('disabled', false).removeClass('disabled');
        });
        if (typeof window.initializeUpload === 'function') window.initializeUpload();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUploadTypeSelection);
    } else {
        initUploadTypeSelection();
    }
})();
</script>
<div class="group-discography">
    <?php if ($show_all_tabs) : ?>
    <div class="tabs">
        <ul class="tab-list clearfix">
            <li class="tab-item <?php echo $current_tab === 'overview' ? 'active' : ''; ?>" data-tab="overview-tab" id="tab-overview">
                <i class="fas fa-music"></i> <?php echo 'Overview'; ?>
                <span class="tab-count" id="overview-count"><?php echo $published_count; ?></span>
            </li>
            <li class="tab-item <?php echo $current_tab === 'in-progress' ? 'active' : ''; ?>" data-tab="in-progress-tab" id="tab-in-progress">
                <i class="fas fa-clock"></i> <?php echo 'In Progress'; ?>
                <span class="tab-count" id="in-progress-count"><?php echo $draft_count; ?></span>
            </li>
            <li class="tab-item <?php echo $current_tab === 'scheduled' ? 'active' : ''; ?>" data-tab="scheduled-tab" id="tab-scheduled">
                <i class="fas fa-calendar-alt"></i> <?php echo 'Scheduled'; ?>
                <span class="tab-count" id="scheduled-count"><?php echo $scheduled_count; ?></span>
            </li>
            <li class="tab-item <?php echo $current_tab === 'under-review' ? 'active' : ''; ?>" data-tab="under-review-tab" id="tab-under-review">
                <i class="fas fa-eye"></i> <?php echo 'Under Review'; ?>
                <span class="tab-count" id="under-review-count"><?php echo $under_review_count; ?></span>
            </li>
            <li class="tab-item <?php echo $current_tab === 'upload' ? 'active' : ''; ?>" data-tab="upload-tab" id="tab-upload">
                <i class="fas fa-upload"></i> <?php echo 'Upload'; ?>
            </li>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Overview Tab Content - Always visible, but only show tabs if show_all_tabs -->
    <div class="tab-content <?php echo ($current_tab === 'overview' || !$show_all_tabs) ? 'active' : ''; ?>" id="overview-tab">
        <?php if (!empty($published_albums)) : ?>
            <div class="music-collection">
                <h3><?php echo 'Published Music'; ?></h3>
                
                
                <?php
                $albums = $published_albums;
                $show_actions = $show_edit_delete_buttons; // Only show actions if user has permission
                $empty_message = 'No published music found.';
                $grid_class = 'albums-grid';
                // Pass group_id to template context
                $template_group_id = $group_id;
                
                // Debug logging
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log('GROUP MUSIC DEBUG: show_edit_delete_buttons=' . ($show_edit_delete_buttons ? 'true' : 'false') . ', is_simezu_super_admin=' . ($is_simezu_super_admin ? 'true' : 'false') . ', is_member=' . ($is_member ? 'true' : 'false') . ', has_admin_rights=' . ($has_admin_rights ? 'true' : 'false') . ', current_user_id=' . $current_user_id . ', albums_count=' . count($albums));
                }

                include(md_locate_template('template-parts/album-grid.php'));
                ?>
            </div>
        <?php else : ?>
            <div class="no-music">
                <i class="fas fa-music"></i>
                <h3><?php echo 'No music uploads yet!'; ?></h3>
                <?php if ($can_upload) : ?>
                    <p><?php echo 'Start by uploading your first track or album.'; ?></p>
                    <button id="show-upload-empty" class="button">
                        <i class="fas fa-upload"></i> <?php echo 'Upload Music'; ?>
                    </button>
                <?php else : ?>
                    <p><?php echo 'No music has been uploaded to this group yet.'; ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($show_all_tabs) : ?>
    <!-- In Progress Tab Content -->
    <div class="tab-content <?php echo $current_tab === 'in-progress' ? 'active' : ''; ?>" id="in-progress-tab">
        <h3><?php echo 'In Progress'; ?></h3>
        <p><?php echo 'Drafts and uploads that are not yet completed.'; ?></p>
        
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
            $empty_message = 'No drafts found.';
            $grid_class = 'albums-grid draft-albums';
            $template_group_id = $group_id; // Pass group_id to album-grid.php
            
            include(md_locate_template('template-parts/album-grid.php'));
            ?>
        <?php else : ?>
            <div class="no-albums-message">
                <i class="fas fa-clock"></i>
                <p><?php echo 'No drafts found. Start a new upload to create drafts.'; ?></p>
                <button class="button show-upload-tab">
                    <i class="fas fa-upload"></i> <?php echo 'Start Upload'; ?>
                </button>
                                </div>
        <?php endif; ?>
    </div>
    
    <!-- Scheduled Tab Content -->
    <div class="tab-content <?php echo $current_tab === 'scheduled' ? 'active' : ''; ?>" id="scheduled-tab">
        <h3><?php echo 'Scheduled'; ?></h3>
        <p><?php echo 'Items scheduled for future release.'; ?></p>
        
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
            $empty_message = 'No scheduled releases found.';
            $grid_class = 'albums-grid scheduled-albums';
            $template_group_id = $group_id; // Pass group_id to album-grid.php
            
            include(md_locate_template('template-parts/album-grid.php'));
            ?>
        <?php else : ?>
            <div class="no-albums-message">
                <i class="fas fa-calendar-alt"></i>
                <p><?php echo 'No scheduled releases found.'; ?></p>
                <p><small><?php echo 'Albums with future release dates will appear here.'; ?></small></p>
                                </div>
        <?php endif; ?>
                            </div>
                            
    <!-- Under Review Tab Content -->
    <div class="tab-content <?php echo $current_tab === 'under-review' ? 'active' : ''; ?>" id="under-review-tab">
        <h3><?php echo 'Under Review'; ?></h3>
        <p><?php echo 'Items that require editorial review before publication.'; ?></p>
        
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
            $empty_message = 'No items under review.';
            $grid_class = 'albums-grid review-albums';
            $template_group_id = $group_id; // Pass group_id to album-grid.php
            
            include(md_locate_template('template-parts/album-grid.php'));
            ?>
        <?php else : ?>
            <div class="no-albums-message">
                <i class="fas fa-eye"></i>
                <p><?php echo 'No items currently under review.'; ?></p>
            </div>
        <?php endif; ?>
                                </div>
                                
    <!-- Upload Tab Content - FULL UPLOAD WIZARD -->
    <div class="tab-content <?php echo $current_tab === 'upload' ? 'active' : ''; ?>" id="upload-tab">
        <div id="simuze-upload-container" class="upload-wizard" data-current-step="1">
            <div class="upload-header">
                <h3><?php echo 'Upload Music'; ?></h3>
                <p><?php echo 'Share your music with the group by uploading tracks or albums.'; ?></p>
                                </div>
                                
            <!-- Step Navigation -->
            <div class="upload-steps">
                <div class="step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-title"><?php echo 'Choose Type'; ?></div>
                                </div>
                <div class="step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-title"><?php echo 'Upload Files'; ?></div>
                                        </div>
                <div class="step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-title"><?php echo 'Add Details'; ?></div>
                                    </div>
                                </div>
                                
            <!-- Step 1: Choose Upload Type -->
            <div id="step-1" class="upload-step-content">
                <h4><?php echo 'What would you like to upload?'; ?></h4>
                <div class="upload-type-choices">
                    <div class="upload-type" data-type="single">
                        <i class="fas fa-music"></i>
                        <h5><?php echo 'Single Track'; ?></h5>
                        <p><?php echo 'Upload one standalone song'; ?></p>
                    </div>
                    <div class="upload-type" data-type="album">
                        <i class="fas fa-compact-disc"></i>
                        <h5><?php echo 'Album'; ?></h5>
                        <p><?php echo 'Upload multiple songs as an album'; ?></p>
                    </div>
                                    </div>
                                </div>
                                
            <!-- Step 2: Upload Files -->
            <div id="step-2" class="upload-step-content hidden">
                <h4><?php echo 'Upload Your Files'; ?></h4>
                <div class="file-upload-area">
                    <input type="file" id="music-files" multiple accept=".mp3,.wav,.aiff,.flac,.m4a,.ogg" class="file-input-hidden">
                    <div id="drag-drop-area" class="upload-dropzone">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                        <p class="upload-text"><?php echo 'Drag and drop your files here, or click to browse'; ?></p>
                        <button type="button" class="button upload-button browse-files" id="browse-files">
                            <i class="fas fa-folder-open"></i> <?php echo 'Browse Files'; ?>
                        </button>
                    </div>
                    <div class="uploaded-files"></div>
                </div>
            </div>

            <!-- Step 3: Add Metadata -->
            <div id="step-3" class="upload-step-content hidden">
                <h4><?php echo 'Add Details'; ?></h4>
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
                    <i class="fas fa-arrow-left"></i> <?php echo 'Previous'; ?>
                </button>
                <button type="button" class="button cancel-upload hidden">
                    <i class="fas fa-times"></i> <?php echo 'Cancel'; ?>
                </button>
                <button type="button" class="button next-step">
                    <?php echo 'Next'; ?> <i class="fas fa-arrow-right"></i>
                </button>
                <button type="button" class="button submit-upload hidden" id="finish-upload">
                    <i class="fas fa-check"></i> <?php echo 'Publish'; ?>
                </button>
                <button type="button" class="button save-draft hidden" id="save-draft">
                    <i class="fas fa-save"></i> <?php echo 'Save as Draft'; ?>
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
        const nonce = '<?php echo md_csrf_token(); ?>';
        
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
            : '<?php echo md_url('/api/simezu-ajax.php'); ?>';
        
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
                nonce: '<?php echo md_csrf_token(); ?>'
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