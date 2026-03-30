<?php
/**
 * AJAX handlers for group music upload (Modular).
 * Registers: simuze_ajax_create_album, simuze_get_tab_counts, simuze_upload_single_file.
 */
if (!defined('SIMUZE_MODULAR_RUN')) {
    return;
}

add_action('wp_ajax_simuze_ajax_create_album', 'simuze_modular_ajax_create_album');
add_action('wp_ajax_simuze_get_tab_counts', 'simuze_modular_ajax_get_tab_counts');
add_action('wp_ajax_simuze_upload_single_file', 'simuze_modular_ajax_upload_single_file');
add_action('wp_ajax_simuze_save_metadata', 'simuze_modular_ajax_save_metadata');

// Album edit actions
add_action('wp_ajax_simuze_update_album',            'simuze_modular_ajax_update_album');
add_action('wp_ajax_simuze_delete_track',            'simuze_modular_ajax_delete_track');
add_action('wp_ajax_simuze_delete_album_frontend',   'simuze_modular_ajax_delete_album_frontend');
add_action('wp_ajax_simuze_change_audio_file',       'simuze_modular_ajax_change_audio_file');
add_action('wp_ajax_simuze_refresh_in_progress_tab', 'simuze_modular_ajax_refresh_in_progress_tab');
add_action('wp_ajax_simuze_search_genres',           'simuze_modular_ajax_search_genres');
add_action('wp_ajax_nopriv_simuze_search_genres',    'simuze_modular_ajax_search_genres');
add_action('wp_ajax_simuze_create_genre',            'simuze_modular_ajax_create_genre');

function simuze_modular_ajax_create_album() {
    if (!function_exists('md_csrf_token') || empty($_POST['_ajax_nonce']) || !hash_equals(md_csrf_token(), $_POST['_ajax_nonce'])) {
        wp_send_json(['success' => false, 'message' => 'Invalid nonce']);
    }
    $user = null;
    if (class_exists('Simuze_Auth')) {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
    }
    if (!$user || empty($user->id)) {
        wp_send_json(['success' => false, 'message' => 'Not logged in']);
    }
    $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
    $upload_type = isset($_POST['upload_type']) ? md_sanitize_text($_POST['upload_type']) : 'single';
    if ($group_id <= 0) {
        wp_send_json(['success' => false, 'message' => 'Missing group_id']);
    }
    $is_album = (strtolower($upload_type) === 'album') ? 1 : 0;
    $slug = 'temp_upload_' . time() . '_' . bin2hex(random_bytes(4));
    $now = date('Y-m-d H:i:s');
    $album_name = $is_album ? 'Draft Album' : 'Draft Single';
    $data = [
        'group_id' => $group_id,
        'artist_id' => $user->id,
        'album_name' => $album_name,
        'album_nicename' => $slug,
        'is_album' => $is_album,
        'status' => 'draft',
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ];
    $album_id = md_db_insert('md_music_albums', $data);
    if (!$album_id) {
        wp_send_json(['success' => false, 'message' => 'Failed to create album']);
    }
    wp_send_json([
        'success' => true,
        'data' => [
            'album_id' => (int) $album_id,
            'post_id' => null,
            'slug' => $slug,
            'reused' => false,
            'album_number' => 1,
        ],
    ]);
}

