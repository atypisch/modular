<?php
/**
 * Public Group Music Template
 * Shows published albums and singles for Artist groups (public view)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verify this is an Artist group
$group_type_data = $group->get_group_type();
$group_type_slug = $group_type_data ? $group_type_data['slug'] : null;

if ($group_type_slug !== 'artist') {
    echo '<div class="access-denied"><p>This page is only available for Artist groups.</p></div>';
    return;
}

// Determine if the current user can manage this group's music (uploader/admin): they see card style with border + edit/delete
if (!isset($current_user)) {
    $auth = Simuze_Auth::get_instance();
    $current_user = $auth->get_current_user();
}
$is_simezu_super_admin = $current_user && class_exists('Simuze_Auth') && Simuze_Auth::get_instance()->is_admin();
$can_manage_music = $is_simezu_super_admin || ($current_user && $group->can_manage($current_user->id));

// Get published albums and singles for this group
global $wpdb;

// Get all published albums (including singles - is_album = 0)
$albums_table = $wpdb->prefix . 'music_albums';
$albums_data = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, p.ID as album_post_id, p.post_title, p.post_content, p.post_date
     FROM {$albums_table} a
     INNER JOIN {$wpdb->postmeta} pm ON pm.meta_value = a.id AND pm.meta_key = '_simuze_album_id'
     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_type = 'album' AND p.post_status = 'publish'
     WHERE a.group_id = %d
     AND a.status = 'published'
     ORDER BY a.release_date DESC, a.id DESC",
    $group->id
), ARRAY_A);

// Process albums for display
// Prefer shared Modular helper when available, so artwork is resolved
// from a single source of truth. Fall back to wp_upload_dir() logic
// when running inside classic WordPress without MusicRepository.
$albums = array();
$upload_dir = function_exists('wp_upload_dir') ? wp_upload_dir() : null;

foreach ($albums_data as $album_data) {
    // Single source-of-truth artwork URL when MusicRepository is available
    if (class_exists('MusicRepository')) {
        $artwork_url = MusicRepository::getAlbumArtworkUrl($album_data);
    } else {
        $artwork_url = '';
        if ($upload_dir && !empty($album_data['artwork_path'])) {
            $artwork_url = $upload_dir['baseurl'] . '/' . str_replace($upload_dir['basedir'] . '/', '', $album_data['artwork_path']);
        } elseif ($upload_dir && !empty($album_data['artwork_thumbnail_path'])) {
            $artwork_url = $upload_dir['baseurl'] . '/' . str_replace($upload_dir['basedir'] . '/', '', $album_data['artwork_thumbnail_path']);
        }
    }
    
    // Get track count
    $track_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = 'track'
         AND p.post_status = 'publish'
         AND pm.meta_key = '_simuze_album_id'
         AND pm.meta_value = %d",
        $album_data['id']
    ));
    
    // Get album URL
    $album_url = simuze_get_album_url($album_data['album_post_id'], $group->id);
    
    // Determine if it's a single or album
    $is_single = isset($album_data['is_album']) && $album_data['is_album'] == 0;
    
    // Format release date - album-grid.php uses formatted_release_date or post_date
    $formatted_release_date = '';
    $release_date_for_display = $album_data['release_date'] ?: $album_data['post_date'];
    if (!empty($release_date_for_display)) {
        $formatted_release_date = date_i18n(get_option('date_format'), strtotime($release_date_for_display));
    }
    
    $albums[] = array(
        'id' => $album_data['id'],
        'album_name' => $album_data['album_name'] ?: $album_data['post_title'],
        'description' => $album_data['post_content'],
        'artwork_url' => $artwork_url,
        'artwork_thumbnail_url' => $artwork_url, // Use same for thumbnail
        'release_date' => $release_date_for_display,
        'formatted_release_date' => $formatted_release_date,
        'post_date' => $album_data['post_date'], // album-grid.php uses this for album_date
        'track_count' => $track_count,
        'view_url' => $album_url, // album-grid.php uses 'view_url'
        'album_url' => $album_url,
        'album_post_id' => $album_data['album_post_id'],
        'is_single' => $is_single,
        'group_id' => $group->id,
        'status' => 'publish', // All are published
        'artist_name' => $group->name, // Group name as artist
        'artist_id' => $group->id // Group ID as artist ID
    );
}
?>

<div class="group-music-public">
    <h2>Music</h2>
    
    <?php if (!empty($albums)): ?>
        <?php
        // For users who can manage this group's music: show cards with border + edit/delete (albums-grid).
        // For everyone else: standard public cards, no border, no actions (albums-discovery-grid).
        $grid_class = $can_manage_music ? 'albums-grid' : 'albums-discovery-grid';
        $template_group_id = $group->id;
        $show_actions = $can_manage_music;
        include get_template_directory() . '/template-parts/album-grid.php';
        ?>
    <?php else: ?>
        <div class="no-music">
            <p>No published music yet.</p>
        </div>
    <?php endif; ?>
</div>

<style>
.group-music-public {
    padding: 20px 0;
}

.group-music-public h2 {
    margin-bottom: 30px;
    color: #fff;
    font-size: 1.8rem;
}

.no-music {
    text-align: center;
    padding: 60px 20px;
    color: rgba(255,255,255,0.6);
}

.no-music p {
    font-size: 1.1rem;
}
</style>
