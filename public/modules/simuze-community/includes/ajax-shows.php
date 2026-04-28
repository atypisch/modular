<?php
/**
 * Show / Event AJAX handlers.
 * Attendance, comments, guestlist, organiser dashboard data.
 * All handlers registered in module.php init action.
 */
if (!defined('SIMUZE_MODULAR_RUN') && !defined('ABSPATH')) {
    exit;
}

/**
 * Permission helper: can $uid manage this event?
 */
function _simuze_event_can_manage(int $event_id, int $uid): bool {
    $event = md_db_row('SELECT id, organiser_type, organiser_id FROM simuze_events WHERE id = ? AND deleted_at IS NULL LIMIT 1', [$event_id]);
    if (!$event) {
        return false;
    }
    if ($event['organiser_type'] === 'user') {
        return (int) $event['organiser_id'] === $uid;
    }
    // group organiser — check membership
    $is_member = md_db_var(
        'SELECT id FROM simuze_group_members WHERE group_id = ? AND user_id = ? AND status = ? LIMIT 1',
        [(int) $event['organiser_id'], $uid, 'active']
    );
    return (bool) $is_member;
}

/**
 * Nonce helper: accept _ajax_nonce, nonce, or _csrf_token.
 * Normalises to $_POST['_csrf_token'] so md_verify_csrf() can read it.
 */
function _simuze_show_check_nonce(): bool {
    // md_verify_csrf() / csrf_verify() always reads $_POST['_csrf_token'].
    // Normalise whatever field the client sent so the verifier can find it.
    if (empty($_POST['_csrf_token'])) {
        $alt = $_POST['_ajax_nonce'] ?? $_POST['nonce'] ?? '';
        if ($alt !== '') {
            $_POST['_csrf_token'] = $alt;
        }
    }
    return md_verify_csrf();
}

// ---------------------------------------------------------------------------
// Attendance toggle: "I'm going"
// ---------------------------------------------------------------------------
function simuze_ajax_toggle_attendance(): void {
    if (!_simuze_show_check_nonce()) {
        echo json_encode(['success' => false, 'data' => 'Security check failed']);
        exit;
    }
    $auth = class_exists('Simuze_Auth') ? Simuze_Auth::get_instance()->get_current_user() : null;
    if (!$auth || empty($auth->id)) {
        echo json_encode(['success' => false, 'data' => 'Login required']);
        exit;
    }
    $uid      = (int) $auth->id;
    $event_id = (int) ($_POST['event_id'] ?? 0);
    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'data' => 'Missing event']);
        exit;
    }
    $exists = md_db_var(
        'SELECT id FROM simuze_event_attendance WHERE event_id = ? AND user_id = ? LIMIT 1',
        [$event_id, $uid]
    );
    if ($exists) {
        md_db_query('DELETE FROM simuze_event_attendance WHERE event_id = ? AND user_id = ?', [$event_id, $uid]);
        $attending = false;
    } else {
        md_db_insert('simuze_event_attendance', [
            'event_id'   => $event_id,
            'user_id'    => $uid,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $attending = true;
    }
    $count = (int) md_db_var('SELECT COUNT(*) FROM simuze_event_attendance WHERE event_id = ?', [$event_id]);
    echo json_encode(['success' => true, 'data' => ['attending' => $attending, 'count' => $count]]);
    exit;
}

// ---------------------------------------------------------------------------
// Post a comment on an event
// ---------------------------------------------------------------------------
function simuze_ajax_post_event_comment(): void {
    if (!_simuze_show_check_nonce()) {
        echo json_encode(['success' => false, 'data' => 'Security check failed']);
        exit;
    }
    $auth = class_exists('Simuze_Auth') ? Simuze_Auth::get_instance()->get_current_user() : null;
    if (!$auth || empty($auth->id)) {
        echo json_encode(['success' => false, 'data' => 'Login required']);
        exit;
    }
    $uid      = (int) $auth->id;
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $body     = trim(strip_tags((string) ($_POST['body'] ?? '')));
    if ($event_id <= 0 || $body === '') {
        echo json_encode(['success' => false, 'data' => 'Missing data']);
        exit;
    }
    $body = mb_substr($body, 0, 2000);
    $now  = date('Y-m-d H:i:s');
    md_db_insert('simuze_event_comments', [
        'event_id'   => $event_id,
        'user_id'    => $uid,
        'body'       => $body,
        'status'     => 'visible',
        'created_at' => $now,
    ]);
    // Build comment HTML for immediate injection
    $display_name = md_db_var(
        'SELECT display_name FROM simuze_users WHERE id = ? LIMIT 1',
        [$uid]
    ) ?: 'User';
    $html = '<div class="event-comment">'
        . '<span class="event-comment-author">' . md_esc_html($display_name) . '</span>'
        . ' <span class="event-comment-time">' . md_esc_html(date('j M Y H:i', strtotime($now))) . '</span>'
        . '<p class="event-comment-body">' . nl2br(md_esc_html($body)) . '</p>'
        . '</div>';
    echo json_encode(['success' => true, 'data' => ['comment_html' => $html]]);
    exit;
}

// ---------------------------------------------------------------------------
// Add a guestlist entry (organiser only)
// ---------------------------------------------------------------------------
function simuze_ajax_add_guestlist_entry(): void {
    if (!_simuze_show_check_nonce()) {
        echo json_encode(['success' => false, 'data' => 'Security check failed']);
        exit;
    }
    $auth = class_exists('Simuze_Auth') ? Simuze_Auth::get_instance()->get_current_user() : null;
    if (!$auth || empty($auth->id)) {
        echo json_encode(['success' => false, 'data' => 'Login required']);
        exit;
    }
    $uid      = (int) $auth->id;
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $name     = trim(strip_tags((string) ($_POST['name'] ?? '')));
    $email    = trim((string) ($_POST['email'] ?? ''));
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    $note     = trim(strip_tags((string) ($_POST['note'] ?? '')));
    if ($event_id <= 0 || $name === '') {
        echo json_encode(['success' => false, 'data' => 'Name and event required']);
        exit;
    }
    if (!_simuze_event_can_manage($event_id, $uid)) {
        echo json_encode(['success' => false, 'data' => 'Not authorised']);
        exit;
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'data' => 'Invalid email']);
        exit;
    }
    md_db_insert('simuze_event_guestlist', [
        'event_id'          => $event_id,
        'added_by_user_id'  => $uid,
        'name'              => mb_substr($name, 0, 255),
        'email'             => $email ?: null,
        'quantity'          => $quantity,
        'note'              => $note ?: null,
        'created_at'        => date('Y-m-d H:i:s'),
    ]);
    echo json_encode(['success' => true, 'data' => ['name' => $name, 'quantity' => $quantity]]);
    exit;
}