function simuze_modular_ajax_get_tab_counts() {
    if (!function_exists('md_csrf_token') || empty($_POST['_ajax_nonce']) || !hash_equals(md_csrf_token(), $_POST['_ajax_nonce'])) {
        wp_send_json(['success' => false, 'message' => 'Invalid nonce']);
    }
    $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
    if ($group_id <= 0) {
        wp_send_json(['success' => true, 'data' => ['counts' => ['publish' => 0, 'draft' => 0, 'scheduled' => 0, 'under_review' => 0]]]);
    }
    $publish = (int) md_db_var('SELECT COUNT(*) FROM md_music_albums WHERE group_id = ? AND status = ? AND deleted_at IS NULL', [$group_id, 'published']);
    $draft = (int) md_db_var('SELECT COUNT(*) FROM md_music_albums WHERE group_id = ? AND status = ? AND deleted_at IS NULL', [$group_id, 'draft']);
    $scheduled = (int) md_db_var('SELECT COUNT(*) FROM md_music_albums WHERE group_id = ? AND status = ? AND deleted_at IS NULL', [$group_id, 'scheduled']);
    $under_review = (int) md_db_var('SELECT COUNT(*) FROM md_music_albums WHERE group_id = ? AND status = ? AND deleted_at IS NULL', [$group_id, 'under_review']);
    wp_send_json([
        'success' => true,
        'data' => [
            'counts' => [
                'publish' => $publish,
                'draft' => $draft,
                'scheduled' => $scheduled,
                'under_review' => $under_review,
            ],
        ],
    ]);
}

function simuze_modular_ajax_upload_single_file() {
    if (!function_exists('md_csrf_token') || empty($_POST['_ajax_nonce']) || !hash_equals(md_csrf_token(), $_POST['_ajax_nonce'])) {
        wp_send_json(['success' => false, 'message' => 'Invalid nonce']);
    }
    $user = null;
    if (class_exists('Simuze_Auth')) {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
    }
    if (!$user || empty($user->id)) {
        wp_send_json(['success' => false, 'message' => 'Not logged in']);
    }
    $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
    $album_id = isset($_POST['album_id']) ? (int) $_POST['album_id'] : 0;
    if ($group_id <= 0 || $album_id <= 0) {
        wp_send_json(['success' => false, 'message' => 'Missing group_id or album_id']);
    }
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json(['success' => false, 'message' => 'No file or upload error']);
    }
    $file = $_FILES['file'];
    $allowed = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/aiff', 'audio/x-aiff', 'audio/flac', 'audio/m4a', 'audio/x-m4a', 'audio/ogg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed, true)) {
        wp_send_json(['success' => false, 'message' => 'Invalid file type']);
    }
    $upload_dir = md_upload_dir();
    $basedir = $upload_dir['basedir'];
    $baseurl = $upload_dir['baseurl'];
    $subdir = 'music/group_' . $group_id . '/album_' . $album_id;
    $dir = $basedir . '/' . $subdir;
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            wp_send_json(['success' => false, 'message' => 'Could not create upload directory']);
        }
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'bin';
    $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name'], '.' . $ext));
    $filename = $safe_name . '_' . time() . '.' . strtolower($ext);
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        wp_send_json(['success' => false, 'message' => 'Failed to save file']);
    }
    $relative_path = '/' . $subdir . '/' . $filename;
    $song_name = pathinfo($file['name'], PATHINFO_FILENAME);
    $now = date('Y-m-d H:i:s');
    $row = [
        'group_id' => $group_id,
        'album_id' => $album_id,
        'artist_id' => $user->id,
        'file_path' => $relative_path,
        'original_file_path' => $relative_path,
        'mime_type' => $mime,
        'file_size' => (int) $file['size'],
        'song_name' => $song_name,
        'status' => 'draft',
        'uploaded_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ];
    $upload_id = md_db_insert('md_music_uploads', $row);
    if (!$upload_id) {
        @unlink($dest);
        wp_send_json(['success' => false, 'message' => 'Failed to save record']);
    }
    $play_url = rtrim($baseurl, '/') . $relative_path;
    wp_send_json([
        'success' => true,
        'data' => [
            'files' => [
                [
                    'id' => (int) $upload_id,
                    'url' => $play_url,
                    'name' => $song_name,
                    'conversion_pending' => false,
                ],
            ],
        ],
    ]);
}

/**
 * Save album and track metadata (draft or publish). Handles simuze_save_metadata AJAX action.
 */
