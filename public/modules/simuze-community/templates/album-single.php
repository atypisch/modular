<?php
/**
 * Single album view for /music/album/{slug}
 * Expects $album, $group (optional), $tracks (array).
 */
if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}
$album  = $album ?? null;
$group  = $group ?? null;
$tracks = $tracks ?? [];
if (!$album || empty($album['id'])) {
    echo '<div class="simuze-notice"><p>' . __('Album not found.', 'simuze') . '</p></div>';
    return;
}
$artwork_url = class_exists('MusicRepository') ? MusicRepository::getAlbumArtworkUrl($album) : '';
$edit_url = '';
if ($group && method_exists($group, 'get_url')) {
    $edit_url = rtrim($group->get_url(), '/') . '/music?edit=true&album_id=' . (int) $album['id'] . '&group_id=' . (int) $album['group_id'];
}
$release_date = $album['release_date'] ?? $album['created_at'] ?? '';
$group_name = $group ? $group->name : '';

// Build track data for the Simuze audio player so we can reuse the exact WP player UI.
$formatted_tracks = [];
if (!empty($tracks) && class_exists('MusicRepository')) {
    foreach ($tracks as $index => $t) {
        if (!is_array($t)) {
            continue;
        }
        $track_id   = (int) ($t['id'] ?? 0);
        $song_name  = $t['song_name'] ?? $t['title'] ?? ('Track ' . ($index + 1));
        $artist_name = $group_name ?: ($t['artist_name'] ?? '');
        $artist_url  = $group && method_exists($group, 'get_url')
            ? rtrim($group->get_url(), '/') . '/music'
            : ($t['artist_url'] ?? md_home_url('/music'));

        $album_name = $album['album_name'] ?? '';
        $album_url  = method_exists('MusicRepository', 'getAlbumUrl')
            ? MusicRepository::getAlbumUrl($album)
            : md_home_url('/music/album/' . ($album['album_nicename'] ?? $album['id']));

        $file_url    = method_exists('MusicRepository', 'getAudioUrl') ? MusicRepository::getAudioUrl($t) : ($t['file_url'] ?? '');
        $track_cover = $artwork_url ?: ($t['artwork_url'] ?? ($t['cover_url'] ?? ''));

        $formatted_tracks[] = [
            'id'           => $track_id,
            'song_name'    => $song_name,
            'artist_name'  => $artist_name,
            'artist_url'   => $artist_url,
            'album_name'   => $album_name,
            'album_url'    => $album_url,
            'file_url'     => $file_url,
            'artwork_url'  => $track_cover,
            // Optional extras used by the player for actions; safe defaults here.
            'genre'        => $t['genre'] ?? '',
            'license'      => $t['license'] ?? ($t['license_short'] ?? ''),
            'upload_id'    => $t['id'] ?? null,
            'track_post_id'=> 0,
            'song_url'     => md_home_url('/music/album/' . ($album['album_nicename'] ?? $album['id'])),
        ];
    }
}
?>
<div class="album-single-view group-discography">
    <div class="album-single-header">
        <?php if ($artwork_url) : ?>
        <div class="album-single-artwork">
            <img src="<?php echo esc_url($artwork_url); ?>" alt="<?php echo esc_attr($album['album_name'] ?? ''); ?>" class="album-cover">
        </div>
        <?php else : ?>
        <div class="album-single-artwork album-placeholder"><i class="fas fa-music"></i></div>
        <?php endif; ?>
        <div class="album-single-meta">
            <h1 class="album-title"><?php echo esc_html($album['album_name'] ?? 'Album'); ?></h1>
            <?php if ($group_name) : ?>
            <p class="album-artist"><a href="<?php echo $group ? esc_url(rtrim($group->get_url(), '/') . '/music') : '#'; ?>"><?php echo esc_html($group_name); ?></a></p>
            <?php endif; ?>
            <?php if ($release_date) : ?>
            <p class="album-release-date"><?php echo esc_html(function_exists('date_i18n') ? date_i18n(get_option('date_format'), strtotime($release_date)) : date('F j, Y', strtotime($release_date))); ?></p>
            <?php endif; ?>
            <?php if ($edit_url) : ?>
            <p><a href="<?php echo esc_url($edit_url); ?>" class="button"><?php _e('Edit album', 'simuze'); ?></a></p>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($album['description'])) : ?>
    <div class="album-description"><?php echo nl2br(esc_html($album['description'])); ?></div>
    <?php endif; ?>
    <?php if (!empty($formatted_tracks) && function_exists('simuze_generate_audio_player')) : ?>
        <div class="album-player-wrapper">
            <?php
            echo simuze_generate_audio_player($formatted_tracks, [
                'disable_drag_drop'      => true,
                'show_download_buttons'  => true,
                'show_playlist'          => true,
                'show_timeline'          => true,
                'show_controls'          => true,
                'show_artwork'           => false,
                'large_player_title'     => __('Now Playing', 'simuze'),
                'playlist_title'         => __('Tracks', 'simuze'),
            ]);
            ?>
        </div>
    <?php elseif (!empty($tracks)) : ?>
        <ul class="album-tracks">
            <?php foreach ($tracks as $i => $t) : ?>
                <li class="track-item"><?php echo esc_html($t['song_name'] ?? ('Track ' . ($i + 1))); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<style>
.album-single-view { padding: 24px 0; max-width: 800px; margin: 0 auto; }
.album-single-header { display: flex; gap: 24px; margin-bottom: 24px; align-items: flex-start; }
.album-single-artwork { flex-shrink: 0; }
.album-single-artwork img { width: 200px; height: 200px; object-fit: cover; border-radius: 8px; }
.album-single-artwork.album-placeholder { width: 200px; height: 200px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 48px; background: linear-gradient(135deg, #3a3a3a 0%, #51ac32 100%); color: rgba(255,255,255,0.9); }
.album-single-meta .album-title { margin: 0 0 8px 0; font-size: 1.75rem; }
.album-single-meta .album-artist { margin: 0 0 4px 0; }
.album-single-meta .button { margin-top: 12px; }
.album-description { margin-bottom: 24px; line-height: 1.6; }
.album-tracks { list-style: none; padding: 0; margin: 0; }
.album-tracks .track-item { padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.08); }
</style>
