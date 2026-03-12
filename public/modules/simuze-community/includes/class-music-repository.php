<?php
/**
 * MusicRepository
 *
 * All data access for md_music_albums and md_music_uploads.
 * Replaces direct $wpdb calls in templates and classes.
 * Uses md_db() / md_db_*() helpers — no $wpdb dependency.
 */
class MusicRepository {

    // ------------------------------------------------------------------
    // Albums
    // ------------------------------------------------------------------

    /**
     * Find a single album by its primary key.
     */
    public static function findAlbumById(int $id): ?array {
        return md_db_row('SELECT * FROM md_music_albums WHERE id = ?', [$id]);
    }

    /**
     * Find a published album by slug or album_nicename (for /music/album/{slug} URLs).
     */
    public static function findAlbumBySlug(string $slug): ?array {
        if ($slug === '') {
            return null;
        }
        if (is_numeric($slug)) {
            $row = md_db_row('SELECT * FROM md_music_albums WHERE id = ? AND status = ? AND deleted_at IS NULL', [$slug, 'published']);
            return $row ?: null;
        }
        $row = md_db_row(
            'SELECT * FROM md_music_albums WHERE album_nicename = ? AND status = ? AND deleted_at IS NULL',
            [$slug, 'published']
        );
        return $row ?: null;
    }

    /**
     * Find all albums belonging to a group, optionally filtered by status.
     * @param string|null $status  'published' | 'draft' | null (all)
     */
    public static function findAlbumsByGroup(int $group_id, ?string $status = 'published'): array {
        if ($status !== null) {
            return md_db_query(
                'SELECT * FROM md_music_albums WHERE group_id = ? AND status = ? ORDER BY created_at DESC',
                [$group_id, $status]
            );
        }
        return md_db_query(
            'SELECT * FROM md_music_albums WHERE group_id = ? ORDER BY created_at DESC',
            [$group_id]
        );
    }

    /**
     * Find all albums by an artist (user), optionally filtered by status.
     */
    public static function findAlbumsByArtist(int $artist_id, ?string $status = 'published'): array {
        if ($status !== null) {
            return md_db_query(
                'SELECT * FROM md_music_albums WHERE artist_id = ? AND status = ? ORDER BY created_at DESC',
                [$artist_id, $status]
            );
        }
        return md_db_query(
            'SELECT * FROM md_music_albums WHERE artist_id = ? ORDER BY created_at DESC',
            [$artist_id]
        );
    }

    /**
     * Count albums for a group.
     */
    public static function countAlbumsByGroup(int $group_id, string $status = 'published'): int {
        return (int) md_db_var(
            'SELECT COUNT(*) FROM md_music_albums WHERE group_id = ? AND status = ?',
            [$group_id, $status]
        );
    }

    // ------------------------------------------------------------------
    // Tracks
    // ------------------------------------------------------------------

    /**
     * Find a single track by its primary key.
     */
    public static function findTrackById(int $id): ?array {
        return md_db_row('SELECT * FROM md_music_uploads WHERE id = ?', [$id]);
    }

    /**
     * Find all tracks belonging to an album.
     */
    public static function findTracksByAlbum(int $album_id): array {
        return md_db_query(
            'SELECT * FROM md_music_uploads WHERE album_id = ? ORDER BY track_number ASC, id ASC',
            [$album_id]
        );
    }

    /**
     * Find tracks uploaded by a user, optionally limited.
     */
    public static function findTracksByArtist(int $artist_id, int $limit = 50): array {
        return md_db_query(
            'SELECT * FROM md_music_uploads WHERE artist_id = ? ORDER BY created_at DESC LIMIT ?',
            [$artist_id, $limit]
        );
    }

    /**
     * Count tracks for a group across all its albums.
     */
    public static function countTracksByGroup(int $group_id): int {
        return (int) md_db_var(
            'SELECT COUNT(*) FROM md_music_uploads u
             INNER JOIN md_music_albums a ON u.album_id = a.id
             WHERE a.group_id = ?',
            [$group_id]
        );
    }

    // ------------------------------------------------------------------
    // URL / path helpers
    // ------------------------------------------------------------------

    /**
     * Build the playback-ready audio URL for a track.
     * Converts WAV path → MP3 path; falls back to original if MP3 missing.
     */
    public static function getAudioUrl(array $track): string {
        if (empty($track['file_path'])) return '';
        $uploads = md_upload_dir();
        $mp3_path = str_replace('/original/', '/mp3/', $track['file_path']);
        $mp3_path = preg_replace('/\.wav$/i', '.mp3', $mp3_path);
        if (file_exists($uploads['basedir'] . $mp3_path)) {
            return $uploads['baseurl'] . $mp3_path;
        }
        return $uploads['baseurl'] . $track['file_path'];
    }

    /**
     * Build the artwork URL for an album.
     * Returns empty string when no artwork is set.
     */
    public static function getAlbumArtworkUrl(array $album): string {
        // Prefer expliciete thumbnail als die beschikbaar is, anders volledige artwork.
        $pathField = '';
        if (!empty($album['artwork_thumbnail_path'])) {
            $pathField = $album['artwork_thumbnail_path'];
        } elseif (!empty($album['artwork_path'])) {
            $pathField = $album['artwork_path'];
        }
        if ($pathField === '') {
            return '';
        }
        $uploads = md_upload_dir();
        $path = ltrim($pathField, '/');
        // If it already starts with basedir, strip it
        if (strpos($path, ltrim($uploads['basedir'], '/')) === 0) {
            $relative = substr($path, strlen(ltrim($uploads['basedir'], '/')));
            return $uploads['baseurl'] . '/' . ltrim($relative, '/');
        }
        return $uploads['baseurl'] . '/' . $path;
    }

    /**
     * Build the album detail URL.
     * /music/album/{slug}  (Modular route)
     * @param array|null $album Album row (slug, album_nicename, or id)
     */
    public static function getAlbumUrl(?array $album): string {
        if ($album === null || empty($album)) {
            return '';
        }
        $slug = $album['slug'] ?? $album['album_nicename'] ?? $album['id'] ?? '';
        return md_home_url('/music/album/' . $slug);
    }
}