function simuze_modular_ajax_save_metadata() {
    if (!function_exists('md_csrf_token') || empty($_POST['_ajax_nonce']) || !hash_equals(md_csrf_token(), $_POST['_ajax_nonce'])) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Invalid nonce']]);
    }
    $user = null;
    if (class_exists('Simuze_Auth')) {
        $auth = Simuze_Auth::get_instance();
        $user = $auth->get_current_user();
    }
    if (!$user || empty($user->id)) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Not logged in']]);
    }
    $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
    $state_json = isset($_POST['state']) ? $_POST['state'] : '';
    $is_draft = !empty($_POST['is_draft']) && $_POST['is_draft'] !== 'false';
    if ($group_id <= 0 || $state_json === '') {
        wp_send_json(['success' => false, 'data' => ['message' => 'Missing group_id or state']]);
    }
    $state = json_decode($state_json, true);
    if (!is_array($state)) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Invalid state JSON']]);
    }
    $album_id = null;
    if (!empty($state['files']) && is_array($state['files']) && isset($state['files'][0]['album_id'])) {
        $album_id = (int) $state['files'][0]['album_id'];
    }
    if (!$album_id && !empty($state['tracks']) && is_array($state['tracks']) && isset($state['tracks'][0]['album_id'])) {
        $album_id = (int) $state['tracks'][0]['album_id'];
    }
    if (!$album_id) {
        wp_send_json(['success' => false, 'data' => ['message' => 'No album_id in state']]);
    }
    $album = md_db_row('SELECT * FROM md_music_albums WHERE id = ? AND group_id = ? AND deleted_at IS NULL', [$album_id, $group_id]);
    if (!$album) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Album not found or access denied']]);
    }
    $now = date('Y-m-d H:i:s');
    $album_data = isset($state['albumData']) && is_array($state['albumData']) ? $state['albumData'] : [];
    $title = isset($album_data['title']) ? trim((string) $album_data['title']) : $album['album_name'];
    if ($title === '') {
        $title = $album['is_album'] ? 'Untitled Album' : 'Untitled Single';
    }
    $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($title));
    $slug = preg_replace('/-+/', '-', trim($slug, '-'));
    if ($slug === '') {
        $slug = 'release-' . $album_id;
    }
    $description = isset($album_data['description']) ? trim((string) $album_data['description']) : ($album['description'] ?? '');
    $release_date = null;
    if (!empty($album_data['releaseDate'])) {
        $ts = strtotime($album_data['releaseDate']);
        if ($ts !== false) {
            $release_date = date('Y-m-d', $ts);
        }
    }
    if ($release_date === null && !empty($album['release_date'])) {
        $release_date = $album['release_date'];
    }
    $license = isset($album_data['license']) ? md_sanitize_text($album_data['license']) : ($album['license'] ?? 'cc-by');
    $status = $is_draft ? 'draft' : 'published';
    $update_album = [
        'album_name' => $title,
        'album_nicename' => $slug,
        'description' => $description,
        'release_date' => $release_date,
        'license' => $license,
        'status' => $status,
        'updated_at' => $now,
    ];
    if (!empty($album_data['pricing']) && is_array($album_data['pricing'])) {
        $pt = $album_data['pricing']['type'] ?? 'free';
        $amount = isset($album_data['pricing']['amount']) ? (float) $album_data['pricing']['amount'] : 0;
        $update_album['is_free'] = ($pt === 'free' || $amount <= 0) ? 1 : 0;
        $update_album['price'] = $amount > 0 ? $amount : null;
    }
    md_db_update('md_music_albums', $update_album, ['id' => $album_id]);
    if (!empty($state['tracks']) && is_array($state['tracks'])) {
        foreach ($state['tracks'] as $track) {
            $tid = isset($track['id']) ? (int) $track['id'] : 0;
            if ($tid <= 0) {
                continue;
            }
            $row = md_db_row('SELECT id FROM md_music_uploads WHERE id = ? AND album_id = ? AND (group_id = ? OR group_id IS NULL)', [$tid, $album_id, $group_id]);
            if (!$row) {
                continue;
            }
            $song_name = isset($track['title']) ? trim((string) $track['title']) : '';
            if ($song_name === '') {
                $song_name = isset($track['name']) ? trim((string) $track['name']) : 'Track ' . $tid;
            }
            $track_update = [
                'song_name' => $song_name,
                'updated_at' => $now,
                'status' => $status,
            ];
            if (isset($track['genre'])) {
                $track_update['genre'] = md_sanitize_text($track['genre']);
            }
            if (isset($track['license'])) {
                $track_update['license'] = md_sanitize_text($track['license']);
            }
            if (!empty($track['releaseDate'])) {
                $ts = strtotime($track['releaseDate']);
                if ($ts !== false) {
                    $track_update['release_date'] = date('Y-m-d', $ts);
                }
            }
            if (!empty($track['pricing']) && is_array($track['pricing'])) {
                $amt = isset($track['pricing']['amount']) ? (float) $track['pricing']['amount'] : 0;
                $track_update['is_free'] = $amt <= 0 ? 1 : 0;
                $track_update['price'] = $amt > 0 ? $amt : null;
            }
            md_db_update('md_music_uploads', $track_update, ['id' => $tid]);
        }
    }
    if (!empty($_FILES['album_artwork_cropped']) && $_FILES['album_artwork_cropped']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = md_upload_dir();
        $basedir = $upload_dir['basedir'];
        $baseurl = $upload_dir['baseurl'];
        $subdir = 'music/group_' . $group_id . '/album_' . $album_id;
        $art_dir = $basedir . '/' . $subdir;
        if (!is_dir($art_dir)) {
            @mkdir($art_dir, 0755, true);
        }
        $ext = pathinfo($_FILES['album_artwork_cropped']['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $art_name = 'artwork_' . $album_id . '_' . time() . '.' . strtolower($ext);
        $art_path = $art_dir . '/' . $art_name;
        if (move_uploaded_file($_FILES['album_artwork_cropped']['tmp_name'], $art_path)) {
            $relative = $subdir . '/' . $art_name;
            md_db_update('md_music_albums', [
                'artwork_path' => $relative,
                'artwork_thumbnail_path' => $relative,
                'updated_at' => $now,
            ], ['id' => $album_id]);
        }
    }
    $redirect_url = '';
    if (!$is_draft && class_exists('MusicRepository')) {
        $updated = md_db_row('SELECT * FROM md_music_albums WHERE id = ?', [$album_id]);
        $redirect_url = MusicRepository::getAlbumUrl($updated);
    }
    wp_send_json([
        'success' => true,
        'data' => [
            'message' => $is_draft ? 'Draft saved.' : 'Published.',
            'redirect_url' => $redirect_url,
        ],
    ]);
}

if (!function_exists('wp_send_json')) {
    function wp_send_json($data) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}

// ---------------------------------------------------------------------------
// Shared nonce helper — accepts _ajax_nonce OR _csrf_token
// ---------------------------------------------------------------------------
function _simuze_check_nonce(): bool {
    $nonce = $_POST['_ajax_nonce'] ?? $_POST['_csrf_token'] ?? '';
    return function_exists('md_csrf_token') && $nonce !== '' && hash_equals(md_csrf_token(), $nonce);
}

// ---------------------------------------------------------------------------
// Permission helper — loads album, verifies group ownership + user membership
// ---------------------------------------------------------------------------
function _simuze_album_permission(int $album_id, int $group_id, int $uid): ?array {
    if ($album_id <= 0 || $group_id <= 0 || $uid <= 0) return null;
    $album = md_db_row('SELECT * FROM md_music_albums WHERE id = ? AND deleted_at IS NULL', [$album_id]);
    if (!$album || (int) $album['group_id'] !== $group_id) return null;
    // Super admins always have permission
    if (class_exists('Simuze_Auth') && Simuze_Auth::get_instance()->is_admin()) return $album;
    $is_member = md_db_var(
        'SELECT id FROM simuze_group_members WHERE group_id = ? AND user_id = ? AND status = ?',
        [$group_id, $uid, 'active']
    );
    if (!$is_member && (int) ($album['created_by'] ?? 0) !== $uid) return null;
    return $album;
}

// ---------------------------------------------------------------------------
// simuze_update_album — save / publish / schedule / set-to-draft
// ---------------------------------------------------------------------------
function simuze_modular_ajax_update_album() {
    if (!_simuze_check_nonce()) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Invalid nonce']]);
    }
    $user = null;
    if (class_exists('Simuze_Auth')) {
        $user = Simuze_Auth::get_instance()->get_current_user();
    }
    if (!$user || empty($user->id)) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Not logged in']]);
    }
    $uid      = (int) $user->id;
    $album_id = (int) ($_POST['album_id'] ?? 0);
    $group_id = (int) ($_POST['group_id'] ?? 0);
    $save_type = trim($_POST['save_type'] ?? 'update');

    $album = _simuze_album_permission($album_id, $group_id, $uid);
    if (!$album) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Album not found or permission denied']]);
    }

    $now = date('Y-m-d H:i:s');

    // Determine new status
    $release_date_raw = trim($_POST['album_release_date'] ?? '');
    $release_ts       = $release_date_raw ? strtotime($release_date_raw) : false;
    switch ($save_type) {
        case 'publish':
            $new_status = 'published';
            break;
        case 'schedule':
            $new_status = ($release_ts && $release_ts > time()) ? 'scheduled' : 'published';
            break;
        case 'draft':
            $new_status = 'draft';
            break;
        default: // 'update'
            $new_status = $album['status'];
    }

    // Build album update
    $album_update = ['status' => $new_status, 'updated_at' => $now];
    $title = trim($_POST['album_title'] ?? '');
    if ($title !== '') {
        $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($title));
        $slug = preg_replace('/-+/', '-', trim($slug, '-')) ?: ('release-' . $album_id);
        $album_update['album_name']     = $title;
        $album_update['album_nicename'] = $slug;
    }
    $desc = $_POST['album_description'] ?? null;
    if ($desc !== null) {
        $album_update['description'] = trim($desc);
    }
    if ($release_ts) {
        $album_update['release_date'] = date('Y-m-d', $release_ts);
    }
    $license = trim($_POST['album_license'] ?? '');
    if ($license !== '') {
        $album_update['license'] = $license;
    }
    $price_raw = $_POST['album_price'] ?? null;
    if ($price_raw !== null && $price_raw !== '') {
        $price = (float) $price_raw;
        $album_update['price']   = $price > 0 ? $price : null;
        $album_update['is_free'] = $price <= 0 ? 1 : 0;
    }
    if ($save_type === 'publish') {
        $album_update['publication_date'] = $now;
    }

    md_db_update('md_music_albums', $album_update, ['id' => $album_id]);

    // Artwork — cropped first, then raw upload
    $artwork_file = null;
    if (!empty($_FILES['cropped_artwork']) && $_FILES['cropped_artwork']['error'] === UPLOAD_ERR_OK) {
        $artwork_file = $_FILES['cropped_artwork'];
    } elseif (!empty($_FILES['new_album_artwork']) && $_FILES['new_album_artwork']['error'] === UPLOAD_ERR_OK) {
        $artwork_file = $_FILES['new_album_artwork'];
    }
    if ($artwork_file) {
        $upload_dir = md_upload_dir();
        $basedir    = $upload_dir['basedir'];
        $subdir     = 'music/group_' . $group_id . '/album_' . $album_id;
        $art_dir    = $basedir . '/' . $subdir;
        if (!is_dir($art_dir)) {
            @mkdir($art_dir, 0755, true);
        }
        $ext      = strtolower(pathinfo($artwork_file['name'], PATHINFO_EXTENSION) ?: 'jpg');
        $art_name = 'artwork_' . $album_id . '_' . time() . '.' . $ext;
        $art_path = $art_dir . '/' . $art_name;
        if (move_uploaded_file($artwork_file['tmp_name'], $art_path)) {
            $relative = $subdir . '/' . $art_name;
            md_db_update('md_music_albums', [
                'artwork_path'           => $relative,
                'artwork_thumbnail_path' => $relative,
                'updated_at'             => $now,
            ], ['id' => $album_id]);
        }
    }

    // Tracks
    $tracks = $_POST['tracks'] ?? [];
    if (is_array($tracks)) {
        foreach ($tracks as $t) {
            $tid = (int) ($t['upload_id'] ?? $t['post_id'] ?? 0);
            if ($tid <= 0) continue;
            $row = md_db_row(
                'SELECT id FROM md_music_uploads WHERE id = ? AND album_id = ? AND deleted_at IS NULL',
                [$tid, $album_id]
            );
            if (!$row) continue;
            $tu = ['updated_at' => $now, 'status' => $new_status];
            if (isset($t['title'])        && trim($t['title']) !== '')   $tu['song_name']    = trim($t['title']);
            if (isset($t['genre']))                                       $tu['genre']        = trim($t['genre']);
            if (isset($t['license'])      && trim($t['license']) !== '') $tu['license']      = trim($t['license']);
            if (!empty($t['release_date'])) {
                $rts = strtotime($t['release_date']);
                if ($rts) $tu['release_date'] = date('Y-m-d', $rts);
            }
            if (isset($t['price']) && $t['price'] !== '') {
                $tp = (float) $t['price'];
                $tu['price']   = $tp > 0 ? $tp : null;
                $tu['is_free'] = $tp <= 0 ? 1 : 0;
            }
            // Moods
            $moods = $t['moods'] ?? [];
            if (!empty($moods)) {
                $map = [
                    'slowFast'        => 'mood_slow_fast',
                    'quietLoud'       => 'mood_quiet_loud',
                    'staticDynamic'   => 'mood_static_dynamic',
                    'sadHappy'        => 'mood_sad_happy',
                    'mellowAggressive'=> 'mood_mellow_aggressive',
                ];
                foreach ($map as $js_key => $col) {
                    if (isset($moods[$js_key])) {
                        $tu[$col] = (int) $moods[$js_key];
                    }
                }
            }
            md_db_update('md_music_uploads', $tu, ['id' => $tid]);
        }
    }

    wp_send_json(['success' => true, 'data' => ['status' => $new_status, 'message' => 'Saved.']]);
}