// ---------------------------------------------------------------------------
// Get organiser dashboard data (JSON, for JS refresh)
// ---------------------------------------------------------------------------
function simuze_ajax_get_organiser_dashboard(): void {
    if (!_simuze_show_check_nonce()) {
        echo json_encode(['success' => false, 'data' => 'Security check failed']);
        exit;
    }
    $auth = class_exists('Simuze_Auth') ? Simuze_Auth::get_instance()->get_current_user() : null;
    if (!$auth || empty($auth->id)) {
        echo json_encode(['success' => false, 'data' => 'Login required']);
        exit;
    }
    $uid      = (int) $auth->id;
    $event_id = (int) ($_POST['event_id'] ?? $_GET['event_id'] ?? 0);
    if ($event_id <= 0 || !_simuze_event_can_manage($event_id, $uid)) {
        echo json_encode(['success' => false, 'data' => 'Not authorised']);
        exit;
    }
    $orders    = md_db_query(
        'SELECT id, buyer_name, buyer_email, quantity, total_price, currency, status, created_at FROM simuze_event_orders WHERE event_id = ? AND status = ? ORDER BY created_at DESC',
        [$event_id, 'paid']
    ) ?: [];
    $tickets   = md_db_query(
        'SELECT id, ticket_code, buyer_email, status, issued_at FROM simuze_event_tickets WHERE event_id = ? ORDER BY id ASC',
        [$event_id]
    ) ?: [];
    $guestlist = md_db_query(
        'SELECT id, name, email, quantity, note, created_at FROM simuze_event_guestlist WHERE event_id = ? ORDER BY created_at ASC',
        [$event_id]
    ) ?: [];
    echo json_encode(['success' => true, 'data' => compact('orders', 'tickets', 'guestlist')]);
    exit;
}

