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
// Get all published albums (including singles) directly from Modular table
$albums_data = md_db_query(
    "SELECT * FROM md_music_albums WHERE group_id = ? AND status = 'published' ORDER BY release_date DESC, id DESC",
    [$group->id]
);

// Process albums for display – always derive artwork from a single source of truth
// (md_music_albums.artwork_path / artwork_thumbnail_path via MusicRepository).
$albums = array();

foreach ($albums_data as $album_data) {
    // Single source-of-truth artwork URL
    $artwork_url = class_exists('MusicRepository')
        ? MusicRepository::getAlbumArtworkUrl($album_data)
        : '';
    
    // Get track count from Modular uploads table
    $track_count = (int) md_db_var(
        "SELECT COUNT(*) FROM md_music_uploads WHERE album_id = ? AND status != 'deleted'",
        [$album_data['id']]
    );
    
    // Get album URL — md_music_albums has album_nicename; use MusicRepository or md_home_url in Modular
    $album_nicename = !empty($album_data['album_nicename']) ? $album_data['album_nicename'] : $album_data['id'];
    $album_url = function_exists('md_home_url') ? md_home_url('/music/album/' . $album_nicename) : (function_exists('base_url') ? base_url('/music/album/' . $album_nicename) : '');

    // Determine if it's a single or album
    $is_single = isset($album_data['is_album']) && $album_data['is_album'] == 0;

    // Format release date — md_music_albums uses release_date and created_at (no post_date)
    $formatted_release_date = '';
    $release_date_for_display = $album_data['release_date'] ?: $album_data['created_at'];
    if (!empty($release_date_for_display)) {
        $formatted_release_date = date('j F Y', strtotime($release_date_for_display));
    }

    $albums[] = array(
        'id' => $album_data['id'],
        'album_name' => $album_data['album_name'],
        'description' => $album_data['description'],
        'artwork_url' => $artwork_url,
        'artwork_thumbnail_url' => $artwork_url,
        'release_date' => $release_date_for_display,
        'formatted_release_date' => $formatted_release_date,
        'post_date' => $album_data['created_at'],
        'track_count' => $track_count,
        'view_url' => $album_url, // album-grid.php uses 'view_url' for array-format albums
        'album_url' => $album_url,
        'album_post_id' => $album_data['post_id'],
        'is_single' => $is_single,
        'group_id' => $group->id,
        'status' => 'publish',
        'artist_name' => $group->name,
        'artist_id' => $group->id
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