// ---------------------------------------------------------------------------
// simuze_delete_track — soft-delete a single track
// ---------------------------------------------------------------------------
function simuze_modular_ajax_delete_track() {
    if (!_simuze_check_nonce()) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Invalid nonce']]);
    }
    $user = null;
    if (class_exists('Simuze_Auth')) {
        $user = Simuze_Auth::get_instance()->get_current_user();
    }
    if (!$user || empty($user->id)) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Not logged in']]);
    }
    $uid      = (int) $user->id;
    $track_id = (int) ($_POST['track_id'] ?? 0);
    if ($track_id <= 0) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Invalid track ID']]);
    }
    $track = md_db_row('SELECT * FROM md_music_uploads WHERE id = ? AND deleted_at IS NULL', [$track_id]);
    if (!$track) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Track not found']]);
    }
    $album_id = (int) ($track['album_id'] ?? 0);
    $group_id = (int) ($track['group_id'] ?? 0);
    if (!_simuze_album_permission($album_id, $group_id, $uid)) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Permission denied']]);
    }
    $now = date('Y-m-d H:i:s');
    md_db_update('md_music_uploads', ['deleted_at' => $now, 'status' => 'trash'], ['id' => $track_id]);
    wp_send_json(['success' => true]);
}

// ---------------------------------------------------------------------------
// simuze_delete_album_frontend — soft-delete album + its tracks
// ---------------------------------------------------------------------------
function simuze_modular_ajax_delete_album_frontend() {
    if (!_simuze_check_nonce()) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Invalid nonce']]);
    }
    $user = null;
    if (class_exists('Simuze_Auth')) {
        $user = Simuze_Auth::get_instance()->get_current_user();
    }
    if (!$user || empty($user->id)) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Not logged in']]);
    }
    $uid      = (int) $user->id;
    $album_id = (int) ($_POST['album_id'] ?? 0);
    $group_id = (int) ($_POST['group_id'] ?? 0);
    $album    = _simuze_album_permission($album_id, $group_id, $uid);
    if (!$album) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Album not found or permission denied']]);
    }
    $now = date('Y-m-d H:i:s');
    md_db_update('md_music_albums',  ['deleted_at' => $now, 'status' => 'trash'], ['id' => $album_id]);
    md_db_update('md_music_uploads', ['deleted_at' => $now, 'status' => 'trash'], ['album_id' => $album_id]);
    wp_send_json(['success' => true]);
}