// ---------------------------------------------------------------------------
// Search Simuze artists (users) — no auth required, used by show form
// ---------------------------------------------------------------------------
function simuze_ajax_search_artists(): void {
    $q = trim(strip_tags($_REQUEST['query'] ?? $_REQUEST['q'] ?? ''));
    if (strlen($q) < 2) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    $like  = '%' . $q . '%';
    $users = md_db_query(
        "SELECT id, display_name, username, avatar_url FROM simuze_users
         WHERE (display_name LIKE ? OR username LIKE ?)
         ORDER BY display_name ASC LIMIT 20",
        [$like, $like]
    ) ?: [];
    $groups = md_db_query(
        "SELECT id, name, slug, group_type FROM simuze_groups
         WHERE deleted_at IS NULL
           AND (name LIKE ? OR slug LIKE ?)
           AND (group_type = 'artist' OR group_type = 'band' OR group_type = 'collective')
         ORDER BY name ASC LIMIT 20",
        [$like, $like]
    ) ?: [];

    $user_items = array_map(fn($u) => [
        'id'         => (int) $u['id'],
        'label'      => $u['display_name'] ?: $u['username'],
        'username'   => $u['username'],
        'avatar_url' => $u['avatar_url'] ?? '',
        'type'       => 'user',
        'kind'       => 'user',
    ], $users);
    $group_items = array_map(fn($g) => [
        'id'         => (int) $g['id'],
        'label'      => (string) ($g['name'] ?? ''),
        'username'   => (string) ($g['slug'] ?? ''),
        'avatar_url' => '',
        'type'       => 'group',
        'kind'       => 'group',
    ], $groups);

    echo json_encode(['success' => true, 'data' => array_values(array_merge($user_items, $group_items))]);
    exit;
}

// ---------------------------------------------------------------------------
// Get matching artists for a show — used by show add/edit form
// ---------------------------------------------------------------------------
function simuze_get_matching_artists(): void {
    if (!class_exists('ShowMatcher')) {
        // Lazy-load in case it was not included yet
        $path = __DIR__ . '/class-show-matcher.php';
        if (is_file($path)) require_once $path;
    }

    $show_id = isset($_REQUEST['show_id']) ? (int) $_REQUEST['show_id'] : null;

    if ($show_id) {
        $event = md_db_row('SELECT genres, mood, city_id, type FROM simuze_events WHERE id = ? AND deleted_at IS NULL LIMIT 1', [$show_id]);
        if (!$event) {
            echo json_encode(['success' => false, 'message' => 'Show not found']);
            exit;
        }
        $genres   = is_array($event['genres']) ? $event['genres'] : (json_decode((string) $event['genres'], true) ?: []);
        $show_data = [
            'genres'  => $genres,
            'mood'    => $event['mood'],
            'city_id' => $event['city_id'] ? (int) $event['city_id'] : null,
            'type'    => $event['type'],
        ];
    } else {
        // Inline params from form fields
        $genres_raw = $_REQUEST['genres'] ?? '';
        $genres     = $genres_raw !== '' ? array_values(array_filter(array_map('trim', explode(',', $genres_raw)))) : [];
        $show_data  = [
            'genres'  => $genres,
            'mood'    => $_REQUEST['mood'] ?? null,
            'city_id' => isset($_REQUEST['city_id']) && $_REQUEST['city_id'] !== '' ? (int) $_REQUEST['city_id'] : null,
            'type'    => $_REQUEST['type'] ?? 'show',
        ];
    }

    $matches = class_exists('ShowMatcher') ? ShowMatcher::findMatches($show_data) : [];
    echo json_encode(['success' => true, 'data' => $matches]);
    exit;
}
add_action('wp_ajax_simuze_get_matching_artists',        'simuze_get_matching_artists');
add_action('wp_ajax_nopriv_simuze_get_matching_artists', 'simuze_get_matching_artists');

// ---------------------------------------------------------------------------
// Invite artist to show — organiser only
// TODO: send notification to artist group members when invite system matures
// ---------------------------------------------------------------------------
function simuze_invite_artist_to_show(): void {
    $auth = Simuze_Auth::get_instance();
    $user = $auth->get_current_user();
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Login required']);
        exit;
    }

    $show_id         = isset($_POST['show_id'])         ? (int) $_POST['show_id']         : 0;
    $artist_group_id = isset($_POST['artist_group_id']) ? (int) $_POST['artist_group_id'] : 0;
    if (!$show_id || !$artist_group_id) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }

    if (!_simuze_event_can_manage($show_id, (int) $user->id)) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }

    // Check if already invited
    $existing = md_db_var(
        'SELECT status FROM simuze_show_invites WHERE show_id = ? AND artist_group_id = ? LIMIT 1',
        [$show_id, $artist_group_id]
    );
    if ($existing) {
        echo json_encode(['success' => true, 'status' => $existing, 'already_invited' => true]);
        exit;
    }

    md_db_insert('simuze_show_invites', [
        'show_id'             => $show_id,
        'artist_group_id'     => $artist_group_id,
        'invited_by_user_id'  => (int) $user->id,
        'status'              => 'pending',
        'created_at'          => date('Y-m-d H:i:s'),
        'updated_at'          => date('Y-m-d H:i:s'),
    ]);

    echo json_encode(['success' => true, 'status' => 'pending', 'already_invited' => false]);
    exit;
}
add_action('wp_ajax_simuze_invite_artist_to_show', 'simuze_invite_artist_to_show');
