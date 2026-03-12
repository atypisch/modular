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
    $upload_type = isset($_POST['upload_type']) ? sanitize_text_field($_POST['upload_type']) : 'single';
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
    $license = isset($album_data['license']) ? sanitize_text_field($album_data['license']) : ($album['license'] ?? 'cc-by');
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
                $track_update['genre'] = sanitize_text_field($track['genre']);
            }
            if (isset($track['license'])) {
                $track_update['license'] = sanitize_text_field($track['license']);
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