// ---------------------------------------------------------------------------
// simuze_change_audio_file — replace the audio file for a single
// ---------------------------------------------------------------------------
function simuze_modular_ajax_change_audio_file() {
    if (!_simuze_check_nonce()) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Invalid nonce']]);
    }
    $user = null;
    if (class_exists('Simuze_Auth')) {
        $user = Simuze_Auth::get_instance()->get_current_user();
    }
    if (!$user || empty($user->id)) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Not logged in']]);
    }
    $uid      = (int) $user->id;
    $album_id = (int) ($_POST['album_id'] ?? 0);
    $group_id = (int) ($_POST['group_id'] ?? 0);
    $album    = _simuze_album_permission($album_id, $group_id, $uid);
    if (!$album) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Album not found or permission denied']]);
    }
    if (empty($_FILES['new_audio_file']) || $_FILES['new_audio_file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json(['success' => false, 'data' => ['message' => 'No file or upload error']]);
    }
    $file    = $_FILES['new_audio_file'];
    $allowed = ['audio/mpeg','audio/mp3','audio/wav','audio/x-wav','audio/aiff','audio/x-aiff',
                'audio/flac','audio/x-flac','audio/m4a','audio/x-m4a','audio/ogg','audio/mp4'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed, true)) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Invalid file type']]);
    }
    $track = md_db_row(
        'SELECT * FROM md_music_uploads WHERE album_id = ? AND deleted_at IS NULL ORDER BY id ASC LIMIT 1',
        [$album_id]
    );
    if (!$track) {
        wp_send_json(['success' => false, 'data' => ['message' => 'No track found for this album']]);
    }
    $upload_dir = md_upload_dir();
    $basedir    = $upload_dir['basedir'];
    $baseurl    = $upload_dir['baseurl'];
    $subdir     = 'music/group_' . $group_id . '/album_' . $album_id;
    $dir        = $basedir . '/' . $subdir;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'mp3');
    $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name'], '.' . $ext));
    $filename  = $safe_name . '_' . time() . '.' . $ext;
    $dest      = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Failed to save file']]);
    }
    $relative = '/' . $subdir . '/' . $filename;
    $now      = date('Y-m-d H:i:s');
    md_db_update('md_music_uploads', [
        'file_path'          => $relative,
        'original_file_path' => $relative,
        'mime_type'          => $mime,
        'file_size'          => (int) $file['size'],
        'updated_at'         => $now,
    ], ['id' => (int) $track['id']]);
    $url = rtrim($baseurl, '/') . $relative;
    wp_send_json(['success' => true, 'data' => ['file_info' => [
        'filename' => $file['name'],
        'size'     => round($file['size'] / 1024, 1) . ' KB',
        'url'      => $url,
    ]]]);
}

// ---------------------------------------------------------------------------
// simuze_refresh_in_progress_tab — return HTML list of draft albums
// ---------------------------------------------------------------------------
function simuze_modular_ajax_refresh_in_progress_tab() {
    if (!_simuze_check_nonce()) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Invalid nonce']]);
    }
    $group_id = (int) ($_POST['group_id'] ?? 0);
    $drafts   = $group_id > 0
        ? (md_db_query(
            'SELECT id, album_name, updated_at FROM md_music_albums WHERE group_id = ? AND status = ? AND deleted_at IS NULL ORDER BY updated_at DESC LIMIT 20',
            [$group_id, 'draft']
          ) ?: [])
        : [];
    ob_start();
    if (empty($drafts)): ?>
        <p style="color:var(--color-text-muted,#888);padding:.5rem 0;">No drafts.</p>
    <?php else: ?>
        <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.5rem;">
        <?php foreach ($drafts as $d):
            $edit_url = md_home_url('/group/music?edit=true&album_id=' . (int)$d['id'] . '&group_id=' . $group_id);
            $updated  = !empty($d['updated_at']) ? date('j M Y', strtotime($d['updated_at'])) : '';
        ?>
            <li style="display:flex;align-items:center;gap:.6rem;padding:.4rem .6rem;border:1px solid var(--color-border,#2f3642);border-radius:6px;">
                <span style="flex:1;font-size:.9rem;"><?php echo htmlspecialchars((string)$d['album_name'], ENT_QUOTES); ?></span>
                <?php if ($updated): ?><span style="font-size:.75rem;color:#888;"><?php echo $updated; ?></span><?php endif; ?>
                <a href="<?php echo htmlspecialchars($edit_url); ?>" style="font-size:.8rem;">Edit</a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif;
    $html = ob_get_clean();
    wp_send_json(['success' => true, 'data' => ['html' => $html]]);
}

// ---------------------------------------------------------------------------
// simuze_search_genres — autocomplete from existing track genres
// ---------------------------------------------------------------------------
function simuze_modular_ajax_search_genres() {
    $term = trim($_REQUEST['search_term'] ?? $_REQUEST['term'] ?? $_REQUEST['q'] ?? '');
    if ($term === '') {
        $rows = md_db_query(
            "SELECT genre, COUNT(*) AS c FROM md_music_uploads WHERE genre != '' AND genre IS NOT NULL AND deleted_at IS NULL GROUP BY genre ORDER BY c DESC LIMIT 20"
        ) ?: [];
    } else {
        $rows = md_db_query(
            "SELECT DISTINCT genre FROM md_music_uploads WHERE genre LIKE ? AND deleted_at IS NULL ORDER BY genre LIMIT 20",
            ['%' . $term . '%']
        ) ?: [];
    }
    $genres = array_map(fn($r) => ['id' => 0, 'name' => (string)($r['genre'] ?? '')], $rows);
    $genres = array_values(array_filter($genres, fn($g) => $g['name'] !== ''));
    wp_send_json(['success' => true, 'data' => ['genres' => $genres]]);
}

// ---------------------------------------------------------------------------
// simuze_create_genre — return normalised genre name (no genres table)
// ---------------------------------------------------------------------------
function simuze_modular_ajax_create_genre() {
    if (!_simuze_check_nonce()) {
        wp_send_json(['success' => false, 'data' => ['message' => 'Invalid nonce']]);
    }
    $name = trim($_POST['genre_name'] ?? '');
    if ($name === '') {
        wp_send_json(['success' => false, 'data' => ['message' => 'Genre name required']]);
    }
    wp_send_json(['success' => true, 'data' => ['genre' => ['id' => 0, 'name' => $name], 'created' => true]]);
}
